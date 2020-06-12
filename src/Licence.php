<?php

namespace NsUtil;

use NsUtil\Crypto;

class Licence {

    private $crypto;

    /**
     * Classe para criação e leiturade licenças codificadas
     * @param type $chave: String para codificação. Mínimo de 16 digitos.
     */
    public function __construct($chave) {
        $this->crypto = new Crypto($chave);
    }

    /**
     * Le o arquivo origem, codifica e salva no destino
     * @param type $filenameOrigem
     * @param type $filenameDestino
     * @return type
     * @throws \Exception
     */
    public function create($filenameOrigem, $filenameDestino) {
        $origem = realpath($filenameOrigem);
        $destino = $filenameDestino;

        if (!file_exists($filenameOrigem)) {
            throw new \Exception('NsLicence: Arquivo de origem não localizado: ' . $origem);
        }
        // carga do conteudo
        $content = file_get_contents($origem);
        // crypto
        $pre = $this->crypto->getHash($content);
        // save
        $toSave = $this->crypto->encrypt($pre . $content);
        // return
        return Helper::saveFile($destino, '', $toSave, 'SOBREPOR');
    }

    /**
     * Le o arquivo de licenca e retorna decodificado
     * @param type $licenceFile Path do arquivo de licenca a ser lido. Ira buscar em 10 diretorios abaixo procurando este arquivo.
     * @return type
     */
    public function read($licenceFile) {
        // varredura em busca do arquivo
        $filename = __DIR__ . '/' . $licenceFile;
        $count = 0;
        while (!file_exists($filename) && $count < 10) { // paths acima
            $dirs .= '../';
            $filename = __DIR__ . $dirs . $licenceFile;
            $count++;
        }
        
        $filename = realpath($filename);
        if (!file_exists($filename)) {
            die ("NsLicence: Arquivo não localizado: $licenceFile");
        }

        // decodificar config
        $string = file_get_contents($filename);
        $config = $this->crypto->decrypt($string);
        $md5 = substr($config, 0, 64);
        $code = substr($config, 64);
        $pre = $this->crypto->getHash($code);

        // validação que o código não foi alterado
        if ($pre !== $md5) {
            die('NsLicence: Arquivo de configuração inválido ou violado');
        }
        return $code;
    }

}
