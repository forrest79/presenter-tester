<?php declare(strict_types=1);

namespace Forrest79\PresenterTester\Exceptions;

use Nette\Application\IPresenter;
use Nette\Application\Response;

class ResponseDataException extends \LogicException
{
	public readonly IPresenter|null $presenter;

	public readonly Response $response;


	public function __construct(IPresenter|null $presenter, Response $response)
	{
		parent::__construct();
		$this->presenter = $presenter;
		$this->response = $response;
	}

}
