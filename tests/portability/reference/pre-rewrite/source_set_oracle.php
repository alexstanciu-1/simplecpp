<?php
declare(strict_types=1);

// Host-only comparison with the frozen pre-adaptation owner; never converted.
require __DIR__ . '/../../compiler/bootstrap.php';
$baseline = file_get_contents(__DIR__ . '/reference/source_set_before.php');
$baseline = str_replace('class Source_Set implements', 'class Baseline_Source_Set implements', $baseline);
eval(substr($baseline, strlen('<?php')));

function check_source_set(bool $condition, string $message): void {
    if (!$condition) { throw new RuntimeException($message); }
}

$strings = ['', '/', 'é😀', "quote\"\\line\n", "\0\x01\x1f", "\u{2028}\u{2029}"];
for ($code = 0; $code < 128; $code++) { $strings[] = chr($code); }
foreach ($strings as $value) {
    check_source_set(read_sources\Source_Json::quote($value) === json_encode($value, JSON_THROW_ON_ERROR), 'JSON string spelling');
}
foreach (["\x80", "\xc0\xaf", "\xed\xa0\x80", "\xf4\x90\x80\x80"] as $value) {
    try { read_sources\Source_Json::quote($value); throw new RuntimeException('Malformed UTF-8 accepted'); }
    catch (JsonException $error) { check_source_set($error->getCode() === JSON_ERROR_UTF8, 'JSON error code'); }
}
for ($variant = 0; $variant < 30; $variant++) {
    $current = new read_sources\Source_Set();
    $original = new read_sources\Baseline_Source_Set();
    for ($folder_index = 0; $folder_index < 2; $folder_index++) {
        $folder = new read_sources\source_folder();
        $folder->path = $strings[($variant + $folder_index) % count($strings)];
        $folder->resolved_path = '/root/' . $folder_index;
        $folder->file_names = match ($variant % 3) { 0 => null, 1 => [], default => ['a.phs', 'é😀.phs'] };
        $current->folders[] = $folder;
        $original->folders[] = $folder;
    }
    for ($index = 0; $index < 3; $index++) {
        $row = new read_sources\source_file();
        $row->id = 10 + 7 * $index;
        $row->top_folder_index = $index % 2;
        $row->full_path = '/root/' . $index;
        $row->relative_path = $strings[$variant % count($strings)];
        $row->mtime = 100 + $variant;
        $row->size = $variant;
        $row->needs_recompile = ($index + $variant) % 2 === 0;
        $row->change_state = $index === 2 ? read_sources\file_change::deleted : read_sources\file_change::changed;
        $row->buffer = new read_sources\Source_Buffer($row->id, $row->full_path, $row->mtime, 'bytes');
        $current->files[] = $row;
        $original->files[] = $row;
    }
    $current->removed_file_ids = $original->removed_file_ids = [24];
    $current->next_file_id = $original->next_file_id = 100;
    $current->refresh_indexes(); $original->refresh_indexes();
    $current->set_entry_file(10); $original->set_entry_file(10);
    check_source_set($current->to_json() === $original->to_json(), 'Source schema spelling');
    $before = serialize($current);
    $accepted = $current->acknowledged(); $expected = $original->acknowledged();
    check_source_set(serialize($current) === $before, 'Old snapshot mutated');
    check_source_set($accepted->to_json() === $expected->to_json(), 'Acknowledged export');
    foreach ($current->files as $index => $row) {
        check_source_set(($accepted->files[$index] === $row) === !$row->needs_recompile, 'Row sharing');
        check_source_set($accepted->files[$index]->buffer === $row->buffer, 'Buffer sharing');
    }
    check_source_set($accepted->file_ids_in_folder(0) === $expected->file_ids_in_folder(0), 'Folder membership');
    check_source_set($accepted->find_file_id('/root/2') === 0, 'Tombstone live index');
    check_source_set($accepted->next_file_id === 100, 'Allocation state');
}
echo "Source_Set: 30 baseline comparisons, JSON quoting and snapshot identity passed\n";
