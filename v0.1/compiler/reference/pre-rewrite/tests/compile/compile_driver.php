<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

class Compile_Driver_Test
{
    public static function check_compile(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }
}

$path = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$first = $session->compile($path);
Compile_Driver_Test::check_compile((!$first->completed) && ($first->stopped_before === 'build_native'), 'Report actual progress');
Compile_Driver_Test::check_compile(($first->inputs->context->full_rebuild) && (count($first->inputs->sources->files) === 3), 'First request must run manifest and discovery');
Compile_Driver_Test::check_compile($session->observed?->inputs === $first->inputs, 'Retain observed inputs');
foreach (['resolutions', 'types', 'bodies', 'lifetimes', 'backend', 'lowered', 'llvm'] as $stage) {
    Compile_Driver_Test::check_compile($session->observed->$stage === $first->$stage,
        'Snapshot shares the completed stage: ' . $stage);
}
Compile_Driver_Test::check_compile($session->observed->symbols === $first->symbols->current,
    'Retain the current symbol store without the per-update change catalog');
Compile_Driver_Test::check_compile(($session->generation === 0) && ($session->published === null), 'Do not publish incomplete compilation');
$original_snapshot = $first->to_json();
$original_manifest = $first->inputs->manifest->content;

$second = $session->compile($path);
Compile_Driver_Test::check_compile(!$second->inputs->context->full_rebuild, 'Successive requests use the same session');
foreach ($second->inputs->sources->files as $file) {
    Compile_Driver_Test::check_compile(($file->change_state === \read_sources\file_change::unchanged) && ($file->needs_recompile), 'Unchanged input still has unfinished compilation work');
}
$source_path = $second->inputs->manifest->directory . '/src/nested/value.phs';
Compile_Driver_Test::check_compile(file_put_contents($source_path, "\n", FILE_APPEND) === 1, 'Edit source fixture');
$changed = $session->compile($path);
$id = $changed->inputs->sources->find_file_id($source_path);
Compile_Driver_Test::check_compile($changed->inputs->sources->file_by_id($id)->change_state === \read_sources\file_change::changed, 'The same driver discovers edits');
Compile_Driver_Test::check_compile((!$changed->inputs->context->full_rebuild) && ($first->to_json() === $original_snapshot), 'Input refresh preserves earlier observations');

// A manifest-only edit sets this request's flag, not the next request's flag.
file_put_contents($path, $original_manifest . "\n");
$full = $session->compile($path);
Compile_Driver_Test::check_compile($full->inputs->context->full_rebuild, 'Manifest changes select full rebuild');
$again = $session->compile($path);
Compile_Driver_Test::check_compile(!$again->inputs->context->full_rebuild, 'The full flag is per request');

// Failure after manifest reading cannot replace the retained observation either.
$retained = $session->observed?->inputs;
$before = $retained->to_json();
file_put_contents($path, '{"source_folders":["missing"],"entry":"src/main.phs"}');
$rejected = false;
try {
    $session->compile($path);
}
catch (Exception $exception) {
    $rejected = str_starts_with($exception->getMessage(), 'Cannot resolve source path:');
}
Compile_Driver_Test::check_compile(($rejected) && ($session->observed?->inputs === $retained) && ($retained->to_json() === $before), 'Failed discovery preserves retained observations');
file_put_contents($path, $retained->manifest->content);
$repaired = $session->compile($path);
Compile_Driver_Test::check_compile((!$repaired->inputs->context->full_rebuild) && (!$repaired->completed), 'Repair resumes from the same baseline without fake completion');

// Real publication establishes the comparison baseline for later inspections.
$built = $session->compile($path, getcwd() . '/program');
$published = $session->published;
$published_before = serialize($published);
Compile_Driver_Test::check_compile(($built->completed) && ($published !== null) && ($session->generation === 1),
    'Publish a complete snapshot through the actual native path');
Compile_Driver_Test::check_compile(($published->native === $built->native)
    && ($session->observed->native === $built->native) && ($published->llvm === $built->llvm)
    && ($published->inputs !== $built->inputs) && ($session->observed->inputs === $built->inputs),
    'Publication shares stage results and separates acknowledged inputs from observed changes');
foreach ($published->inputs->sources->files as $file) {
    $observed_file = $built->inputs->sources->file_by_id($file->id);
    Compile_Driver_Test::check_compile((!$file->needs_recompile) && ($observed_file->needs_recompile)
        && ($file !== $observed_file) && ($file->buffer === $observed_file->buffer),
        'Publication acknowledges work with separate rows and shares the exact source buffers');
}
file_put_contents($path, $built->inputs->manifest->content . "\n");
$published_comparison = $session->compile($path);
$repeat_inspection = $session->compile($path);
Compile_Driver_Test::check_compile(($published_comparison->inputs->context->full_rebuild) && ($repeat_inspection->inputs->context->full_rebuild),
    'Inspections compare against publication, even after observing the edited manifest');
Compile_Driver_Test::check_compile(($session->published === $published) && (serialize($published) === $published_before)
    && ($session->generation === 1) && ($session->observed->inputs === $repeat_inspection->inputs),
    'Inspection replaces a complete observation while leaving publication untouched');

echo "compile driver ok: ordered input work, explicit stopping point, resident observations, rebuild decisions, and failure isolation\n";
