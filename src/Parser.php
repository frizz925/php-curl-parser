<?php namespace CurlParser;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

class Parser
{

    /**
     * Parse cURL command into objects
     *
     * @param string $curl
     * @return Parser
     */
    public static function parse($curl)
    {
        return new static($curl);
    }

    /** @var string */
    private $curl;

    /** @var UriInterface */
    private $uri;

    /** @var string */
    private $method;

    /** @var string */
    private $body;

    /** @var string[] */
    protected $singleParams = [
        'compressed'
    ];

    /** @var string[] */
    protected $ignoreHeaders = [
        'Cookie'
    ];

    /**
     * Constructor for the parser
     *
     * @param string $curl
     */
    public function __construct($curl)
    {
        $this->curl = $curl;
        $this->tree = $this->parseCurl($curl);
        $this->uri = $this->parseUri($this->tree);
        $this->method = $this->parseMethod($this->tree);
        $this->headers = $this->parseHeaders($this->tree);
        $this->body = $this->parseBody($this->tree);
    }

    /**
     * Get the cURL request URI
     *
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Get the cURL request method
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Get the cURL request headers
     *
     * @return string[][]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the cURL request body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Convert parse cURL request into PSR request
     *
     * @return RequestInterface
     */
    public function toRequest()
    {
        return new Request(
            $this->getMethod(),
            $this->getUri(),
            $this->getHeaders(),
            $this->getBody()
        );
    }

    protected function parseCurl($curl)
    {
        $parts = preg_split('/\s+[-]{1,2}/', $curl);
        $result = [];
        foreach ($parts as $chunk) {
            if (strpos($chunk, 'curl') !== false) {
                $chunk = trim(str_replace('curl', '', $chunk));
            }
            if (empty($chunk)) {
                continue;
            }

            $pos = strpos($chunk, ' ');
            if ($pos === false) {
                $param = null;
                $value = $chunk;
            } else {
                $param = trim(substr($chunk, 0, $pos));
                $value = trim(substr($chunk, $pos + 1));
            }

            if (in_array($value[0], ["'", '"'])) {
                $value = substr($value, 1, strlen($value) - 2);
            }

            if (is_null($param) || in_array($param, $this->singleParams)) {
                $result[] = $value;
            } else {
                $result[] = [$param, $value];
            }
        }
        return $result;
    }

    protected function parseUri($tree)
    {
        $uri = null;
        foreach ($tree as $arg) {
            if (is_string($arg)) {
                $uri = $arg;
                break;
            }
        }
        return new Uri($uri);
    }

    protected function parseMethod($tree)
    {
        $method = 'GET';
        $tree = $this->filterTree($tree, ['X', 'data', 'data-binary']);
        foreach ($tree as $arg) {
            list($param, $value) = $arg;
            if ($param === 'X') {
                return strtoupper($value);
            } else {
                $method = 'POST';
            }
        }
        return $method;
    }

    protected function parseHeaders($tree)
    {
        $headers = [];
        foreach ($this->filterTree($tree, 'H') as $arg) {
            list($param, $headerStr) = $arg;
            $pos = strpos($headerStr, ': ');
            $prop = trim(substr($headerStr, 0, $pos));
            $value = trim(substr($headerStr, $pos + 1));
            if (in_array($prop, $this->ignoreHeaders)) {
                continue;
            }
            $headers[$prop] = array_map(function($val) {
                return trim($val);
            }, explode(',', $value));
        }
        return $headers;
    }

    protected function parseBody($tree)
    {
        $tree = $this->filterTree($tree, ['data', 'data-binary']);
        foreach ($tree as $arg) {
            list($param, $value) = $arg;
            return $value;
        }
        return '';
    }

    protected function filterTree($tree, $params)
    {
        if (is_string($params)) {
            $params = [$params];
        }
        $filtered = [];
        foreach ($tree as $arg) {
            if (!is_array($arg) || count($arg) < 2) {
                continue;
            }
            list($param) = $arg;
            if (in_array($param, $params)) {
                $filtered[] = $arg;
            }
        }
        return $filtered;
    }

    public function __toString()
    {
        return $this->curl;
    }
}
