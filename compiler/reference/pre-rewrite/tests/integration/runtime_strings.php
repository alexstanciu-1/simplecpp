<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once __DIR__ . '/../support/lifecycle_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class String_Runtime_Test
{
    /** Capture exact binary output, diagnostics and status from a native probe. */
    public static function execute(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start string probe');
        $out = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $out, $error];
    }

    /** Replace metadata while preserving integrity checks, so rejection tests reach semantic validation. */
    public static function metadata(string $directory, array $metadata): void
    {
        $path = $directory . '/package/manifest.json';
        $manifest = Files::json($path);
        $metadata_path = $directory . '/package/' . $manifest['metadata'];
        Files::write_json($metadata_path, $metadata);
        $manifest['artifacts'][$manifest['metadata']] = hash_file('sha256', $metadata_path);
        Files::write_json($path, $manifest);
        $pointer = Files::json($directory . '/current.json');
        $pointer['manifest_sha256'] = hash_file('sha256', $path);
        Files::write_json($directory . '/current.json', $pointer);
    }
}

$root = getcwd() . '/strings-runtime';
Files::directory($root . '/definitions');
Files::directory($root . '/project/src');
$preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
foreach (glob($preparation . '/definitions/*.json') as $path) {
    Files::write($root . '/definitions/' . basename($path), Files::read($path));
}
Files::write($root . '/packet.hpp', <<<'CPP'
#pragma once
#include <string>
#include <string_view>
#include <cstdio>
#include <cstdlib>
#include <cstdint>
namespace probe {
inline int live = 0;
class alignas(64) packet {
    const packet *identity;
    std::string *owned;
public:
    explicit packet(std::string_view bytes) : identity(this), owned(new std::string(bytes)) {
        ++live; std::fprintf(stderr, "C:%zu\n", owned->size());
    }
    packet(const packet& source) : identity(this), owned(new std::string(*source.owned)) {
        if (source.identity != &source) std::abort();
        ++live; std::fprintf(stderr, "K:%zu\n", owned->size());
    }
    ~packet() {
        if (identity != this) std::abort();
        std::fprintf(stderr, "D:%zu\n", owned->size()); delete owned; --live;
    }
    void display() const {
        if (identity != this) std::abort();
        std::putchar('['); std::fwrite(owned->data(), 1, owned->size(), stdout); std::putchar(']');
    }
};
inline void display(const packet& value) { value.display(); }
inline std::int64_t live_count() { return live; }
}
CPP);
$extra = ['schema_version' => 1, 'types' => [
    ['id' => 'packet', 'kind' => 'runtime_value', 'cpp_name' => 'probe::packet', 'header' => 'packet.hpp', 'storage' => 'inline',
        'language_type' => ['name' => 'packet', 'namespace' => ''],
        'lifecycle' => ['construct' => 'packet.make', 'destroy' => 'packet.destroy', 'copy_construct' => 'packet.copy']],
], 'operations' => [
    ['id' => 'packet.make', 'kind' => 'construct_from_bytes', 'type' => 'packet', 'parameter_type' => 'bytes', 'error_policy' => 'terminate',
        'expose_as' => ['name' => 'make_packet', 'namespace' => ''], 'language_binding' => 'byte_literal'],
    ['id' => 'packet.copy', 'kind' => 'copy_construct', 'type' => 'packet', 'error_policy' => 'terminate'],
    ['id' => 'packet.destroy', 'kind' => 'destroy', 'type' => 'packet', 'error_policy' => 'terminate'],
    ['id' => 'packet.output', 'kind' => 'free_function', 'cpp_name' => 'probe::display', 'header' => 'packet.hpp',
        'parameters' => [['type' => 'packet', 'passing' => 'const_address', 'borrow_scope' => 'call']], 'result_type' => 'void',
        'error_policy' => 'terminate', 'expose_as' => ['name' => 'packet_output', 'namespace' => ''], 'language_binding' => 'echo'],
    ['id' => 'packet.live', 'kind' => 'free_function', 'cpp_name' => 'probe::live_count', 'header' => 'packet.hpp', 'parameters' => [],
        'result_type' => 'native_int', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'packet_live', 'namespace' => '']],
]];
Files::write_json($root . '/definitions/packet.json', $extra);
$config = Files::json($preparation . '/config.json');
$config['definitions_directory'] = $root . '/definitions';
$config['output_directory'] = $root . '/generated';
$config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($preparation, $path)),
    $config['include_directories']), $root];
Files::write_json($root . '/config.json', $config);
$prepared = (new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json');
$manifest_data = Files::json($prepared['manifest']);
$metadata = Files::json(dirname($prepared['manifest']) . '/' . $manifest_data['metadata']);
Check::check((new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json')['status'] === 'reused',
    'The same definitions reuse their prepared runtime package');

// Real strings and a distinct provider-defined type share literal, borrow, copy and output paths.
$manifest = $root . '/project/project.json';
Files::write_json($manifest, ['source_folders' => ['src'], 'entry' => 'src/main.phs']);
Files::write($root . '/project/src/main.phs', 'run(1); run(0); unrelated(); return packet_live();');
Files::write($root . '/project/src/unrelated.phs', 'function unrelated(): void { echo "stable\n"; }');
$source = <<<'PHS'
/** Use both provider types through their declared capabilities and preserve all reached cleanup. */
function run($flag int): void
{
    $text string = "hello\0world\n";
    $copy string = $text;
    echo $text, $copy, '', 'quote\' slash\\ raw\n', "\n\xFF\101\$\tλ\n";
    $value packet = "P\0Q";
    $other packet = $value;
    echo $value, make_packet(""), $other;
    packet_output("argument");
    $again int = 1;
    while ($again) {
        $loop packet = "loop";
        echo $loop;
        $again = 0;
    }
    if ($flag) {
        echo "early\n", make_packet("early");
        return;
    }
    echo "normal\n";
}
PHS;
$body_path = $root . '/project/src/body.phs';
Files::write($body_path, $source);
Files::write($root . '/oracle.cpp', <<<'CPP'
#include "packet.hpp"
#include "lang/php/support/php_string.hpp"
using scpp::string_t; using scpp::php::echo_one; using probe::packet;
static void run(std::int64_t flag) {
    string_t text(std::string_view("hello\0world\n", 12));
    string_t copy = text;
    echo_one(text); echo_one(copy); echo_one(string_t("")); echo_one(string_t("quote' slash\\ raw\\n"));
    echo_one(string_t("\n\xFF" "A$\tλ\n"));
    packet value(std::string_view("P\0Q", 3)); packet other = value;
    probe::display(value); probe::display(packet(std::string_view(""))); probe::display(other);
    probe::display(packet(std::string_view("argument")));
    std::int64_t again = 1;
    while (again) { packet loop(std::string_view("loop")); probe::display(loop); again = 0; }
    if (flag) {
#if STRING_EDIT
        echo_one(string_t("edited\n")); probe::display(packet(std::string_view("edited")));
#else
        echo_one(string_t("early\n")); probe::display(packet(std::string_view("early")));
#endif
        return;
    }
    echo_one(string_t("normal\n"));
}
int main() { run(1); run(0); echo_one(string_t("stable\n")); return probe::live_count(); }
CPP);
$oracles = [];
foreach ([0, 1] as $edit) {
    [$status, , $error] = String_Runtime_Test::execute([$manifest_data['link_driver']['executable'], '--driver-mode=g++',
        '--target=' . $manifest_data['target']['triple'], '-std=c++23', '-I' . $config['include_directories'][0],
        '-DSTRING_EDIT=' . $edit, $root . '/oracle.cpp', '-o', $root . '/oracle']);
    Check::check($status === 0, 'Compile string oracle: ' . $error);
    $oracles[] = String_Runtime_Test::execute([$root . '/oracle']);
}
$session = new \compile\Compiler_Session(runtime_package_path: $config['output_directory']);
$output = $root . '/program';
$first = $session->compile($manifest, $output);
$actual = String_Runtime_Test::execute([$output]);
Check::check($actual === $oracles[0], 'Metadata-driven literals/output match native values and cleanup: ' . json_encode(array_map('base64_encode', [$actual[1], $oracles[0][1], $actual[2], $oracles[0][2]])));
Check::check($actual[0] === 0, 'All second-type resources are destroyed');
Lifecycle_Test::preparation($first);
$id = $first->symbols->current->find_symbol('run', '', \collect_symbols\symbol_kind::function_symbol);
$plan = $first->lowered->for_symbol($id);
$before = serialize($first);
Check::check(str_contains($first->to_json(), 'byte_literal'), 'Binary literal debug exports remain valid JSON');

// Reversed fixed body workers exercise the same paths and exact shared contracts.
$tasks = \Step_Test::select(\lower\Lowerer::class, $first->lifetimes, $first->backend, new \lower\Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new \lower\Lowered_Set(), $tasks))->join($results);
Check::check(($joined->to_json() === $first->lowered->to_json()) && (serialize($first) === $before), 'Literal lowering has deterministic private outputs');
Check::edit($body_path, str_replace('early', 'edited', $source));
$second = $session->compile($manifest, $output);
Check::check(String_Runtime_Test::execute([$output]) === $oracles[1], 'One body increment replaces literal data and matches native execution');
Check::check((!$second->inputs->context->full_rebuild) && ($second->backend === $first->backend)
    && ($second->lowered->for_symbol($id) !== $plan)
    && ($second->native->object_for($plan->source_file_id()) !== $first->native->object_for($plan->source_file_id())),
    'Literal edit selects changed body work under unchanged contracts');
foreach ($first->llvm->modules as $module) {
    if ($module->source_file_id !== $plan->source_file_id()) {
        Check::check(($second->native->object_for($module->source_file_id) === $first->native->object_for($module->source_file_id))
            && ($second->llvm->module_for($module->source_file_id) === $module), 'Reuse unchanged caller and unrelated literal module');
    }
}
Check::check(serialize($first) === $before, 'Increment leaves prior snapshots unchanged');

// Unsupported syntax/contracts are explicit failures, never a provider-name fallback.
$bad_path = $root . '/bad.phs';
foreach ([['echo 7; return 0;', 'No echo contract'], ['$raw literal_bytes = "x"; return 0;', 'literal call operands'], ['echo "hello $name"; return 0;', 'interpolation'],
    ['echo "hello $λ"; return 0;', 'interpolation'],
    ['echo "\\u{41}"; return 0;', 'Unicode escape'], ['echo "unterminated;', 'Unterminated']] as [$bad, $reason]) {
    Files::write($bad_path, $bad);
    Check::rejects(static fn() => (new \compile\Compiler_Session(runtime_package_path: $config['output_directory']))
        ->compile($bad_path, $root . '/bad-program'), $reason);
}
foreach (['span', 'void', 'binding', 'default'] as $fault)
{
    $invalid = $metadata;
    foreach ($invalid['operations'] as &$operation)
    {
        if (($fault === 'default') && (($operation['id'] ?? null) === 'packet.make')) {
            $operation['default_literal'] = true;
            break;
        }
        if (($fault === 'span') && (($operation['language_binding'] ?? null) === 'byte_literal')) {
            $operation['parameters'][0]['abi_indices'] = [1];
            break;
        }
        if (($fault === 'void') && (($operation['language_binding'] ?? null) === 'echo')) {
            $operation['abi']['return_type'] = 'i64';
            break;
        }
        if (($fault === 'binding') && (($operation['language_binding'] ?? null) === 'echo')) {
            $operation['language_binding'] = 'byte_literal';
            break;
        }
    }
    unset($operation);
    String_Runtime_Test::metadata($config['output_directory'], $invalid);
    Check::rejects(static fn() => \load_runtime\Package_Adapter::open($config['output_directory'], $first->backend->runtime->base_catalog),
        match ($fault) {
            'span' => 'parameter passing', 'void' => 'void result ABI',
            'binding' => 'span constructor', 'default' => 'Duplicate language operation'
        });
}
echo "runtime strings ok: real Simple C++ strings, second type via definitions, spans, void borrows, literals/echo/copies, native cleanup, fixed workers and incremental literal replacement\n";
