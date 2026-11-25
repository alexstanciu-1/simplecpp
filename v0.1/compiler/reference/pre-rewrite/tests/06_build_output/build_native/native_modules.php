<?php
declare(strict_types=1);
require_once dirname(__DIR__, 3) . '/bootstrap.php';
require_once __DIR__ . '/../../support/step_support.php';

class Module_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
    }

    public static function tool(\compile\Compiler_Session $session): \prepare_backend\LLVM_Toolchain
    {
        return (new ReflectionProperty($session, 'toolchain'))->getValue($session);
    }

    public static function run(string $path, int $expected): void
    {
        $p = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
        self::check(is_resource($p) && (proc_close($p) === $expected), 'Real executable result');
    }
}
$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$output = getcwd() . '/module program';
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest, $output);
$tool = Module_Test::tool($session);
Module_Test::check((count($first->llvm->modules) === 3) && (count($first->native->objects) === 3), 'One native unit per contributing source file');
$seen = [];
foreach ($first->llvm->modules as $module) {
    foreach ($module->functions as $function) {
        $id = $function->body->binding->callable_id;
        Module_Test::check(!isset($seen[$id]), 'One definition across the program');
        $seen[$id] = true;
    }
}
$count = $tool->invocation_count();
$unchanged = $session->compile($manifest, $output);
Module_Test::check(($unchanged->llvm === $first->llvm) && ($unchanged->native === $first->native)
    && ($tool->invocation_count() === $count), 'Unchanged module/object/artifact reuse launches no tools');
$value_file = $first->inputs->sources->find_file_id($root . '/src/nested/value.phs');
Module_Test::edit($root . '/src/nested/value.phs', 'function value(): int { return 43; }');
$edited = $session->compile($manifest, $output);
Module_Test::check(($tool->invocation_count() - $count) === 2, 'One object compilation and one link after body edit');
foreach ($first->llvm->modules as $module) {
    $id = $module->source_file_id;
    Module_Test::check(($module === $edited->llvm->module_for($id)) === ($id !== $value_file), 'Only edited file IR replaced');
    Module_Test::check(($first->native->object_for($id) === $edited->native->object_for($id)) === ($id !== $value_file), 'Callers retain native objects');
    Module_Test::check(is_file($first->native->object_for($id)->path), 'Old readers retain their files');
}
Module_Test::run($output, 43);

// Declarations are limited to calls crossing the module boundary, even with repeated uses.
Module_Test::edit($root . '/src/main.phs', 'value(); value(); return answer();');
$imports = $session->compile($manifest, $output);
$main_id = $imports->inputs->sources->entry_file()->id;
$main = $imports->llvm->module_for($main_id);
Module_Test::check(substr_count($main->ir, 'declare ') === 2, 'Distinct actual cross-file declarations only');
Module_Test::run($output, 43);

// Missing and tampered objects are repaired despite unchanged emitted IR.
foreach (['missing', 'tampered'] as $operation)
{
    $before = $session->published;
    $object = $before->native->object_for($value_file);
    if ($operation === 'missing') {
        unlink($object->path);
    }
    else {
        file_put_contents($object->path, 'invalid object');
    }
    $count = $tool->invocation_count();
    $repair = $session->compile($manifest, $output);
    Module_Test::check(($repair->llvm === $before->llvm) && (($tool->invocation_count() - $count) === 2)
        && ($repair->native->object_for($value_file) !== $object), 'Repair one invalid object and relink');
    Module_Test::run($output, 43);
}

// Full selection compiles every module through the same worker path.
Module_Test::edit($manifest, file_get_contents($manifest) . "\n");
$count = $tool->invocation_count();
$full = $session->compile($manifest, $output);
Module_Test::check(($full->inputs->context->full_rebuild) && (($tool->invocation_count() - $count) === (count($full->llvm->modules) + 1)),
    'Full rebuild selects every file object plus link');

// Worker results join in arbitrary completion order; unaccepted owners clean on last release.
$tasks = \Step_Test::select(\build_native\Native_Builder::class, $full->llvm, null, true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = \build_native\Native_Compiler::compile($task, $tool);
}
$objects = (new \build_native\Native_Join($full->llvm, null, $tasks))->join($results);
Module_Test::check(array_map(static fn($o) => $o->module, $objects) === $full->llvm->modules, 'Native join restores deterministic module order');
$paths = array_map(static fn($o) => $o->path, $objects);
unset($objects, $results);
foreach ($paths as $path) {
    Module_Test::check(!file_exists($path), 'Last unaccepted object reader releases owned file');
}

// A worker failure after one real object compilation releases partial new output
// without altering any previously accepted object or executable.
class Failing_Object_Toolchain extends \prepare_backend\LLVM_Toolchain
{
    public array $attempts = [];

    /** Inject one invalid object input to prove failed batches cannot publish. */
    public function compile_objects(array $requests): void
    {
        foreach ($requests as $request) {
            $this->attempts[] = $request->output;
        }
        if (count($requests) < 2) {
            throw new Exception('Expected multiple test objects');
        }
        $bad = $requests[1];
        $requests[1] = new \prepare_backend\object_compilation('invalid llvm', $bad->output, $bad->configuration);
        parent::compile_objects($requests);
    }
}
$failure = new Failing_Object_Toolchain();
$failure->configuration();
$published = $session->published;
$key = hash_file('sha256', $output);
try {
    \Step_Test::run(new \build_native\Native_Builder($full->llvm, $output, $failure, $full->native, true));
    throw new Exception('Expected worker failure');
}
catch (RuntimeException $error) {
    Module_Test::check(str_contains($error->getMessage(), 'Clang backend operation failed'), $error->getMessage());
}
foreach ($failure->attempts as $path) {
    Module_Test::check(!file_exists($path), 'Partial failed native objects cleaned');
}
foreach ($full->native->objects as $object) {
    Module_Test::check($object->is_current($object->module), 'Failure leaves old objects intact');
}
Module_Test::check(($session->published === $published) && (hash_file('sha256', $output) === $key), 'Object failure preserves accepted state/output');
Module_Test::check($session->compile($manifest, $output)->native === $full->native, 'Repair can reuse the accepted artifact');

// Losing only the executable should require a link, with no object recompilation.
unlink($output);
$count = $tool->invocation_count();
$relinked = $session->compile($manifest, $output);
Module_Test::check((($tool->invocation_count() - $count) === 1) && ($relinked->native->objects === $full->native->objects),
    'Missing executable relinks the existing object set');
Module_Test::run($output, 43);

// File removal excludes its object; a held old snapshot keeps its physical bytes.
$removed_object = $full->native->object_for($value_file);
Module_Test::edit($root . '/src/main.phs', 'return answer();');
Module_Test::edit($root . '/src/answer.phs', 'function answer(): int { return 44; }');
unlink($root . '/src/nested/value.phs');
$removed = $session->compile($manifest, $output);
Module_Test::check((count($removed->native->objects) === 2) && ($removed->native->object_for($value_file) === null)
    && is_file($removed_object->path), 'Removed file is absent from new link without deleting an older snapshot object');
Module_Test::run($output, 44);
$export = $removed->llvm->to_array();
Module_Test::check((count($export['modules']) === 2) && (!isset($export['ir'])), 'Debug export preserves separate file modules');
echo "native modules ok: file units, cross-file declarations, object reuse, missing/tampered repair, full selection, worker join, removal and file ownership\n";
