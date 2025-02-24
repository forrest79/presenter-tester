<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Mocks\Http;

use Nette\Http;

/**
 * @method self deleteHeader(string $name)
 */
class Response implements Http\IResponse
{
	/** @var array<string, string> */
	private array $cookies = [];

	/** This is used by Nette DI itself */
	public string $cookieDomain = '';

	/** This is used by Nette DI itself */
	public string $cookiePath = '/';

	/** This is used by Nette DI itself */
	public bool $cookieSecure = false;


	public function setCode(int $code, string|null $reason = null): static
	{
		return $this;
	}


	public function getCode(): int
	{
		return 0;
	}


	public function setHeader(string $name, string $value): static
	{
		return $this;
	}


	public function addHeader(string $name, string $value): static
	{
		return $this;
	}


	public function setContentType(string $type, string|null $charset = null): static
	{
		return $this;
	}


	public function redirect(string $url, int $code = self::S302_Found): void
	{
	}


	public function setExpiration(string|null $expire): static
	{
		return $this;
	}


	public function isSent(): bool
	{
		return false;
	}


	public function getHeader(string $header): string|null
	{
		return null;
	}


	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return [];
	}


	public function setCookie(
		string $name,
		string $value,
		string|int|\DateTimeInterface|null $expire,
		string|null $path = null,
		string|null $domain = null,
		bool|null $secure = null,
		bool|null $httpOnly = null,
		string|null $sameSite = null,
	): static
	{
		$this->cookies[$name] = $value;
		return $this;
	}


	public function deleteCookie(
		string $name,
		string|null $path = null,
		string|null $domain = null,
		bool|null $secure = null,
	): void
	{
		unset($this->cookies[$name]);
	}


	/**
	 * @return array<string, string>
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}

}
