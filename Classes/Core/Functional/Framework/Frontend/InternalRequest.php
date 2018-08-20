<?php
namespace TYPO3\TestingFramework\Core\Functional\Framework\Frontend;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Http\Request;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Functional\Framework\AssignablePropertyTrait;

/**
 * Model of internal frontend request context
 */
class InternalRequest extends Request implements \JsonSerializable
{
    use AssignablePropertyTrait;

    /**
     * @param array $data
     * @return InternalRequest
     */
    public static function fromArray(array $data): InternalRequest
    {
        $target = (new static($data['uri'] ?? ''));
        $target->getBody()->write($data['body'] ?? '');
        unset($data['uri'], $data['body']);
        return $target->with($data);
    }

    /**
     * @param string|null $uri URI for the request, if any.
     */
    public function __construct($uri = null) {
        if ($uri === null) {
            $uri = 'http://localhost/';
        }
        $body = new Stream('php://temp', 'rw');
        parent::__construct($uri, 'GET', $body);
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'method' => $this->method,
            'headers' => $this->headers,
            'uri' => (string)$this->uri,
            'body' => (string)$this->body,
        ];
    }

    /**
     * @param int $pageId
     * @return InternalRequest
     */
    public function withPageId(int $pageId): InternalRequest
    {
        return $this->withQueryParameter('id', $pageId);
    }

    /**
     * @param int $languageId
     * @return InternalRequest
     */
    public function withLanguageId(int $languageId): InternalRequest
    {
        return $this->withQueryParameter('L', $languageId);
    }

    /**
     * @param string $parameterName
     * @param null|int|float|string $value
     * @return InternalRequest
     */
    public function withQueryParameter(string $parameterName, $value): InternalRequest
    {
        $query = $this->modifyQueryParameter(
            $this->uri->getQuery() ?? '',
            $parameterName,
            $value
        );

        $target = clone $this;
        $target->uri = $target->uri->withQuery($query);
        return $target;
    }

    /**
     * @param array $parameters
     * @return InternalRequest
     */
    public function withQueryParameters(array $parameters): InternalRequest
    {
        if (empty($parameters)) {
            return $this;
        }

        $query = $this->uri->getQuery() ?? '';

        foreach ($parameters as $parameterName => $value) {
            $query = $this->modifyQueryParameter(
                $query,
                $parameterName,
                $value
            );
        }

        $target = clone $this;
        $target->uri = $target->uri->withQuery($query);
        return $target;
    }

    /**
     * @param string $query
     * @param string $parameterName
     * @param null|int|float|string $value
     * @return string
     */
    private function modifyQueryParameter(string $query, string $parameterName, $value): string
    {
        if (!is_float($value) && !is_int($value) && !is_string($value) && $value !== null) {
            throw new \RuntimeException(
                sprintf('Invalid type "%s"', gettype($value)),
                1533639711
            );
        }

        $parameters = \GuzzleHttp\Psr7\parse_query($query);
        $parameters[$parameterName] = $value;
        return \GuzzleHttp\Psr7\build_query($parameters);
    }
}