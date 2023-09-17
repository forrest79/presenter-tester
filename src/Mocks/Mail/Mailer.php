<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Mocks\Mail;

use Nette\Mail;

final class Mailer implements Mail\Mailer, \Countable
{
	/** @var list<Mail\Message> */
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
	 * @return list<Mail\Message>
	 */
	public function getMessages(): array
	{
		return $this->messages;
	}


	public function getLastMessage(): Mail\Message|NULL
	{
		$key = array_key_last($this->messages);

		if ($key === NULL) {
			return NULL;
		}

		return $this->messages[$key];
	}

}
