<?php

namespace App\Core\Logger;

use Tracy\ILogger;

class DiscordLogger implements ILogger
{
    private ILogger $inner;
    private string $webhookUrl;

    public function __construct(ILogger $inner, string $webhookUrl)
    {
        $this->inner = $inner;
        $this->webhookUrl = $webhookUrl;
    }

    /**
     * @param mixed $value
     */
    public function log($value, $level = self::INFO)
    {
        // 1) vždy normálně zaloguj na server (soubor, syslog, atd.)
        $this->inner->log($value, $level);

        // 2) jen ERROR (== 500) posílej do Discordu
        if (in_array($level, [self::ERROR, self::EXCEPTION, self::CRITICAL]) && $this->webhookUrl) {
            try {
                $this->notifyDiscord($value);
            } catch (\Throwable $e) {
                // selhalo odeslání do Discordu – log necháme bejt, nechceme tím rozbít appku
            }
        }
    }

    /**
     * @param mixed $value
     */
    private function notifyDiscord($value): void
    {
        // Tracy umí logovat jak string, tak Throwable
        if ($value instanceof \Throwable) {
            $message = $value->getMessage();
            $file = $value->getFile();
            $line = $value->getLine();
            $trace = $value->getTraceAsString();
        } else {
            $message = (string) $value;
            $file = 'n/a';
            $line = 0;
            $trace = '';
        }

        // zkrácený trace, aby to Discord sežral
        $traceLines = explode("\n", $trace);
        $traceShort = implode("\n", array_slice($traceLines, 0, 8));

        $embed = [
            'title' => mb_substr($message, 0, 200),
            'description' => sprintf("`%s:%d`", $file, $line),
            'timestamp' => gmdate('c'),
            'fields' => [
                [
                    'name' => 'Level',
                    'value' => 'ERROR (500)',
                    'inline' => true,
                ],
                [
                    'name' => 'Trace',
                    'value' => $traceShort ? "```" . mb_substr($traceShort, 0, 1800) . "```" : '_no trace_',
                    'inline' => false,
                ],
            ],
        ];

        $body = [
            'username' => 'Nette-ErrorBot',
            'embeds' => [$embed],
        ];

        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $ch = curl_init($this->webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 2,
        ]);
        curl_exec($ch);
        curl_close($ch);
    }
}