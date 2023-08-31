<?php

namespace Rabsana\Exchanger\Exchangers\Libs;

use Exception;

class CoinexLib
{
    private string $accessId;
    private string $secretKey;
    private string $baseUrl = 'https://api.coinex.com';
    private string $method = 'GET';

    public function __construct()
    {
        $this->accessId = config("rabsana-exchanger.coinex.accessId");
        $this->secretKey = config("rabsana-exchanger.coinex.secretKey");
    }

    public function setMethod(string $method): CoinexLib
    {
        if (!in_array(strtoupper($method), ["GET", "POST"])) {
            throw new Exception("The '{$method}' is not valid.");
        }
        $this->method = strtoupper($method);
        return $this;
    }

    public function httpRequest(string $endpoint, array $params = [])
    {
        try {

            $url = trim($this->baseUrl, '/') . '/' . trim($endpoint, '/');

            //add tonce to params
            $params = array_merge($params, ['tonce' => time() * 1000]);
            $params = array_merge($params, ['access_id' => $this->accessId]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->method);

            if ($this->method == 'GET') {
                $url .= '?' . http_build_query($params);
            }

            if ($this->method == 'POST') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            }

            //sign params
            ksort($params);
            $params = array_merge($params, ['secret_key' => $this->secretKey]);
            $sign = http_build_query($params);
            $sign = md5($sign);
            $sign = strtoupper($sign);

            //set url
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            //set header request
            $headers = [];
            $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/39.0.2171.71 Safari/537.36';
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Authorization: ' . $sign;
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


            //send request
            $result = curl_exec($ch);

            //error handling request
            if (curl_errno($ch)) {
                throw new Exception("COINEX-ERROR: " . curl_error($ch));
            }

            //close request
            curl_close($ch);

            //return json result
            return json_decode($result, true);


            // 
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function createHistoryFilters(array $params = [])
    {
        $filters = [];

        if (isset($params['coin'])) {
            $filters['coin_type'] = $params['coin'];
        }

        if (isset($params['status'])) {
            $filters['status'] = strtoupper($params['status']);
        }

        $filters['limit'] = isset($params['limit']) ? (int) $params['limit'] : 500;

        return $filters;
    }
}
