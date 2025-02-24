<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Http;

class HttpRequestFactory
{
	protected readonly Http\IRequest $httpRequest;

	/** @var array<string, mixed> */
	protected readonly array $cookies;

	/** @var array<string, string> */
	protected readonly array $headers;

	protected readonly string|null $remoteAddress;

	protected readonly string|null $remoteHost;


	/**
	 * @param array<string, mixed> $cookies
	 * @param array<string, string> $headers
	 */
	public function __construct(
		Http\IRequest $httpRequest,
		array $cookies = [],
		array $headers = [],
		string|null $remoteAddress = null,
		string|null $remoteHost = null,
	)
	{
		$this->httpRequest = $httpRequest;
		$this->cookies = array_merge($cookies, [Http\Helpers::StrictCookieName => true]);
		$this->headers = $headers;
		$this->remoteAddress = $remoteAddress;
		$this->remoteHost = $remoteHost;
	}


	/**
	 * @param array<string, mixed> $post
	 * @param array<string, mixed> $cookies
	 * @param array<string, string> $headers
	 */
	public function create(
		string $method,
		Http\UrlScript $url,
		array $post,
		array $cookies,
		array $headers,
		callable $rawBodyCallback,
	): Http\IRequest
	{
		$httpRequestClassName = $this->httpRequest::class;
		return new $httpRequestClassName(
			$url,
			$post,
			[],
			$cookies + $this->cookies,
			$headers + $this->headers,
			$method,
			$this->remoteAddress,
			$this->remoteHost,
			$rawBodyCallback,
		);
	}

}
