<?php

namespace UpserverOnline\Core\Monitors;

use Aws\Ses\Exception\SesException;
use Illuminate\Mail\Transport\SesTransport;

trait InteractsWithSes
{
    private function runSesCheck(SesTransport $transport)
    {
        try {
            // Try to fetch the 'SendQuota' from Amazon SES.
            $result = $transport->ses()->getSendQuota();
        } catch (SesException $exception) {
            // Check if the exception was caused by an invalid token
            if ($exception->getAwsErrorCode() === "InvalidClientTokenId") {
                return $this->error(Mail::ERROR_SES_KEY, [
                    'message' => $exception->getAwsErrorMessage(),
                ]);
            }

            return $this->error(Mail::ERROR_SES_CONNECTION, [
                'message' => $exception->getAwsErrorMessage(),
            ]);
        }

        // Verify if results came back
        if (array_key_exists('Max24HourSend', $result->toArray())) {
            return;
        }

        return $this->error(Mail::ERROR_SES_INVALID, [
            'message' => 'Amazon SES API did not respond successfully',
        ]);
    }
}
