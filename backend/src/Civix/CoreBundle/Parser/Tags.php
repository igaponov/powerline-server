<?php

namespace Civix\CoreBundle\Parser;

class Tags
{
    /**
     * @param string $text
     *
     * @return array
     */
    public static function parseHashTags($text): array
    {
        $result = array();
        preg_match_all('/(\s|^)(#[\w-]+)/', $text, $matches);
        /** @var array $original */
        $original = $matches[2];
        foreach ($original as $item) {
            $hash = mb_strtolower($item);
            if (!in_array($hash, $result, true)) {
                $result[] = $hash;
            }
        }

        return array(
            'parsed' => $result,
            'original' => $original,
        );
    }

    public static function parseMentionTags($text)
    {
        preg_match_all('/@([a-zA-Z0-9._-]+[a-zA-Z0-9])/', $text, $matches);

        return $matches[1];
    }

    public static function replaceMentionTags($text, $replacements)
    {
        return preg_replace_callback(
            '/(?<!>)(@([a-zA-Z0-9._-]+[a-zA-Z0-9]))/',
            function ($matches) use ($replacements) {
                return $replacements[$matches[1]] ?? $matches[1];
            },
            $text
        );
    }

    public static function wrapHashTags($text)
    {
        return preg_replace('/(\s|^)(#[\w-]+)/', '$1<a data-hashtag="$2">$2</a>', $text);
    }
}
