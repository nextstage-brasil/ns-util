<?php

namespace NsUtil\Commands;

use NsUtil\Commands\NsUtils\MakeCommand;
use NsUtil\Helper;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

use function NsUtil\dd;

class Runner
{
    public static function getNamespaceFromFile($filePath)
    {
        Helper::directorySeparator($filePath);
        $parts = explode(DIRECTORY_SEPARATOR,  explode('src/', $filePath)[1]);
        $file = array_pop($parts);
        return (stripos($filePath, 'ns-util') === false ? Helper::getPsr4Name() : 'NsUtil')
            . '\\'
            . implode('\\', $parts);
    }

    public static function loadClassNamesFromPath($path)
    {
        $classNames = [];

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $filePath = $file->getPathname();
                $namespace = self::getNamespaceFromFile($filePath);
                $className = $namespace . '\\' . $file->getBasename('.php');
                $classNames[] = $className;
            }
        }

        return $classNames;
    }

    public static function handle(array $argv, ?string $pathToCommands = null): void
    {
        try {
            $pathToCommands ??= Helper::getPathApp() . '/src/Console/Commands';
            Helper::mkdir($pathToCommands);
            $command = $argv[1] ?? null;

            if (null == $command) {
                throw new \Exception("Command not provided. Use: php nsutil {command}");
            }

            $classNames = array_merge(
                self::loadClassNamesFromPath($pathToCommands),
                self::loadClassNamesFromPath(__DIR__ . '/NsUtils')
            );
            unset($argv[0]); //nsutil
            unset($argv[1]); //command

            $targetClass = null;

            foreach ($classNames as $className) {
                $reflectionClass = new ReflectionClass($className);
                $signatureProperty = $reflectionClass->getProperty('signature');
                $signatureProperty->setAccessible(true);
                $signatureValue = $signatureProperty->getValue(new $className());

                if ($signatureValue === $command) {
                    (new $className())->handle(array_values($argv));
                    $targetClass = $className;
                    break;
                }
            }

            if (null == $targetClass) {
                throw new \Exception("Command $command not found");
            }
        } catch (\Exception $exc) {
            (new MakeCommand())->error($exc->getMessage());
        }
    }
}
