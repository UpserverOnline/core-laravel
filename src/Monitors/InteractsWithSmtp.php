<?php

namespace UpserverOnline\Core\Monitors;

use ReflectionMethod;
use Swift_SmtpTransport;
use Throwable;

trait InteractsWithSmtp
{
    private function runSmtpCheck(Swift_SmtpTransport $transport)
    {
        $method = new ReflectionMethod(Swift_SmtpTransport::class, 'doHeloCommand');
        $method->setAccessible(true);

        try {
            // Send the HELO command
            $method->invokeArgs($transport, []);
        } catch (Throwable $exception) {
            $this->error(Mail::ERROR_SMTP_CONNECTION, [
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
