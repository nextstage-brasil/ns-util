<?php

namespace NsUtil;

use Exception;
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
     * @throws Exception
     */
    public function create($filenameOrigem, $filenameDestino) {
        $origem = realpath($filenameOrigem);
        $destino = $filenameDestino;

        if (!file_exists($filenameOrigem)) {
            throw new Exception('NsLicence: Arquivo de origem não localizado: ' . $origem);
        }
        // carga do conteudo
        $content = file_get_contents($origem);
        $content = str_replace('<?php', '', $content);

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
    public function read($licenceFile, $dieIfNotExists = true) {
        if (!file_exists((string) $licenceFile)) {
            $filename = Helper::fileSearchRecursive($licenceFile, __DIR__, 20);
            if (!file_exists((string) $filename)) {
                if (!$dieIfNotExists) {
                    return null;
                }
                die("NsLicence: Arquivo não localizado: $licenceFile");
            }
            return $this->read($filename, $dieIfNotExists = true);
        }


        // decodificar config
        $string = file_get_contents((string) $licenceFile);
        $config = $this->crypto->decrypt((string) $string);
        $md5 = substr((string) $config, 0, 64);
        $code = substr((string) $config, 64);
        $pre = $this->crypto->getHash((string) $code);

        // validação que o código não foi alterado
        if ($pre !== $md5) {
            die('NsLicence: Arquivo de configuração inválido ou violado');
        }
        return $code;
    }

    public static function readFromIoncube($licenceName) {
        if (function_exists('ioncube_license_properties')) {
            return ioncube_license_properties()[$licenceName]['value'];
        } else {
            die('Obrigatório utilização do Ioncube (NS88)');
        }
    }

}
