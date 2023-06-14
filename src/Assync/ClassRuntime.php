<?php

namespace NsUtil\Assync;

use Exception;
use NsUtil\Log;

try {
    $autoloader = $argv[1] ?? null;
    $params = $argv[2] ?? '{}';
    $logfile = $argv[3] ?? '/tmp/NSUtilAssyncError.log';
    $ref = $argv[4] ?? 'closure';

    if (!$autoloader) {
        throw new Exception('No autoloader provided in child process.');
    }

    if (!file_exists($autoloader)) {
        throw new Exception("Could not find autoloader in child process: {$autoloader}");
    }

    // Execution
    require_once $autoloader;

    $params = json_decode(base64_decode($params), true);
    $className = $params['__CLASS__'];
    $function = $params['__FUNCTION__'];
    $class = new $className();

    if (method_exists($class, $function)) {
        $output = $class->$function($params);
        $output = (string) is_string($output) ? $output : json_encode($output);

        // Log
        Log::logTxt($logfile . '_success.log', "[$ref] " . $output, true);
    } else {
        throw new Exception("Function  $function not found on class $className");
    }
} catch (Exception $exception) {
    Log::logTxt($logfile . '_error.log', "[$ref] " . $exception->getMessage());
}
