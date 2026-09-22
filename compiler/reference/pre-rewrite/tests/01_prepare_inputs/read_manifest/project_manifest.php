<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

class Project_Manifest_Test
{
    public static function check_manifest(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function write_manifest_fixture(string $path, string $content): void
    {
        $written = 0;

        self::check_manifest((($written = file_put_contents($path, $content)) !== false), "fixture write failed");
    }

    public static function expect_invalid_manifest(string $content, string $expected): void
    {
        $path = "invalid.json";
        self::write_manifest_fixture($path, $content);
        $rejected = false;
        try {
            \Step_Test::run(new \read_manifest\Manifest_Reader($path));
        }
        catch (Exception $exception) {
            $rejected = str_contains($exception->getMessage(), $expected);
        }
        self::check_manifest($rejected, "missing manifest diagnostic: " . $expected);

        // Repair and re-read in this same process after every rejected input.
        self::write_manifest_fixture($path, '{"source_folders":["src"],"entry":"src/main.phs"}');
        $repaired = \Step_Test::run(new \read_manifest\Manifest_Reader($path));
        self::check_manifest($repaired->entry_path === "src/main.phs", "Reader must recover after invalid input");
    }
}

$path = "../fixtures/three_files/project.json";
$manifest = \Step_Test::run(new \read_manifest\Manifest_Reader($path));
Project_Manifest_Test::check_manifest(count($manifest->source_folder_paths) === 1, "sample must declare one module root");
Project_Manifest_Test::check_manifest($manifest->source_folder_paths[0] === "src", "configured root must stay relative");
Project_Manifest_Test::check_manifest($manifest->entry_path === "src/main.phs", "entry must come from the manifest");
Project_Manifest_Test::check_manifest(is_file($manifest->directory . "/" . $manifest->entry_path), "relative entry must use manifest directory, not cwd");

$session = new \compile\Compiler_Session();
$baseline_manifest = new \read_manifest\Project_Manifest();
$decision = \compile\Input_Selection::select($baseline_manifest, $manifest, true);
Project_Manifest_Test::check_manifest($decision->full_rebuild, "first update must select full rebuild");
Project_Manifest_Test::check_manifest(((int)$session->generation === 0) && ($baseline_manifest->path === ""), "decision must not publish state");

// Seed only a manifest comparison baseline; this is not compiler publication.
$baseline_manifest = $manifest;

$unchanged = \Step_Test::run(new \read_manifest\Manifest_Reader($path));
$decision = \compile\Input_Selection::select($baseline_manifest, $unchanged, false);
Project_Manifest_Test::check_manifest(!$decision->full_rebuild, "unchanged manifest must leave selective work possible");
$original = $manifest->content;
Project_Manifest_Test::write_manifest_fixture($path, $original . "\n \n");
$changed = \Step_Test::run(new \read_manifest\Manifest_Reader($path));
Project_Manifest_Test::check_manifest($changed->content === $original . "\n \n", "retain exact bytes, including whitespace");
$decision = \compile\Input_Selection::select($baseline_manifest, $changed, false);
Project_Manifest_Test::check_manifest($decision->full_rebuild, "any content change must select full rebuild");
Project_Manifest_Test::check_manifest($baseline_manifest->content === $original, "reading replacement must preserve baseline");
Project_Manifest_Test::write_manifest_fixture("other.json", $original);
$other = \Step_Test::run(new \read_manifest\Manifest_Reader("other.json"));
$decision = \compile\Input_Selection::select($baseline_manifest, $other, false);
Project_Manifest_Test::check_manifest($decision->full_rebuild, "manifest path change must select full rebuild");

// A valid inspection runs through LLVM emission without publishing.
$result = $session->compile($path);
Project_Manifest_Test::check_manifest((!$result->completed) && ($result->stopped_before === "build_native"), "valid manifest reaches named and entry body checks and lifetime analysis before LLVM emission");
Project_Manifest_Test::check_manifest(((int)$session->generation === 0) && ($session->published === null) && ($baseline_manifest->content === $original), "inspection must not publish, or mutate the manifest comparison baseline");

Project_Manifest_Test::write_manifest_fixture("paths.json", '{"source_folders":["folder with spaces","../shared"],"entry":"folder with spaces/a=b.phs"}');
$paths = \Step_Test::run(new \read_manifest\Manifest_Reader("paths.json"));
Project_Manifest_Test::check_manifest((count($paths->source_folder_paths) === 2) && ($paths->source_folder_paths[1] === "../shared"), "support multiple roots");
Project_Manifest_Test::check_manifest($paths->entry_path === "folder with spaces/a=b.phs", "preserve path characters");
echo "valid manifest checks passed: disk reads, snapshots, rebuild decisions, and discovery handoff\n";

Project_Manifest_Test::expect_invalid_manifest("", "Invalid JSON:");
Project_Manifest_Test::expect_invalid_manifest("{", "Invalid JSON:");
Project_Manifest_Test::expect_invalid_manifest('{"entry":"\\q"}', "Invalid JSON:");
Project_Manifest_Test::expect_invalid_manifest("null", "Required settings");
Project_Manifest_Test::expect_invalid_manifest("false", "Required settings");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":["src"]}', "Required settings");
Project_Manifest_Test::expect_invalid_manifest('{"entry":"main.phs"}', "Required settings");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":[],"entry":"main.phs"}', "nonempty list");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":[""],"entry":"main.phs"}', "nonempty string path");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":[42],"entry":"main.phs"}', "invalid.json:");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":["src"],"entry":false}', "invalid.json:");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":["src"],"entry":"a\u0000b"}', "NUL bytes");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":["src"],"entry":"main.phs","unknown":1}', "Unknown manifest setting");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":["src","src"],"entry":"main.phs"}', "Duplicate source folder");
Project_Manifest_Test::expect_invalid_manifest('{"source_folders":{"name":"src"},"entry":"main.phs"}', "invalid.json:");
$missing = false;
try {
    \Step_Test::run(new \read_manifest\Manifest_Reader("missing.json"));
}
catch (Exception $exception) {
    $missing = str_contains($exception->getMessage(), "Cannot locate project input");
}
Project_Manifest_Test::check_manifest($missing, "missing input must not become empty/default configuration");
Project_Manifest_Test::write_manifest_fixture($path, $original);
$repaired = \Step_Test::run(new \read_manifest\Manifest_Reader($path));
$decision = \compile\Input_Selection::select($baseline_manifest, $repaired, false);
Project_Manifest_Test::check_manifest(!$decision->full_rebuild, "repair to baseline must not inherit earlier update flag");
echo "project manifest ok: disk reads, validation, exact snapshots, rebuild decisions, and failure isolation\n";
