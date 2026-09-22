<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/bootstrap.php';

use compile\Compiler_Session;

class Incremental_Policy_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    public static function edit(string $path, string $source): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $source);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }
}

$manifest = '../fixtures/three_files/project.json';
$path = realpath(dirname($manifest)) . '/src/nested/value.phs';
$output = getcwd() . '/program';
$inspection = new Compiler_Session();
$native = new Compiler_Session();
$base = 'function value(): int { return 42; }';
$edited = 'function value(): int { return 43; }';
$added = $edited . ' function extra(): int { return 7; }';
$signature = $edited . ' function extra(): void { return; }';
$renamed = $edited . ' function renamed(): void { return; }';
$previous_inspection = null;
$previous_native = null;
foreach ([
        ['initial', $base, true],
        ['unchanged', $base, false],
        ['body edit', $edited, false],
        ['addition', $added, true],
        ['signature change', $signature, true],
        ['rename', $renamed, true],
        ['removal', $edited, true],
        ['body edit after fallback', $base, false],
    ] as [$label, $source, $full])
{
    if (file_get_contents($path) !== $source) {
        Incremental_Policy_Test::edit($path, $source);
    }
    $inspected = $inspection->compile($manifest);
    $built = $native->compile($manifest, $output);
    Incremental_Policy_Test::check(($inspected->inputs->context->full_rebuild === $full)
        && ($built->inputs->context->full_rebuild === $full), $label . ': both modes apply the same eligibility rule');
    Incremental_Policy_Test::check($inspected->llvm->ir_by_file() === $built->llvm->ir_by_file(),
        $label . ': both modes reach identical LLVM through the common stages');
    Incremental_Policy_Test::check((!$inspected->completed) && ($inspected->native === null)
        && ($inspection->published === null) && ($built->completed), $label . ': only native requests publish');
    if ($previous_inspection !== null) {
        $id = $inspected->types->entry->symbol->symbol_id;
        Incremental_Policy_Test::check((($inspected->llvm->function_for($id) === $previous_inspection->llvm->function_for($id)) === !$full)
            && (($built->llvm->function_for($id) === $previous_native->llvm->function_for($id)) === !$full),
            $label . ': full selection refreshes unchanged entry work; body-only selection reuses it');
    }
    $previous_inspection = $inspected;
    $previous_native = $built;
}

// Both modes preserve their complete baseline when an unsupported edit fails.
$observed = $inspection->observed;
$published = $native->published;
$native_observed = $native->observed;
$generation = $native->generation;
$before = serialize([$observed, $published, $native_observed]);
$artifact = hash_file('sha256', $output);
Incremental_Policy_Test::edit($path, $base . ' function invalid(): void { return 1; }');
foreach ([[$inspection, null], [$native, $output]] as [$session, $destination])
{
    $failed = false;
    try {
        $session->compile($manifest, $destination);
    }
    catch (\diagnostics\Source_Error $error) {
        $failed = str_contains($error->getMessage(), 'Cannot return a value');
    }
    Incremental_Policy_Test::check($failed, 'Invalid added declaration is rejected');
}
Incremental_Policy_Test::check(($inspection->observed === $observed) && ($native->published === $published)
    && ($native->observed === $native_observed) && ($native->generation === $generation)
    && (serialize([$observed, $published, $native_observed]) === $before)
    && (hash_file('sha256', $output) === $artifact), 'Failed fallback preserves snapshots and executable');
Incremental_Policy_Test::edit($path, $edited);
$repair = $inspection->compile($manifest);
$built = $native->compile($manifest, $output);
Incremental_Policy_Test::check((!$repair->inputs->context->full_rebuild) && (!$built->inputs->context->full_rebuild)
    && ($repair->llvm->ir_by_file() === $built->llvm->ir_by_file()), 'Repair resumes selective work from the accepted baseline');
echo "incremental policy ok: shared inspection/native gate, body reuse, definition fallback, equal LLVM and failure/repair\n";
