<?php

declare(strict_types=1);

namespace MailchimpMarketing\Api;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Query;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\RequestOptions;
use MailchimpMarketing\ApiException;
use MailchimpMarketing\Configuration;
use MailchimpMarketing\HeaderSelector;
use MailchimpMarketing\ObjectSerializer;

abstract class AbstractApi
{
    public Configuration $config {
        get => $this->config;
    }
    protected Client $client;
    protected HeaderSelector $headerSelector;

    public function __construct(?Configuration $config = null)
    {
        $this->client = new Client([
            'defaults' => [
                'timeout' => 120.0,
            ],
        ]);
        $this->headerSelector = new HeaderSelector();
        $this->config = $config ?: new Configuration();
    }

    protected function sendRequest(Request $request): mixed
    {
        try {
            $options = $this->createHttpClientOption();
            $response = $this->client->send($request, $options);
            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode > 299) {
                throw new ApiException(
                    \sprintf('[%d] Error connecting to the API (%s)', $statusCode, $request->getUri()),
                    $statusCode,
                    $response->getHeaders(),
                    $response->getBody()
                );
            }

            return json_decode($response->getBody()->getContents());
        } catch (ApiException $e) {
            throw $e->responseBody;
        }
    }

    protected function requireParam(mixed $value, string $name): void
    {
        if (null === $value || (\is_array($value) && 0 === \count($value))) {
            throw new \InvalidArgumentException("Missing the required parameter \${$name} when calling ");
        }
    }

    protected function buildQueryParams(mixed $fields = null, mixed $exclude_fields = null, mixed $count = null, mixed $offset = null): array
    {
        $queryParams = [];
        $this->addQueryParam($queryParams, 'fields', $fields);
        $this->addQueryParam($queryParams, 'exclude_fields', $exclude_fields);
        $this->addQueryParam($queryParams, 'count', $count);
        $this->addQueryParam($queryParams, 'offset', $offset);

        return $queryParams;
    }

    protected function addQueryParam(array &$queryParams, string $key, mixed $value, string $format = 'csv'): void
    {
        if (\is_array($value)) {
            $queryParams[$key] = ObjectSerializer::serializeCollection($value, $format);
        } elseif (null !== $value) {
            $queryParams[$key] = ObjectSerializer::toQueryValue($value);
        }
    }

    protected function replacePathParam(string &$resourcePath, string $key, mixed $value): void
    {
        if (null !== $value) {
            $resourcePath = str_replace('{'.$key.'}', ObjectSerializer::toPathValue($value), $resourcePath);
        }
    }

    protected function buildRequest(
        string $method,
        string $resourcePath,
        array $queryParams,
        mixed $body = null
    ): Request {
        $headers = $this->headerSelector->selectHeaders(
            ['application/json', 'application/problem+json'],
            ['application/json']
        );

        if (null === $body) {
            $httpBody = '';
        } elseif ($body instanceof \stdClass || \is_array($body)) {
            $httpBody = json_encode($body instanceof \stdClass ? $body : ObjectSerializer::sanitizeForSerialization($body));
        } else {
            $httpBody = $body;
        }

        if (!empty($this->config->getUsername()) && !empty($this->config->getPassword())) {
            $headers['Authorization'] = 'Basic '.base64_encode($this->config->getUsername().':'.$this->config->getPassword());
        }

        if (!empty($this->config->getAccessToken())) {
            $headers['Authorization'] = 'Bearer '.$this->config->getAccessToken();
        }

        if ($this->config->getUserAgent()) {
            $headers = array_merge(['User-Agent' => $this->config->getUserAgent()], $headers);
        }

        $query = Query::build($queryParams);

        return new Request(
            $method,
            $this->config->getHost().$resourcePath.($query ? "?{$query}" : ''),
            $headers,
            $httpBody
        );
    }

    protected function createHttpClientOption(): array
    {
        $options = [];
        if ($this->config->getDebug()) {
            $options[RequestOptions::DEBUG] = fopen($this->config->getDebugFile(), 'a');
            if (!$options[RequestOptions::DEBUG]) {
                throw new \RuntimeException('Failed to open the debug file: '.$this->config->getDebugFile());
            }
        }

        if ($this->config->getTimeout()) {
            $options[RequestOptions::TIMEOUT] = $this->config->getTimeout();
        }

        return $options;
    }
}
