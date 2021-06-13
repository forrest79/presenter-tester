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


	/**
	 * @return static
	 */
	public function setCode(int $code, ?string $reason = NULL): self
	{
		return $this;
	}


	public function getCode(): int
	{
		return 0;
	}


	/**
	 * @return static
	 */
	public function setHeader(string $name, string $value): self
	{
		return $this;
	}


	/**
	 * @return static
	 */
	public function addHeader(string $name, string $value): self
	{
		return $this;
	}


	/**
	 * @return static
	 */
	public function setContentType(string $type, ?string $charset = NULL): self
	{
		return $this;
	}


	public function redirect(string $url, int $code = self::S302_FOUND): void
	{
	}


	/**
	 * @return static
	 */
	public function setExpiration(?string $expire): self
	{
		return $this;
	}


	public function isSent(): bool
	{
		return FALSE;
	}


	public function getHeader(string $header): ?string
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
	 * @return static
	 */
	public function setCookie(
		string $name,
		string $value,
		$expire,
		?string $path = NULL,
		?string $domain = NULL,
		?bool $secure = NULL,
		?bool $httpOnly = NULL,
	): self
	{
		$this->cookies[$name] = $value;
		return $this;
	}


	public function deleteCookie(string $name, ?string $path = NULL, ?string $domain = NULL, ?bool $secure = NULL): void
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
