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
    }

    public function testChrome()
    {
        $this->withFixtureTest('Chrome');
    }

    protected function withFixtureTest($name)
    {
        $fixture = $this->getFixture($name);
        $parsed = Parser::parse($fixture);
        $this->assertEquals($fixture, strval($parsed));
        $this->assertEquals('GET', $parsed->getMethod());

        $uri = $parsed->getUri();
        $this->assertInstanceOf(UriInterface::class, $uri);
        $this->assertEquals('api.github.com', $uri->getHost());
        $this->assertEquals('https', $uri->getScheme('https'));

        $headers = $parsed->getHeaders();
        $this->assertTrue(is_array($headers));
        $this->assertArraySubset([
            'DNT'               => [1],
            'Accept'            => ['text/html', 'application/xhtml+xml'],
            'Accept-Language'   => ['en-US', 'en;q=0.9']
        ], $headers);

        $body = $parsed->getBody();
        $this->assertTrue(is_string($body));
        $this->assertEmpty($body);

        $req = $parsed->toRequest();
        $this->assertInstanceOf(RequestInterface::class, $req);
        $reqHeaders = $req->getHeaders();
        $this->assertArraySubset([
            'DNT'               => [1],
            'Accept'            => ['text/html', 'application/xhtml+xml'],
            'Accept-Language'   => ['en-US', 'en;q=0.9']
        ], $reqHeaders);
    }

    public function getFixture($name)
    {
        return file_get_contents(__DIR__."/../fixtures/$name.txt");
    }
}
