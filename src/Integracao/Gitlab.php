<?php

namespace NsUtil\Integracao;

class Gitlab {

    private $config, $depara, $project;

    public function __construct(string $token, string $url = 'https://gitlab.com/api/v4') {
        $this->config = new \NsUtil\Config([
            'token' => $token,
            'url' => $url
        ]);
        $ret = $this->fetch('version');
        if ($ret->status !== 200) {
            $err = 'Erro ao conectar com Gitlab: ' . $ret->error;
            throw new Exception($err);
        }
        $this->config->set('gl-version', $ret->content['version']);
    }

    private function getFromDePara($chave, $valor) {
        return ( (isset($this->depara[$chave][$valor])) ? $this->depara[$chave][$valor] : $valor);
    }

    public function setIdProject(int $idProject) {
        $this->config->set('idProject', $idProject);
        $this->config->set('rProject', 'projects/' . $idProject);
        return $this;
    }

    public function getIdProject() {
        return $this->config->get('idProject');
    }

    public function projectRead() {
        $this->project = $this->fetch('projects/' . $this->getIdProject())->content;
        return $this->project;
    }

    /**
     * Executa uma chamada ao gitlab e retorna um array
     * @param string $resource
     * @param array $data
     */
    private function fetch(string $resource, array $data = [], string $method = 'GET') {
        $header = ['PRIVATE-TOKEN:' . $this->config->get('token')];

        $url = $this->config->get('url')
                . '/'
                . $resource
                . ((count($data) > 0) ? '?' . http_build_query($data) : '')
        ;
        //echo $url . PHP_EOL; 
        $ret = \NsUtil\Helper::curlCall($url, [], $method, $header);
        if ($ret->status >= 203) {
            throw new \Exception(PHP_EOL .
                            'Chamada ao recurso ' . $resource . ' com status ' . $ret->status
                            . PHP_EOL
                            . 'URL: ' . $url
                            . PHP_EOL
                            . PHP_EOL
                            . var_export($ret, true)
                            . PHP_EOL);
        } else {
            $ret->content = json_decode($ret->content, true);
        }

        return $ret;
    }

    /**
     * Obtem a lista full de registros do resouce solicitado
     * @param type $resource
     * @return type
     */
    public function list($resource) {
        $out = [];
        $page = 1;
        do {
            $ret = $this->fetch($resource, ['scope' => 'all', 'per_page' => 100, 'page' => $page]);
            $page = (int) ((isset($ret->headers['X-Next-Page'])) ? $ret->headers['X-Next-Page'] : $ret->headers['x-next-page']);
            $out = array_merge($out, $ret->content);
        } while ($page > 0);

        return $out;
    }

    /**
     * Faz a a leitura de um item especifico do resource
     * @param type $resource
     * @param type $id
     * @param type $action
     * @return type
     */
    public function read($resource, $id, $action = null) {
        return $this->fetch($resource . '/' . $id . (($action !== null) ? '/' . $action : ''))->content;
    }

    private function issueSetDateFormat(&$data) {
        $format = new \NsUtil\Format();
        if (isset($data['due_date'])) {
            $data['due_date'] = $format->setString($data['due_date'])->date('arrumar', false, true);
        }
        if (isset($data['created_at'])) {
            $data['created_at'] = $format->setString($data['created_at'])->date('iso8601');
        }
        if (isset($data['start_date'])) {
            $data['start_date'] = $format->setString($data['start_date'])->date('iso8601');
        }
    }

    public function issueAdd($title, array $data = []) {
        $this->issueSetDateFormat($data);
        $resource = $this->config->get('rProject') . '/issues';
        $data['title'] = $title;
        $method = 'POST';
        try {
            $ret = $this->fetch($resource, $data, $method);
            return $ret->content;
        } catch (\Exception $ex) {
            echo PHP_EOL . $ex->getMessage() . PHP_EOL;
            return [];
        }
    }

    public function issueEdit($issue_iid, array $data) {
        $this->issueSetDateFormat($data);
        $resource = $this->config->get('rProject') . '/issues/' . $issue_iid;
        $method = 'PUT';
        $ret = $this->fetch($resource, $data, $method);
        return $ret->content;
    }

    public function setEstimate($issue_iid, $estimate) {
        if (strlen($estimate) <= 0) {
            return;
        }
        $resource = $this->config->get('rProject') . '/issues/' . $issue_iid . '/time_estimate';
        $data = ['duration' => $estimate];
        $method = 'POST';
        $ret = $this->fetch($resource, $data, $method);
        return $ret->content;
    }

    public function setSpend($issue_iid, $spend) {
        if (strlen($spend) <= 0) {
            return;
        }
        $resource = $this->config->get('rProject') . '/issues/' . $issue_iid . '/add_spent_time';
        $data = ['duration' => $spend];
        $method = 'POST';
        $ret = $this->fetch($resource, $data, $method);
        return $ret->content;
    }

    public function addComments($issue_iid, $body, $createdAt = false) {
        $resource = $this->config->get('rProject') . '/issues/' . $issue_iid . '/notes';
        $data = ['body' => $body, 'created_at' => $createdAt];
        $this->issueSetDateFormat($data);
        $method = 'POST';
        try {
            $ret = $this->fetch($resource, $data, $method);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }

        return $ret->content;
    }

    private function trelloSetMarkdown($text) {
        $from = ["\n"];
        $to = ["\n\n"];
        foreach (['*', '**'] as $val) {
            $from[] = $val . ' ';
            $from[] = ' ' . $val;
            $to[] = $val;
            $to[] = $val;
        }
        return str_replace($from, $to, $text);
    }

    /**
     * Ira efetuar a leitura de um JSON exportador pelo Trello e criar as issues e comentários
     * @param string $fileJson Path do arquivo JSON exportado do Trello
     * @param array $depara Array contendo de para de configuração
     * @param string $timeEstimatedName Nome do campo que contem o tempo estimado de conclusão
     * @param string $timeSpendName Nome od campo que contem o tempo já investido na tarefa
     * @param type $ignoreClosedList Caso true, os cards em listas arquivadas não serão importados
     * @return string
     */
    public function importFromJsonTrello(string $fileJson, array $depara, string $timeEstimatedName, string $timeSpendName, $ignoreClosedList = true) {
        if (!file_exists($fileJson)) {
            return 'Arquivo não localizado: ' . $fileJson;
        }

        // Todos as labels com projeto marcado devem ser anuladas e não importadas
        foreach ($depara['projectByLabel'] as $key => $val) {
            // vale o set definido manual antes
            if (!isset($depara['labels'][$key])) {
                $depara['labels'][$key] = false;
            }
        }
        $this->depara = $depara;

        // Carregar JSON
        $trello = Trello::readJson($fileJson, []);
        $data = $trello['data'];

        // Ordenar os cards pela posicao no trello
        foreach ($trello as $key => $val) {
            if (isset($val[0]['pos'])) {
                \NsUtil\Helper::arrayOrderBy($trello[$key], 'pos');
            }
        }

        $format = new \NsUtil\Format();
        $loader = new \NsUtil\StatusLoader(count($data['cards']), 'Gitlab from Trello');
        $loader->setShowQtde(true);
        $milestonePrefix = 1;
        $tarefasPadrao = [];
        foreach ($data['cards'] as $chaveItem => $item) {

            // Ignorar as listas do trello arquivadas...
            if ($ignoreClosedList && ($item['list_state'] !== 'opened' || $item['card_state'] !== 'opened')) {
                continue;
            }

            // Actions
            $actions = [];
            foreach ($data['actions'] as $val) {
                if (\NsUtil\Helper::compareString($item['id'], $val['id_card'])) {
                    $actions[] = $val;
                }
            }
            \NsUtil\Helper::arrayOrderBy($actions, 'date', 'ASC');

            // Obter o createtime. Obterá o mais antigo entre criação e update. POr causa da limitação de 10000 registros do trello
            $created = ['date' => $format->setString(date('Y-m-d H:i:s'))->date('iso8601')];
            foreach ($actions as $v) {
                $actionDate = (int) $format->setString($v['date'])->date('timestamp');
                $atual = (int) $format->setString($created['date'])->date('timestamp');
                if (($v['action'] === 'createCard' || $v['action'] === 'updateCard') && $actionDate < $atual) {
                    $created = $v;
                }
            }

            // Descrição
            $item['description'] = "*Importado do Trello em " . date('d/m/Y H:i:s') . "*\n\n"
                    . (($created['action'] === 'updateCard') ? "*Obtido a data mais antiga de atualização pois a data de criação não estava disponível na importação*\n\n" : "")
                    . PHP_EOL
                    . $this->trelloSetMarkdown($item['description']);

            // Project
            if (isset($this->depara['projectByLabel']['default'])) {
                $this->setIdProject((int) $this->depara['projectByLabel']['default']);
            }

            // labels
            $item['labels'][] = $this->getFromDePara('list_name', $item['list_name']);
            foreach ($item['labels'] as $key => $val) {
                // Seleção de projeto por etiqueta
                if (isset($this->depara['projectByLabel'][$val])) {
                    $this->setIdProject((int) $this->depara['projectByLabel'][$val]);
                }
                $item['labels'][$key] = $this->getFromDePara('labels', $val);
                if ($item['labels'][$key] === false) {
                    unset($item['labels'][$key]);
                }
            }
            $item['labels'][] = 'Importado do Trello (' . $trello['name'] . ')';

            // params
            $params = [
                'due_date' => (string) trim($item['duedate']),
                'labels' => (string) implode(',', $item['labels']),
                'assignee_ids' => (string) trim($this->getFromDePara('assigned', $item['assigned'])),
                'created_at' => (string) trim($created['date']),
                'description' => $item['description'],
            ];

            // comentarios
            $coments = [];
            $checklists = [];
            foreach ($actions as $action) {
                $dataActions = $action['text'];
                switch ($action['action']) {
                    case 'commentCard' :
                        $text = $dataActions['text'];
                        break;
                    case 'addAttachmentToCard':
                    case 'deleteAttachmentFromCard':
                        $text = 'ID: ' . $dataActions['attachment']['url'] . PHP_EOL
                                . ((isset($dataActions['attachment']['url'])) ? 'URL: ' . $dataActions['attachment']['url'] : '')
                        ;
                        break;
                    default:
                        $text = false;
                        break;
                }
                if ($text !== false) {
                    $nomeUsuario = \NsUtil\Helper::arraySearchByKey($data['members'], 'id', $action['id_member'])['name'];
                    $body = "*Importado do Trello. "
                            . "Usuário: " . $nomeUsuario
                            . ", ação: " . $action['action']
                            . " em " . $format->setString($action['date'])->date('mostrar', true)
                            . "* \n\n"
                            . $this->trelloSetMarkdown($text);
                    $createdAt = $action['date'];
                    $coments[] = ['body' => $body, 'createAt' => $createdAt, 'text' => $text, 'type' => 'comments'];
                }
            }

            // checklists - obter os checlists do card
            $checklists = array_filter($data['checklists'], function($v) use ($item) {
                return $item['id'] === $v['idCard'];
            });
            foreach ($checklists as $checklist) {
                $checklist['items'] = array_filter($data['checklists_items'], function($v) use ($checklist) {
                    //var_export($v);
                    return $v['idChecklist'] === $checklist['id'];
                });
                \NsUtil\Helper::arrayOrderBy($checklist['items'], 'pos');
                $text = "";
                // Criar comentário com o checklist
                foreach ($checklist['items'] as $check) {
                    $state = (($check['state'] === 'complete') ? 'x' : ' ');
                    $text .= "- [$state] " . $check['name'] . "\r\n";

                    // Geração das tarefas basicas das milestones
                    if (stripos($item['list_name'], 'milestone') !== false) {
                        $tarefasPadrao[] = ['issue_name' => $check['name'], 'project_name' => $trello['name'], 'milestone_name' => $item['title']];
                    }
                }
                $nomeUsuario = \NsUtil\Helper::arraySearchByKey($data['members'], 'id', $checklist['items'][0]['id_member'])['name'];
                $body = "*Importado do Trello. "
                        . "Criador: " . $nomeUsuario
                        . "* \r\n"
                        . "### " . $checklist['name'] . "\r\n"
                        . $text
                ;
                $createdAt = $checklist['date'];
                $coments[] = ['body' => $body, 'createAt' => $createdAt, 'text' => $text, 'type' => 'checklist'];
            }

            continue; /* @rever */

            // Esta na lista de Milestone?
            if (stripos($item['list_name'], 'milestone') !== false) {
                $prefix = str_pad((string) $milestonePrefix, 3, "0", STR_PAD_LEFT);
                $milestonePrefix++;
                $title = $prefix . ' - ' . $item['title'];
                $description = [];
                $description[] = $item['description'];
                foreach ($coments as $coment) {
                    if ($coment['type'] === 'checklist') {
                        $description[] = $coment['body'];
                    }
                }
                $out['milestones'][] = $this->milestoneAdd($title, implode("\n\n", $description));
            }
            // Issues
            else {
                // Criar issue
                $issue_iid = 0;
                $card = $this->issueAdd($item['title'], $params);
                if ($card['iid']) {
                    $issue_iid = $card['iid'];
                } else {
                    $error[] = "Erro ao criar issue: " . $item['title'];
                    continue;
                }

                // tempo
                if ((int) $item[$timeEstimatedName] > 0) {
                    $this->setEstimate($issue_iid, $item[$timeEstimatedName]);
                }
                if ((int) $item[$timeSpendName] > 0) {
                    $this->setSpend($issue_iid, $item[$timeSpendName]);
                }

                // coments
                foreach ($coments as $coment) {
                    $this->addComments($issue_iid, $coment['body'], $coment['createdAt']);
                }

                // Se o estado for closed, encerrar e corrigir labels
                if ($item['state'] === 'closed') {
                    $update['state_event'] = 'close';
                    $this->issueEdit($issue_iid, $update);
                }
            }

            $loader->done($chaveItem + 1);
        }

        return [
            'cronograma' => $tarefasPadrao,
            'error' => $error
        ];
    }

    public function milestoneAdd($title, $description = '', $local = 'projects', $startDate = '', $dueDate = '') {
        switch ($local) {
            case 'projects':
                $resource = 'projects/' . $this->getIdProject() . '/milestones';
                break;
            case 'groups':
                $this->projectRead();
                $resource = 'groups/' . $this->project['namespace']['id'] . '/milestones';
                break;
            default:
                die('Tipo de local não permitido: ' . $local);
        }
        $data = [
            'title' => $title,
            'description' => $description,
            'start_date' => $startDate,
            'due_date' => $dueDate
        ];
        $this->issueSetDateFormat($data);
        $method = 'POST';
        try {
            $ret = $this->fetch($resource, $data, $method);
            return $ret->content;
        } catch (\Exception $ex) {
            //echo "Erro ao criar milestone: " . $title;
            return [];
        }
    }

}
