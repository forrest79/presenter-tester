<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as AppRequest;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\Request;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Nette\Utils\Arrays;

class PresenterTester
{
	private Application $application;

	private Session $session;

	private IPresenterFactory $presenterFactory;

	private Router $router;

	private string $baseUrl;

	private User $user;

	/** @var array<PresenterTesterListener> */
	private array $listeners;

	/** @var callable|NULL */
	private $identityFactory;

	/** @var list<TestPresenterResult> */
	private array $results = [];


	/**
	 * @param list<PresenterTesterListener> $listeners
	 */
	public function __construct(
		string $baseUrl,
		Application $application,
		Session $session,
		IPresenterFactory $presenterFactory,
		Router $router,
		User $user,
		array $listeners = [],
		callable|NULL $identityFactory = NULL,
	)
	{
		$this->baseUrl = $baseUrl;
		$this->application = $application;
		$this->session = $session;
		$this->presenterFactory = $presenterFactory;
		$this->router = $router;
		$this->user = $user;
		$this->listeners = $listeners;
		$this->identityFactory = $identityFactory;
	}


	public function execute(TestPresenterRequest $testRequest): TestPresenterResult
	{
		foreach ($this->listeners as $listener) {
			$testRequest = $listener->onRequest($testRequest);
		}
		$applicationRequest = self::createApplicationRequest($testRequest);

		// Inject application request into private Application::$requests
		if ($testRequest->getInjectedRequest()) {
			(function () use ($applicationRequest): void {
				$this->requests = [$applicationRequest];
			})->call($this->application);
		}

		$httpRequest = $this->createHttpRequest($testRequest);
		$presenter = $this->createPresenter($testRequest);
		if ($applicationRequest->getMethod() === 'GET') {
			$params = $this->router->match($httpRequest);
			PresenterAssert::assertRequestMatch($applicationRequest, $params);
		}

		Arrays::invoke($this->application->onRequest, $this->application, $applicationRequest);

		$badRequestException = NULL;

		try {
			$response = $presenter->run($applicationRequest);
		} catch (BadRequestException $e) {
			$badRequestException = $e;
			$response = NULL;
		}

		$result = new TestPresenterResult($this->router, $applicationRequest, $presenter, $response, $badRequestException);
		foreach ($this->listeners as $listener) {
			$listener->onResult($result);
		}
		$this->results[] = $result;

		return $result;
	}


	public function createRequest(string $presenterName): TestPresenterRequest
	{
		return new TestPresenterRequest($presenterName, $this->session, $this);
	}


	/**
	 * @return list<TestPresenterResult>
	 */
	public function getResults(): array
	{
		return $this->results;
	}


	protected function createHttpRequest(TestPresenterRequest $request): IRequest
	{
		$appRequest = self::createApplicationRequest($request);
		$refUrl = new UrlScript($this->baseUrl, '/');

		$routerUrl = $this->router->constructUrl($appRequest->toArray(), $refUrl);
		assert(is_string($routerUrl));

		$headers = $request->getHeaders();
		if ($request->isAjax()) {
			$headers['x-requested-with'] = 'XMLHttpRequest';
		} else {
			unset($headers['x-requested-with']);
		}

		return new Request(
			url: new UrlScript($routerUrl, '/'),
			post: $request->getPost(),
			cookies: $request->getCookies(),
			headers: $headers,
			method: ($request->getPost() !== [] || $request->getRawBody() !== NULL) ? 'POST' : 'GET',
			rawBodyCallback: static fn (): string|NULL => $request->getRawBody(),
		);
	}


	protected function createPresenter(TestPresenterRequest $request): IPresenter
	{
		$this->loginUser($request);
		$presenter = $this->presenterFactory->createPresenter($request->getPresenterName());
		if ($presenter instanceof Presenter) {
			$this->setupUIPresenter($presenter);
		}

		return $presenter;
	}


	protected static function createApplicationRequest(TestPresenterRequest $testRequest): AppRequest
	{
		return new AppRequest(
			$testRequest->getPresenterName(),
			$testRequest->getPost() !== [] ? 'POST' : $testRequest->getMethodName(),
			$testRequest->getParameters(),
			$testRequest->getPost(),
			$testRequest->getFiles(),
		);
	}


	protected function loginUser(TestPresenterRequest $request): void
	{
		if ($request->getKeepIdentity()) {
			return;
		}

		$this->user->logout(TRUE);
		$identity = $request->getIdentity();
		if ($identity === NULL && $request->shouldHaveIdentity()) {
			if ($this->identityFactory === NULL) {
				throw new \LogicException('identityFactory is not set');
			}
			$identity = ($this->identityFactory)($request);
			if (!$identity instanceof IIdentity) {
				throw new \LogicException('identityFactory is not returning IIdentity');
			}
		}

		if ($identity !== NULL) {
			$this->user->login($identity);
		}
	}


	protected function setupUIPresenter(Presenter $presenter): void
	{
		$presenter->autoCanonicalize = FALSE;
		$presenter->invalidLinkMode = Presenter::INVALID_LINK_EXCEPTION;
	}

}
