<?php

namespace NsUtil\Storage;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Filesystem;

class S3 {

    public $endpoint;
    private $fs, $adapter;
    private $key, $secret, $bucket, $region, $version, $s3Client;

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
        $this->s3Client = new S3Client([
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret
            ],
            'region' => $this->region,
            'version' => $this->version,
        ]);
        $this->endpoint = "https://" . $this->bucket . ".s3-" . $this->region . ".amazonaws.com";
        $this->adapter = new AwsS3Adapter($this->s3Client, $this->bucket);
        $this->fs = new Filesystem($this->adapter);
    }

    public function getUrlSigned(string $item, int $minutes = 10): string {
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $item
            ]);
            $request = $this->s3Client->createPresignedRequest($cmd, '+' . $minutes . ' minutes');
            return (string) $request->getUri();
        } catch (\Exception $exc) {
            return 'Error on get url signed to item: ' . $exc->getMessage();
        }
    }

}
