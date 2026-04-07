<?php
declare(strict_types=1);

const SCPP_PROFILE_BEGIN = '# >>> scpp user-local bin >>>';
const SCPP_PROFILE_END = '# <<< scpp user-local bin <<<';

main();

function main(): void
{
	echo "Prism++ uninstall\n\n";

	$binDir = get_user_bin_dir();
	remove_launcher_files($binDir);
	remove_profile_block();
	print_done($binDir);
}

function get_user_bin_dir(): string
{
	if (PHP_OS_FAMILY === 'Windows') {
		$localAppData = (string) (getenv('LOCALAPPDATA') ?: '');
		if ($localAppData === '') {
			throw new RuntimeException('Unable to determine %LOCALAPPDATA% for the current user.');
		}

		return $localAppData . DIRECTORY_SEPARATOR . 'Programs' . DIRECTORY_SEPARATOR . 'scpp' . DIRECTORY_SEPARATOR . 'bin';
	}

	$home = (string) (getenv('HOME') ?: '');
	if ($home === '') {
		throw new RuntimeException('Unable to determine the user home directory.');
	}

	return $home . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'bin';
}

function remove_launcher_files(string $binDir): void
{
	$paths = [
		$binDir . DIRECTORY_SEPARATOR . 'scpp',
		$binDir . DIRECTORY_SEPARATOR . 'scpp.cmd',
		$binDir . DIRECTORY_SEPARATOR . 'scpp.php',
		$binDir . DIRECTORY_SEPARATOR . 'scpp.json',
	];

	foreach ($paths as $path) {
		if (is_file($path)) {
			if (!@unlink($path)) {
				throw new RuntimeException("Failed to remove file: {$path}");
			}
			echo "Removed: {$path}\n";
		}
	}
}

function remove_profile_block(): void
{
	if (PHP_OS_FAMILY === 'Windows') {
		remove_windows_user_path_entry();
		return;
	}

	$home = (string) getenv('HOME');
	$profiles = [
		$home . DIRECTORY_SEPARATOR . '.profile',
		$home . DIRECTORY_SEPARATOR . '.bash_profile',
		$home . DIRECTORY_SEPARATOR . '.zprofile',
	];

	$pattern = '/' . preg_quote(SCPP_PROFILE_BEGIN, '/') . '.*?' . preg_quote(SCPP_PROFILE_END, '/') . '\n?/s';
	foreach ($profiles as $profilePath) {
		if (!is_file($profilePath)) {
			continue;
		}

		$content = file_get_contents($profilePath);
		if ($content === false) {
			throw new RuntimeException("Failed to read profile: {$profilePath}");
		}

		$updated = preg_replace($pattern, '', $content);
		if (!is_string($updated)) {
			throw new RuntimeException("Failed to update profile: {$profilePath}");
		}

		if ($updated !== $content) {
			if (file_put_contents($profilePath, $updated) === false) {
				throw new RuntimeException("Failed to write profile: {$profilePath}");
			}
			echo "Profile cleaned: {$profilePath}\n";
		}
	}
}


function remove_windows_user_path_entry(): void
{
	$localAppData = (string) (getenv('LOCALAPPDATA') ?: '');
	if ($localAppData === '') {
		throw new RuntimeException('Unable to determine %LOCALAPPDATA% for the current user.');
	}

	$binDir = $localAppData . DIRECTORY_SEPARATOR . 'Programs' . DIRECTORY_SEPARATOR . 'scpp' . DIRECTORY_SEPARATOR . 'bin';
	$psDir = str_replace('/', '\\', $binDir);
	$script = '$target = ' . single_quote_ps($psDir) . '; ' .
		'$existing = [Environment]::GetEnvironmentVariable("Path", "User"); ' .
		'if ([string]::IsNullOrWhiteSpace($existing)) { exit 0 } ' .
		'$parts = $existing -split ";" | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne "" }; ' .
		'$normalizedTarget = $target.TrimEnd("\\"); ' .
		'$filtered = @(); ' .
		'foreach ($part in $parts) { if ($part.TrimEnd("\\") -ine $normalizedTarget) { $filtered += $part } } ' .
		'[Environment]::SetEnvironmentVariable("Path", ($filtered -join ";"), "User")';

	$command = 'powershell -NoProfile -ExecutionPolicy Bypass -Command ' . escapeshellarg($script);
	exec($command, $output, $code);
	if ($code !== 0) {
		throw new RuntimeException('Failed to remove the user PATH entry on Windows.');
	}

	echo "User PATH cleaned: {$binDir}\n";
}

function single_quote_ps(string $value): string
{
	return "'" . str_replace("'", "''", $value) . "'";
}

function print_done(string $binDir): void
{
	echo "\nDone.\n";
	echo "User-local bin: {$binDir}\n";
	echo "If you used a repo-based install, the repository itself was not removed.\n";
}
