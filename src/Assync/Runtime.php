<?php

namespace NsUtil\Assync;

use Exception;
use NsUtil\Log;

try {
    $autoloader = $argv[1] ?? null;
    $serializedClosure = $argv[2] ?? null;
    $logfile = $argv[3] ?? '/tmp/NSUtilAssyncError.log';
    $ref = $argv[4] ?? 'closure';

    if (!$autoloader) {
        throw new Exception('No autoloader provided in child process.');
    }

    if (!file_exists($autoloader)) {
        throw new Exception("Could not find autoloader in child process: {$autoloader}");
    }

    if (!$serializedClosure) {
        throw new Exception('No valid closure was passed to the child process.');
    }

    // Execution
    require_once $autoloader;

    $task = Assync::decodeTask($serializedClosure);
    $output = call_user_func($task);

    // Log
    Log::logTxt($logfile . '-SUCCESS', "[$ref] " . $output);
} catch (Exception $exception) {
    Log::logTxt($logfile . '-ERROR', "[$ref] " . $exception->getMessage());
}
