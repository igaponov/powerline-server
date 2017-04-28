<?php

namespace Civix\CoreBundle\Service\API;

class ServiceApi
{
    private $agent = "Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 6.0)'";

    protected function getResponse($url, $parameters = array(), $method = 'GET')
    {
        $cHandle = curl_init();

        if ($method == 'GET' && !empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }
        curl_setopt($cHandle, CURLOPT_URL, $url);
        curl_setopt($cHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($cHandle, CURLOPT_USERAGENT, $this->agent);
        if (($method == 'POST') && (!empty($parameters))) {
            curl_setopt($cHandle, CURLOPT_POST, true);
            curl_setopt($cHandle, CURLOPT_POSTFIELDS, http_build_query($parameters));
        }
        $result = curl_exec($cHandle);
        curl_close($cHandle);

        return json_decode($result);
    }
}
