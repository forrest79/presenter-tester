<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Http;

class HttpRequestFactory
{
	/** @var array<string, mixed> */
	protected readonly array $cookies;

	/** @var array<string, string> */
	protected readonly array $headers;

	protected readonly string|NULL $remoteAddress;

	protected readonly string|NULL $remoteHost;


	/**
	 * @param array<string, mixed> $cookies
	 * @param array<string, string> $headers
	 */
	public function __construct(
		array $cookies = [],
		array $headers = [],
		string|NULL $remoteAddress = NULL,
		string|NULL $remoteHost = NULL,
	)
	{
		$this->cookies = array_merge($cookies, [Http\Helpers::StrictCookieName => TRUE]);
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
	): Http\Request
	{
		return new Http\Request(
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
