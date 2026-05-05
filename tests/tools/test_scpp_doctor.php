<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppDoctorTest
{
	public function run(): int
	{
		$php = PHP_BINARY;
		$cwd = __DIR__;

		$timedOut = scpp_run_optional_command($cwd, [$php, '-r', 'fwrite(STDERR, "starting\n"); sleep(10);'], [], 0.2);
		$this->assertSame(124, $timedOut['exit_code'], 'optional command should time out instead of hanging forever');
		$this->assertContains('Timed out after 0.2s', $timedOut['stderr'], 'timeout should be reported in stderr');

		$fast = scpp_run_optional_command($cwd, [$php, '-r', 'echo "ok\n";'], [], 0.5);
		$this->assertSame(0, $fast['exit_code'], 'fast optional command should succeed');
		$this->assertSame("ok\n", $fast['stdout'], 'fast optional command should capture stdout');

		echo "PASS: scpp doctor\n";
		return 0;
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '` in `' . $haystack . '`');
		}
	}
}

exit((new ScppDoctorTest())->run());
