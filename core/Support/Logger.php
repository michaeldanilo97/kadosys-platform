<?php

declare(strict_types=1);

namespace Kadosys\Core\Support;

use Kadosys\Core\Core\Application;

/**
 * Logger
 *
 * Registra mensagens de log em arquivo (storage/logs/), com niveis
 * de severidade padrao (PSR-3 like), sem dependencia externa.
 */
final class Logger
{
    public const DEBUG = 'debug';
    public const INFO = 'info';
    public const WARNING = 'warning';
    public const ERROR = 'error';
    public const CRITICAL = 'critical';

    public static function debug(string $message, array $context = []): void
    {
        self::write(self::DEBUG, $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write(self::INFO, $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write(self::WARNING, $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write(self::ERROR, $message, $context);
    }

    public static function critical(string $message, array $context = []): void
    {
        self::write(self::CRITICAL, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $message, array $context): void
    {
        $logDir = Application::getInstance()->basePath('storage/logs');

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $logFile = $logDir . '/' . date('Y-m-d') . '.log';

        $contextString = empty($context) ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE);

        $line = sprintf(
            '[%s] %s: %s%s%s',
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $contextString,
            PHP_EOL
        );

        @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
    }
}
