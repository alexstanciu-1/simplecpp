<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

class Lock_Ownership_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }
}

$manifest = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$session->compile($manifest);
$lock = \compile\Project_Lock::acquire($manifest);
$lock->release();
$original = file_get_contents($manifest);
file_put_contents($manifest, '{');
$failed = false;
try {
    $session->compile($manifest);
}
catch (Exception $exception) {
    $failed = str_contains($exception->getMessage(), 'Invalid JSON:');
}
Lock_Ownership_Test::check($failed, 'Exercise a compile failure');
$lock = \compile\Project_Lock::acquire($manifest);
$lock->release();
file_put_contents($manifest, $original);
$session->compile($manifest);

$lock = \compile\Project_Lock::acquire($manifest);
$borrower = new \compile\Compiler_Session($lock);
$borrower->compile($manifest);
$blocked = false;
try {
    (new \compile\Compiler_Session())->compile($manifest);
}
catch (Exception $exception) {
    $blocked = str_contains($exception->getMessage(), 'already being compiled');
}
Lock_Ownership_Test::check($blocked, 'A borrowed lock stays held after compilation, including against another session in this process');

file_put_contents('other.json', $original);
$blocked = false;
try {
    $borrower->compile('other.json');
}
catch (Exception $exception) {
    $blocked = str_contains($exception->getMessage(), 'No active lock for project:');
}
Lock_Ownership_Test::check($blocked, 'A borrowed lock cannot authorize a different project');
$lock->release();
$blocked = false;
try {
    $borrower->compile($manifest);
}
catch (Exception $exception) {
    $blocked = str_contains($exception->getMessage(), 'No active lock for project:');
}
Lock_Ownership_Test::check($blocked, 'A released lock cannot authorize compilation');
$session->compile($manifest);

// An executed tool must not inherit the reservation and extend its lifetime.
$lock = \compile\Project_Lock::acquire($manifest);
$child = proc_open([PHP_BINARY, '-r', 'echo "ready\n"; fgets(STDIN);'],
    [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
Lock_Ownership_Test::check(is_resource($child), 'Start a tool while the project is reserved');
try {
    Lock_Ownership_Test::check(fgets($pipes[1]) === "ready\n", 'Tool has executed and remains alive');
    $lock->release();
    $next = \compile\Project_Lock::acquire($manifest);
    $next->release();
}
finally {
    fwrite($pipes[0], "finish\n");
    foreach ($pipes as $pipe) {
        fclose($pipe);
    }
    proc_close($child);
    $lock->release();
}
echo "lock ownership ok: success/failure release, same-process exclusion, and borrowed-lock validation\n";
