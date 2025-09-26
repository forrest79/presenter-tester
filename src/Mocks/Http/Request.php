<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Mocks\Http;

use Nette\Http;

class Request extends Http\Request
{
	/** @var array<string, string> */
	private array $headers = [];

	private string|null $body = null;


	public function setRawBody(string|null $body): void
	{
		$this->body = $body;
	}


	public function getRawBody(): string|null
	{
		return $this->body ?? parent::getRawBody();
	}


	public function setHeader(string $name, string $value): void
	{
		$this->headers[$name] = $value;
	}


	public function getHeader(string $header): string|null
	{
		if (isset($this->headers[$header])) {
			return $this->headers[$header];
		}

		return parent::getHeader($header);
	}


	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		/** @phpstan-var array<string, string> */
		return array_merge(parent::getHeaders(), $this->headers);
	}


	public function isSameSite(): bool
	{
		return true;
	}

}
