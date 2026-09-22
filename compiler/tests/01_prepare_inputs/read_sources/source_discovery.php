<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

class Source_Discovery_Test
{
    public static function check_sources(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function write_source_fixture(string $path, string $content): void
    {
        $written = 0;

        self::check_sources((($written = file_put_contents($path, $content)) !== false), "Cannot write fixture: " . $path);
        self::check_sources($written === strlen($content), "Incomplete fixture write");
    }
}

$manifest = \Step_Test::run(new \read_manifest\Manifest_Reader("../fixtures/three_files/project.json"));
$folder_zero = 0;
$folder_one = 1;
$empty = new \read_sources\Source_Set();
$first = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $empty));
Source_Discovery_Test::check_sources((count($first->folders) === 1) && (count($first->files) === 3), "Recursive scan must find all three files");
Source_Discovery_Test::check_sources(count($first->file_ids_in_folder($folder_zero)) === 3, "Nested files belong to the top folder");
foreach ($first->files as $file) {
    Source_Discovery_Test::check_sources(($file->change_state === \read_sources\file_change::added) && ($file->needs_recompile), "First scan selects added files");
}
$before = $first->to_json();
$unchanged = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $first));
foreach ($unchanged->files as $file) {
    Source_Discovery_Test::check_sources($file->change_state === \read_sources\file_change::unchanged, "Second scan must classify unchanged files");
    Source_Discovery_Test::check_sources($file->needs_recompile, "Unchanged metadata must not clear unfinished stage work");
    Source_Discovery_Test::check_sources($first->find_file_id($file->full_path) === $file->id, "Stable path must preserve file ID");
}
Source_Discovery_Test::check_sources(($first->to_json() === $before) && (count($empty->files) === 0), "Discovery must not mutate its inputs");

$decoded = json_decode($unchanged->to_json(), true, 512, JSON_THROW_ON_ERROR);
Source_Discovery_Test::check_sources((count($decoded["files"]) === 3) && (count($decoded["folders"]) === 1), "Debug snapshot must export source membership");
Source_Discovery_Test::check_sources($decoded["files"][0]["change_state"] === "unchanged", "Export must carry actual change state");

// The common entry point reports where work stops without publishing.
$session = new \compile\Compiler_Session();
$result = $session->compile($manifest->path);
Source_Discovery_Test::check_sources((!$result->completed) && ($result->stopped_before === "build_native"), "Compilation must identify the LLVM-emission boundary");
Source_Discovery_Test::check_sources(((int)$session->generation === 0) && ($session->published === null), "Input progress must not publish compilation");

// mtime changes independently of size.
$path = \read_sources\Source_Paths::resolve($manifest->directory, "src/nested/value.phs");
$id = $unchanged->find_file_id($path);
$old = $unchanged->file_by_id($id);
$before_touch = $unchanged->to_json();
usleep(1100000);
Source_Discovery_Test::check_sources(touch($path), "Cannot touch fixture");
usleep(1100000);
$touched = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $unchanged));
Source_Discovery_Test::check_sources($unchanged->to_json() === $before_touch, "Changed rows must not mutate retained objects");
$row = $touched->file_by_id($id);
Source_Discovery_Test::check_sources(($row->mtime !== $old->mtime) && ($row->size === $old->size), "Fixture must change only mtime");
Source_Discovery_Test::check_sources($row->change_state === \read_sources\file_change::changed, "mtime alone must trigger change");

// Deliberately edit within one timestamp second to prove the size check too.
// Retry only if the real filesystem clock crosses a second during the edit.
$baseline = $touched;
$size_proved = false;
$attempt = 0;
$text = "return 1;\n";
while (($attempt < 3) && (!$size_proved))
{
    $old = $baseline->file_by_id($id);
    Source_Discovery_Test::write_source_fixture($path, $text);
    $next = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $baseline));
    $row = $next->file_by_id($id);
    if ($row->mtime === $old->mtime) {
        Source_Discovery_Test::check_sources($row->size !== $old->size, "Fixture must change size");
        Source_Discovery_Test::check_sources($row->change_state === \read_sources\file_change::changed, "Size alone must trigger change");
        $size_proved = true;
    }
    $baseline = $next;
    $text = $text . "\n";
    $attempt = $attempt + 1;
}
Source_Discovery_Test::check_sources($size_proved, "Could not exercise equal-mtime size change on this filesystem");

// One shared dataset: additions, tombstones, and new identity on recreation.
$added_path = $manifest->directory . "/src/new.phs";
Source_Discovery_Test::write_source_fixture($added_path, "function added(): int { return 7; }\n");
Source_Discovery_Test::write_source_fixture($manifest->directory . "/src/notes.txt", "not source code");
$added = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $baseline));
$added_id = $added->find_file_id($added_path);
Source_Discovery_Test::check_sources(((int)$added_id !== 0) && (count($added->files) === 4), "Add source, ignore unrelated extension");
$row = $added->file_by_id($added_id);
Source_Discovery_Test::check_sources($row->change_state === \read_sources\file_change::added, "New path must be added");
$before_removal = $added->to_json();
Source_Discovery_Test::check_sources(@unlink($added_path), "Cannot remove fixture");
$removed = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $added));
Source_Discovery_Test::check_sources($added->to_json() === $before_removal, "Tombstones must not mutate retained live records");
$row = $removed->file_by_id($added_id);
Source_Discovery_Test::check_sources(($row->change_state === \read_sources\file_change::deleted) && (!$row->needs_recompile), "Removed file must be inactive");
Source_Discovery_Test::check_sources(((int)$removed->find_file_id($added_path) === 0) && (count($removed->file_ids_in_folder($folder_zero)) === 3), "Live indexes must exclude deleted rows");
Source_Discovery_Test::check_sources($row->full_path === $added_path, "Tombstone must retain origin");
$again = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $removed));
Source_Discovery_Test::check_sources(count($again->removed_file_ids) === 1, "Pending removals must not duplicate");
$pending = serialize($again);
$acknowledged = $again->acknowledged();
Source_Discovery_Test::check_sources((serialize($again) === $pending) && ($acknowledged !== $again)
    && ($acknowledged->removed_file_ids === []) && ($acknowledged->entry_file()->id === $again->entry_file()->id)
    && ($acknowledged->file_ids_in_folder(0) === $again->file_ids_in_folder(0))
    && ($acknowledged->file_by_id($added_id) === $again->file_by_id($added_id)),
    'Acknowledgment preserves pending input, entry identity, live indexes and shared tombstones');
foreach ($again->files as $file) {
    $accepted = $acknowledged->file_by_id($file->id);
    Source_Discovery_Test::check_sources((!$accepted->needs_recompile) && ($accepted->buffer === $file->buffer)
        && ($accepted->change_state === $file->change_state)
        && ($file->needs_recompile ? $accepted !== $file : $accepted === $file),
        'Only pending rows are replaced; unchanged rows and buffers stay shared');
}
$acknowledged_again = $acknowledged->acknowledged();
Source_Discovery_Test::check_sources($acknowledged_again->files === $acknowledged->files,
    'Already acknowledged rows need no duplicate records');
Source_Discovery_Test::write_source_fixture($added_path, "return 9;\n");
$recreated = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $again));
Source_Discovery_Test::check_sources($recreated->find_file_id($added_path) !== $added_id, "Recreated path must not reuse retired identity");
Source_Discovery_Test::check_sources((count($recreated->files) === 5) && (count($recreated->file_ids_in_folder($folder_zero)) === 4), "Retain old tombstone outside live index");

// Root reorder changes folder positions, not logical file identity.
Source_Discovery_Test::check_sources(@mkdir($manifest->directory . "/other"), "Cannot create second root");
Source_Discovery_Test::write_source_fixture($manifest->directory . "/other/helper.phs", "return 2;\n");
Source_Discovery_Test::write_source_fixture($manifest->directory . "/two.json", "{\"source_folders\":[\"src\",\"other\"],\"entry\":\"src/main.phs\"}");
$two_manifest = \Step_Test::run(new \read_manifest\Manifest_Reader($manifest->directory . "/two.json"));
$two = \Step_Test::run(new \read_sources\Source_Discovery($two_manifest, $recreated));
Source_Discovery_Test::check_sources((count($two->file_ids_in_folder($folder_zero)) === 4) && (count($two->file_ids_in_folder($folder_one)) === 1), "Each module indexes its own file IDs");
Source_Discovery_Test::write_source_fixture($manifest->directory . "/two.json", "{\"source_folders\":[\"other\",\"src\"],\"entry\":\"src/main.phs\"}");
$two_manifest = \Step_Test::run(new \read_manifest\Manifest_Reader($manifest->directory . "/two.json"));
$reordered = \Step_Test::run(new \read_sources\Source_Discovery($two_manifest, $two));
$row = $reordered->file_by_id($id);
Source_Discovery_Test::check_sources(((int)$row->top_folder_index === 1) && (count($reordered->file_ids_in_folder($folder_zero)) === 1), "Folder indexes must follow current manifest order");
Source_Discovery_Test::check_sources($reordered->find_file_id($path) === $id, "Root reorder must preserve path identity");
$single = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $reordered));
$other_id = $reordered->find_file_id($manifest->directory . "/other/helper.phs");
$row = $single->file_by_id($other_id);
Source_Discovery_Test::check_sources(($row->change_state === \read_sources\file_change::deleted) && ((int)$row->top_folder_index === -1), "Removed root must withdraw its files without dangling ownership");

// A failed candidate must leave the previous snapshot usable.
$before = $single->to_json();
Source_Discovery_Test::write_source_fixture($manifest->directory . "/bad.json", "{\"source_folders\":[\"src\",\"src/nested\"],\"entry\":\"src/main.phs\"}");
$bad = \Step_Test::run(new \read_manifest\Manifest_Reader($manifest->directory . "/bad.json"));
$rejected = false;
try {
    \Step_Test::run(new \read_sources\Source_Discovery($bad, $single));
}
catch (Exception $exception) {
    $rejected = str_starts_with($exception->getMessage(), "Overlapping source roots:");
}
Source_Discovery_Test::check_sources(($rejected) && ($single->to_json() === $before), "Failed discovery must not mutate previous state");
echo "source discovery ok: recursive membership, mtime and size independently, stable IDs, additions/removals/recreation, folder indexes, exports, and input purity\n";
