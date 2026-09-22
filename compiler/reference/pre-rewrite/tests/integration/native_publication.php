<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
require_once __DIR__ . '/../support/step_support.php';

class Publication_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    public static function rejects(callable $operation, string $message): Throwable
    {
        try {
            $operation();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $message), $error->getMessage());
            return $error;
        }
        throw new Exception('Expected rejection: ' . $message);
    }

    public static function workspace(): string
    {
        $paths = glob(getcwd() . '/.scpp-native-*');
        self::check(count($paths) === 1, 'Exactly one private candidate workspace');
        return $paths[0];
    }
}

$manifest = '../fixtures/three_files/project.json';
$config = getcwd() . '/backend.json';
$catalog = getcwd() . '/types.json';
$output = getcwd() . '/program';
file_put_contents($config, json_encode(['clang' => 'clang', 'target' => null], JSON_THROW_ON_ERROR));
copy(\load_runtime\Language_Types::input_path(), $catalog);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog, backend_toolchain_path: $config);
$first = $session->compile($manifest, $output);
$before = get_object_vars($session);
$key = hash_file('sha256', $output);
$input_bytes = [file_get_contents($config), file_get_contents($catalog)];
$lock = \compile\Project_Lock::acquire($manifest);
$lock_path = $lock->path();
$lock_inode = fileinode($lock_path);
$lock->release();
foreach ([$config, $catalog, './backend.json', './types.json', $lock_path] as $collision) {
    Publication_Test::rejects(static fn() => $session->compile($manifest, $collision), 'would replace compiler input');
    Publication_Test::check((get_object_vars($session) === $before) && (hash_file('sha256', $output) === $key),
        'Rejected output preserves the entire retained session and published artifact');
}
clearstatcache(true, $lock_path);
Publication_Test::check(fileinode($lock_path) === $lock_inode, 'Native destination protection preserves the lock owner inode');

// Canonical input targets and directory aliases must use the same guard.
symlink(getcwd(), getcwd() . '/alias');
symlink($catalog, getcwd() . '/types-link.json');
$aliased = new \compile\Compiler_Session(type_catalog_path: './types-link.json', backend_toolchain_path: './alias/backend.json');
foreach (['./alias/types.json', './alias/backend.json'] as $collision) {
    Publication_Test::rejects(static fn() => $aliased->compile($manifest, $collision), 'would replace compiler input');
}
Publication_Test::check($input_bytes === [file_get_contents($config), file_get_contents($catalog)], 'All input bytes survive collision requests');
$repair = $session->compile($manifest, $output);
Publication_Test::check(($repair->completed) && ($repair->native === $first->native), 'Ordinary request still reuses the intact executable');

// Verify default input paths and the resolved toolchain executable without ever
// attempting to write repository/runtime files, even if this guard regresses.
$toolchain = new \prepare_backend\LLVM_Toolchain();
$toolchain->configuration();
$protected = [\load_runtime\Language_Types::input_path(), ...$toolchain->input_paths()];
foreach ($protected as $path) {
    Publication_Test::rejects(static fn() => \build_native\Native_Paths::destination(realpath($path),
            $first->inputs->manifest, $first->inputs->sources, $protected), 'would replace compiler input');
}

// Prove the real session publication boundary. Delete a prepared candidate's
// temporary executable to force the actual final rename to fail; no fake emitter
// or linker is involved, and the previous executable remains at its destination.
$toolchain = new \prepare_backend\LLVM_Toolchain($config);
$toolchain->configuration();
$publish = new ReflectionMethod(\compile\Compiler_Session::class, 'publish');
$result = $session->compile($manifest);
$before = get_object_vars($session);
$candidate = \Step_Test::run(new \build_native\Native_Builder($result->llvm, $output, $toolchain, null, true));
$directory = Publication_Test::workspace();
unlink($directory . '/program');
Publication_Test::rejects(static fn() => $publish->invoke($session, $result, $candidate), 'Cannot publish native executable');
Publication_Test::check((!$result->completed) && ($result->native === null) && (get_object_vars($session) === $before)
    && (hash_file('sha256', $output) === $key) && (!is_dir($directory)), 'Rename failure preserves output/state and cleans candidate');

// Simultaneous publication and cleanup failure preserves the primary cause.
$candidate = \Step_Test::run(new \build_native\Native_Builder($result->llvm, $output, $toolchain, null, true));
$directory = Publication_Test::workspace();
unlink($directory . '/program');
file_put_contents($directory . '/unowned', 'retain this file');
$error = Publication_Test::rejects(static fn() => $publish->invoke($session, $result, $candidate), 'Cannot publish native executable');
Publication_Test::check(str_contains($error->getMessage(), 'Native cleanup warning:') && ($error->getPrevious() !== null)
    && (get_object_vars($session) === $before) && (hash_file('sha256', $output) === $key), 'Primary failure and cleanup detail remain visible without publication');
unlink($directory . '/unowned');
rmdir($directory);

// Cleanup after a successful rename is nonfatal and exported, so executable
// contents, retained state and the completion result cannot contradict each other.
$candidate = \Step_Test::run(new \build_native\Native_Builder($result->llvm, $output, $toolchain, null, true));
$directory = Publication_Test::workspace();
file_put_contents($directory . '/unowned', 'retain this file');
$generation = $session->generation;
$publish->invoke($session, $result, $candidate);
$export = json_decode($result->to_json(), true, 512, JSON_THROW_ON_ERROR);
Publication_Test::check(($result->completed) && ($session->generation === ($generation + 1))
    && ($session->published->native === $result->native) && (count($result->warnings) === 1)
    && str_contains($result->warnings[0], $directory) && ($export['warnings'] === $result->warnings)
    && ($result->native->content_key === hash_file('sha256', $output)), 'Successful publication retains coherent state with cleanup warnings');
unlink($directory . '/unowned');
rmdir($directory);
Publication_Test::check($session->compile($manifest, $output)->warnings === [], 'Warnings belong to the attempt, not later builds');

// A failed discard is retryable; unexpected directory contents are never removed.
$directory = getcwd() . '/cleanup-retry';
mkdir($directory);
mkdir($directory . '/program'); // unlink must fail on an unexpected directory.
$workspace = new \build_native\Native_Workspace($directory);
$warnings = $workspace->discard();
Publication_Test::check((count($warnings) === 2) && is_dir($directory . '/program'), 'Report failed file and directory removal without recursive deletion');
rmdir($directory . '/program');
Publication_Test::check(($workspace->discard() === []) && (!is_dir($directory)) && ($workspace->discard() === []), 'Cleanup retries after repair and is idempotent after success');

// Fallback cleanup of an abandoned owner still has a diagnostic recipient.
$directory = getcwd() . '/cleanup-abandoned';
mkdir($directory);
file_put_contents($directory . '/unowned', 'retain');
$old_log = ini_set('error_log', getcwd() . '/cleanup.log');
$abandoned = new \build_native\Native_Workspace($directory);
unset($abandoned);
ini_set('error_log', $old_log);
Publication_Test::check(str_contains(file_get_contents(getcwd() . '/cleanup.log'), $directory), 'Abandoned cleanup failure reaches the PHP error log');
unlink($directory . '/unowned');
rmdir($directory);
Publication_Test::check(glob(getcwd() . '/.scpp-native-*') === [], 'No unexpected staging directories remain');
echo "native publication ok: protected input paths, canonical aliases, rename rollback, cleanup warnings, retry and abandoned cleanup diagnostics\n";
