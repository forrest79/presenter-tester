<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Application;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\UI;
use Nette\DI;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Nette\Security\IIdentity;
use Nette\Security\User;

class PresenterTester
{
	private Session $session;

	private Router $router;

	private HttpRequestFactory $httpRequestFactory;

	private string $baseUrl;

	private User $user;

	private DI\Container $container;

	/** @var array<PresenterTesterListener> */
	private array $listeners;

	/** @var \Closure(TestPresenterRequest): IIdentity|null */
	private \Closure|null $identityFactory;

	/** @var list<TestPresenterResult> */
	private array $results = [];


	/**
	 * @param list<PresenterTesterListener> $listeners
	 * @param \Closure(TestPresenterRequest): IIdentity|null $identityFactory
	 */
	public function __construct(
		string $baseUrl,
		Session $session,
		Router $router,
		HttpRequestFactory $httpRequestFactory,
		User $user,
		DI\Container $container,
		array $listeners = [],
		\Closure|null $identityFactory = null,
	)
	{
		$this->baseUrl = $baseUrl;
		$this->session = $session;
		$this->router = $router;
		$this->httpRequestFactory = $httpRequestFactory;
		$this->user = $user;
		$this->container = $container;
		$this->listeners = $listeners;
		$this->identityFactory = $identityFactory;
	}


	public function execute(TestPresenterRequest $testRequest): TestPresenterResult
	{
		foreach ($this->listeners as $listener) {
			$testRequest = $listener->onRequest($testRequest);
		}

		$this->setupHttpRequest($testRequest);

		$this->loginUser($testRequest);

		$application = $this->container->getByType(Application\Application::class);

		$application->onPresenter[] = static function (Application\Application $application, IPresenter $presenter): void {
			if ($presenter instanceof UI\Presenter) {
				self::setupUIPresenter($presenter);
			}
		};

		$application->onResponse[] = static function (Application\Application $application, Application\Response $response): void {
			throw new Exceptions\ResponseDataException($application->getPresenter(), $response);
		};

		$applicationRequest = self::createApplicationRequest($testRequest);

		if ($applicationRequest->getMethod() === 'GET') {
			$params = $this->router->match($this->container->getByType(IRequest::class));
			PresenterAssert::assertRequestMatch($applicationRequest, $params);
		}

		$presenter = null;
		$response = null;
		$badRequestException = null;

		try {
			$application->processRequest($applicationRequest);
		} catch (Exceptions\ResponseDataException $e) {
			$presenter = $e->presenter;
			$response = $e->response;
		} catch (BadRequestException $e) {
			$badRequestException = $e;
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


	protected static function createApplicationRequest(TestPresenterRequest $testRequest): Application\Request
	{
		return new Application\Request(
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

		$this->user->logout(true);
		$identity = $request->getIdentity();
		if ($identity === null && $request->shouldHaveIdentity()) {
			if ($this->identityFactory === null) {
				throw new \LogicException('identityFactory is not set');
			}
			$identity = ($this->identityFactory)($request);
		}

		if ($identity !== null) {
			$this->user->login($identity);
		}
	}


	protected function setupHttpRequest(TestPresenterRequest $request): void
	{
		$appRequest = self::createApplicationRequest($request);
		$refUrl = new UrlScript($request->getBaseUrl() ?? $this->baseUrl, '/');

		$routerUrl = $this->router->constructUrl($appRequest->toArray(), $refUrl);
		assert(is_string($routerUrl));

		$headers = $request->getHeaders();
		if ($request->isAjax()) {
			$headers['x-requested-with'] = 'XMLHttpRequest';
		} else {
			unset($headers['x-requested-with']);
		}

		$this->container->removeService('http.request');
		$this->container->addService('http.request', $this->httpRequestFactory->create(
			$request->getPost() !== [] || $request->getRawBody() !== null ? 'POST' : 'GET',
			new UrlScript($routerUrl, '/'),
			$request->getPost(),
			$request->getCookies(),
			$headers,
			static fn (): string|null => $request->getRawBody(),
		));
	}


	protected static function setupUIPresenter(UI\Presenter $presenter): void
	{
		$presenter->autoCanonicalize = false;
		$presenter->invalidLinkMode = UI\Presenter::InvalidLinkException;
	}

}
