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
	public bool $cookieSecure = FALSE;


	public function setCode(int $code, string|NULL $reason = NULL): static
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


	public function setContentType(string $type, string|NULL $charset = NULL): static
	{
		return $this;
	}


	public function redirect(string $url, int $code = self::S302_Found): void
	{
	}


	public function setExpiration(string|NULL $expire): static
	{
		return $this;
	}


	public function isSent(): bool
	{
		return FALSE;
	}


	public function getHeader(string $header): string|NULL
	{
		return NULL;
	}


	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return [];
	}


	/**
	 * @param \DateTimeInterface|int|string $expire
	 */
	public function setCookie(
		string $name,
		string $value,
		$expire,
		string|NULL $path = NULL,
		string|NULL $domain = NULL,
		bool|NULL $secure = NULL,
		bool|NULL $httpOnly = NULL,
	): static
	{
		$this->cookies[$name] = $value;
		return $this;
	}


	public function deleteCookie(
		string $name,
		string|NULL $path = NULL,
		string|NULL $domain = NULL,
		bool|NULL $secure = NULL,
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
