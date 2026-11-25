<?php
declare(strict_types=1);

const PHP_AST_WINDOWS_RELEASE = '1.1.3';
const PHP_AST_WINDOWS_BASE_URL = 'https://downloads.php.net/~windows/pecl/releases/ast/' . PHP_AST_WINDOWS_RELEASE;
const SCPP_MIN_PHP_VERSION = '8.4.0';
const SCPP_VERSION = '0.1.0-dev';
const SCPP_PROFILE_BEGIN = '# >>> scpp user-local bin >>>';
const SCPP_PROFILE_END = '# <<< scpp user-local bin <<<';

main();

function main(): void
{
	echo "Prism++ installer\n\n";

	ensure_php_version();

	if (PHP_OS_FAMILY === 'Windows') {
		ensure_windows_php_ast();
	}
	else {
		ensure_non_windows_php_ast();
	}

	$repoRoot = dirname(__DIR__);
	$binDir = get_user_bin_dir();

	ensure_dir($binDir);
	$launcherPhpBin = get_launcher_php_bin();
	echo "Launcher PHP binary: {$launcherPhpBin}\n";
	write_php_shim($binDir);
	write_launcher($binDir, $launcherPhpBin);
	write_optional_windows_sccache_shim($binDir);
	write_config($repoRoot, $binDir, $launcherPhpBin);
	ensure_user_path_entry($binDir);
	verify_install($binDir);
	print_success_notes($repoRoot, $binDir);
}

function ensure_php_version(): void
{
	if (version_compare(PHP_VERSION, SCPP_MIN_PHP_VERSION, '<')) {
		throw new RuntimeException('Prism++ requires PHP 8.4 or newer.');
	}

	echo "PHP version OK: " . PHP_VERSION . "\n";
}

function ensure_windows_php_ast(): void
{
	$extDirIni = trim((string) ini_get('extension_dir'));
	$resolvedExtDir = resolve_active_php_extension_dir();
	$dllTarget = rtrim($resolvedExtDir, "\/") . DIRECTORY_SEPARATOR . 'php_ast.dll';
	$repoLocalDll = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'ext' . DIRECTORY_SEPARATOR . 'php_ast.dll';

	echo "php.ini extension_dir: " . ($extDirIni === '' ? '(empty)' : $extDirIni) . "\n";
	echo "Resolved PHP extension dir: {$resolvedExtDir}\n";
	echo "Expected php_ast.dll target: {$dllTarget}\n";
	if (is_file($repoLocalDll)) {
		echo "Note: repo-local php_ast.dll exists at: {$repoLocalDll}\n";
	}

	if (extension_loaded('ast')) {
		if (is_file($dllTarget)) {
			echo "php-ast already enabled and present in the active PHP extension dir\n";
			return;
		}

		echo "php-ast appears loaded in the current process, but php_ast.dll is missing from the active PHP extension dir. Continuing with install to fix a likely cwd-relative false positive.\n";
	}

	$phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
	if (!in_array($phpVersion, ['8.4', '8.5'], true)) {
		throw new RuntimeException("Automatic Windows php-ast install currently supports PHP 8.4 and 8.5. Detected: {$phpVersion}");
	}

	$threadSafety = detect_windows_php_thread_safety() ? 'ts' : 'nts';
	$zipName = sprintf(
		'php_ast-%s-%s-%s-vs17-x64.zip',
		PHP_AST_WINDOWS_RELEASE,
		$phpVersion,
		$threadSafety
	);

	$url = PHP_AST_WINDOWS_BASE_URL . '/' . $zipName;
	echo "Installing php-ast from: {$url}\n";

	$tempDir = rtrim(sys_get_temp_dir(), "\\/") . DIRECTORY_SEPARATOR . 'prismpp_php_ast';
	ensure_dir($tempDir);

	$zipFile = $tempDir . DIRECTORY_SEPARATOR . $zipName;
	file_put_contents_or_throw($zipFile, download_file($url));

	$extractDir = $tempDir . DIRECTORY_SEPARATOR . 'extract';
	if (is_dir($extractDir)) {
		delete_tree($extractDir);
	}
	ensure_dir($extractDir);

	$zip = new ZipArchive();
	$res = $zip->open($zipFile);
	if ($res !== true) {
		throw new RuntimeException("Unable to open php-ast zip: {$zipFile}");
	}

	if ($zip->extractTo($extractDir) !== true) {
		$zip->close();
		throw new RuntimeException("Failed to extract php-ast zip: {$zipFile}");
	}
	$zip->close();

	$dllSource = find_windows_php_ast_dll($extractDir);
	if ($dllSource === null) {
		$files = list_relative_files_recursive($extractDir);
		$preview = array_slice($files, 0, 20);
		$details = $preview === [] ? '(archive extracted no files)' : implode(PHP_EOL, $preview);
		throw new RuntimeException("No suitable php-ast DLL was found in the downloaded package. Extracted files:
{$details}");
	}

	$extDir = $resolvedExtDir;
	ensure_dir($extDir);

	$dllTarget = rtrim($extDir, "\\/") . DIRECTORY_SEPARATOR . 'php_ast.dll';
	echo "Copying php_ast.dll to: {$dllTarget}\n";
	if (!@copy($dllSource, $dllTarget) && !is_file($dllTarget)) {
		throw new RuntimeException("Failed to copy php_ast.dll to: {$dllTarget}");
	}

	echo "Copied php_ast.dll size: " . filesize($dllTarget) . " bytes\n";

	$phpIni = locate_php_ini_windows();
	ensure_windows_php_ini_has_ast($phpIni);

	echo "php-ast installed to: {$dllTarget}\n";
	echo "php.ini updated: {$phpIni}\n";
	echo "Open a new terminal after installation so PHP reloads the updated php.ini.\n";
}

function ensure_non_windows_php_ast(): void
{
	if (!extension_loaded('ast')) {
		throw new RuntimeException('php-ast is required. Install and enable it before running install.php.');
	}

	echo "php-ast already enabled\n";
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


function get_launcher_php_bin(): string
{
	$requested = trim((string) (getenv('SCPP_INSTALL_PHP_BIN') ?: ''));
	if ($requested !== '') {
		$resolved = resolve_command_path($requested);
		if ($resolved === null) {
			throw new RuntimeException("Requested launcher PHP binary was not found on PATH: {$requested}");
		}

		return $resolved;
	}

	$phpBinary = PHP_BINARY;
	if ($phpBinary === '') {
		throw new RuntimeException('Unable to determine the PHP binary for the launcher.');
	}

	return $phpBinary;
}

function resolve_command_path(string $command): ?string
{
	if ($command === '') {
		return null;
	}

	if (strpbrk($command, '/\\') !== false) {
		$real = realpath($command);
		if ($real !== false && is_file($real)) {
			return $real;
		}

		return is_file($command) ? $command : null;
	}

	$path = (string) getenv('PATH');
	foreach (explode(PATH_SEPARATOR, $path) as $dir) {
		if ($dir === '') {
			continue;
		}

		$candidate = $dir . DIRECTORY_SEPARATOR . $command;
		if (is_file($candidate) && is_executable($candidate)) {
			$real = realpath($candidate);
			return $real === false ? $candidate : $real;
		}
	}

	return null;
}

function escape_posix_sh_arg(string $value): string
{
	return escapeshellarg($value);
}

function escape_windows_cmd_arg(string $value): string
{
	return str_replace('"', '""', $value);
}

function write_php_shim(string $binDir): void
{
	$shimPath = $binDir . DIRECTORY_SEPARATOR . 'scpp.php';
	$content = <<<'PHP'
<?php
declare(strict_types=1);

// Repo-based installed shim. Reads sibling config and forwards to the repo entrypoint.
$configPath = __DIR__ . DIRECTORY_SEPARATOR . 'scpp.json';
if (!is_file($configPath)) {
	fwrite(STDERR, "Missing scpp.json next to launcher\n");
	exit(2);
}

$configJson = file_get_contents($configPath);
if ($configJson === false) {
	fwrite(STDERR, "Failed to read scpp.json\n");
	exit(2);
}

$config = json_decode($configJson, true);
if (!is_array($config) || !isset($config['repo_root']) || !is_string($config['repo_root']) || $config['repo_root'] === '') {
	fwrite(STDERR, "Invalid scpp.json: missing repo_root\n");
	exit(2);
}

$entry = rtrim($config['repo_root'], "\\/") . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . 'scpp.php';
if (!is_file($entry)) {
	fwrite(STDERR, "Entrypoint not found: {$entry}\n");
	fwrite(STDERR, "This is a repo-based install. If the repo moved, run the installer again.\n");
	exit(2);
}

require $entry;
PHP;
	file_put_contents_or_throw($shimPath, $content . PHP_EOL);
}

function write_launcher(string $binDir, string $launcherPhpBin): void
{
	if (PHP_OS_FAMILY === 'Windows') {
		$cmdLauncherPath = $binDir . DIRECTORY_SEPARATOR . 'scpp.cmd';
		$cmdContent = "@echo off\r\n" .
			'"' . escape_windows_cmd_arg($launcherPhpBin) . '" "%~dp0scpp.php" %*' . "\r\n";
		file_put_contents_or_throw($cmdLauncherPath, $cmdContent);
		echo "Launcher installed: {$cmdLauncherPath}\n";

		$bashLauncherPath = $binDir . DIRECTORY_SEPARATOR . 'scpp';
		$bashContent = "#!/usr/bin/env sh\n" .
			'exec ' . escape_posix_sh_arg($launcherPhpBin) . ' "$(dirname "$0")/scpp.php" "$@"' . "\n";
		file_put_contents_or_throw($bashLauncherPath, $bashContent);
		@chmod($bashLauncherPath, 0755);
		echo "Launcher installed: {$bashLauncherPath} (Git Bash / MinGW)\n";
		return;
	}

	$launcherPath = $binDir . DIRECTORY_SEPARATOR . 'scpp';
	$content = "#!/usr/bin/env sh\n" .
		'exec ' . escape_posix_sh_arg($launcherPhpBin) . ' "$(CDPATH= cd -- "$(dirname -- "$0")" && pwd)/scpp.php" "$@"' . "\n";

	file_put_contents_or_throw($launcherPath, $content);
	@chmod($launcherPath, 0755);

	echo "Launcher installed: {$launcherPath}\n";
}

function write_optional_windows_sccache_shim(string $binDir): void
{
	if (PHP_OS_FAMILY !== 'Windows') {
		return;
	}

	$sccacheExe = find_windows_winget_sccache_executable();
	if ($sccacheExe === null) {
		echo "sccache not found in the Winget package cache. Skipping sccache launcher shim.\n";
		return;
	}

	$cmdShimPath = $binDir . DIRECTORY_SEPARATOR . 'sccache.cmd';
	$cmdContent = "@echo off\r\n" .
		"\"" . str_replace('"', '""', $sccacheExe) . "\" %*\r\n";
	file_put_contents_or_throw($cmdShimPath, $cmdContent);
	echo "Launcher installed: {$cmdShimPath}\n";

	$bashShimPath = $binDir . DIRECTORY_SEPARATOR . 'sccache';
	$bashContent = "#!/usr/bin/env sh\n" .
		"exec \"" . str_replace('\\', '/', $sccacheExe) . "\" \"$@\"\n";
	file_put_contents_or_throw($bashShimPath, $bashContent);
	@chmod($bashShimPath, 0755);
	echo "Launcher installed: {$bashShimPath} (Git Bash / MinGW)\n";
}

function write_config(string $repoRoot, string $binDir, string $launcherPhpBin): void
{
	$configPath = $binDir . DIRECTORY_SEPARATOR . 'scpp.json';
	$config = [
		'repo_root' => $repoRoot,
		'installed_at' => date(DATE_ATOM),
		'install_mode' => 'repo-based-user-local',
		'version' => SCPP_VERSION,
		'php_bin' => $launcherPhpBin,
	];

	$json = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	if (!is_string($json)) {
		throw new RuntimeException('Failed to encode installer config.');
	}

	file_put_contents_or_throw($configPath, $json . PHP_EOL);
	echo "Config written: {$configPath}\n";
}

function ensure_user_path_entry(string $binDir): void
{
	if (PHP_OS_FAMILY === 'Windows') {
		ensure_windows_user_path_entry($binDir);
		return;
	}

	ensure_posix_profile_path_entry($binDir);
}

function ensure_windows_user_path_entry(string $binDir): void
{
	$current = (string) getenv('PATH');
	if (path_contains_dir($current, $binDir)) {
		echo "PATH already contains launcher directory in current process\n";
	}

	$userPath = read_windows_user_path();
	if ($userPath !== null && path_contains_dir($userPath, $binDir)) {
		echo "User PATH already contains launcher directory\n";
		return;
	}

	$psDir = windows_path_for_powershell($binDir);
	$script = <<<'PS'
$ErrorActionPreference = 'Stop'
$target = __TARGET__
$existing = [Environment]::GetEnvironmentVariable('Path', 'User')
if ([string]::IsNullOrWhiteSpace($existing)) {
	[Environment]::SetEnvironmentVariable('Path', $target, 'User')
	exit 0
}
$parts = $existing -split ';' | ForEach-Object { $_.Trim() } | Where-Object { $_ -ne '' }
$normalizedTarget = $target.TrimEnd('\')
$exists = $false
foreach ($part in $parts) {
	if ($part.TrimEnd('\') -ieq $normalizedTarget) {
		$exists = $true
		break
	}
}
if (-not $exists) {
	[Environment]::SetEnvironmentVariable('Path', ($existing.TrimEnd(';') + ';' + $target), 'User')
}
PS;
	$script = str_replace('__TARGET__', single_quote_ps($psDir), $script);

	$result = run_powershell_script($script);
	if ($result['code'] === 0) {
		$verified = read_windows_user_path();
		if ($verified !== null && path_contains_dir($verified, $binDir)) {
			echo "User PATH ensured: {$binDir}\n";
			return;
		}
	}

	if (try_set_windows_user_path_entry_via_registry($binDir)) {
		echo "User PATH ensured via registry fallback: {$binDir}\n";
		return;
	}

	if (try_setx_windows_user_path_entry($binDir)) {
		echo "User PATH ensured via setx fallback: {$binDir}\n";
		return;
	}

	$details = [];
	if (!empty($result['output'])) {
		$details[] = "PowerShell output:\n" . implode(PHP_EOL, $result['output']);
	}
	throw new RuntimeException(
		"Failed to update the user PATH on Windows." .
		($details !== [] ? "\n" . implode("\n\n", $details) : '')
	);
}

function find_windows_winget_sccache_executable(): ?string
{
	$localAppData = (string) (getenv('LOCALAPPDATA') ?: '');
	if ($localAppData === '') {
		return null;
	}

	$packageRoot = $localAppData . DIRECTORY_SEPARATOR . 'Microsoft' . DIRECTORY_SEPARATOR . 'WinGet' . DIRECTORY_SEPARATOR . 'Packages' . DIRECTORY_SEPARATOR . 'Mozilla.sccache_Microsoft.Winget.Source_8wekyb3d8bbwe';
	if (!is_dir($packageRoot)) {
		return null;
	}

	$matches = glob($packageRoot . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'sccache.exe');
	if (!is_array($matches) || $matches === []) {
		return null;
	}

	usort($matches, static fn (string $a, string $b): int => strcmp($b, $a));
	$resolved = realpath($matches[0]);
	return $resolved === false ? $matches[0] : $resolved;
}

function ensure_posix_profile_path_entry(string $binDir): void
{
	$profiles = [
		'.profile',
		'.bash_profile',
		'.zprofile',
	];

	$home = dirname($binDir, 2);
	$snippet = SCPP_PROFILE_BEGIN . "\n" .
		'if [ -d "$HOME/.local/bin" ] && ! printf %s ":$PATH:" | grep -Fq ":$HOME/.local/bin:"; then' . "\n" .
		"\t" . 'export PATH="$HOME/.local/bin:$PATH"' . "\n" .
		'fi' . "\n" .
		SCPP_PROFILE_END . "\n";

	foreach ($profiles as $profileName) {
		$profilePath = $home . DIRECTORY_SEPARATOR . $profileName;
		$existing = is_file($profilePath) ? file_get_contents($profilePath) : '';
		if ($existing === false) {
			throw new RuntimeException("Failed to read profile: {$profilePath}");
		}

		if (str_contains($existing, SCPP_PROFILE_BEGIN) && str_contains($existing, SCPP_PROFILE_END)) {
			echo "Profile already configured: {$profilePath}\n";
			continue;
		}

		if ($existing !== '' && !str_ends_with($existing, "\n")) {
			$existing .= "\n";
		}

		file_put_contents_or_throw($profilePath, $existing . $snippet);
		echo "Profile updated: {$profilePath}\n";
	}

	$sessionPath = (string) getenv('PATH');
	if (!path_contains_dir($sessionPath, $binDir)) {
		$separator = $sessionPath === '' ? '' : PATH_SEPARATOR;
		putenv('PATH=' . $binDir . $separator . $sessionPath);
	}
}

function try_set_windows_user_path_entry_via_registry(string $binDir): bool
{
	$currentUserPath = read_windows_user_path();
	if ($currentUserPath === null) {
		$currentUserPath = '';
	}

	if (path_contains_dir($currentUserPath, $binDir)) {
		return true;
	}

	$updated = trim_path_list($currentUserPath, ';');
	if ($updated !== '') {
		$updated .= ';';
	}
	$updated .= windows_path_for_powershell($binDir);

	$command = 'reg add HKCU\Environment /v Path /t REG_EXPAND_SZ /d ' . escapeshellarg($updated) . ' /f';
	exec($command . ' 2>&1', $output, $code);
	if ($code !== 0) {
		return false;
	}

	$verified = read_windows_user_path();
	return $verified !== null && path_contains_dir($verified, $binDir);
}

function try_setx_windows_user_path_entry(string $binDir): bool
{
	$currentUserPath = read_windows_user_path();
	if ($currentUserPath === null) {
		return false;
	}

	if (path_contains_dir($currentUserPath, $binDir)) {
		return true;
	}

	$updated = trim_path_list($currentUserPath, ';');
	if ($updated !== '') {
		$updated .= ';';
	}
	$updated .= windows_path_for_powershell($binDir);

	$command = 'setx PATH ' . escapeshellarg($updated);
	exec($command . ' 2>&1', $output, $code);
	if ($code !== 0) {
		return false;
	}

	$verified = read_windows_user_path();
	return $verified !== null && path_contains_dir($verified, $binDir);
}

function read_windows_user_path(): ?string
{
	$script = <<<'PS'
$ErrorActionPreference = 'Stop'
$value = [Environment]::GetEnvironmentVariable('Path', 'User')
if ($null -eq $value) {
	$value = ''
}
Write-Output $value
PS;
	$result = run_powershell_script($script);
	if ($result['code'] !== 0) {
		return null;
	}

	return implode(PHP_EOL, $result['output']);
}

function run_powershell_script(string $script): array
{
	$tempFile = tempnam(sys_get_temp_dir(), 'scpp_ps_');
	if ($tempFile === false) {
		throw new RuntimeException('Failed to create a temporary PowerShell script file.');
	}

	$psFile = $tempFile . '.ps1';
	if (!@rename($tempFile, $psFile)) {
		@unlink($tempFile);
		$psFile = $tempFile . '.ps1';
	}

	file_put_contents_or_throw($psFile, $script . PHP_EOL);
	$command = 'powershell -NoProfile -NonInteractive -ExecutionPolicy Bypass -File ' . escapeshellarg($psFile);
	exec($command . ' 2>&1', $output, $code);
	@unlink($psFile);

	return [
		'output' => $output,
		'code' => $code,
	];
}

function trim_path_list(string $value, string $separator): string
{
	return trim($value, " 	

 " . $separator);
}

function verify_install(string $binDir): void
{
	$commandPath = $binDir . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'scpp.cmd' : 'scpp');
	$command = PHP_OS_FAMILY === 'Windows'
		? 'cmd /c ' . escapeshellarg($commandPath) . ' --version'
		: escapeshellarg($commandPath) . ' --version';

	exec($command . ' 2>&1', $output, $code);
	if ($code !== 0) {
		throw new RuntimeException("Post-install verification failed:\n" . implode(PHP_EOL, $output));
	}

	echo "Verification OK: " . implode(' ', $output) . "\n";
}

function print_success_notes(string $repoRoot, string $binDir): void
{
	echo "\nDone.\n";
	echo "Repo root: {$repoRoot}\n";
	echo "User-local bin: {$binDir}\n";
	echo "This is a repo-based install for the current user only.\n";
	echo "If you move or delete the repo, run the installer again.\n";
	echo "If sccache is installed and on PATH, Prism++ build and test flows will use it automatically.\n";

	if (PHP_OS_FAMILY === 'Windows') {
		echo "Open a new terminal so updated PATH entries are picked up.\n";
	}
	else {
		echo "Open a new shell, or run: source ~/.profile\n";
	}
}


function resolve_active_php_extension_dir(): string
{
	$extDir = trim((string) ini_get('extension_dir'));
	if ($extDir === '') {
		return dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'ext';
	}

	if (is_absolute_path($extDir)) {
		return $extDir;
	}

	return dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . $extDir;
}

function is_absolute_path(string $path): bool
{
	if ($path === '') {
		return false;
	}

	if (strlen($path) >= 3 && ctype_alpha($path[0]) && $path[1] === ':' && ($path[2] === '\\' || $path[2] === '/')) {
		return true;
	}

	if (str_starts_with($path, '\\')) {
		return true;
	}

	if ($path[0] === '/' || $path[0] === '\\') {
		return true;
	}

	return false;
}

function detect_windows_php_thread_safety(): bool
{
	ob_start();
	phpinfo(INFO_GENERAL);
	$phpinfo = (string) ob_get_clean();

	if (preg_match('/Thread Safety\s*<\/td>\s*<td[^>]*>\s*(enabled|disabled)/i', $phpinfo, $matches)) {
		return strtolower($matches[1]) === 'enabled';
	}

	$plain = trim(strip_tags($phpinfo));
	if (preg_match('/Thread Safety\s*=>\s*(enabled|disabled)/i', $plain, $matches)) {
		return strtolower($matches[1]) === 'enabled';
	}

	throw new RuntimeException('Unable to determine PHP thread safety on Windows.');
}

function locate_php_ini_windows(): string
{
	$loaded = php_ini_loaded_file();
	if (is_string($loaded) && $loaded !== '') {
		return $loaded;
	}

	$phpDir = dirname(PHP_BINARY);
	$candidate = $phpDir . DIRECTORY_SEPARATOR . 'php.ini';
	if (!is_file($candidate)) {
		$production = $phpDir . DIRECTORY_SEPARATOR . 'php.ini-production';
		if (is_file($production)) {
			if (!@copy($production, $candidate) && !is_file($candidate)) {
				throw new RuntimeException('Failed to create php.ini from php.ini-production');
			}
		}
		else {
			file_put_contents_or_throw($candidate, '');
		}
	}

	return $candidate;
}

function ensure_windows_php_ini_has_ast(string $phpIni): void
{
	$content = file_get_contents($phpIni);
	if ($content === false) {
		throw new RuntimeException("Failed to read php.ini: {$phpIni}");
	}

	$pattern = '/(^|\R)\s*;?\s*extension\s*=\s*php_ast\.dll\s*(?=$|\R)/i';
	if (preg_match($pattern, $content)) {
		$content = preg_replace($pattern, '$1extension=php_ast.dll', $content);
	}
	else {
		if ($content !== '' && !str_ends_with($content, "\n") && !str_ends_with($content, "\r")) {
			$content .= PHP_EOL;
		}
		$content .= 'extension=php_ast.dll' . PHP_EOL;
	}

	if (!is_string($content)) {
		throw new RuntimeException("Failed to update php.ini: {$phpIni}");
	}

	file_put_contents_or_throw($phpIni, $content);
}

function download_file(string $url): string
{
	if (!function_exists('curl_init')) {
		throw new RuntimeException('The PHP cURL extension is required for automatic Windows php-ast installation.');
	}

	$ch = curl_init($url);
	curl_setopt_array($ch, [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_SSL_VERIFYHOST => 2,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_TIMEOUT => 120,
	]);

	$data = curl_exec($ch);
	if ($data === false) {
		$error = curl_error($ch);
		curl_close($ch);

		if (PHP_OS_FAMILY === 'Windows' && is_windows_tls_chain_error($error)) {
			echo "cURL TLS verification failed because PHP could not validate the certificate chain. Retrying with PowerShell and the Windows certificate store...\n";
			return download_file_with_powershell($url);
		}

		throw new RuntimeException("Download failed: {$error}");
	}

	$status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	curl_close($ch);

	if ($status < 200 || $status >= 300) {
		throw new RuntimeException("Download failed with HTTP {$status}: {$url}");
	}

	return $data;
}

function is_windows_tls_chain_error(string $error): bool
{
	$error = strtolower($error);
	return str_contains($error, 'unable to get local issuer certificate')
		|| str_contains($error, 'certificate')
		|| str_contains($error, 'schannel');
}

function download_file_with_powershell(string $url): string
{
	$tempFile = tempnam(sys_get_temp_dir(), 'scpp_dl_');
	if ($tempFile === false) {
		throw new RuntimeException('Failed to create a temporary file for PowerShell download fallback.');
	}

	$psFile = windows_path_for_powershell($tempFile);
	$psUrl = $url;
	$script = <<<'PS'
$ErrorActionPreference = 'Stop'
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$url = __SCPP_URL__
$out = __SCPP_OUT__
Invoke-WebRequest -UseBasicParsing -Uri $url -OutFile $out
PS;
	$script = str_replace(
		['__SCPP_URL__', '__SCPP_OUT__'],
		[single_quote_ps($psUrl), single_quote_ps($psFile)],
		$script
	);
	$result = run_powershell_script($script);
	if ($result['code'] !== 0 || !is_file($tempFile)) {
		@unlink($tempFile);
		$details = $result['output'] === [] ? 'PowerShell download failed.' : implode(PHP_EOL, $result['output']);
		throw new RuntimeException("Download failed after PowerShell fallback: {$details}");
	}

	$data = file_get_contents($tempFile);
	@unlink($tempFile);
	if ($data === false) {
		throw new RuntimeException('PowerShell download fallback succeeded, but the temporary file could not be read.');
	}

	return $data;
}

function find_file_recursive(string $baseDir, string $fileName): ?string
{
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $fileInfo) {
		if ($fileInfo->isFile() && strcasecmp($fileInfo->getFilename(), $fileName) === 0) {
			return $fileInfo->getPathname();
		}
	}

	return null;
}


function find_windows_php_ast_dll(string $baseDir): ?string
{
	$exact = find_file_recursive($baseDir, 'php_ast.dll');
	if ($exact !== null) {
		return $exact;
	}

	$candidates = [];
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $fileInfo) {
		if (!$fileInfo->isFile()) {
			continue;
		}

		$filename = $fileInfo->getFilename();
		if (!str_ends_with(strtolower($filename), '.dll')) {
			continue;
		}

		$normalized = strtolower($filename);
		$score = 0;
		if (str_contains($normalized, 'php_ast')) {
			$score += 100;
		}
		if (str_contains($normalized, 'ast')) {
			$score += 20;
		}
		if (str_contains($normalized, 'php')) {
			$score += 10;
		}

		$candidates[] = [
			'path' => $fileInfo->getPathname(),
			'filename' => $filename,
			'score' => $score,
		];
	}

	if ($candidates === []) {
		return null;
	}

	usort(
		$candidates,
		static function (array $left, array $right): int {
			if ($left['score'] !== $right['score']) {
				return $right['score'] <=> $left['score'];
			}

			return strcasecmp((string) $left['filename'], (string) $right['filename']);
		}
	);

	return (string) $candidates[0]['path'];
}

function list_relative_files_recursive(string $baseDir): array
{
	$files = [];
	$baseDir = rtrim($baseDir, "\/");
	$prefixLen = strlen($baseDir) + 1;
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
	);

	foreach ($iterator as $fileInfo) {
		$path = $fileInfo->getPathname();
		$files[] = substr($path, $prefixLen);
	}

	sort($files, SORT_NATURAL | SORT_FLAG_CASE);
	return $files;
}

function ensure_dir(string $dir): void
{
	if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
		throw new RuntimeException("Failed to create directory: {$dir}");
	}
}

function file_put_contents_or_throw(string $path, string $content): void
{
	if (file_put_contents($path, $content) === false) {
		throw new RuntimeException("Failed to write file: {$path}");
	}
}

function delete_tree(string $dir): void
{
	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST
	);

	foreach ($iterator as $fileInfo) {
		if ($fileInfo->isDir()) {
			@rmdir($fileInfo->getPathname());
		}
		else {
			@unlink($fileInfo->getPathname());
		}
	}

	@rmdir($dir);
}

function path_contains_dir(string $pathValue, string $dir): bool
{
	$needle = normalize_path_for_compare($dir);
	if ($needle === '') {
		return false;
	}

	foreach (explode(PATH_SEPARATOR, $pathValue) as $part) {
		if (normalize_path_for_compare($part) === $needle) {
			return true;
		}
	}

	return false;
}

function normalize_path_for_compare(string $path): string
{
	$trimmed = trim($path);
	if ($trimmed === '') {
		return '';
	}

	$trimmed = rtrim($trimmed, "\\/");
	if (PHP_OS_FAMILY === 'Windows') {
		$trimmed = strtolower($trimmed);
	}

	return $trimmed;
}

function windows_path_for_powershell(string $path): string
{
	return str_replace('/', '\\', $path);
}

function single_quote_ps(string $value): string
{
	return "'" . str_replace("'", "''", $value) . "'";
}
