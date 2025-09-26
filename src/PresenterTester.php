<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Application\Application;
use Nette\Application\BadRequestException;
use Nette\Application\IPresenter;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request as AppRequest;
use Nette\Application\UI\Presenter;
use Nette\DI;
use Nette\Http\IRequest;
use Nette\Http\Session;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use Nette\Security\IIdentity;
use Nette\Security\User;
use Nette\Utils\Arrays;

class PresenterTester
{
	private Session $session;

	private IPresenterFactory $presenterFactory;

	private Router $router;

	private HttpRequestFactory $httpRequestFactory;

	private string $baseUrl;

	private User $user;

	private DI\Container $container;

	/** @var array<PresenterTesterListener> */
	private array $listeners;

	/** @var callable|null */
	private $identityFactory;

	/** @var list<TestPresenterResult> */
	private array $results = [];


	/**
	 * @param list<PresenterTesterListener> $listeners
	 */
	public function __construct(
		string $baseUrl,
		Session $session,
		IPresenterFactory $presenterFactory,
		Router $router,
		HttpRequestFactory $httpRequestFactory,
		User $user,
		DI\Container $container,
		array $listeners = [],
		callable|null $identityFactory = null,
	)
	{
		$this->baseUrl = $baseUrl;
		$this->session = $session;
		$this->presenterFactory = $presenterFactory;
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

		$presenter = $this->createPresenter($testRequest);

		$application = $this->container->getByType(Application::class);
		$applicationRequest = self::createApplicationRequest($testRequest);

		// Inject application request into private Application::$requests
		if ($testRequest->getInjectedRequest()) {
			(function () use ($applicationRequest): void {
				$this->requests = [$applicationRequest];
			})->call($application);
		}

		if ($applicationRequest->getMethod() === 'GET') {
			$params = $this->router->match($this->container->getByType(IRequest::class));
			PresenterAssert::assertRequestMatch($applicationRequest, $params);
		}

		Arrays::invoke($application->onRequest, $application, $applicationRequest);

		$badRequestException = null;

		try {
			$response = $presenter->run($applicationRequest);
		} catch (BadRequestException $e) {
			$badRequestException = $e;
			$response = null;
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


	protected function createPresenter(TestPresenterRequest $request): IPresenter
	{
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

		$this->user->logout(true);
		$identity = $request->getIdentity();
		if ($identity === null && $request->shouldHaveIdentity()) {
			if ($this->identityFactory === null) {
				throw new \LogicException('identityFactory is not set');
			}
			$identity = ($this->identityFactory)($request);
			if (!$identity instanceof IIdentity) {
				throw new \LogicException('identityFactory is not returning IIdentity');
			}
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


	protected function setupUIPresenter(Presenter $presenter): void
	{
		$presenter->autoCanonicalize = false;
		$presenter->invalidLinkMode = Presenter::INVALID_LINK_EXCEPTION;
	}

}
