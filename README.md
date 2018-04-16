[![Build Status](https://travis-ci.org/Frizz925/php-curl-parser.svg?branch=master)](https://travis-ci.org/Frizz925/php-curl-parser)

# cURL Parser

cURL command line parser for PHP

## Installation

Install using composer

```sh
composer require frizz925/curl-parser
```

## Usage

```php
<?php


require_once(__DIR__.'/vendor/autoload.php');
$curl = <<<EOF
curl 'https://api.github.com/' -H 'Pragma: no-cache' -H 'DNT: 1' -H 'Accept-Encoding: gzip, deflate, br' -H 'Accept-Language: en-US,en;q=0.9' -H 'Upgrade-Insecure-Requests: 1' -H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36' -H 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8' -H 'Cache-Control: no-cache' -H 'Connection: keep-alive' --compressed
EOF;
$parsed = CurlParser\Parser::parse($curl);
$uri = $parsed->getUri();
echo $uri;
// 'https://api.github.com/'
echo $uri instanceof Psr\Http\Message\UriInterface;
// true
echo $uri->getHost();
// 'api.github.com'

echo $parsed->getMethod();
// 'GET'
echo $parsed->getBody();
// ''
var_dump($parsed->getHeaders());
// ['Accept-Encoding' => ['gzip', 'deflate', 'br'], DNT' => ['1'], ...]

$req = $parsed->toRequest();
echo $req instanceof Psr\Http\Message\RequestInterface;
// true
```
