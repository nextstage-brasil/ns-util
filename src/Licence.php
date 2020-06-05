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
        $destino = realpath($filenameDestino);

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
        return Helper::saveFile($destino.'.lic', '', $toSave, 'SOBREPOR');
    }

    /**
     * Le o arquivo de licenca e retorna decodificado
     * @param type $licenceFile Path do arquivo de licenca a ser lido
     * @return type
     */
    public function read($licenceFile) {
        $filename = realpath($licenceFile.'.lic');
        if (!file_exists($filename)) {
            return ['error' => "NsLicence: Arquivo não localizado: $filename"];
        }

        // decodificar config
        $string = file_get_contents($filename);
        $config = $this->crypto->decrypt($string);
        $md5 = substr($config, 0, 64);
        $code = substr($config, 64);
        $pre = $this->crypto->getHash($code);

        // validação que o código não foi alterado
        if ($pre !== $md5) {
            return ['error' => 'NsLicence: Arquivo de configuração violado'];
        }
        return $code;
    }

}
