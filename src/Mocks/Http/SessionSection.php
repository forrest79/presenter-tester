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


	public function &__get(string $name): mixed
	{
		if ($this->warnOnUndefined && !array_key_exists($name, $this->data)) {
			trigger_error(sprintf('The variable \'%s\' does not exist in session section', $name));
		}

		return $this->data[$name];
	}


	public function __isset(string $name): bool
	{
		return isset($this->data[$name]);
	}


	public function __unset(string $name): void
	{
		$this->remove($name);
	}


	/**
	 * @param string|array<string>|null $variables
	 */
	public function setExpiration(string|null $expire, string|array|null $variables = null): static
	{
		return $this;
	}


	/**
	 * @param string|array<string>|null $variables
	 */
	public function removeExpiration(string|array|null $variables = null): void
	{
	}


	/**
	 * @param string|array<string>|null $name
	 */
	public function remove(string|array|null $name = null): void
	{
		if ($name === null) {
			$this->data = [];
		} else {
			if (is_array($name)) {
				foreach ($name as $item) {
					$this->remove($item);
				}
			} else {
				unset($this->data[$name]);
			}
		}
	}

}
