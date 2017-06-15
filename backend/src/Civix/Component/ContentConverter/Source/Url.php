<?php

namespace Civix\Component\ContentConverter\Source;

class Url implements ContentSourceInterface
{
    /**
     * @var array
     */
    private $ignore;

    public function __construct(array $ignore = [])
    {
        $this->ignore = $ignore;
    }

    public function isSupported(string $content): bool
    {
        return (bool) filter_var($content, FILTER_VALIDATE_URL);
    }

    public function convert(string $content)
    {
        $host = parse_url($content, PHP_URL_HOST);
        if (!$host || ($this->ignore && in_array($host, $this->ignore, true))) {
            return null;
        }

        $options = array(
            'http' => array(
                'method' => 'GET',
                'header' => "Accept-language: en\r\n".
                    "User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2\r\n"
            )
        );

        $context  = stream_context_create($options);

        if ($data = @file_get_contents($content, false, $context)) {
            return $data;
        }

        throw new \RuntimeException("Unable to convert from given url ($context).");
    }
}