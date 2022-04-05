<?php

namespace NsUtil;

class Ioncube {

    private $licenseProperties;

    public function __construct() {
        $this->licenseProperties = ioncube_license_properties();
        if ($this->licenseProperties === false) {
            throw new \Exception("Ioncube: Unable to read license properties");
        }
        $new = [];
        foreach ($this->licenseProperties as $key => $val) {
            $new[$key] = $val['value'];
        }
        $this->licenseProperties = (object) $new;
    }

    public function get($key = false) {
        if ($key === false) {
            return $this->licenseProperties;
        }
        if (isset($this->licenseProperties->specialFeaturesAccess)) {
            return $this->licenseProperties->specialFeaturesAccess;
        } else {
            throw new \Exception("Ioncube: Unable to read key '$key'");
        }
    }

    public function encode($pathToEncoderBatFile, $pathToPostEncoderBatFile) {
        // Acionar ioncube
        echo "\nCodificando arquivos PHP";
        $cmd = 'call ' . $pathToEncoderBatFile;
        shell_exec($cmd);

        // Gerando build
        echo "\nGerando build";
        $cmd = 'call ' . $pathToPostEncoderBatFile;
        shell_exec($cmd);
    }

}
