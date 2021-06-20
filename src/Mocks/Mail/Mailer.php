<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Mocks\Mail;

use Nette\Mail;

final class Mailer implements Mail\Mailer, \Countable
{
	/** @var array<Mail\Message> */
	private array $messages = [];


	public function send(Mail\Message $mail): void
	{
		$this->messages[] = $mail;
	}


	public function count(): int
	{
		return count($this->messages);
	}


	/**
	 * @return array<Mail\Message>
	 */
	public function getMessages(): array
	{
		return $this->messages;
	}


	public function getLastMessage(): ?Mail\Message
	{
		$key = array_key_last($this->messages);

		if ($key === NULL) {
			return NULL;
		}

		return $this->messages[$key];
	}

}
