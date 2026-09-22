<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

class Manifest_Export_Test
{
    public static function check_export(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function check_manifest_export(\read_manifest\Project_Manifest $manifest): void
    {
        $before = $manifest->content;
        $json = $manifest->to_json();

        // Export shape mirrors Project_Manifest's five fields.

        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::check_export(count($decoded) === 5, "export must contain every snapshot field");
        self::check_export($decoded["path"] === $manifest->path, "manifest path must survive export");
        self::check_export($decoded["content"] === $before, "exact source bytes must survive JSON escaping");
        self::check_export($decoded["directory"] === $manifest->directory, "resolved directory must survive export");
        self::check_export($decoded["entry_path"] === $manifest->entry_path, "entry path must survive export");
        $roots = $decoded["source_folder_paths"];
        self::check_export(count($roots) === count($manifest->source_folder_paths), "root count must survive export");
        foreach ($roots as $index => $root) {
            $position = $index;
            self::check_export($root === $manifest->source_folder_paths[$position], "root order and spelling must survive export");
        }
        self::check_export(($manifest->content === $before) && ($manifest->to_json() === $json), "export must be repeatable and leave the snapshot unchanged");
    }
}

$manifest = \Step_Test::run(new \read_manifest\Manifest_Reader("../fixtures/three_files/project.json"));
Manifest_Export_Test::check_manifest_export($manifest);
echo $manifest->to_json(), "\n";

// Exercise JSON escaping and multiple roots through a real read as well.
$content = "{\n\t\"source_folders\":[\"folder with spaces\",\"../shared\"],\n\t\"entry\":\"src/a\\\"b\\\\c.phs\"\n}\n";
$written = 0;

Manifest_Export_Test::check_export((($written = file_put_contents("export_input.json", $content)) !== false), "fixture write failed");
$other = \Step_Test::run(new \read_manifest\Manifest_Reader("export_input.json"));
Manifest_Export_Test::check_manifest_export($other);
Manifest_Export_Test::check_export($other->content === $content, "reader must retain tabs and newlines");

// The same method can inspect an unfinished/default result.
$empty = new \read_manifest\Project_Manifest();
Manifest_Export_Test::check_manifest_export($empty);
echo "manifest JSON export ok: actual reader output, escaping, root order, empty snapshot, and repeatability\n";
