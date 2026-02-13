<?php

/**
 * @license MIT
 * Copyright (c) 2026 Guillaume Crégut
 */

namespace App\Kernel;

class Logger
{

    public static function error(object | string $sender, string $message, bool $debugOnly, bool $systemFile): void
    {
        $senderName = self::getCallerName($sender);
        $messageToPost = self::createMessage($senderName, $message, 'ERROR');
        $envs = self::getEnvValues('error_log_path');
        if ($debugOnly && !$envs['debugMode']) {
            return;
        }
        if ($systemFile) {
            error_log($messageToPost);
            return;
        }
        if (empty($envs['path']) && $envs['path'] == '') {
            $code = 0;
            $nl = '';
        } else {
            $code = 3;
            $nl = PHP_EOL;
        }
        $fullPath = $envs['appPath'] . $envs['path'];
        if (self::checkLogFile($fullPath)) {
            error_log($messageToPost . $nl, $code, $fullPath);
        }
    }

    public static function warning(object $sender, string $message, bool $debugOnly, bool $systemFile): void
    {
        $senderName = self::getCallerName($sender);
        $messageToPost = self::createMessage($senderName, $message, 'WARNING');
        $envs = self::getEnvValues('warning_log_path');
        if ($debugOnly && !$envs['debugMode']) {
            return;
        }
        if ($systemFile) {
            error_log($messageToPost);
            return;
        }
        if (empty($envs['path']) && $envs['path'] == '') {
            $code = 0;
            $nl = '';
        } else {
            $code = 3;
            $nl = PHP_EOL;
        }
        $fullPath = $envs['appPath'] . $envs['path'];
        if (self::checkLogFile($fullPath)) {
            error_log($messageToPost . $nl, $code, $fullPath);
        }
    }

    public static function info(object $sender, string $message, bool $debugOnly, bool $systemFile): void
    {
        $senderName = self::getCallerName($sender);
        $messageToPost = self::createMessage($senderName, $message, 'INFO');
        $envs = self::getEnvValues('info_log_path');
        if ($debugOnly && !$envs['debugMode']) {
            return;
        }
        if ($systemFile) {
            error_log($messageToPost);
            return;
        }
        if (empty($envs['path']) && $envs['path'] == '') {
            $code = 0;
            $nl = '';
        } else {
            $code = 3;
            $nl = PHP_EOL;
        }
        $fullPath = $envs['appPath'] . $envs['path'];
        if (self::checkLogFile($fullPath)) {
            error_log($messageToPost . $nl, $code, $fullPath);
        }
    }

    private static function getEnvValues(string $path): array
    {
        $returnArray = [];
        $envMode = GetEnvDatas::getEnvInstance()->get('debug_mode', 'false');
        $returnArray['debugMode'] = filter_var($envMode, FILTER_VALIDATE_BOOLEAN);
        $returnArray['path'] = GetEnvDatas::getEnvInstance()->get($path, '');
        $returnArray['appPath'] = GetEnvDatas::getAppPath();
        return $returnArray;
    }

    private static function createMessage(string $sender, string $message, string $level): string
    {
        //check if null characters in message
        if (strpos($message, "\0") !== false) {
            $message = str_replace("\0", '', $message);
        }
        date_default_timezone_set('Europe/Paris');
        $date = date('Y-m-d H:i:s');
        return $level . ': ' . $date . ' : ' . $sender . ' : ' . $message;
    }

    private static function checkLogFile(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            if (!self::createPath($filePath)) {
                return false;
            }
        }
        if (!is_writable($filePath)) {
            return false;
        }
        return true;
    }

    private static function createPath(string $filename): bool
    {
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            if (false === mkdir($directory, 0766, true) && !is_dir($directory)) {
                self::error(self::class, "Directory $directory can't be created", false, true);
                return false;
            }
        }
        if (! touch($filename)) {
            self::error(self::class, "Log file $filename can't be created", false, true);
            return false;
        }
        return true;
    }

    private static function getCallerName(object | string $caller): string
    {
        if (is_object($caller)) {
            return get_class($caller);
        }
        return (string)$caller;
    }
}
