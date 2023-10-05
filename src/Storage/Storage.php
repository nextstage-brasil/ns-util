<?php

namespace NsUtil\Storage;

use Exception;
use League\Flysystem\Filesystem;
use NsUtil\Validate;

use function NsUtil\env;

class Storage
{
    public static function getDrive(?string $bucketName = null, ?string $drive = null): Filesystem
    {

        $drive ??= env('STORAGE_DRIVE', 'STORAGE_DRIVE_NOT_DEFINED');
        switch ($drive) {
            case 'S3':
                $st = new S3(
                    env('S3_KEY', 'S3_KEY_NOT_DEFINED'),
                    env('S3_SECRET', 'S3_SECRET_NOT_DEFINED'),
                    $bucketName ?? env('BUCKET_NAME', 'BUCKET_NAME_NOT_DEFINED'),
                    env('S3_REGION', 'us-east-2'),
                    env('S3_VERSION', '2006-03-01')
                );
                $fs = $st->getFs();
                break;

            default:
                throw new Exception("Drive $drive not enabled");
        }

        return $fs;
    }
}
