<?php
namespace NsUtil\Storage;

use League\Flysystem\Filesystem;

class NsFilesystem extends Filesystem
{
    public function rename($path, $newpath)
    {
        if (method_exists($this, "move")) {
            return $this->move($path, $newpath);
        } else {
            return $this->rename($path, $newpath);
        }
    }

    public function update($path, $contents, $config = [])
    {
        return parent::write($path, $contents);
    }
    public function updateStream($path, $contents, $config = [])
    {
        return parent::writeStream($path, $contents);
    }
    public function put($path, $contents, $config = [])
    {
        return parent::write($path, $contents);
    }
    public function putStream($path, $contents, $config = [])
    {
        return parent::writeStream($path, $contents);
    }

    public function getTimestamp($path)
    {
        return method_exists($this, "lastModified")
            ? parent::lastModified($path)
            : parent::getTimestamp($path);
    }

    public function has($path)
    {
        return method_exists($this, "has")
            ? parent::has($path)
            : parent::fileExists($path);
    }

    public function getMimetype($path)
    {
        return method_exists($this, "getMimetype")
            ? parent::getMimetype($path)
            : parent::mimeType($path);
    }

    public function getSize($path)
    {
        return method_exists($this, "getSize")
            ? parent::getSize($path)
            : parent::fileSize($path);
    }

    public function getVisibility($path)
    {
        return method_exists($this, "getVisibility")
            ? parent::getVisibility($path)
            : parent::visibility($path);
    }

    public function listContents($directory = '', $recursive = false)
    {
        $itens = parent::listContents($directory, $recursive);
        $type = gettype($itens);

        // version 1.0
        switch ($type) {
            case 'array':
                break;
            case 'object':
                $itens = $itens
                    // ->filter(fn($attributes) => $attributes->isFile())
                    // ->map(fn($attributes) => $attributes->path())
                    ->toArray();
                break;
            default:
                $itens = [];
                break;
        }

        return array_map(
            fn($item) => $item['type'] === 'file'
            ? array_merge($item, [
                'filename' => $item['path'] ?? '',
                'timestamp' => $item['last_modified'] ?? '',
                'size' => $item['file_size'] ?? '',
                'storageclass' => $item['extra_metadata']['StorageClass'] ?? '',
                'etag' => $item['extra_metadata']['ETag'] ?? '',
            ])
            : [
                'dirname' => $item['path'] ?? '',
                'basename' => $item['path'] ?? '',
                'filename' => $item['path'] ?? '',
            ],
            $itens
        );
    }





}