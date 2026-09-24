<?php

namespace App\Services\CloudBackup;

class HttpClient
{
    /**
     * @param  array<string,string>  $headers
     * @return array{status:int,body:string,headers:array<string,string>}
     */
    public function request(string $method, string $url, ?string $body = null, array $headers = [], int $timeout = 120): array
    {
        if (! function_exists('curl_init')) {
            return $this->streamRequest($method, $url, $body, $headers, $timeout);
        }

        $ch = curl_init($url);
        $hdr = [];
        foreach ($headers as $k => $v) {
            $hdr[] = $k.': '.$v;
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => $hdr,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);

            return ['status' => 0, 'body' => $err, 'headers' => []];
        }
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);
        $rawHeaders = substr($raw, 0, $headerSize);
        $respBody = substr($raw, $headerSize);

        return ['status' => $status, 'body' => $respBody, 'headers' => $this->parseHeaders($rawHeaders)];
    }

    /** @param array<string,string> $headers */
    public function postForm(string $url, array $fields, array $headers = []): array
    {
        return $this->request('POST', $url, http_build_query($fields), array_merge([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ], $headers));
    }

    /** @param array<string,string> $headers */
    public function postJson(string $url, array $payload, array $headers = []): array
    {
        return $this->request('POST', $url, json_encode($payload, JSON_UNESCAPED_UNICODE), array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ], $headers));
    }

    /** @param array<string,string> $headers */
    public function putFile(string $url, string $path, array $headers = [], int $timeout = 600): array
    {
        $size = filesize($path) ?: 0;
        $headers = array_merge([
            'Content-Length' => (string) $size,
        ], $headers);

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            $hdr = [];
            foreach ($headers as $k => $v) {
                $hdr[] = $k.': '.$v;
            }
            $fp = fopen($path, 'rb');
            curl_setopt_array($ch, [
                CURLOPT_PUT => true,
                CURLOPT_INFILE => $fp,
                CURLOPT_INFILESIZE => $size,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $hdr,
                CURLOPT_HEADER => true,
            ]);
            $raw = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $err = curl_error($ch);
            curl_close($ch);
            if (is_resource($fp) || (is_object($fp) && get_resource_type($fp) === 'stream')) {
                fclose($fp);
            }
            if ($raw === false) {
                return ['status' => 0, 'body' => $err, 'headers' => []];
            }

            return [
                'status' => $status,
                'body' => substr($raw, $headerSize),
                'headers' => $this->parseHeaders(substr($raw, 0, $headerSize)),
            ];
        }

        return $this->request('PUT', $url, file_get_contents($path) ?: '', $headers, $timeout);
    }

    /**
     * @param  array<string,string>  $headers
     * @return array{status:int,body:string,headers:array<string,string>}
     */
    private function streamRequest(string $method, string $url, ?string $body, array $headers, int $timeout): array
    {
        $hdr = '';
        foreach ($headers as $k => $v) {
            $hdr .= $k.': '.$v."\r\n";
        }
        $ctx = stream_context_create([
            'http' => [
                'method' => strtoupper($method),
                'header' => $hdr,
                'content' => $body ?? '',
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        $status = 0;
        $respHeaders = [];
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int) $m[1];
            $respHeaders = $this->parseHeaders(implode("\r\n", $http_response_header));
        }

        return ['status' => $status, 'body' => is_string($resp) ? $resp : '', 'headers' => $respHeaders];
    }

    /** @return array<string,string> */
    private function parseHeaders(string $raw): array
    {
        $out = [];
        foreach (explode("\n", $raw) as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, ':')) {
                continue;
            }
            [$k, $v] = explode(':', $line, 2);
            $out[strtolower(trim($k))] = trim($v);
        }

        return $out;
    }
}
