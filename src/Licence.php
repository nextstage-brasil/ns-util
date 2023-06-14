<?php

namespace NsUtil;

use Exception;
use NsUtil\Crypto;

class Licence
{

    private $crypto;

    /**
     * Classe para criação e leiturade licenças codificadas
     * @param string $chave: String para codificação. Mínimo de 16 digitos.
     */
    public function __construct($chave)
    {
        $this->crypto = new Crypto(trim($chave));
    }

    /**
     * Le o arquivo origem, codifica e salva no destino
     * @param string $filenameOrigem
     * @param string $filenameDestino
     * @return bool
     * @throws Exception
     */
    public function create($filenameOrigem, $filenameDestino)
    {
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
     * @param string $licenceFile Path do arquivo de licenca a ser lido. Ira buscar em 10 diretorios abaixo procurando este arquivo.
     * @return mixed
     */
    public function read($licenceFile, $dieIfNotExists = true)
    {
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

    public static function readFromIoncube($licenceName)
    {
        if (function_exists('ioncube_license_properties')) {
            $lic = ioncube_license_properties()[$licenceName]['value'] ?? null;
            if (null !== $lic) {
                $lic = trim($lic);
            }
            return $lic;
        } else {
            die('Obrigatório utilização do Ioncube (NS88)');
        }
    }
}
