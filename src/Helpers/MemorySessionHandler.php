<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Helpers;

class MemorySessionHandler implements \SessionHandlerInterface
{
	private static bool $installed = FALSE;

	/** @var array<string, string> */
	private array $sessionData = [];


	public function open(string $path, string $name): bool
	{
		return TRUE;
	}


	public function read(string $id): string
	{
		if (!isset($this->sessionData[$id])) {
			$this->sessionData[$id] = '';
		}

		return $this->sessionData[$id];
	}


	public function write(string $id, string $data): bool
	{
		if (!isset($this->sessionData[$id])) {
			return FALSE;
		}

		$this->sessionData[$id] = $data;

		return TRUE;
	}


	public function destroy(string $id): bool
	{
		if (!isset($this->sessionData[$id])) {
			return FALSE;
		}

		unset($this->sessionData[$id]);

		return TRUE;
	}


	public function close(): bool
	{
		foreach (array_keys($this->sessionData) as $id) {
			unset($this->sessionData[$id]);
		}

		return TRUE;
	}


	public function gc(int $maxLifeTime): int|FALSE
	{
		return 300;
	}


	public static function install(): void
	{
		if (!self::$installed) {
			session_set_save_handler(new self(), TRUE);
			self::$installed = TRUE;
		}
	}

}
