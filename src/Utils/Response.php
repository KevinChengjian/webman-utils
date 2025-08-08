<?php

namespace Nasus\WebmanUtils\Utils;

use Nasus\WebmanUtils\Enums\CodeInterface;
use Webman\Http\Response as ResponseJson;

class Response
{
    /**
     * @param CodeInterface $code
     * @param mixed|null $data
     * @param string $msg
     * @param mixed $options
     * @return ResponseJson
     */
    public static function json(CodeInterface $code, mixed $data = null, string $msg = '', mixed $options = []): ResponseJson
    {
        $httpCode = empty($options['httpCode']) ? 200 : $options['httpCode'];

        $headers = empty($options['headers']) ? [] : $options['headers'];
        $headers = array_merge(['Content-Type' => 'application/json'], $headers);

        return new ResponseJson($httpCode, $headers, json_encode([
            'code' => $code,
            'data' => $data,
            'msg' => $msg
        ], JSON_UNESCAPED_UNICODE));
    }
}