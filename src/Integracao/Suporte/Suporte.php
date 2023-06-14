<?php

namespace NsUtil\Integracao\Suporte;

use NsUtil\Integracao\Adapter;

class Suporte extends Adapter {

    private $usuarioNome; // nome do usuario para tratamento
    private $licencaSuporte; // Atributo unico que define a licenca de uso do cliente no software. Será utilizado como chave primaria desta aplicação.

    public function __construct($endpoint, $appkey, $usuarioNome, $licencaSuporte) {
        //$this->showLogs = true;
        $this->usuarioNome = $usuarioNome;
        $this->licencaSuporte = $licencaSuporte;
        $this->sessionName = md5((string)$endpoint . $appkey . $usuarioNome . $licencaSuporte);
        parent::__construct($endpoint, $appkey);
    }

    // Reescrito para acrsecentar o nome do usuario
    public function call($recurso, $params = [], $method = 'POST', $header = []) {
        $params['usuario'] = $this->usuarioNome;
        $params['licenca'] = $this->licencaSuporte;
        return parent::call($recurso, $params, $method, $header);
    }

    // Adiciona um novo ticket
    public function add($titulo, $descricao, $refLocal='.', $dados = []) {
        if (count($dados) > 0) {
            $extras = [];
            foreach ($dados as $key => $val) {
                $extras[] = "<strong>$key</strong>: $val";
            }
            $descricao .= '<br/><br/>'
                    . implode('<br/>', $extras);
        }
        return $this->call('ticket/adicionar', ['titulo' => $titulo, 'descricao' => $descricao, 'ref' => $refLocal]);
    }

    // Listar meus tickets
    public function ls() {
        return $this->call('ticket/listar');
    }

    // Ler ticket
    public function read($ticket) {
        return $this->call('ticket/ler', ['ticket' => $ticket]);
    }
    
    // Add msg
    public function msg($ticket, $mensagem) {
        return $this->call('ticket/mensagem', ['ticket' => $ticket, 'conteudo' => $mensagem]);
    }

    // Alterar status
    public function st($ticket, $status, $coments) {
        return $this->call('ticket/st', ['ticket' => $ticket, 'st' => $status, 'coments' => $coments]);
    }

    // le notificacoes dos meus tickets
    public function readNotification() {
        // Anotar em um arquivo a ultima leitura efetuada
        $file = '/tmp/ns_sup_last_update';
        $lastUpdate = (int) file_get_contents($file);
        return $this->call('ticket/readNotification', ['lastUpdate' => $lastUpdate]);
    }

}
