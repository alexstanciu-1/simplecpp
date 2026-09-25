<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function native_check(bool $ok): void
{
	if (!$ok) {
		throw new \LogicException('Native runner assertion failed');
	}
}
function native_initialized(object $record): void
{
	foreach ((new \ReflectionClass($record))->getProperties() as $property) {
		native_check($property->isInitialized($record));
	}
}
$runner = new Native_Runner();
$modules = new Storage();
$module = new llvm_module();
$module->file_name = 'test.ll';
$modules[] = $module;
$before = glob(sys_get_temp_dir() . '/my-try-native-*');
$module->text = 'invalid LLVM';
$failed = $runner->run($modules);
native_initialized($failed);
native_initialized($failed->build);
native_check($failed->build->exit_code !== 0 && $failed->execution === null && $failed->build->stderr !== '');
$module->text = "define i32 @main() { ret i32 7 }\n";
$success = $runner->run($modules);
native_initialized($success);
native_initialized($success->build);
native_check($success->build->exit_code === 0 && $success->execution !== null);
native_initialized($success->execution);
native_check($success->execution->exit_code === 7 && $success->execution->stdout === '' && $success->execution->stderr === '');
native_check($failed->execution === null);
native_check(glob(sys_get_temp_dir() . '/my-try-native-*') === $before);
$module->file_name = '.'; // Source write targets its directory and must fail.
$write_failed = false;
try {
	@$runner->run($modules);
}
catch (\RuntimeException $error) {
	$write_failed = $error->getMessage() === 'Cannot write LLVM input';
}
native_check($write_failed && glob(sys_get_temp_dir() . '/my-try-native-*') === $before);

// Host-only access exercises process outcomes without adding a public test API.
$path = tempnam(sys_get_temp_dir(), 'scpp-process-test-');
if ($path === false) {
	throw new \RuntimeException('Cannot create process fixture');
}
unlink($path);
mkdir($path, 0700);
$process = new \ReflectionMethod(Native_Runner::class, 'process');
try
{
	$result = $process->invoke($runner, [PHP_BINARY, '-r', 'fwrite(STDOUT, "out"); fwrite(STDERR, "err"); exit(4);'], $path);
	native_initialized($result);
	native_check($result->exit_code === 4 && $result->stdout === 'out' && $result->stderr === 'err');
	// Deleting a capture file simulates a failed read after a successful launch.
	foreach (['stdout', 'stderr'] as $stream)
	{
		$rejected = false;
		try {
			@$process->invoke($runner, [PHP_BINARY, '-r', 'unlink(' . var_export($stream, true) . ');'], $path);
		}
		catch (\RuntimeException $error) {
			$rejected = str_contains($error->getMessage(), 'Cannot read native input/output:');
		}
		native_check($rejected);
	}
	$recovered = $process->invoke($runner, [PHP_BINARY, '-r', ''], $path);
	native_check($recovered->exit_code === 0 && $recovered->stdout === '' && $recovered->stderr === '');
}
finally
{
	foreach (['stdout', 'stderr'] as $stream) {
		if (is_file($path . '/' . $stream)) {
			unlink($path . '/' . $stream);
		}
	}
	rmdir($path);
}
echo "Native runner: initialized results, failed build, nonzero execution, stream failures, reuse and cleanup passed\n";
