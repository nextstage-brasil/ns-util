<?php

namespace NsUtil\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class S3 {

    public $endpoint;
    private $fs, $adapter;
    private $key, $secret, $bucket, $region, $version;

    public function __construct($key, $secret, $bucket, $region = 'us-east-2', $version = '2006-03-01') {
        $this->key = $key;
        $this->secret = $secret;
        $this->bucket = $bucket;
        $this->region = $region;
        $this->version = $version;
        $this->init();
    }

    public function setBucket($bucket) {
        $this->bucket = $bucket;
        $this->init();
    }

    public function getFs(): Filesystem {
        return $this->fs;
    }

    public function getAdapter(): AwsS3Adapter {
        return $this->adapter;
    }

    private function init() {
        $client = new S3Client([
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret
            ],
            'region' => $this->region,
            'version' => $this->version,
        ]);
        $this->endpoint = "https://" . $this->bucket . ".s3-" . $this->region . ".amazonaws.com";
        $this->adapter = new AwsS3Adapter($client, $this->bucket);
        $this->fs = new Filesystem($this->adapter);
    }

}
