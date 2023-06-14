<?php

namespace NsUtil\Storage;

/**
 * Description of AbstractStorage
 *
 * @author crist
 */
abstract class AbstractStorage {

    public function setBucket($bucket) {
        $this->bucket = $bucket;
    }

    public function getFs(): Filesystem {
        return $this->fs;
    }

    public function getAdapter(): AwsS3Adapter {
        return $this->adapter;
    }

}
