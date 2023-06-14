<?php

namespace NsUtil\Storage\Adapters;

use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use NsUtil\Helper;
use NsUtil\Storage\Clients\SmbClient;

use function NsUtil\dd;

class SmbAdapter implements AdapterInterface
{

    public $service, $username, $password, $smbver, $tmp_dir;
    private $smbclient;
    private $pathCache = [];

    public function __construct($service, $username, $password, $domain = '', $smbver = "", $tmp_dir = '/tmp')
    {
        $this->service = $service;
        $this->username = $username;
        $this->password = $password;
        $this->smbver = $smbver;
        $this->tmp_dir = $tmp_dir;
        $this->smbclient = new SmbClient($service, $username, $password, $domain, $smbver);
    }

    public function copy($path, $saveToLocal): bool
    {
        return true;
    }

    public function createDir($dirname, Config $config)
    {
        $this->smbclient->mkdir($dirname);
    }

    public function delete($path): bool
    {
        return $this->smbclient->del($path);
    }

    public function deleteDir($dirname): bool
    {
        return $this->smbclient->del($dirname);
    }

    public function getMetadata($path)
    {
        if (!isset($this->pathCache[md5((string)$path)]['type'])) {
            $dir_ret = $this->smbclient->dir('', $path);
            if (isset($dir_ret[0])) {
                $this->mapFile($dir_ret[0], $path);
            }
        }
        return $this->pathCache[md5((string)$path)] ?? [];
    }

    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }

    public function getVisibility($path)
    {
        return $this->getMetadata($path);
    }

    public function has($path)
    {
        $ret = $this->getMetadata($path);
        return isset($ret['type']) ? true : false;
    }

    public function listContents($directory = '', $recursive = false): array
    {
        $list = $this->smbclient->dir($directory);
        $out = [];
        foreach ($list as $item) {
            $out[] = $this->mapFile($item, 'qwqco');
        }
        return $out;
    }

    public function read($path)
    {
        if ($this->has($path)) {
            $local = $this->tmp_dir . DIRECTORY_SEPARATOR . md5((string) $path);
            $this->smbclient->get($path, $local);
            $this->pathCache[md5((string)$path)]['contents'] = fopen($local, 'r');
            unlink($local);
            return $this->pathCache[md5((string)$path)];
        } else {
            return false;
        }
    }

    public function readStream($path)
    {
        return $this->read($path);
    }

    public function rename($path, $newpath): bool
    {
        return $this->smbclient->rename($path, $newpath);
    }

    public function setVisibility($path, $visibility)
    {
        return true;
    }

    public function update($path, $contents, Config $config)
    {
        $this->upload($path, $contents);
    }

    public function updateStream($path, $resource, Config $config)
    {
        die('not implements');
    }

    public function write($path, $contents, Config $config)
    {
        $this->upload($path, $contents);
    }

    public function writeStream($path, $resource, Config $config)
    {
        die('not implements');
    }

    protected function upload($path, $contents)
    {
        // salvar o contents em um arquivo local
        $local = $this->tmp_dir . '/' . md5((string)$path);
        file_put_contents(realpath($local), $contents);
        unlink($local);
        return $this->smbclient->put($local, $path);
    }

    protected function mapFile($returnOfDir, $path)
    {
        $this->pathCache[md5((string)$path)] = [
            'type' => isset($returnOfDir['isdir']) ? 'dir' : 'file',
            'path' => $returnOfDir['filename'],
            //'contents' => '',
            //'stream' => '',
            'visibility' => 'public',
            'timestamp' => (int) ($returnOfDir['mtime'] ?? 0),
            'size' => (int) (isset($returnOfDir['isdir']) ? $returnOfDir['size'] : 0),
            'mimetype' => \NsUtil\Storage\libs\Mimes::getMimeType($returnOfDir['filename'])
        ];
        return $this->pathCache[md5((string)$path)];
    }

    public function download($path, $path_save)
    {
        if ($this->has($path)) {
            $local = $path_save;
            $this->smbclient->get($path, $local);
            //var_export($this->smbclient->get_last_cmd_stderr());die();
            return (bool) file_exists($local) && filesize($local) > 0;
        } else {
            throw new \Exception('File not found: ' . $path);
        }
    }
}
