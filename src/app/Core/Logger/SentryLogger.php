<?php

namespace App\Core\Logger;

use Sentry\Severity;
use Sentry\State\Scope;
use Tracy\ILogger;
use function Sentry\captureException;
use function Sentry\captureMessage;
use function Sentry\init;
use function Sentry\withScope;

class SentryLogger implements ILogger
{
    private ILogger $inner;

    public function __construct(ILogger $inner, string $dsn, string $environment)
    {
        $this->inner = $inner;
        init([
            'dsn' => $dsn,
            'environment' => $environment,
        ]);
    }

    /**
     * @param mixed $value
     */
    public function log($value, $level = self::INFO)
    {
        // 1) vždy normálně zaloguj na server (soubor, syslog, atd.)
        $this->inner->log($value, $level);

        // 2) vybrané levely posílej do Sentry
        if (in_array($level, [self::ERROR, self::EXCEPTION, self::CRITICAL, self::WARNING], true)) {
            try {
                $this->notifySentry($value, (string) $level);
            } catch (\Throwable $e) {
                // selhalo odeslání do Sentry – log necháme bejt, nechceme tím rozbít appku
            }
        }
    }

    /**
     * @param mixed $value
     */
    private function notifySentry($value, string $level): void
    {
        $severity = $this->mapSeverity($level);

        if ($value instanceof \Throwable) {
            withScope(function (Scope $scope) use ($value, $severity): void {
                $scope->setLevel($severity);
                captureException($value);
            });
        } else {
            captureMessage((string) $value, $severity);
        }
    }

    private function mapSeverity(string $level): Severity
    {
        return match ($level) {
            self::WARNING => Severity::warning(),
            self::CRITICAL => Severity::fatal(),
            default => Severity::error(),
        };
    }
}
