<?php namespace CurlParser\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use CurlParser\Parser;

abstract class FixtureTestCase extends TestCase
{
    /**
     * Run test case using fixture
     *
     * @param string $name
     * @param array $options
     * @return Parser
     */
    protected function withFixtureTest($name, $options = [])
    {
        $method = 'GET';
        $host = 'api.github.com';
        $scheme = 'https';
        $headers = [
            'DNT'               => '1',
            'Accept'            => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language'   => 'en-US,en;q=0.9',
            'User-Agent'        => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/65.0.3325.181 Safari/537.36',
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

        $actualHeaders = $parsed->getHeaderLines();
        $this->assertTrue(is_array($actualHeaders));
        $this->assertArraySubset($headers, $actualHeaders);
        $this->assertArrayNotHasKey('Cookie', $actualHeaders);

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
        return $parsed;
    }

    public function getFixture($name)
    {
        return file_get_contents(__DIR__."/../fixtures/$name.txt");
    }
}
