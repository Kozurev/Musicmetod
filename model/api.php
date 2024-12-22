<?php

namespace Model;

class Api
{
    const REQUEST_METHOD_GET = 'get';
    const REQUEST_METHOD_POST = 'post';

    /**
     * @param string $link
     * @param array $params
     * @param string $requestMethod
     * @return mixed
     */
    public static function getRequest(string $link, array $params = [], string $requestMethod = self::REQUEST_METHOD_GET)
    {
        $queryParams = http_build_query($params);
        if ($requestMethod === self::REQUEST_METHOD_GET) {
            return json_decode(file_get_contents($link .'?' . $queryParams));
        } elseif ($requestMethod === self::REQUEST_METHOD_POST) {
            return json_decode(file_get_contents($link, false, stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: application/x-www-form-urlencoded',
                    'content' => $queryParams
                ]
            ])));
        } else {
            return null;
        }
    }

    /**
     * @param string $link
     * @param array $params
     * @param array $headers
     * @param string $method
     * @return mixed
     */
    public static function getJsonRequest(
        string $link,
        array $params,
        array $headers = [],
        string $method = self::REQUEST_METHOD_POST
    ) {
        $curl = curl_init();
        if (self::REQUEST_METHOD_GET === $method) {
            curl_setopt($curl, CURLOPT_URL, $link . '?' . http_build_query($params));
        } else {
            curl_setopt($curl, CURLOPT_URL, $link);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array_merge(['Content-Type: application/json', 'accept: application/json'], $headers));
        curl_setopt($curl, CURLOPT_POST, $method === self::REQUEST_METHOD_POST);
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}
