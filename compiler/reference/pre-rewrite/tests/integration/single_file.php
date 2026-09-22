<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';

use Body_Test_Stages as Check;
use read_sources\Source_Set;

class Single_File_Test
{
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
            2 => ['file', '/dev/null', 'w']], $pipes);
        Check::check(is_resource($process), 'Start single-file executable');
        return proc_close($process);
    }
}

mkdir('one file');
$directory = realpath('one file');
$source = $directory . '/main.phs';
file_put_contents($source, <<<'PHS'
function answer($value int): int
{
    if ($value) {
        return $value + 2;
    }
    return 0;
}
return answer(40);
PHS);
file_put_contents($directory . '/neighbor.phs', 'This must never be tokenized!');
file_put_contents($directory . '/project.json', 'This must never be decoded!');
symlink('/missing-neighbor', $directory . '/unrelated-link');
$files_before = scandir($directory);

$manifest = Step_Test::run(new \read_manifest\Manifest_Reader($source));
Check::check(($manifest->content === null) && ($manifest->source_folder_paths === [])
    && ($manifest->source_file_paths === ['main.phs']) && ($manifest->entry_path === 'main.phs'),
    'Virtual manifest retains selection, not source bytes or invented JSON');
$fixed = serialize($manifest);
$sources = Step_Test::run(new \read_sources\Source_Discovery($manifest, new Source_Set()));
$task = new \read_sources\source_scan_task(0, 0, $directory, '', ['main.phs']);
$scan = \read_sources\Source_Scanner::scan($task);
$candidate = new Source_Set();
$candidate->folders = $sources->folders;
Check::check((count($scan->files) === 1) && ($scan->directories === []) && ($scan->task === $task),
    'Explicit scan observes only its selected file, with exact worker provenance');
$next = (new \read_sources\Source_Scan_Join($candidate, new Source_Set(), [$task]))->join([$scan]);
$candidate->refresh_indexes();
Check::check(($next === []) && ($candidate->find_file_id($source) === $sources->find_file_id($source))
    && (serialize($manifest) === $fixed), 'Explicit scans reuse the ordinary identity join without mutating inputs');

$inspection = (new \compile\Compiler_Session())->compile($source);
Check::check((!$inspection->completed) && (scandir($directory) === $files_before),
    'API inspection creates neither a manifest nor an executable');
$session = new \compile\Compiler_Session();
$first = $session->compile($source, default_output: true);
$output = $directory . '/main';
Check::check(($first->completed) && ($first->inputs->context->full_rebuild) && ($first->native->path === $output)
    && (Single_File_Test::run($output) === 42), 'The common pipeline builds and executes a one-file project');
$retained = serialize($first);
$id = $first->inputs->sources->find_file_id($source);
Check::edit($source, str_replace('$value + 2', '$value + 3', file_get_contents($source)));
$second = $session->compile($source, default_output: true);
Check::check((!$second->inputs->context->full_rebuild) && (Single_File_Test::run($output) === 43)
    && ($second->inputs->sources->find_file_id($source) === $id) && (serialize($first) === $retained),
    'A body edit uses incremental selection, stable source identity and unchanged retained inputs');
$fresh = (new \compile\Compiler_Session())->compile($source);
Check::check($fresh->llvm->to_array() === $second->llvm->to_array(), 'Fresh and incremental one-file LLVM agree');

// Naming consumes actual canonical triples from Clang; no Windows runtime/linker is needed for this probe.
foreach (['x86_64-pc-windows-msvc', 'x86_64-w64-mingw32'] as $target)
{
    $configuration_path = getcwd() . '/target.json';
    file_put_contents($configuration_path, json_encode(['clang' => 'clang', 'target' => $target], JSON_THROW_ON_ERROR));
    $configuration = (new \prepare_backend\LLVM_Toolchain($configuration_path))->configuration();
    Check::check(\build_native\Native_Paths::default_output($sources, $configuration) === $output . '.exe',
        'Windows executable suffix follows the probed target, independently of the Linux host');
}

echo "single file ok: virtual input, isolated workers, native execution, body increment, retained purity and Windows target naming\n";
