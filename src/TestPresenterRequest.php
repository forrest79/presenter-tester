<?php declare(strict_types = 1);

namespace Forrest79\Tester\PresenterTester;

use Nette\Forms\Controls\CsrfProtection;
use Nette\Http\Session;
use Nette\Security\IIdentity;
use Nette\SmartObject;

/**
 * Immutable object
 */
class TestPresenterRequest
{
	use SmartObject;

	private Session $session;

	private string $presenterName;

	private ?PresenterTester $presenterTester;

	private string $methodName = 'GET';

	private array $headers = [];

	private array $parameters = [];

	private array $post = [];

	private ?string $rawBody = NULL;

	private array $cookies = [];

	private array $files = [];

	private bool $ajax = FALSE;

	private bool $shouldHaveIdentity = FALSE;

	private ?IIdentity $identity = NULL;

	private bool $keepIdentity = FALSE;


	public function __construct(string $presenterName, Session $session, ?PresenterTester $presenterTester = NULL)
	{
		$this->presenterName = $presenterName;
		$this->session = $session;
		$this->presenterTester = $presenterTester;

		$session->getSection(CsrfProtection::class)->token = 'mango.token';
	}


	public function getMethodName(): string
	{
		return $this->methodName;
	}


	public function getHeaders(): array
	{
		return $this->headers;
	}


	public function getPresenterName(): string
	{
		return $this->presenterName;
	}


	public function getParameters(): array
	{
		return $this->parameters + ['action' => 'default'];
	}


	public function getPost(): array
	{
		return $this->post;
	}


	public function getRawBody(): ?string
	{
		return $this->rawBody;
	}


	public function getCookies(): array
	{
		return $this->cookies;
	}


	public function getFiles(): array
	{
		return $this->files;
	}


	public function isAjax(): bool
	{
		return $this->ajax;
	}


	public function shouldHaveIdentity(): bool
	{
		return $this->shouldHaveIdentity;
	}


	public function getIdentity(): ?IIdentity
	{
		return $this->identity;
	}


	public function getKeepIdentity(): bool
	{
		return $this->keepIdentity;
	}


	public function withSignal(string $signal, array $componentParameters = []): self
	{
		assert(!isset($this->parameters['do']));
		$request = clone $this;
		$request->parameters['do'] = $signal;
		$lastDashPosition = strrpos($signal, '-');
		$componentName = $lastDashPosition !== false ? substr($signal, 0, $lastDashPosition) : '';

		if ($componentName !== '') {
			$newParameters = [];
			foreach ($componentParameters as $key => $value) {
				$newParameters["$componentName-$key"] = $value;
			}
			$componentParameters = $newParameters;
		}

		$request->parameters = $componentParameters + $request->parameters;

		return $request;
	}


	public function withMethod(string $methodName): self
	{
		$request = clone $this;
		$request->methodName = $methodName;

		return $request;
	}


	public function withForm(string $formName, array $post, array $files = [], bool $withProtection = true): self
	{
		$request = $this->withSignal("$formName-submit");
		if ($withProtection) {
			$token = 'abcdefghij' . base64_encode(sha1(('mango.token' ^ $this->session->getId()) . 'abcdefghij', true));
			$post = $post + ['_token_' => $token];
		}
		$request->post = $post;
		$request->files = $files;

		return $request;
	}


	public function withRawBody(string $rawBody): self
	{
		$request = clone $this;
		$request->rawBody = $rawBody;

		return $request;
	}


	public function withHeaders(array $headers): self
	{
		$request = clone $this;
		$request->headers = array_change_key_case($headers, CASE_LOWER) + $request->headers;

		return $request;
	}


	public function withAjax(bool $enable = true): self
	{
		$request = clone $this;
		$request->ajax = $enable;

		return $request;
	}


	public function withParameters(array $parameters): self
	{
		$request = clone $this;
		$request->parameters = $parameters + $this->parameters;

		return $request;
	}


	public function withPost(array $post): self
	{
		$request = clone $this;
		$request->post = $post + $this->post;

		return $request;
	}


	public function withCookies(array $cookies): self
	{
		$request = clone $this;
		$request->cookies = $cookies + $this->cookies;

		return $request;
	}


	public function withFiles(array $files): self
	{
		$request = clone $this;
		$request->files = $files + $this->files;

		return $request;
	}


	public function withIdentity(IIdentity $identity = null): self
	{
		$request = clone $this;
		$request->shouldHaveIdentity = true;
		$request->identity = $identity;

		return $request;
	}


	public function withKeepIdentity(bool $enable = true): self
	{
		$request = clone $this;
		$request->keepIdentity = $enable;

		return $request;
	}


	public function execute(): TestPresenterResult
	{
		if ($this->presenterTester === NULL) {
			throw new \RuntimeException('Presenter tester is not set.');
		}
		return $this->presenterTester->execute($this);
	}

}
