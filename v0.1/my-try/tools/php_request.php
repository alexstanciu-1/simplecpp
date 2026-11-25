<?php
// Host-only FPM transport. This file is not portable compiler source.
$__request_folder = getenv('MY_TRY_REQUEST');
$__request = json_decode(file_get_contents($__request_folder . '/request.json'), true, flags: JSON_THROW_ON_ERROR);
if (!function_exists('posix_setsid')) {
	throw new RuntimeException('FPM executor requires the POSIX extension');
}
if (posix_getpgrp() !== getmypid()) {
	posix_setsid();
}
if (posix_getpgrp() !== getmypid()) {
	throw new RuntimeException('Cannot isolate FPM worker process group');
}
file_put_contents($__request_folder . '/pid.tmp', (string)getmypid());
rename($__request_folder . '/pid.tmp', $__request_folder . '/pid');
chdir($__request['cwd']);
foreach (['TMPDIR', 'TMP', 'TEMP'] as $__key) {
	putenv($__key . '=' . $__request['temporary']);
}
define('STDIN', fopen($__request_folder . '/stdin', 'rb'));
define('STDOUT', fopen($__request_folder . '/stdout', 'ab'));
define('STDERR', fopen($__request_folder . '/stderr', 'ab'));
ob_start(static function (string $text): string {
	fwrite(STDOUT, $text);
	return '';
}, 1);
$argv = $__request['argv'];
$argc = count($argv);
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = $argc;
$__status = ['code' => null, 'error' => 'PHP ended before returning; use explicit CLI for exit()/die() contracts'];
register_shutdown_function(static function () use ($__request_folder, &$__status): void {
	$last = error_get_last();
	if ($last !== null && in_array($last['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
		$__status = ['code' => 255, 'error' => $last['message']];
	}
	file_put_contents($__request_folder . '/result.json', json_encode($__status, JSON_THROW_ON_ERROR));
});
try
{
	if ($__request['script'] !== null) {
		require $__request['script'];
	}
	else {
		eval($__request['code']);
	}
	$__status = ['code' => 0];
}
catch (Throwable $__error) {
	fwrite(STDERR, (string)$__error . "\n");
	$__status = ['code' => 255];
}
