<?php

namespace Rabsana\Exchanger\Traits;

use Exception;

trait HttpTrait
{

    public function postRequest(string $url, $data, array $headers = [])
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => array_merge(
                [
                    'Content-Type: application/json'
                ],
                $headers
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        $info = curl_getinfo($curl);

        // 
        $response['status'] = (int) $info['http_code'];
        if ($response['status'] < 200 || $response['status'] > 299) {
            throw new Exception(json_encode($response));
        }

        curl_close($curl);

        return $response;
    }


    public function getRequest(string $url, array $data = [], array $headers = [])
    {
        $url = $this->buildQueryString($url, $data);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array_merge(
                [
                    'Content-Type: application/json'
                ],
                $headers
            ),
        ));

        $response = curl_exec($curl);
        $response = json_decode($response, true);
        $info = curl_getinfo($curl);

        // 
        $response['status'] = (int) $info['http_code'];
        if ($response['status'] < 200 || $response['status'] > 299) {
            throw new Exception($response);
        }

        curl_close($curl);

        return $response;
    }

    public function buildQueryString($url, $data = [])
    {
        if (!empty($data)) {
            $i = 0;
            foreach ($data as $key => $item) {

                if (empty($item)) {
                    continue;
                }

                $url .= (($i == 0) ? "?" : "&") . $key . "=" . $item;
                $i++;
            }
        }

        return $url;
    }
}
