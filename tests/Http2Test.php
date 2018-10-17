<?php namespace CurlParser\Tests;

use CurlParser\Parser;

class Http2Test extends FixtureTestCase
{
    public function testHttp2()
    {
        $headers = [
            'authority'                 => 'www.google.com',
            'upgrade-insecure-requests' => '1',
            'dnt'                       => '1',
            'user-agent'                => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_13_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36',
            'accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'referer'                   => 'https://www.google.com/',
            'accept-encoding'           => 'gzip, deflate, br',
            'accept-language'           => 'en-US,en;q=0.9'
        ];
        $parser = $this->withFixtureTest('ChromeHttp2', [
            'host'      => 'www.google.com',
            'headers'   => $headers,
        ]);
        $this->assertInstanceOf(Parser::class, $parser);
    }
}
