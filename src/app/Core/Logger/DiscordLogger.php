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

        $url = isset($_SERVER['HTTP_HOST'])
            ? ($_SERVER['HTTPS'] ?? '' === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . ($_SERVER['REQUEST_URI'] ?? '')
            : 'CLI';

        $embed = [
            'title' => mb_substr($message, 0, 200),
            'description' => sprintf("`%s:%d`", $file, $line),
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'fields' => [
                [
                    'name' => 'URL',
                    'value' => $url,
                    'inline' => true,
                ],
                [
                    'name' => 'Level',
                    'value' => 'ERROR (500)',
                    'inline' => true,
                ],
            ],
        ];

        $body = [
            'username' => 'Nette-ErrorBot',
            'embeds' => [$embed],
        ];

        $payload = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nContent-Length: " . \strlen($payload) . "\r\n",
                'content' => $payload,
                'timeout' => 5,
                'ignore_errors' => true,
            ],
        ];

        $result = @\file_get_contents($this->webhookUrl, false, \stream_context_create($opts));
    }
}