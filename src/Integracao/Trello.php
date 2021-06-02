<?php

namespace NsUtil\Integracao;

class Trello {

    private static $depara;

    public function __construct() {
        
    }

    /**
     * Busca no depara o name para id
     * @param type $chave
     * @param type $valor
     * @return type
     */
    private static function getFromDePara($chave, $valor) {
        return ( (isset(self::$depara[$chave][$valor])) ? self::$depara[$chave][$valor] : $valor);
    }

    /**
     * Cria uma tabela dimensao e acrescenta os dados em depara para não imprimir o ID e sim o name
     * @param type $chave
     * @param type $trello
     * @param type $out
     * @return type
     */
    private static function setDimensao($chave, &$trello, &$out) {
        // lists
        $lists = [];
        foreach ($trello[$chave] as $item) {
            $name = $item['name'] ?? $item['fullName'];
            $out[$chave][] = [
                'id' => $item['id'],
                'project_id' => self::$depara['projectID']['id'],
                'name' => $name,
                'closed' => (($item['closed'] === 'closed' || $item['closed'] === true) ? 'closed' : 'opened')
            ];
            $lists[$item['id']] = self::getFromDePara($chave, $name);
        }
        return $lists;
    }

    private static function readFromDimensao($chave, $id) {
        foreach ($chave as $value) {
            if ($value['id'] === $id) {
                return $value;
            }
        }
    }

    /**
     * Le um arquivo exportado do Trello e prerara so diversos CSV para importação ou manipulação dos dados
     * @param type $fileJson
     * @param type $depara
     * @return array []
     */
    public static function readJson($fileJson, $depara = []) {
        if (!file_exists($fileJson)) {
            return 'File not exists: ' . $fileJson;
        }
        self::$depara = $depara;
        $trello = json_decode(file_get_contents($fileJson), true);

        self::$depara['projectID'] = ['id' => $trello['id'], 'name' => $trello['name'], 'description' => (string) $trello['desc']];

        $out = [];

        // Projeto
        $out['board'][] = self::$depara['projectID'];

        // Dimensões
        $dimensoes = ['labels', 'lists', 'customFields', 'members'];
        foreach ($dimensoes as $chave) {
            self::$depara['dim_' . $chave] = self::setDimensao($chave, $trello, $out);
        }

        // cards
        foreach ($trello['cards'] as $card) {
            $labels = [];
            foreach ($card['labels'] as $label) {
                $l = self::getFromDePara('dim_labels', $label['id']);
                if ($l === $label['id']) { // para ignorar labels não existente ou naõ nomeadas
                    continue;
                }
                $labels[] = $l;
            }
            $listState = self::readFromDimensao($out['lists'], $card['idList'])['closed'];
            $newcard = [
                'id' => $card['id'],
                'project_id' => self::$depara['projectID']['id'],
                'title' => $card['name'],
                'description' => $card['desc'],
                'state' => (($card['dueComplete'] === true || $listState === 'closed') ? 'closed' : 'opened'),
                'card_state' => (($card['closed']) ? 'closed' : 'opened'), 
                'id_list' => $card['idList'], // self::getFromDePara('dim_lists', $card['idList']),
                'list_name' => self::getFromDePara('dim_lists', $card['idList']),
                'list_state' => $listState,
                'duedate' => $card['due'],
                'labels' => $labels, // implode(',', $labels),
                'date_last_activity' => $card['dateLastActivity'],
                'id_assigned' => $card['idMembers'][0],
                'assigned' => self::getFromDePara('dim_members', $card['idMembers'][0])
            ];

            // custom fields
            foreach (self::$depara['dim_customFields'] as $id => $nomeCustomField) {
                $newcard[$nomeCustomField] = null;
                foreach ($card['customFieldItems'] as $custom) {
                    if ($custom['idCustomField'] === $id) {
                        foreach ($custom['value'] as $tipo => $valor) {
                            $newcard[$nomeCustomField] = $valor;
                        }
                    }
                }
            }
            $out['cards'][] = $newcard;
        }

        // checklist dos cards
        foreach ($trello['checklists'] as $item) {
            foreach ($item['checkItems'] as $check) {
                $check['idCard'] = $item['idCard'];
                $out['checklists_items'][] = $check;
            }
            unset($item['checkItems']);
            $out['checklists'][] = $item;
        }

        // actions
        foreach ($trello['actions'] as $item) {
            //$item['data'] = str_replace("\n", 'NOALINHA', $item['data']);
            $out['actions'][] = [
                'id' => $item['id'],
                'date' => $item['date'],
                'action' => $item['type'],
                'id_card' => $item['data']['card']['id'],
                'text' => $item['data'], 
                'id_member' => $item['idMemberCreator']
            ];
        }

        return [
            'name' => $trello['name'],
            'data' => $out
        ];
    }

    /**
     * Pega o array gerado em loadJsonTrello e salva em arquivos CSV no diretorio especificado
     * @param array $out
     * @param string $dirOut
     */
    public static function loadOut2Csv(array $out, string $dirOut) {
        foreach ($out as $key => $val) {         // gerar csv
            if (is_array($val)) {
                if (count($val) > 0) {
                    \NsUtil\Helper::array2csv($val, $dirOut . DIRECTORY_SEPARATOR . $key . '.csv');
                }
            }
        }
    }

    /**
     * Obtém os arquivos CSV em um diretorio e importar para o Postgres conforme conexão
     * @param \NsUtil\ConnectionPostgreSQL $con
     * @param string $schema
     * @param type $dirOut
     * @param type $dropSchema
     * @param type $trucnateTables
     */
    public static function loadCsv2Postgres(\NsUtil\ConnectionPostgreSQL $con, $schema, $dirOut, $dropSchema = true, $trucnateTables = true) {
        $schema = 'trello_' . \NsUtil\Helper::sanitize($schema);
        $load = new NsUtil\PgLoadCSV($con, $schema, true, true);
        $load->run($dirOut);
    }

}
