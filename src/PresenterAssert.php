<?php declare(strict_types=1);

namespace Forrest79\PresenterTester;

use Nette\Application\Request;
use Nette\Application\UI;
use Tester\Assert;

class PresenterAssert
{

	/**
	 * @param array<string, mixed>|NULL $actual
	 */
	public static function assertRequestMatch(
		Request $expected,
		?array $actual,
		bool $onlyIntersectedParameters = TRUE,
	): void
	{
		Assert::notSame(NULL, $actual);
		assert($actual !== NULL);

		$presenter = $actual[UI\Presenter::PRESENTER_KEY] ?? NULL;
		Assert::same($expected->getPresenterName(), $presenter);
		unset($actual[UI\Presenter::PRESENTER_KEY]);

		$expectedParameters = $expected->getParameters();

		foreach ($actual as $key => $actualParameter) {
			if (!isset($expectedParameters[$key])) {
				if ($onlyIntersectedParameters) {
					continue;
				}
				Assert::fail(sprintf('Parameter %s not expected', $key));
			}

			$expectedParameter = $expectedParameters[$key];
			if (is_string($actualParameter) && !is_string($expectedParameter)) {
				$expectedParameter = (string) $expectedParameter;
			}

			Assert::same($actualParameter, $expectedParameter, $key);
		}
	}

}
