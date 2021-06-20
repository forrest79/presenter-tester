# Forrest79/PresenterTester

[![Latest Stable Version](https://poser.pugx.org/forrest79/presenter-tester/v)](//packagist.org/packages/forrest79/presenter-tester)
[![Monthly Downloads](https://poser.pugx.org/forrest79/presenter-tester/d/monthly)](//packagist.org/packages/forrest79/presenter-tester)
[![License](https://poser.pugx.org/forrest79/presenter-tester/license)](//packagist.org/packages/forrest79/presenter-tester)
[![Build](https://github.com/forrest79/PresenterTester/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/PresenterTester/actions/workflows/build.yml)

> [Mango Presenter Tester](https://github.com/mangoweb-backend/presenter-tester) fork just for personal use with some tweaks.

Testing tool for Nette presenter with easy to use API.

## Installation

The recommended way to install is via Composer:

```
composer require forrest79/presenter-tester
```

It requires PHP version 7.4.

## Integration & configuration

If you are using power of Nette DI Container in your tests, you can use Presenter Tester in your current testing environment. All you need is to register PresenterTester service in `.neon` configuration for tests.

```yaml
services:
	- Forrest79\PresenterTester\PresenterTester(baseUrl: "http://my-app.test")
```

You can also specify list of [listeners](#listeners):

```yaml
parameters:
	baseUrl: 'http://my-app.dev'

services:
	- Forrest79\PresenterTester\PresenterTester(
		baseUrl: %baseUrl%
		listeners: [
			MyListener()
		]
	)
```

## Usage

Get `PresenterTester` service from DI container. When you get the service, you can start testing your presenters:

```php
$testRequest = $presenterTester->createRequest('Admin:Article')
	->withParameters([
		'action' => 'edit',
		'id' => 1,
	]);
$testResult = $presenterTester->execute($testRequet);
$testResult->assertRenders('%A%Hello world article editation%A%');
```

As you can see, you first create a `TestPresenterRequest` using `createRequest` method on `PresenterTester`. You pass a presenter name (without an action) and later you configure the test request. You can set additional request parameters like `action` or your own application parameters. There are many other things you can configure on the request, like form values or headers.

After the test request is configured, you pass it to `execute` method, which performs presenter execution and returns `TestPresenterResult`, which wraps `Nette\Application\IResponse` with some additional data collected during execution.

The `TestPresenterResult` contains many useful assert functions like render check or form validity check. In our example there is `assertRenders` method, which asserts that presenter returns `TextResponse` and that the text contains given pattern. You probably already know the pattern format from [Tester\Assert::match()](https://tester.nette.org/en/writing-tests#toc-assert-match) function.

## Helpers

### MemorySessionHandler

To bypass PHP session you can use for testing saving session just to memory. To do this, simply call this before running tests:

```php
Forrest79\PresenterTester\Helpers\MemorySessionHandler::install();
```

Or use mocked `Session`.

## Mocks

> Some mocks are taken from https://github.com/mangoweb-backend/tester-http-mocks

### Http\Request

To correct set SameSite cookie you need to redefine `Http\Request` for testing. You can use the original one from Nette:

```yaml
services:
	http.request: Nette\Http\Request(
		url: Nette\Http\UrlScript(%baseUrl%)
		cookies: ['_nss': true] # Nette\Http\Helpers::STRICT_COOKIE_NAME
		remoteAddress: '255.254.253.252' # you can set also some others values, for example REMOTE_ADDRESS
	)
```

Or you can use mocked `Http\Request` like this:

```yaml
services:
	http.request: Forrest79\PresenterTester\Mocks\Http\Request(
		url: Nette\Http\UrlScript('http://my-app.dev')
		remoteAddress: '255.254.253.252' # you can set also some others values, for example REMOTE_ADDRESS
	)
```

### Http\Response

If you want to test cookies, that're sent, use mocked `Http\Response`:

```yaml
services:
	http.response:
		factory: Forrest79\PresenterTester\Mocks\Http\Response
		alteration: true
```

Then get `Forrest79\PresenterTester\Mocks\Http\Response` service from DI container a read cookies by `getCookies()` method.

### Http\Session

Fake testing session. When you use this, you don't need to install `MemorySessionHandler`.

```yaml
services:
	session.session:
		factory: Forrest79\PresenterTester\Mocks\Http\Session
```

## TestPresenterRequest API

**Beware that ``TestPresenterRequest`` is immutable object.**

### `withParameters(array $parameters)`
Set application request parameters.

### `withForm(string $formName, array $post, array $files)`
Add form submission data to request. You have to specify full component tree path to in `$formName`.

Presenter Tester supports forms with CSRF protection, but since it uses session, it is recommended to install [mangoweb/tester-http-mocks](https://github.com/mangoweb-backend/tester-http-mocks) package.

### `withSignal(string $signal, array $componentParameters = [])`
With Presenter Tester, you can also easily test signal method.

### `withAjax`
(Not only) signals often uses AJAX, which you can enable using this method.

### `withMethod(string $methodName)`
Change the HTTP method. The default is `GET`. You don't have to explicitly set a method for forms.

### `withHeaders(array $headers)`
Pass additional HTTP headers.

### `withIdentity(Nette\Security\IIdentity $identity)`
Change identity of User, which is executing given request. This is useful when login is required to perform the action. You can implement [identity factory](#identity-factory), which provides a default identity for each request.

### `withPost(array $post)`
### `withFiles(array $files)`
### `withRawBody(string$rawBody)`

## TestPresenterResult API
It is a result of test execution. It wraps `Nette\Application\IResponse` and adds few methods to check the response easily.

### `assertRenders($match)`
Checks that response is `TextResponse`. Also, you can provide a `$match` parameter to check that response contains some text. You can either pass [pattern](https://tester.nette.org/en/writing-tests#toc-assert-match) or an array plain strings.

### `assertNotRenders($matches)`
Checks that given pattern or strings were not rendered.

### `assertJson($expected)`
Checks that response is JSON. You can optionally pass expected payload.

### `assertBadRequest($code)`
Checks that requests terminates with bad request exception (e.g. 404 not found).

### `assertRedirects(string $presenterName, array $parameters)`
Checks that request redirects to given presenter. You can also pass parameters to check. Extra parameters in redirect request are ignored.

### `assertRedirectsUrl($url)`
### `assertFormValid($formName)`
### `assertFormHasErrors($formName, $formErrors)`

Also, there are methods like `getResponse` or `getPresenter` to access original data and perform some custom checks.

## Listeners

You can hook to some events by implementing `Forrest79\PresenterTester\PresenterTesterListener` interface. Then you can e.g. modify test request or execute some implicit result checks.

To register a listener, simply register it as a service in DI container (infrastructure container if you are using Mango Tester Infrastructure).

## Identity factory

Using identity factory you can implement a factory which creates a default identity. The factory is a simple PHP callback, which accepts `PresenterTestRequest` and returns `Nette\Security\IIdentity`.
