<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Forms\Controls\CsrfProtection;
use Nette\Http\Session;
use Nette\Security\IIdentity;

/**
 * Immutable object
 */
class TestPresenterRequest
{
	private Session $session;

	private string $presenterName;

	private PresenterTester|NULL $presenterTester;

	private string $methodName = 'GET';

	/** @var array<string, string> */
	private array $headers = [];

	/** @var array<string, mixed> */
	private array $parameters = [];

	/** @var array<string, mixed> */
	private array $post = [];

	private string|NULL $rawBody = NULL;

	/** @var array<string, string> */
	private array $cookies = [];

	/** @var array<string, mixed> */
	private array $files = [];

	private bool $ajax = FALSE;

	private bool $shouldHaveIdentity = FALSE;

	private IIdentity|NULL $identity = NULL;

	private bool $keepIdentity = FALSE;


	public function __construct(string $presenterName, Session $session, PresenterTester|NULL $presenterTester = NULL)
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


	/**
	 * @return array<string, string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}


	public function getPresenterName(): string
	{
		return $this->presenterName;
	}


	/**
	 * @return array<string, mixed>
	 */
	public function getParameters(): array
	{
		return $this->parameters + ['action' => 'default'];
	}


	/**
	 * @return array<string, mixed>
	 */
	public function getPost(): array
	{
		return $this->post;
	}


	public function getRawBody(): string|NULL
	{
		return $this->rawBody;
	}


	/**
	 * @return array<string, string>
	 */
	public function getCookies(): array
	{
		return $this->cookies;
	}


	/**
	 * @return array<string, mixed>
	 */
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


	public function getIdentity(): IIdentity|NULL
	{
		return $this->identity;
	}


	public function getKeepIdentity(): bool
	{
		return $this->keepIdentity;
	}


	/**
	 * @param array<string, mixed> $componentParameters
	 */
	public function withSignal(string $signal, array $componentParameters = []): self
	{
		assert(!isset($this->parameters['do']));
		$request = clone $this;
		$request->parameters['do'] = $signal;
		$lastDashPosition = strrpos($signal, '-');
		$componentName = $lastDashPosition !== FALSE ? substr($signal, 0, $lastDashPosition) : '';

		if ($componentName !== '') {
			$newParameters = [];
			foreach ($componentParameters as $key => $value) {
				$newParameters[$componentName . '-' . $key] = $value;
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


	/**
	 * @param array<string, mixed> $post
	 * @param array<string, mixed> $files
	 */
	public function withForm(string $formName, array $post, array $files = [], bool $withProtection = TRUE): self
	{
		$request = $this->withSignal($formName . '-submit');
		if ($withProtection) {
			$this->session->regenerateId(); // @hack to prevent regenerate session ID during two requests

			$random = 'abcdefghij';
			// The same logic as vendor/nette/forms/src/Forms/Controls/CsrfProtection.php::generateToken(...)
			$token = $random . base64_encode(sha1(('mango.token' ^ $this->session->getId()) . $random, TRUE));
			$post += ['_token_' => $token];
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


	/**
	 * @param array<string, string> $headers
	 */
	public function withHeaders(array $headers): self
	{
		$request = clone $this;
		$request->headers = array_change_key_case($headers, CASE_LOWER) + $request->headers;

		return $request;
	}


	public function withAjax(bool $enable = TRUE): self
	{
		$request = clone $this;
		$request->ajax = $enable;

		return $request;
	}


	/**
	 * @param array<string, mixed> $parameters
	 */
	public function withParameters(array $parameters): self
	{
		$request = clone $this;
		$request->parameters = $parameters + $this->parameters;

		return $request;
	}


	/**
	 * @param array<string, string> $post
	 */
	public function withPost(array $post): self
	{
		$request = clone $this;
		$request->post = $post + $this->post;

		return $request;
	}


	/**
	 * @param array<string, string> $cookies
	 */
	public function withCookies(array $cookies): self
	{
		$request = clone $this;
		$request->cookies = $cookies + $this->cookies;

		return $request;
	}


	/**
	 * @param array<string, mixed> $files
	 */
	public function withFiles(array $files): self
	{
		$request = clone $this;
		$request->files = $files + $this->files;

		return $request;
	}


	public function withIdentity(IIdentity|NULL $identity = NULL): self
	{
		$request = clone $this;
		$request->shouldHaveIdentity = TRUE;
		$request->identity = $identity;

		return $request;
	}


	public function withKeepIdentity(bool $enable = TRUE): self
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
