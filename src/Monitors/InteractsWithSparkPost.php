<?php

namespace UpserverOnline\Core\Monitors;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Mail\Transport\SparkPostTransport;
use Illuminate\Support\Arr;

trait InteractsWithSparkPost
{
    private function runSparkpostCheck(SparkPostTransport $transport)
    {
        $config = config('services.sparkpost', []);

        // Older versions of Laravel didn't have the 'getEndpoint method'
        $endpoint = method_exists($transport, 'getEndpoint') ? $transport->getEndpoint() : 'https://api.sparkpost.com/api/v1/transmissions';

        try {
            // Try to fetch the transmissions from the SparkPost Api
            $response = $this->guzzle($config)->request(
                'GET',
                $endpoint,
                ['headers' => ['Authorization' => $transport->getKey()]],
                $transport->getOptions()
            );
        } catch (BadResponseException $badResponseException) {
            // (4xx or 5xx error)
            $content = json_decode($badResponseException->getResponse()->getBody()->getContents(), true);

            return $this->error(Mail::ERROR_SPARKPOST_KEY, [
                'messages' => Arr::pluck($content['errors'], 'message'),
            ]);
        } catch (RequestException $requestException) {
            return $this->error(Mail::ERROR_SPARKPOST_CONNECTION, [
                'message' => $requestException->getMessage(),
            ]);
        }

        $content = json_decode($response->getBody()->getContents(), true);

        // Verify if results came back
        if (array_key_exists('results', $content)) {
            return;
        }

        $this->error(Mail::ERROR_SPARKPOST_INVALID, [
            'message' => 'SparkPost API did not respond successfully',
        ]);
    }
}
