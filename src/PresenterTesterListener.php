<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

interface PresenterTesterListener
{

	function onRequest(TestPresenterRequest $request): TestPresenterRequest;


	function onResult(TestPresenterResult $result): void;

}
