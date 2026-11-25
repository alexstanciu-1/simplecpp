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

		$warning = scpp_detect_windows_msys2_path_warning(
			'C:\msys64\mingw64\bin;C:\Users\alexv\AppData\Local\Programs\scpp\bin',
			'Windows'
		);
		$this->assertContains(
			'C:/msys64/usr/bin before C:/msys64/mingw64/bin',
			$warning ?? '',
			'doctor should warn when Windows PATH includes mingw64/bin without msys64/usr/bin'
		);

		$noWarning = scpp_detect_windows_msys2_path_warning(
			'C:\msys64\usr\bin;C:\msys64\mingw64\bin;C:\Users\alexv\AppData\Local\Programs\scpp\bin',
			'Windows'
		);
		$this->assertSame(null, $noWarning, 'doctor should not warn once msys64/usr/bin is present');

		$tempRoot = normalize_path(sys_get_temp_dir() . '/scpp_doctor_temp_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		$this->ensureDir($tempRoot);
		$localAppData = $tempRoot . '/local-app-data';
		$this->ensureDir($localAppData . '/Temp');

		$tempWarning = scpp_detect_windows_temp_directory_warning([
			'TMP' => 'C:\Windows',
			'TEMP' => 'C:\Windows',
			'LOCALAPPDATA' => str_replace('/', '\\', $localAppData),
		], 'Windows');
		$this->assertContains(
			'Cannot create temporary file in C:\\WINDOWS',
			$tempWarning ?? '',
			'doctor should warn when Windows TMP/TEMP resolve under C:\\WINDOWS'
		);

		$normalizedEnv = scpp_normalize_windows_temp_environment([
			'TMP' => 'C:\Windows',
			'TEMP' => 'C:\Windows',
			'LOCALAPPDATA' => str_replace('/', '\\', $localAppData),
		], 'Windows');
		$this->assertSame(
			normalize_path($localAppData . '/Temp'),
			normalize_path($normalizedEnv['TMP'] ?? ''),
			'process environment should redirect Windows TMP to a writable fallback'
		);
		$this->assertSame(
			normalize_path($localAppData . '/Temp'),
			normalize_path($normalizedEnv['TEMP'] ?? ''),
			'process environment should redirect Windows TEMP to a writable fallback'
		);

		$msysEnv = scpp_normalize_windows_temp_environment([
			'TMP' => str_replace('/', '\\', $localAppData . '/Temp'),
			'TEMP' => str_replace('/', '\\', $localAppData . '/Temp'),
			'LOCALAPPDATA' => str_replace('/', '\\', $localAppData),
			'MSYSTEM' => 'MINGW64',
			'SHELL' => 'C:\\Program Files\\Git\\usr\\bin\\bash.exe',
		], 'Windows');
		$this->assertSame(
			normalize_path($localAppData . '/Temp'),
			normalize_path($msysEnv['TMP'] ?? ''),
			'Git Bash / MSYS Windows shells should force a stable child TMP value'
		);
		$this->assertSame(
			normalize_path($localAppData . '/Temp'),
			normalize_path($msysEnv['TEMP'] ?? ''),
			'Git Bash / MSYS Windows shells should force a stable child TEMP value'
		);

		echo "PASS: scpp doctor\n";
		return 0;
	}

	private function ensureDir(string $path): void
	{
		if (is_dir($path)) {
			return;
		}
		if (!mkdir($path, 0777, true) && !is_dir($path)) {
			throw new RuntimeException('Failed to create directory: ' . $path);
		}
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
