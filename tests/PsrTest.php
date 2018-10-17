<?php namespace CurlParser\Tests;

use PHPUnit\Framework\TestCase;
use CurlParser\Parser;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\Uri;

class PsrTest extends TestCase
{
    public function testPsrMethods()
    {
        $parser = new Parser('curl https://example.com/');
        // Test protocol version
        $parser = $parser->withProtocolVersion('2.0');
        $this->assertEquals('2.0', $parser->getProtocolVersion());
        // Test headers
        $parser = $parser->withHeader('A', 'B')
            ->withAddedHeader('A', 'C');
        $this->assertEquals(['A' => ['B', 'C']], $parser->getHeaders());
        $this->assertEquals(['B', 'C'], $parser->getHeader('A'));
        $this->assertEquals('B, C', $parser->getHeaderLine('A'));
        $this->assertEquals(['A'], $parser->getHeaderNames());
        $parser = $parser->withoutHeader('A')
            ->withoutHeader('B');
        $this->assertEmpty($parser->getHeader('A'));
        // Test method
        $parser = $parser->withMethod('POST');
        $this->assertEquals('POST', $parser->getMethod());
        // Test URI
        $parser = $parser->withUri(new Uri('https://google.com/'));
        $this->assertEquals('https://google.com/', $parser->getUri());
        // Test request target
        $this->assertEquals('/', $parser->getRequestTarget());
        $parser = $parser->withRequestTarget('*');
        $this->assertEquals('*', $parser->getRequestTarget());
    }
}
