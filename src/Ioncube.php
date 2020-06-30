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

}
