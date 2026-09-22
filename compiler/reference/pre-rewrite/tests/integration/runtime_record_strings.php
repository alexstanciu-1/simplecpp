<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/body_support.php';
require_once dirname(__DIR__, 2) . '/src-runtime-preparation/bootstrap.php';

use Body_Test_Stages as Check;
use runtime_preparation\Files;

final class Record_String_Test
{
    /** Execute a produced native artifact and retain exact status/output for the proof. */
    public static function run(array $command): array
    {
        $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        Check::check(is_resource($process), 'Start native record proof');
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        return [proc_close($process), $stdout, $stderr];
    }
}

// Prepare the real string contracts together with an independently declared provider record.
$root = getcwd() . '/record-string-proof';
Files::directory($root . '/definitions');
$preparation = dirname(__DIR__, 2) . '/src-runtime-preparation';
foreach (glob($preparation . '/definitions/*.json') as $path) {
    Files::write($root . '/definitions/' . basename($path), Files::read($path));
}
Files::write($root . '/native.hpp', <<<'CPP'
#pragma once
#include <cstdint>
#include <scpp/lang/php.hpp>
namespace native {
struct pair { std::uint8_t marker; std::int32_t total; };
inline std::int32_t seed() { return 17; }
inline std::uint8_t tag() { return 3; }
inline scpp::string_t format_record(const pair &value) {
    const std::int64_t amount = value.total + value.marker;
    return scpp::cast<scpp::string_t, std::int64_t>(amount);
}
}
CPP);
$fields = [
    ['name' => 'tag', 'member' => 'marker', 'type' => 'small', 'writable' => true],
    ['name' => 'amount', 'member' => 'total', 'type' => 'wide', 'writable' => true],
];
$definition = ['schema_version' => 1, 'types' => [
    ['id' => 'small', 'cpp_name' => 'std::uint8_t', 'header' => 'cstdint', 'kind' => 'integer',
        'language_type' => ['name' => 'uint8', 'namespace' => '']],
    ['id' => 'wide', 'cpp_name' => 'std::int32_t', 'header' => 'cstdint', 'kind' => 'integer',
        'language_type' => ['name' => 'int32', 'namespace' => '']],
    ['id' => 'pair', 'cpp_name' => 'native::pair', 'header' => 'native.hpp', 'kind' => 'value_record', 'storage' => 'inline',
        'construction' => 'zero', 'copy' => 'value', 'cleanup' => 'none', 'fields' => $fields,
        'language_type' => ['name' => 'provided_pair', 'namespace' => '']],
], 'operations' => [
    ['id' => 'seed', 'kind' => 'free_function', 'cpp_name' => 'native::seed', 'header' => 'native.hpp', 'parameters' => [],
        'result_type' => 'wide', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'seed', 'namespace' => '']],
    ['id' => 'tag', 'kind' => 'free_function', 'cpp_name' => 'native::tag', 'header' => 'native.hpp', 'parameters' => [],
        'result_type' => 'small', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'tag', 'namespace' => '']],
]];
$definition['operations'][] = ['id' => 'format_record', 'kind' => 'free_function',
    'cpp_name' => 'native::format_record', 'header' => 'native.hpp',
    'parameters' => [['type' => 'pair', 'passing' => 'const_address', 'borrow_scope' => 'call']],
    'result_type' => 'string', 'error_policy' => 'terminate', 'expose_as' => ['name' => 'format_record', 'namespace' => '']];
$config = Files::json($preparation . '/config.json');
$config['definitions_directory'] = $root . '/definitions';
$config['include_directories'] = [...array_map(static fn($path) => realpath(Files::path($preparation, $path)),
    $config['include_directories']), $root];
$config['output_directory'] = $root . '/runtime';
Files::write_json($root . '/config.json', $config);
Files::write_json($root . '/definitions/records.json', $definition);
Check::check((new \runtime_preparation\Runtime_Preparation())->run($root . '/config.json')['status'] === 'built',
    'Prepare record borrow and owned string result from local metadata');
$metadata = Files::json($root . '/runtime/package/metadata.json');
$operation = array_column($metadata['operations'], null, 'id')['format_record'];
Check::check(($operation['result']['passing'] === 'caller_storage') && ($operation['result']['ownership'] === 'owned')
    && ($operation['parameters'][0]['abi_indices'] === [1]) && ($operation['parameters'][0]['borrow_scope'] === 'call'),
    'Measured ABI puts owned result storage before the borrowed record address');

// Compose source/provider records, copies and cleanup across a loop and an early return.
$source = <<<'PHS'
struct local_record { public int32 $amount; }
/** Exercise record borrowing, independent copies and owned results on normal and early exits. */
function work($flag int): int
{
    $outer string = "outer";
    $p provided_pair;
    $p->amount = seed();
    $p->tag = tag();
    $copy provided_pair = $p;
    $source local_record;
    $source->amount = $copy->amount;
    $p->amount = $source->amount;
    $i int = 1;
    while ($i)
    {
        $text string = format_record($p);
        $duplicate string = $text;
        $p->amount = seed() + seed();
        echo $text, "/", $duplicate, "/", format_record($p), "\n";
        if ($flag) {
            return int_from_string($text);
        }
        $i = 0;
    }
    echo format_record($copy), "\n";
    return 0;
}
work(0);
return work(1);
PHS;
$path = $root . '/main.phs';
Files::write($path, $source);
$session = new \compile\Compiler_Session(runtime_package_path: $root . '/runtime');
$first = $session->compile($path, $root . '/program');
Check::check(Record_String_Test::run([$root . '/program']) === [20, "20/20/37\n20\n20/20/37\n", ''],
    'Full native build preserves record and string copies across subsequent writes and both exits');
$before = serialize($first);
Check::edit($path, str_replace('seed() + seed();', 'seed() + seed() + seed();', $source));
$second = $session->compile($path, $root . '/program');
Check::check(Record_String_Test::run([$root . '/program']) === [20, "20/20/54\n20\n20/20/54\n", ''],
    'One body replacement updates native behavior through the common stages');
Check::check(!$second->inputs->context->full_rebuild && ($second->backend === $first->backend)
    && (serialize($first) === $before), 'Body increment reuses backend contracts and preserves the old snapshot');

// Owned parameters remain unsupported; owned results must actually return a value, even in unused functions.
foreach (['string', 'provided_pair'] as $type)
{
    foreach (["function unused(\$value $type): int { return 0; } return 0;",
        "function unused(): $type { return; } return 0;"] as $invalid)
    {
        $bad_path = $root . '/invalid.phs';
        Files::write($bad_path, $invalid);
        $isolated = new \compile\Compiler_Session(runtime_package_path: $root . '/runtime');
        $missing_result = str_contains($invalid, 'return;');
        $error = Check::rejects(static fn() => $isolated->compile($bad_path, $root . '/invalid'),
            $missing_result ? 'A value is required' : ($type === 'string' ? 'inline object' : 'Struct function parameters'));
        Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $bad_path)
            && ($error->start === strpos($invalid, $missing_result ? 'return;' : $type))
            && ($error->length === ($missing_result ? 7 : strlen($type))),
            'Unsupported parameter or absent owned result has an exact source diagnostic');
        Check::check(($isolated->published === null) && !file_exists($root . '/invalid'),
            'Rejected source object signature cannot publish a native artifact');
    }
}
echo "record/string composition ok: metadata borrow plus owned result, copies, control flow, one body increment, snapshot purity and source signature diagnostics\n";
