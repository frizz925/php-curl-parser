<?php namespace CurlParser\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use CurlParser\Parser;

class ParserTest extends TestCase
{
    public function testCharles()
    {
        $this->withFixtureTest('Charles');
        $this->withFixtureTest('CharlesWithoutCompressed');
        $this->withFixtureTest('CharlesImageUpload', [
            'method'    => 'POST',
            'host'      => 'encodable.com',
            'body'      => true
        ]);
    }

    public function testChrome()
    {
        $this->withFixtureTest('Chrome');
    }

    protected function withFixtureTest($name, $options=[])
    {
        $method = 'GET';
        $host = 'api.github.com';
        $scheme = 'https';
        $headers = [
            'DNT'               => [1],
            'Accept'            => ['text/html', 'application/xhtml+xml'],
            'Accept-Language'   => ['en-US', 'en;q=0.9']
        ];
        $body = '';
        extract($options, EXTR_IF_EXISTS);

        $fixture = $this->getFixture($name);
        $parsed = Parser::parse($fixture);
        $this->assertEquals($fixture, strval($parsed));
        $this->assertEquals($method, $parsed->getMethod());

        $uri = $parsed->getUri();
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertEquals($host, $uri->getHost());
        $this->assertEquals($scheme, $uri->getScheme('https'));

        $actualHeaders = $parsed->getHeaders();
        $this->assertTrue(is_array($actualHeaders));
        $this->assertArraySubset($headers, $actualHeaders);

        $actualBody = $parsed->getBody();
        $this->assertTrue(is_string($actualBody));
        if ($body === true) {
            $this->assertNotEmpty($actualBody);
        } elseif (is_string($body)) {
            $this->assertEquals($body, $actualBody);
        } else {
            $this->assertEmpty($actualBody);
        }

        $req = $parsed->toRequest();
        $this->assertInstanceOf(RequestInterface::class, $req);
        $reqHeaders = $req->getHeaders();
        $this->assertArraySubset($headers, $reqHeaders);
    }

    public function getFixture($name)
    {
        return file_get_contents(__DIR__."/../fixtures/$name.txt");
    }
}
