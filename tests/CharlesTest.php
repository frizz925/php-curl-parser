<?php namespace CurlParser\Tests;

class CharlesTest extends FixtureTestCase
{
    public function testCharles()
    {
        $this->withFixtureTest('Charles');
        $this->withFixtureTest('CharlesWithoutCompressed');
        $this->withFixtureTest('CharlesImageUpload', [
            'method'    => 'POST',
            'host'      => 'encodable.com',
            'body'      => true,
            'headers'   => [
                'Host'      => 'encodable.com',
                'Accept'    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8'
            ]
        ]);
    }
}
