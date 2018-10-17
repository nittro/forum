<?php

declare(strict_types=1);

namespace App\ORM\Processor;


class InvalidUrlException extends ProcessorException {

    public static function e4xx(string $url, string $type = 'link', string $message = 'Invalid %s - %s cannot be found') : self {
        return new static(sprintf($message, $type, self::simplifyUrl($url)));
    }

    public static function e5xx(string $url, string $type = 'link', string $message = 'Invalid %s - loading %s results in a server error') : self {
        return new static(sprintf($message, $type, self::simplifyUrl($url)));
    }

    public static function tooManyRedirects(string $url, string $type = 'link', string $message = 'Invalid %s - %s redirected too many times') : self {
        return new static(sprintf($message, $type, self::simplifyUrl($url)));
    }

    public static function other(string $url, string $message) : self {
        return new static(sprintf($message, self::simplifyUrl($url)));
    }




    private static function simplifyUrl(string $url) : string {
        $parts = parse_url($url);
        $host = preg_replace('~^www\.~i', '', $parts['host']);
        return !empty($parts['path']) && $parts['path'] !== '/' ? sprintf("the file '%s' at %s", basename($parts['path']), $host) : $host;
    }

}
