<?php

namespace NsUtil;

use League\Flysystem\AdapterInterface;
use NsUtil\Helper;
use NsUtil\ResizeImage;
use NsUtil\Storage\S3;
use PhpZip\ZipFile;

class S3ZipFiles {

    private $filename, $itens, $config;

    /**
     * Zip os itens enviados caso existam no bucket S3 da configuração
     * @param string $filenameSave Nome do arquivo ZIP a ser salvo. Ex.: pedido_1. A extensão ZIP é automática
     * @param array $itens array contendo os itens a serem adicionados ao pedido. Ex.: {
      "resolucao": "3600", // resolução comprada pelo cliente
      "st": 3, Indica qual storage esta o arquivo
      "file": "ft/15/1029/5e5272497b299cfa29165f4b3fd7c47a.jpg", // nome od arquivo no storage
      "nome": "P3S_3502.jpg", // nome comercial do arquivo
      "id_uploadfile": 4570 //  arquivo relacionado
      },
     */
    public function __construct(string $filenameSave, array $itens) {
        $this->filename = str_replace('.zip', '', Helper::sanitize($filenameSave)) . '.zip';
        $this->itens = $itens;
        $this->config = [];
        $path = ((strtoupper(substr((string)PHP_OS, 0, 3)) === 'WIN') ? 'c:\\tmp' : '/tmp');
        $this->setTempPath($path);
    }

    public function setCredentials($key, $secret, $bucket, $bucket_public, $region, $version) {
        $this->config['s3'] = [
            'key' => $key,
            'secret' => $secret,
            'bucket' => $bucket,
            'bucket-public' => $bucket_public,
            'region' => $region,
            'version' => $version
        ];
        return $this;
    }

    public function setTempPath($path) {
        if (!is_dir($path)) {
            throw new Exception('s3ZipFiles ERROR: Temp path is not a dir');
        }
        $this->config['tmpdir'] = $path;
    }

    /**
     * Montara o ZIPFILE em app/tmp e retorna o link para download
     * @param type $dados - idPedido com  pedido a ser processado
     * @return string
     */
    public function run() {
        if (!$this->config['s3']) {
            die('Configuração S3 não definida (S3Z52)');
        }
        $dirTemp = $this->config['tmpdir'] . DIRECTORY_SEPARATOR . md5((string)$this->filename);
        $tmpZip = $this->config['tmpdir'] . DIRECTORY_SEPARATOR . $this->filename;
        Helper::createTreeDir($dirTemp . '/_tmp');
        //$this->console($dirTemp);

        $filename = $this->filename; // ex: pedido_1.zip
        $pathZip = 'pedidos/' . $filename;
        $zipFile = new ZipFile();

        // Storage
        $fs = $this->config['s3']; // \Config::getData('fileserver', 'S3');
        $s3 = new S3($fs['key'], $fs['secret'], $fs['bucket-public'], $fs['region'], $fs['version']);

        // se já existir, notificar link
        if ($s3->getFs()->has($pathZip)) {
            $link = $s3->endpoint . '/' . $pathZip;
            $out = ['idPedido' => $this->filename, 'status' => 'DownloadDisponivel', 'file' => $pathZip, 'link' => $link, 'error' => false];
            return $out;
        } else { // se nao, processar
            $s3->setBucket($fs['bucket']);
            ini_set('max_execution_time', count($this->itens) + 30);
            $out = ['error' => false];
            $resize = new ResizeImage('', '');
            foreach ($this->itens as $item) {


                if (!$s3->getFs()->has($item['file'])) {
                    continue;
                }

                // Obter arquivo
                $path = $dirTemp . '/' . $item['nome'];
                // Obter o arquivo

                $ctt = $s3->getFs()->read($item['file']);
                file_put_contents($path, $ctt);
                unset($ctt);

                if ($item['resolucao']) {
                    // resize imagem
                    $resize->setFile($path)
                            ->setResolucao($item['resolucao'])
                            ->reduz();
                }

                $content = file_get_contents($path);
                $zipFile->addFromString('/pacote-' . $this->filename . '/' . $item['nome'], $content);
                // Adicionar ao ZIP
                //exec("zip -u $zipname $path > /dev/null");
                // Remover arquivo do disco
                unlink($path);
            }
        }
        $zipFile->saveAsFile($tmpZip);
        $zipFile->close();
        unset($zipFile);

        // Salvar em S3 e gerar o link 
        $fp = fopen($tmpZip, 'r');
        $s3->setBucket($fs['bucket-public']);
        $s3->getFs()->write($pathZip, $fp);
        $s3->getFs()->setVisibility($pathZip, AdapterInterface::VISIBILITY_PUBLIC);
        fclose($fp);

        // Remover arquivos do disco
        Helper::deleteFile($dirTemp, true, false);
        Helper::deleteFile($tmpZip, true, false);

        // retorno
        $link = $s3->endpoint . '/' . $pathZip;
        $out = ['idPedido' => $this->filename, 'status' => 'DownloadDisponivel', 'link' => $link, 'file' => $pathZip, 'error' => false];

        return $out;
    }

    private function console($string) {
        echo $string . PHP_EOL;
    }

}
