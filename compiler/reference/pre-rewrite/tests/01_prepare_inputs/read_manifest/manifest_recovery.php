<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

class Manifest_Recovery_Test
{
    public static function check_recovery(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function write_recovery_fixture(string $path, string $text): void
    {
        $written = 0;

        self::check_recovery((($written = file_put_contents($path, $text)) !== false) && ($written === strlen($text)), "Fixture write failed");
    }
}

// Prove malformed-JSON recovery through the real reader and retained input state.

$path = "../fixtures/three_files/project.json";
$session = new \compile\Compiler_Session();
$baseline = $session->compile($path);
$before = $baseline->to_json();
$original = $baseline->inputs->manifest->content;
$invalid = ["", "{", '{"entry":"\q"}', "null trailing"];
$rejected = false;
foreach ($invalid as $text)
{
    $retained = $session->observed?->inputs;
    Manifest_Recovery_Test::write_recovery_fixture($path, $text);
    $rejected = false;
    try {
        $session->compile($path);
    }
    catch (Exception $exception) {
        $rejected = str_contains($exception->getMessage(), $path . ": Invalid JSON:");
    }
    Manifest_Recovery_Test::check_recovery(($session->observed?->inputs === $retained) && ($session->generation === 0), "Failed compilation must preserve observations and published generation");
    Manifest_Recovery_Test::check_recovery($rejected, "Malformed manifest must be a catchable path/JSON diagnostic");
    Manifest_Recovery_Test::check_recovery($baseline->to_json() === $before, "Rejected input must not mutate retained state");
    Manifest_Recovery_Test::write_recovery_fixture($path, $original);
    $repaired = $session->compile($path);
    Manifest_Recovery_Test::check_recovery(!$repaired->inputs->context->full_rebuild, "Repaired manifest must use the retained baseline");
    Manifest_Recovery_Test::check_recovery(count($repaired->inputs->sources->files) === 3, "Source discovery must work after JSON failure");
}
echo "manifest recovery ok: malformed input, path/JSON diagnostics, retained-state purity, and repaired refreshes in one process\n";
