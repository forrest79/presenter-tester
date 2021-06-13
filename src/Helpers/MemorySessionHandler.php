<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Helpers;

class MemorySessionHandler implements \SessionHandlerInterface
{
	private static bool $installed = FALSE;

	/** @var array<string, string> */
	private array $sessionData = [];


	/**
	 * @param string $savePath
	 * @param string $sessionName
	 */
	public function open($savePath, $sessionName): bool
	{
		return TRUE;
	}


	/**
	 * @param string $id
	 */
	public function read($id): string
	{
		if (!isset($this->sessionData[$id])) {
			$this->sessionData[$id] = '';
		}

		return $this->sessionData[$id];
	}


	/**
	 * @param string $id
	 * @param string $data
	 */
	public function write($id, $data): bool
	{
		if (!isset($this->sessionData[$id])) {
			return FALSE;
		}

		$this->sessionData[$id] = $data;

		return TRUE;
	}


	/**
	 * @param string $id
	 */
	public function destroy($id): bool
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


	/**
	 * @param int $maxLifeTime
	 */
	public function gc($maxLifeTime): bool
	{
		return TRUE;
	}


	public static function install(): void
	{
		if (!self::$installed) {
			session_set_save_handler(new self(), TRUE);
			self::$installed = TRUE;
		}
	}

}
