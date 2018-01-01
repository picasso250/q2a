<?php

namespace lib;

/**
 *
 */
class Curl
{

  static $cache_dir = '/tmp/cache';
  static function get($url, $opts = null) {
    $tmp = self::$cache_dir;
    $has_dir = is_dir($tmp) && is_writable($tmp);
    if ($has_dir) {
        $filename = $tmp.'/'.urlencode($url);
        if (file_exists($filename)) {
            return array(200, file_get_contents($filename));
        }
    }
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // wait
    curl_setopt($ch, CURLOPT_DNS_CACHE_TIMEOUT, 1200); // cache dns 20 minutes
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // solve 60      SSL certificate problem: unable to get local issuer certificate
    $cookie_file = __DIR__.'/cookie.txt';
    if (file_exists($cookie_file)) {
      curl_setopt($ch, CURLOPT_COOKIE, trim(file_get_contents($cookie_file)));
    }
    $content = curl_exec($ch);
    if ($errno = curl_errno($ch)) {
        return array($errno, curl_error($ch));
    }
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($has_dir && $code == 200) {
        file_put_contents($filename, $content);
    }
    return array($code, $content);
  }
}
