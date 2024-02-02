<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Mocks\Http;

use Nette\Http;

class Session extends Http\Session
{
	/** @var array<string, SessionSection> */
	private array $sections = [];

	private bool $started = FALSE;

	private bool $exists = FALSE;

	private string $id;


	public function __construct()
	{
		$this->id = md5((string) rand(0, 10000));
	}


	public function start(): void
	{
		$this->started = TRUE;
	}


	public function autoStart(bool $forWrite): void
	{
		$this->start();
	}


	public function isStarted(): bool
	{
		return $this->started;
	}


	public function close(): void
	{
		$this->started = FALSE;
	}


	public function destroy(): void
	{
		$this->started = FALSE;
	}


	public function exists(): bool
	{
		return $this->exists;
	}


	public function setFakeExists(bool $exists): void
	{
		$this->exists = $exists;
	}


	public function regenerateId(): void
	{
	}


	public function getId(): string
	{
		return $this->id;
	}


	public function setFakeId(string $id): void
	{
		$this->id = $id;
	}


	/**
	 * @return Http\SessionSection<string, mixed>
	 */
	public function getSection(string $section, string $class = SessionSection::class): Http\SessionSection
	{
		if (!isset($this->sections[$section])) {
			$sessionSection = parent::getSection($section, $class);
			assert($sessionSection instanceof SessionSection);
			$this->sections[$section] = $sessionSection;
		}

		return $this->sections[$section];
	}


	public function hasSection(string $section): bool
	{
		return isset($this->sections[$section]);
	}


	/**
	 * @return \ArrayIterator<int, string>
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator(array_keys($this->sections));
	}


	public function clean(): void
	{
	}


	public function setName(string $name): static
	{
		return $this;
	}


	public function getName(): string
	{
		return '';
	}


	/**
	 * @param array<mixed> $options
	 */
	public function setOptions(array $options): static
	{
		return $this;
	}


	/**
	 * @return array<string, mixed>
	 */
	public function getOptions(): array
	{
		return [];
	}


	public function setExpiration(string|NULL $time): static
	{
		return $this;
	}


	public function setCookieParameters(
		string $path,
		string|NULL $domain = NULL,
		bool|NULL $secure = NULL,
		string|NULL $sameSite = NULL,
	): static
	{
		return $this;
	}


	/**
	 * @return array<string, mixed>
	 */
	public function getCookieParameters(): array
	{
		return [];
	}


	public function setSavePath(string $path): static
	{
		return $this;
	}


	public function setHandler(\SessionHandlerInterface $handler): static
	{
		return $this;
	}

}
