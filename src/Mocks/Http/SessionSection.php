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
		$this->remove($name);
	}


	/**
	 * @param string|NULL $time
	 * @param string|array<string>|NULL $variables
	 */
	public function setExpiration($time, $variables = NULL): static
	{
		return $this;
	}


	/**
	 * @param string|array<string>|NULL $variables
	 */
	public function removeExpiration($variables = NULL): void
	{
	}


	/**
	 * @param string|array|NULL $name
	 */
	public function remove($name = NULL): void
	{
		if ($name === NULL) {
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
