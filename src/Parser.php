<?php namespace CurlParser;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;

class Parser implements RequestInterface
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

    /** @var string[] */
    protected $singleParams = [
        'compressed'
    ];

    /** @var string[] */
    protected $ignoreHeaders = [
        'cookie'
    ];

    /** @var string */
    private $curl;

    /** @var UriInterface */
    private $uri;

    /** @var string */
    private $method;

    /** @var string */
    private $body;

    /** @var string[][] */
    private $headers;

    /** @var string[][] */
    private $normalizedHeaders;

    /** @var string[] */
    private $headerNamesMap;

    /** @var string */
    private $protocolVersion = '1.1';

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
        $this->normalizedHeaders = $this->normalizeHeaders($this->headers);
        $this->headerNamesMap = $this->mapHeaderNames($this->headers);
        $this->body = $this->parseBody($this->tree);
    }

    public function getProtocolVersion()
    {
        // TODO: Detect parsed HTTP protocol version
        return $this->protocolVersion;
    }

    public function withProtocolVersion($version)
    {
        $new = clone $this;
        $new->protocolVersion = $version;
        return $new;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function hasHeader($name)
    {
        $name = strtolower($name);
        return isset($this->normalizedHeaders[$name]);
    }

    public function getHeader($name)
    {
        $name = strtolower($name);
        if (!$this->hasHeader($name)) {
            return [];
        }
        return $this->normalizedHeaders[$name];
    }

    public function getHeaderLine($name)
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        $normalized = strtolower($name);
        $new = clone $this;
        $new->headers[$name] = $value;
        $new->normalizedHeaders[$normalized] = $value;
        $new->headerNamesMap[$normalized] = $name;
        return $new;
    }

    public function withAddedHeader($name, $value)
    {
        if (!is_array($value)) {
            $value = [$value];
        }
        if ($this->hasHeader($name)) {
            $value = array_merge($this->getHeader($name), $value);
        }
        return $this->withHeader($name, $value);
    }

    public function withoutHeader($name)
    {
        if (!$this->hasHeader($name)) {
            return $this;
        }
        $normalized = strtolower($name);
        $name = $this->headerNamesMap[$normalized];
        $new = clone $this;
        unset($new->headers[$name]);
        unset($new->normalizedHeaders[$normalized]);
        return $new;
    }

    public function getBody()
    {
        return $this->body;
    }

    /**
     * @inheritDoc
     * @codeCoverageIgnore
     */
    public function withBody(StreamInterface $body)
    {
        $new = clone $this;
        $new->body = $body;
        return $new;
    }

    public function getRequestTarget()
    {
        if (isset($this->requestTarget)) {
            return $this->requestTarget;
        }
        $uri = $this->getUri();
        $path = $uri->getPath();
        $query = $uri->getQuery();
        return !empty($query) ? sprintf('%s?%s', $path, $query) : $path;
    }

    public function withRequestTarget($requestTarget)
    {
        $new = clone $this;
        $new->requestTarget = $requestTarget;
        return $new;
    }

    public function getMethod()
    {
        return $this->method;
    }

    public function withMethod($method)
    {
        $new = clone $this;
        $new->method = $method;
        return $new;
    }


    public function getUri()
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $new = clone $this;
        $new->uri = $uri;
        return $new;
    }

    /**
     * Get the cURL request header names
     *
     * @return string[]
     */
    public function getHeaderNames()
    {
        return array_keys($this->headers);
    }

    /**
     * Get the cURL request header lines
     *
     * @return string[]
     */
    public function getHeaderLines()
    {
        $headers = $this->getHeaders();
        foreach ($headers as $name => $values) {
            $headers[$name] = implode(', ', $values);
        }
        return $headers;
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

    /**
     * @codeCoverageIgnore
     */
    protected function parseCurl($curl)
    {
        $parts = explode(' ', $curl);
        $result = [];
        while (count($parts) > 0) {
            $chunk = trim(array_shift($parts));
            if (empty($chunk) || $chunk == 'curl') {
                continue;
            }

            if ($chunk[0] == '-') {
                $param = preg_replace('/^[-]{1,2}/', '', $chunk);
                $value = array_shift($parts);
            } else {
                $param = null;
                $value = $chunk;
            }

            while (!$this->isQuoteClosed($value)) {
                $concat = array_shift($parts);
                if (!$concat) {
                    break;
                }
                $value .= " $concat";
            }

            $value = trim($value);
            if (strlen($value) >= 2 && in_array($value[0], ["'", '"'])) {
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

    /**
     * @codeCoverageIgnore
     */
    protected function isQuoteClosed($text)
    {
        $quotes = ["'", '"'];
        $text = trim($text);
        $len = strlen($text);
        if ($len < 2) {
            return true;
        }
        if (!in_array($text[0], $quotes)) {
            return true;
        }
        $quote = $text[0];
        if ($text[$len-1] == $quote) {
            // check for escaped character
            if ($text[$len-2] == '\\') {
                return false;
            }
            return true;
        }
        return false;
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
            $normalized = strtolower($prop);
            if (in_array($normalized, $this->ignoreHeaders)) {
                continue;
            }
            $headers[$prop] = [$value];
        }
        return $headers;
    }

    protected function normalizeHeaders($headers)
    {
        $normalized = [];
        foreach ($headers as $name => $values) {
            $name = strtolower($name);
            $normalized[$name] = $values;
        }
        return $normalized;
    }

    protected function mapHeaderNames($headers)
    {
        $mapped = [];
        foreach (array_keys($headers) as $header) {
            $normalized = strtolower($header);
            $mapped[$normalized] = $header;
        }
        return $mapped;
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
        return array_filter($tree, function ($arg) use ($params) {
            if (!is_array($arg) || count($arg) < 2) {
                return false;
            }
            list($param) = $arg;
            return in_array($param, $params);
        });
    }

    public function __toString()
    {
        return $this->curl;
    }
}
