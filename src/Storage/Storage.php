<?php

namespace NsUtil\Storage;

use Exception;
use League\Flysystem\Adapter\Local;
use NsUtil\Storage\Adapters\SmbAdapter;

use function NsUtil\env;

class Storage
{
    public static function getDrive(?string $bucketName = null, ?string $drive = null): NsFilesystem
    {

        $drive ??= env('STORAGE_DRIVE', 'STORAGE_DRIVE_NOT_DEFINED');
        $bucketName ??= env('BUCKET_NAME', 'BUCKET_NAME_NOT_DEFINED');
        switch (mb_strtolower($drive)) {
            case 'local':
                $adapter = new Local($bucketName);
                break;
            case 's3':
                $st = new S3(
                    env('S3_KEY', 'S3_KEY_NOT_DEFINED'),
                    env('S3_SECRET', 'S3_SECRET_NOT_DEFINED'),
                    $bucketName,
                    env('S3_REGION', 'us-east-1'),
                    env('S3_VERSION', '2006-03-01')
                );
                $adapter = $st->getAdapter();
                break;

            case 'samba':
                $adapter = new SmbAdapter(
                    env('SMB_SERVICE'),
                    env('SMB_USERNAME'),
                    env('SMD_PASSWORD'),
                    env('SMB_DOMAIN', ''),
                    env('SMB_VERSION', ''),
                    env('SMB_TMP_PATH', '/tmp')
                );
                break;

            default:
                throw new Exception("Drive $drive not enabled");
        }

        return new NsFilesystem($adapter);

    }
}
