<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once __DIR__ . '/../support/lifecycle_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Runtime_Console_Test
{
    /** Exercise the composed compiler output with both advertised provider LTO artifacts. */
    public static function lto(\compile\Compile_Result $result, array $config, string $root): void
    {
        $tools = new \runtime_preparation\Clang_Toolchain($config, $root);
        $runtime = $result->backend->runtime;
        foreach (['full' => \load_runtime\runtime_module_kind::full_lto, 'thin' => \load_runtime\runtime_module_kind::thin_lto] as $mode => $kind)
        {
            $inputs = [];
            foreach ($result->llvm->modules as $index => $module)
            {
                $source = $root . '/caller-' . $index . '.ll';
                $bitcode = $root . '/caller-' . $mode . '-' . $index . '.bc';
                Files::write($source, $module->ir);
                $tools->run([$runtime->link_driver, ...$runtime->link_arguments,
                    '-O2', '-flto=' . $mode, '-c', $source, '-o', $bitcode]);
                $inputs[] = $bitcode;
            }

            // Preserve the prepared bitcode at the linker boundary, including ThinLTO summaries.
            $arguments = [];
            foreach ([...$inputs, ...$runtime->modules_for($kind)] as $input) {
                $arguments[] = '-Xlinker';
                $arguments[] = $input;
            }
            $output = $root . '/program-' . $mode;
            $tools->run([$runtime->link_driver, ...$runtime->link_arguments, '-O2', '-flto=' . $mode,
                '--ld-path=' . Files::executable('/', 'ld.lld-18'), ...$arguments, '-o', $output]);
            Check::check(self::execute([$output], "41\r\n") === [0, "answer=42;bytes=9\ndone\n", ''],
                $mode . ' LTO executes the complete console composition');
            [$status, , $error] = self::execute([$output], "invalid\n");
            Check::check(($status !== 0) && str_contains($error, 'runtime operation string.to_int failed:'),
                $mode . ' LTO preserves the terminate boundary');
        }
    }

    /** File-backed streams preserve binary input and avoid pipe capacity deadlocks. */
    public static function execute(array $command, string $input = '', bool $read_error = false): array
    {
        $in = tmpfile();
        $out = tmpfile();
        $error = tmpfile();
        fwrite($in, $input);
        rewind($in);

        // On the proof host, reading a directory produces a real stdio read error.
        $process = proc_open($command, [0 => $read_error ? ['file', __DIR__, 'r'] : $in, 1 => $out, 2 => $error], $pipes);
        Check::check(is_resource($process), 'Start console probe');
        $status = proc_close($process);
        rewind($out);
        rewind($error);
        $result = [$status, stream_get_contents($out), stream_get_contents($error)];
        fclose($in);
        fclose($out);
        fclose($error);
        return $result;
    }
}

$root = getcwd() . '/console-runtime';
$preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
Files::directory($root . '/project/src');
$config = Files::json($preparation . '/config.json');
$config['definitions_directory'] = $preparation . '/definitions';
$config['output_directory'] = $root . '/generated';
$config['include_directories'] = array_map(static fn($path) => realpath(Files::path($preparation, $path)), $config['include_directories']);
Files::write_json($root . '/config.json', $config);
$prepared = (new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$package = Files::json($prepared['manifest']);
$metadata = Files::json(dirname($prepared['manifest']) . '/metadata.json');
$operations = array_column($metadata['operations'], null, 'id');
Check::check(isset($package['inputs']['headers'][realpath($preparation . '/include/scpp_provider/strings.hpp')])
    && isset($package['inputs']['headers'][realpath($preparation . '/include/scpp_provider/console.hpp')]),
    'Provider implementations participate in preparation invalidation');
Check::check(($operations['string.to_int']['conversion_purpose'] === 'explicit_cast')
    && ($operations['string.to_int']['parameters'][0]['borrow_scope'] === 'call')
    && ($operations['console.input_line']['result']['passing'] === 'caller_storage'),
    'Metadata owns the conversion, borrowed argument and owned input result');
$pointer = Files::read($config['output_directory'] . '/current.json');
$reuse = (new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
Check::check(($reuse['status'] === 'reused') && ($reuse['clang_commands'] === 3), 'Unchanged provider package is reused');

// Compose input, conversion, source calls, arithmetic, owned concatenation and output.
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'run(); stable(); return 0;');
Files::write($root . '/project/src/stable.phs', <<<'PHS'
function stable(): void
{
    echo "done\n";
}
PHS);
$source = <<<'PHS'
/** Read and format one calculation through provider operations. */
function run(): void
{
    $line string = input_line();
    $value int = int_from_string($line);
    $message string = string_concat(prefix(), forward_format(add_delta($value)));
    $copy string = $message;
    echo $copy, ";bytes=", string_from_int(string_byte_length($message)), "\n";
}

function prefix(): string { return "answer="; }

function format_result($value int): string
{
    $text string = string_from_int($value);
    return $text;
}

function forward_format($value int): string
{
    return format_result($value);
}

function add_delta($value int): int
{
    return $value + 1;
}
PHS;
$path = $root . '/project/src/body.phs';
Files::write($path, $source);
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$first = $session->compile($manifest, $root . '/program');
foreach (["41\n", "41\r\n", '41'] as $input) {
    Check::check(Runtime_Console_Test::execute([$root . '/program'], $input) === [0, "answer=42;bytes=9\ndone\n", ''],
        'Composed console flow accepts LF, CRLF and final unterminated lines');
}
Runtime_Console_Test::lto($first, $config, $root);
$before = serialize($first);
Lifecycle_Test::preparation($first);
$tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $first->symbols->current, $first->resolutions,
    $first->types, new \check_bodies\Body_Set(), true);
$results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($tasks));
$joined = (new \check_bodies\Body_Join($first->symbols->current, $first->resolutions, $first->types,
    new \check_bodies\Body_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->bodies->to_json()) && (serialize($first) === $before),
    'Reversed console body work has private outputs and deterministic joins');

// One body edit replaces its module while retaining fixed contracts and unrelated output.
$id = $first->symbols->current->find_symbol('run', '', \collect_symbols\symbol_kind::function_symbol);
$old = $first->lowered->for_symbol($id);
Check::edit($path, str_replace('add_delta($value)', 'add_delta($value + 1)', $source));
$second = $session->compile($manifest, $root . '/program');
Check::check(Runtime_Console_Test::execute([$root . '/program'], "41\n") === [0, "answer=43;bytes=9\ndone\n", ''],
    'One incremental body replacement changes the composed result');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->lowered->for_symbol($id) !== $old), 'Increment reuses prepared contracts and replaces selected work');
foreach ($first->llvm->modules as $module) {
    if ($module->source_file_id !== $old->source_file_id()) {
        Check::check(($second->llvm->module_for($module->source_file_id) === $module)
            && ($second->native->object_for($module->source_file_id) === $first->native->object_for($module->source_file_id)),
            'Unchanged callers retain LLVM and native objects');
    }
}
Check::check((serialize($first) === $before) && (Files::read($config['output_directory'] . '/current.json') === $pointer),
    'Application compilation preserves prior snapshots and never regenerates the provider');

// The strict parser preserves the runtime contract, including complete consumption and range checks.
Files::write($root . '/parse.phs', 'echo string_from_int(int_from_string(input_line())); return 0;');
(new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))->compile($root . '/parse.phs', $root . '/parse');
foreach (['0' => '0', '-0' => '0', '0012' => '12', '-42' => '-42',
    '9223372036854775807' => '9223372036854775807', '-9223372036854775808' => '-9223372036854775808'] as $input => $expected) {
    Check::check(Runtime_Console_Test::execute([$root . '/parse'], $input . "\n") === [0, $expected, ''], 'Strict integer round trip: ' . $input);
}
foreach (['', ' ', ' 1', '1 ', '+1', '-', '1x', '1.5', '0x10', "1\0", '9223372036854775808', '-9223372036854775809'] as $input) {
    [$status, $out, $error] = Runtime_Console_Test::execute([$root . '/parse'], $input . "\n");
    Check::check(($status !== 0) && ($out === '') && str_contains($error, 'runtime operation string.to_int failed:'),
        'Invalid integer terminates at its configured bridge: ' . bin2hex($input));
}
foreach ([false, true] as $read_error) {
    [$status, $out, $error] = Runtime_Console_Test::execute([$root . '/parse'], '', $read_error);
    Check::check(($status !== 0) && ($out === '') && str_contains($error, 'runtime operation console.input_line failed:')
        && str_contains($error, $read_error ? 'read error' : 'end of input'), 'EOF and real read errors terminate clearly');
}

// Input and concatenation preserve arbitrary bytes; byte length is not codepoint length.
Files::write($root . '/bytes.phs', <<<'PHS'
$line string = input_line();
$joined string = string_concat(string_concat("", $line), "é");
echo $line, "|", $joined, "|", string_from_int(string_byte_length($joined));
return 0;
PHS);
(new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))->compile($root . '/bytes.phs', $root . '/bytes');
foreach ([['', "\n"], ["a\0b", "a\0b\r\n"], ["\xff\x80", "\xff\x80\n"], ["x\r", "x\r"],
    [str_repeat('z', 100000), str_repeat('z', 100000) . "\n"]] as [$bytes, $input]) {
    $expected = $bytes . '|' . $bytes . 'é|' . (strlen($bytes) + strlen('é'));
    Check::check(Runtime_Console_Test::execute([$root . '/bytes'], $input) === [0, $expected, ''], 'Input/concat/length preserve bytes');
}
Files::write($root . '/order.phs', 'echo string_concat(input_line(), input_line()); return 0;');
(new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))->compile($root . '/order.phs', $root . '/order');
Check::check(Runtime_Console_Test::execute([$root . '/order'], "left\nright\nignored\n") === [0, 'leftright', ''],
    'Consecutive inputs execute left to right and consume exactly one line each');
[$status, , $error] = Runtime_Console_Test::execute([$root . '/order'], "left\n");
Check::check(($status !== 0) && str_contains($error, 'end of input'), 'Reading again after the final line reports EOF');

echo "runtime console ok: strict parse, line input, concat, byte length, composed execution, fixed workers and incremental replacement\n";
