<?php

namespace NsUtil\AWS;

/**
 * Class that can execute a background job, and check if the
 * background job is still running
 * 
 * @package main
 */
class Lambda {

    public static function clearOldVersion($limit = 3) {
        \NsUtil\Config::setAWSByProfile();
        $args = [
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret
            ]
        ];
        $cli = new \Aws\Lambda\LambdaClient($args);
    }

}
