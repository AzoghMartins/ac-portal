<?php
declare(strict_types=1);

namespace App;

/**
 * Minimal SOAP client wrapper for issuing commands to the AzerothCore worldserver.
 */
final class WorldServerSoap
{
    /**
     * Execute a single SOAP command and return the raw response string.
     *
     * @throws \RuntimeException on configuration or SOAP errors
     */
    public static function execute(string $command): string
    {
        if (!class_exists(\SoapClient::class)) {
            throw new \RuntimeException('PHP SOAP extension is not installed.');
        }

        $host    = Db::env('SOAP_HOST', '127.0.0.1');
        $port    = Db::env('SOAP_PORT', '7878');
        $user    = Db::env('SOAP_USER');
        $pass    = Db::env('SOAP_PASS');
        $uri     = Db::env('SOAP_URI', 'urn:ACSOAP');
        $scheme  = Db::env('SOAP_SCHEME', 'http');
        $timeout = (int)Db::env('SOAP_TIMEOUT', 10);

        if ($user === null || $user === '' || $pass === null || $pass === '') {
            throw new \RuntimeException('SOAP credentials are not configured.');
        }

        $location = sprintf('%s://%s:%s/', $scheme, $host, $port);
        $uris = array_values(array_unique([$uri, 'urn:ACSOAP', 'urn:AC', 'urn:TC']));
        $lastError = null;

        foreach ($uris as $candidateUri) {
            $options  = [
                'location'           => $location,
                'uri'                => $candidateUri,
                'login'              => $user,
                'password'           => $pass,
                'style'              => SOAP_RPC,
                'use'                => SOAP_ENCODED,
                'soap_version'       => SOAP_1_1,
                'connection_timeout' => $timeout,
                'exceptions'         => true,
                'trace'              => false,
                'keep_alive'         => true,
                'stream_context'     => stream_context_create([
                    'http' => [
                        'timeout' => $timeout,
                    ],
                ]),
            ];

            try {
                $client = new \SoapClient(null, $options);
                $param    = new \SoapParam($command, 'command');
                $response = $client->__soapCall('executeCommand', [$param]);
                if (is_array($response)) {
                    $response = reset($response);
                }
                return trim((string)$response);
            } catch (\SoapFault $e) {
                $msg = $e->getMessage();
                $lastError = $msg;
                // If method not recognized, try the next URI candidate.
                if (stripos($msg, 'not implemented') !== false || stripos($msg, 'not recognized') !== false) {
                    continue;
                }
                throw new \RuntimeException('SOAP fault: ' . $msg, 0, $e);
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                // Try next URI on generic errors as a fallback.
                continue;
            }
        }

        throw new \RuntimeException('SOAP request failed: ' . ($lastError ?? 'unknown error'));
    }
}
