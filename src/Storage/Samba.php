<?php

namespace NsUtil\Storage;

use League\Flysystem\Filesystem;
use NsUtil\Storage\Adapters\SmbAdapter;

class Samba {

    private $fs, $adapter, $bucket;
    private $service, $username, $password, $smbver;

    public function __construct($service, $username, $password, $smbver = "") {
        $this->service = $service;
        $this->username = $username;
        $this->password = $password;
        $this->smbver = $smbver;
        $this->adapter = new SmbAdapter($service, $username, $password, $smbver);
        $this->fs = new Filesystem($this->adapter);
    }

    public function init() {
        return true;
    }

    public function setBucket($bucket) {
        $this->bucket = $bucket;
        $this->init();
    }

    public function getFs(): Filesystem {
        return $this->fs;
    }

    public function getAdapter(): SmbAdapter {
        return $this->adapter;
    }
}
