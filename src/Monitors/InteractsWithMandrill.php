<?php

namespace UpserverOnline\Core\Monitors;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Mail\Transport\MandrillTransport;

trait InteractsWithMandrill
{
    private function runMandrillCheck(MandrillTransport $transport)
    {
        $config = config('services.mandrill', []);

        try {
            // Try to to ping the Mandrill Api
            $response = $this->guzzle($config)->request(
                'POST',
                "https://mandrillapp.com/api/1.0/users/ping2.json",
                ['form_params' => ['key' => $transport->getKey()]]
            );
        } catch (BadResponseException $badResponseException) {
            // (4xx or 5xx error)
            $content = json_decode($badResponseException->getResponse()->getBody()->getContents(), true);

            return $this->error(Mail::ERROR_MANDRILL_KEY, [
                'message' => $content['message'] ?? "Could not connect to Mandrill",
            ]);
        } catch (RequestException $requestException) {
            return $this->error(Mail::ERROR_MANDRILL_CONNECTION, [
                'message' => $requestException->getMessage(),
            ]);
        }

        $content = json_decode($response->getBody()->getContents(), true);

        // Check if the Api responded with the right content
        if ($content !== ["PING" => "PONG!"]) {
            return $this->error(Mail::ERROR_MANDRILL_INVALID, [
                'Mandrill API did not respond successfully',
            ]);
        }
    }
}
