<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

class Boundaries_Test
{
    public static function check_boundary(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }
}

// Native PHP can enforce the intended manifest shape without coercing inputs.
foreach ([
        '{"source_folders":[42],"entry":"src/main.phs"}',
        '{"source_folders":[true],"entry":"src/main.phs"}',
        '{"source_folders":[{}],"entry":"src/main.phs"}',
        '{"source_folders":{"0":"src"},"entry":"src/main.phs"}',
        '{"source_folders":"src","entry":"src/main.phs"}',
        '{"source_folders":["src"],"entry":42}',
        '[]',
    ] as $invalid)
{
    $rejected = false;
    try {
        \read_manifest\Manifest_Syntax::parse('invalid.json', $invalid);
    }
    catch (Exception $exception) {
        $rejected = true;
    }
    Boundaries_Test::check_boundary($rejected, 'Wrong JSON shape must be rejected');
}

$manifest = \Step_Test::run(new \read_manifest\Manifest_Reader('../fixtures/three_files/project.json'));
$sources = \Step_Test::run(new \read_sources\Source_Discovery($manifest, new \read_sources\Source_Set()));
foreach ([-1, 1] as $index)
{
    $rejected = false;
    try {
        $sources->folder_by_index($index);
    }
    catch (Exception $exception) {
        $rejected = true;
    }
    Boundaries_Test::check_boundary($rejected, 'Invalid folder index must be rejected');
}
foreach ([-1, 0, count($sources->files) + 1] as $id)
{
    $rejected = false;
    try {
        $sources->file_by_id($id);
    }
    catch (Exception $exception) {
        $rejected = true;
    }
    Boundaries_Test::check_boundary($rejected, 'Invalid file ID must be rejected');
}

$empty = new \read_sources\Source_Set();
$empty->next_file_id = \read_sources\MAX_SOURCE_FILE_ID + 1;
$rejected = false;
try {
    \Step_Test::run(new \read_sources\Source_Discovery($manifest, $empty));
}
catch (Exception $exception) {
    $rejected = $exception->getMessage() === 'Source file ID space exhausted';
}
Boundaries_Test::check_boundary(($rejected) && ($empty->files === []), 'ID exhaustion must fail without changing retained state');

// Export failure is explicit and does not alter the owner's data.
$manifest->content = "\xff";
$rejected = false;
try {
    $manifest->to_json();
}
catch (Exception $exception) {
    $rejected = str_starts_with($exception->getMessage(), 'Cannot export project manifest:');
}
Boundaries_Test::check_boundary(($rejected) && ($manifest->content === "\xff"), 'Encoding failure must preserve the snapshot');
$manifest->content = 'repaired';
Boundaries_Test::check_boundary(json_decode($manifest->to_json(), true, 512, JSON_THROW_ON_ERROR)['content'] === 'repaired', 'Export must recover');

// Real full/selective resolution selection is covered in symbol_resolution.php.
echo "PHP boundaries ok: strict manifest shapes, lookup bounds, ID exhaustion and export recovery\n";
