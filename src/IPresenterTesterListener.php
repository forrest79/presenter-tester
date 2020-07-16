<?php declare(strict_types = 1);

namespace Forrest79\Tester\PresenterTester;

interface IPresenterTesterListener
{

	function onRequest(TestPresenterRequest $request): TestPresenterRequest;


	function onResult(TestPresenterResult $result): void;

}
