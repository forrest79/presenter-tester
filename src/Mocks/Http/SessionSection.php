<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Mocks\Http;

use Nette\Http;

class SessionSection extends Http\SessionSection
{
	/** @var array<string, mixed> */
	private array $data = [];


	public function __construct(Http\Session $session, string $name)
	{
		parent::__construct($session, $name);
	}


	/**
	 * @return \Iterator<string, mixed>
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->data);
	}


	/**
	 * @param mixed $value
	 */
	public function __set(string $name, $value): void
	{
		$this->data[$name] = $value;
	}


	/**
	 * @return mixed
	 */
	public function &__get(string $name)
	{
		if ($this->warnOnUndefined && !array_key_exists($name, $this->data)) {
			trigger_error(sprintf('The variable \'%s\' does not exist in session section', $name), E_USER_NOTICE);
		}

		return $this->data[$name];
	}


	public function __isset(string $name): bool
	{
		return isset($this->data[$name]);
	}


	public function __unset(string $name): void
	{
		unset($this->data[$name]);
	}


	/**
	 * @param string|NULL $time
	 * @param string|array<string> $variables
	 * @return static
	 */
	public function setExpiration($time, $variables = NULL): self
	{
		return $this;
	}


	/**
	 * @param string|array<string> $variables
	 */
	public function removeExpiration($variables = NULL): void
	{
	}


	public function remove(): void
	{
		$this->data = [];
	}

}
