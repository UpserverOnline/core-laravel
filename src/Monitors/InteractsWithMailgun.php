<?php

namespace UpserverOnline\Core\Monitors;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Mail\Transport\MailgunTransport;

trait InteractsWithMailgun
{
    private function runMailgunCheck(MailgunTransport $transport)
    {
        $config = config('services.mailgun', []);

        // Older versions of Laravel didn't have the 'getEndpoint method'
        $endpoint = method_exists($transport, 'getEndpoint') ? $transport->getEndpoint() : 'api.mailgun.net';

        try {
            // Try to fetch the configured domain from the Mailgun Api
            $response = $this->guzzle($config)->request(
                'GET',
                "https://{$endpoint}/v3/domains/{$transport->getDomain()}",
                ['auth' => ['api', $transport->getKey()]]
            );
        } catch (BadResponseException $badResponseException) {
            // (4xx or 5xx error)
            $content = json_decode($badResponseException->getResponse()->getBody()->getContents(), true);

            return $this->error(Mail::ERROR_MAILGUN_DOMAIN, [
                'message' => $content['message'] ?? "Could not connect with Mailgun",
            ]);
        } catch (RequestException $requestException) {
            return $this->error(Mail::ERROR_MAILGUN_CONNECTION, [
                'message' => $requestException->getMessage(),
            ]);
        }

        $content = json_decode($response->getBody()->getContents(), true);

        // Check the domain status
        $isDisabled = $content['domain']['is_disabled'] ?? null;

        if ($isDisabled === true) {
            return $this->error(Mail::ERROR_MAILGUN_DISABLED, [
                'message' => "Mailgun domain is disabled",
            ]);
        }

        // Check the domain state
        $state = $content['domain']['state'] ?? null;

        if ($state !== 'active') {
            return $this->error(Mail::ERROR_MAILGUN_STATE, [
                'message' => "Mailgun domain state is not active",
            ]);
        }
    }
}
