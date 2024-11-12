<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\Request;
use Nette\Application\Response;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\Responses\RedirectResponse;
use Nette\Application\Responses\TextResponse;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\Component;
use Nette\Forms\Control;
use Nette\Forms\Form;
use Nette\Http\Request as HttpRequest;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Tester\Assert;

class TestPresenterResult
{
	private Router $router;

	private IPresenter $presenter;

	private Request $request;

	private Response|NULL $response;

	private string|NULL $textResponseSource = NULL;

	private BadRequestException|NULL $badRequestException;

	private bool $responseInspected = FALSE;


	public function __construct(
		Router $router,
		Request $request,
		IPresenter $presenter,
		Response|NULL $response,
		BadRequestException|NULL $badRequestException,
	)
	{
		$this->presenter = $presenter;
		$this->response = $response;
		$this->router = $router;
		$this->badRequestException = $badRequestException;
		$this->request = $request;
	}


	public function getRequest(): Request
	{
		return $this->request;
	}


	public function getPresenter(): IPresenter
	{
		return $this->presenter;
	}


	public function getUIPresenter(): Presenter
	{
		Assert::type(Presenter::class, $this->presenter);
		assert($this->presenter instanceof Presenter);
		return $this->presenter;
	}


	public function getResponse(): Response
	{
		Assert::null($this->badRequestException);
		assert($this->response !== NULL);
		return $this->response;
	}


	public function getRedirectResponse(): RedirectResponse
	{
		$response = $this->getResponse();
		Assert::type(RedirectResponse::class, $response);
		assert($response instanceof RedirectResponse);
		return $response;
	}


	public function getTextResponse(): TextResponse
	{
		$response = $this->getResponse();
		Assert::type(TextResponse::class, $response);
		assert($response instanceof TextResponse);
		return $response;
	}


	public function getTextResponseSource(): string
	{
		if ($this->textResponseSource === NULL) {
			$source = $this->getTextResponse()->getSource();
			if (is_object($source)) {
				$this->textResponseSource = $source->__toString(TRUE);
			} else if (is_string($source)) {
				$this->textResponseSource = $source;
			} else {
				throw new \RuntimeException('No string type in text response.');
			}
			assert(is_string($this->textResponseSource));
		}

		return $this->textResponseSource;
	}


	public function getJsonResponse(): JsonResponse
	{
		$response = $this->getResponse();
		Assert::type(JsonResponse::class, $response);
		assert($response instanceof JsonResponse);
		return $response;
	}


	public function getBadRequestException(): BadRequestException
	{
		Assert::null($this->response);
		assert($this->badRequestException !== NULL);
		return $this->badRequestException;
	}


	public function assertHasResponse(string|NULL $type = NULL): static
	{
		$this->responseInspected = TRUE;
		Assert::type($type ?? Response::class, $this->response);

		return $this;
	}


	/**
	 * @param string|list<string>|NULL $match
	 */
	public function assertRenders(string|array|NULL $match = NULL): static
	{
		$this->responseInspected = TRUE;
		if (is_array($match)) {
			$match = '%A?%' . implode('%A?%', $match) . '%A?%';
		}

		$source = $this->getTextResponseSource();
		if ($match !== NULL) {
			Assert::match($match, $source);
		}

		return $this;
	}


	/**
	 * @param string|list<string> $matches
	 */
	public function assertNotRenders(string|array $matches): static
	{
		if (is_string($matches)) {
			$matches = [$matches];
		}
		$this->responseInspected = TRUE;
		$source = $this->getTextResponseSource();
		foreach ($matches as $match) {
			$match = '%A%' . $match . '%A%';
			if (Assert::isMatching($match, $source)) {
				[$pattern, $actual] = Assert::expandMatchingPatterns($match, $source);
				Assert::fail('%1 should NOT match %2', $actual, $pattern);
			}
		}
		return $this;
	}


	/**
	 * @param list<mixed>|object|NULL $expected
	 */
	public function assertJson(array|object|NULL $expected = NULL): static
	{
		$this->responseInspected = TRUE;
		$response = $this->getJsonResponse();
		if (func_num_args() !== 0) {
			Assert::equal($expected, $response->getPayload());
		}
		return $this;
	}


	/**
	 * @param array<string, mixed> $parameters optional parameters, extra parameters in a redirect request are ignored
	 */
	public function assertRedirects(string $presenterName, array $parameters = []): static
	{
		$this->responseInspected = TRUE;
		$response = $this->getRedirectResponse();
		$url = $response->getUrl();

		$httpRequest = new HttpRequest(new UrlScript($url, '/'));
		$result = $this->router->match($httpRequest);
		PresenterAssert::assertRequestMatch(new Request($presenterName, NULL, $parameters), $result);

		return $this;
	}


	/**
	 * @param array<string, mixed> $parameters optional parameters, extra parameters in a redirect request are ignored
	 */
	public function assertRedirectsAction(
		string $presenterName,
		string $action = 'default',
		array $parameters = [],
	): static
	{
		if (isset($parameters['action'])) {
			throw new \RuntimeException('In assertRedirectsAction() is not possible to set \'action\' in parameters.');
		}

		$parameters['action'] = $action;

		return $this->assertRedirects($presenterName, $parameters);
	}


	public function assertRedirectsUrl(string $url): static
	{
		$this->responseInspected = TRUE;
		$response = $this->getRedirectResponse();
		Assert::match($url, $response->getUrl());

		return $this;
	}


	public function assertFormValid(string $formName): static
	{
		$this->responseInspected = TRUE;
		$presenter = $this->getUIPresenter();
		$form = $presenter->getComponent($formName, FALSE);
		Assert::type(Form::class, $form);
		assert($form instanceof Form);

		if ($form->hasErrors()) {
			$controls = $form->getComponents(TRUE, Control::class);

			$errorsStr = [];
			foreach ($form->getOwnErrors() as $error) {
				assert(is_string($error));
				$errorsStr[] = "\town error: " . $error;
			}

			foreach ($controls as $control) {
				assert($control instanceof Component && $control instanceof Control);
				$errors = $control->getErrors();
				foreach ($errors as $error) {
					assert(is_string($error));
					$errorsStr[] = "\t" . $control->lookupPath(Form::class) . ': ' . $error;
				}
			}

			Assert::fail(
				'Form has errors: ' . PHP_EOL . implode(PHP_EOL, $errorsStr) . PHP_EOL,
				$form->getErrors(),
				[],
			);
		}
		return $this;
	}


	/**
	 * @param list<string> $formErrors
	 */
	public function assertFormHasErrors(string $formName, array|NULL $formErrors = NULL): static
	{
		$this->responseInspected = TRUE;
		$presenter = $this->getUIPresenter();
		$form = $presenter->getComponent($formName, FALSE);
		Assert::type(Form::class, $form);
		assert($form instanceof Form);
		Assert::true($form->hasErrors());

		if ($formErrors !== NULL) {
			Assert::same($formErrors, $form->getErrors());
		}

		return $this;
	}


	public function assertBadRequest(int|NULL $code = NULL, string|NULL $messagePattern = NULL): static
	{
		$this->responseInspected = TRUE;
		Assert::type(BadRequestException::class, $this->badRequestException);
		assert($this->badRequestException !== NULL);

		if ($code !== NULL) {
			Assert::same($code, $this->badRequestException->getHttpCode());
		}

		if ($messagePattern !== NULL) {
			Assert::match($messagePattern, $this->badRequestException->getMessage());
		}

		return $this;
	}


	/**
	 * @internal
	 */
	public function wasResponseInspected(): bool
	{
		return $this->responseInspected;
	}

}
