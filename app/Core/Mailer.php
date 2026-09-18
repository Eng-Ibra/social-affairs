<?php

namespace App\Core;

/**
 * Minimal mail sender. If MAIL_HOST is configured it sends via SMTP
 * (STARTTLS + AUTH LOGIN). Otherwise it writes the message to
 * storage/logs/mail.log so the system remains fully usable offline —
 * this keeps password reset working without any external API dependency.
 */
class Mailer
{
    public static function send(string $to, string $subject, string $body): bool
    {
        $cfg = config('mail');
        if (empty($cfg['host'])) {
            self::logToFile($to, $subject, $body);
            return true;
        }

        try {
            return self::sendSmtp($cfg, $to, $subject, $body);
        } catch (\Throwable $e) {
            self::logToFile($to, $subject, $body . "\n\n[SMTP ERROR: {$e->getMessage()}]");
            return false;
        }
    }

    private static function logToFile(string $to, string $subject, string $body): void
    {
        $line = sprintf(
            "===== %s =====\nTo: %s\nSubject: %s\n\n%s\n\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            $body
        );
        $path = base_path('storage/logs/mail.log');
        @file_put_contents($path, $line, FILE_APPEND);
    }

    private static function sendSmtp(array $cfg, string $to, string $subject, string $body): bool
    {
        $host = $cfg['encryption'] === 'ssl' ? 'ssl://' . $cfg['host'] : $cfg['host'];
        $fp = @fsockopen($host, (int) $cfg['port'], $errno, $errstr, 10);
        if (!$fp) {
            throw new \RuntimeException("Could not connect to SMTP host: {$errstr}");
        }
        $read = fn () => fgets($fp, 512);
        $write = function (string $cmd) use ($fp) {
            fwrite($fp, $cmd . "\r\n");
        };

        $read();
        $write('EHLO localhost');
        $read();

        if ($cfg['encryption'] === 'tls') {
            $write('STARTTLS');
            $read();
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write('EHLO localhost');
            $read();
        }

        if (!empty($cfg['username'])) {
            $write('AUTH LOGIN');
            $read();
            $write(base64_encode($cfg['username']));
            $read();
            $write(base64_encode($cfg['password']));
            $read();
        }

        $write('MAIL FROM:<' . $cfg['from_address'] . '>');
        $read();
        $write('RCPT TO:<' . $to . '>');
        $read();
        $write('DATA');
        $read();

        $headers = "From: {$cfg['from_name']} <{$cfg['from_address']}>\r\n";
        $headers .= "To: <{$to}>\r\n";
        $headers .= "Subject: {$subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n";

        $write($headers . "\r\n" . $body . "\r\n.");
        $read();
        $write('QUIT');
        fclose($fp);
        return true;
    }
}
