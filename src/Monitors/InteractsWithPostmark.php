<?php

namespace UpserverOnline\Core\Monitors;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Swift_Transport;

trait InteractsWithPostmark
{
    private function runPostmarkCheck(Swift_Transport $transport)
    {
        $config = config('services.postmark', []);

        try {
            // Try to fetch the outbound messages from the Postmark Api
            $response = $this->guzzle($config)->request(
                'GET',
                'https://api.postmarkapp.com/messages/outbound?count=1&offset=0',
                ['headers' => [
                    'X-Postmark-Server-Token' => $config['token'],
                    'Content-Type'            => 'application/json',
                ]]
            );
        } catch (BadResponseException $badResponseException) {
            // (4xx or 5xx error)
            $content = json_decode($badResponseException->getResponse()->getBody()->getContents(), true);

            return $this->error(Mail::ERROR_POSTMARK_KEY, [
                'message' => $content['Message'],
            ]);
        } catch (RequestException $requestException) {
            return $this->error(Mail::ERROR_POSTMARK_CONNECTION, [
                'message' => $requestException->getMessage(),
            ]);
        }

        $content = json_decode($response->getBody()->getContents(), true);

        // Verify if results came back
        if (array_key_exists('TotalCount', $content)) {
            return;
        }

        $this->error(Mail::ERROR_POSTMARK_INVALID, [
            'message' => 'Postmark API did not respond successfully',
        ]);
    }
}
