<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/publication.php';
require_once __DIR__ . '/render_boxed.php';
require_once __DIR__ . '/render_json.php';
require_once __DIR__ . '/render_conversions.php';
require_once __DIR__ . '/scalar_abi.php';
require_once __DIR__ . '/concrete.php';

const BANNER = "// Generated from reviewed runtime metadata. Do not edit.\n";
const NATIVES = ['int', 'unsigned int', 'long', 'unsigned long', 'long long', 'unsigned long long', 'short', 'unsigned short', 'bool', 'char', 'signed char', 'unsigned char', 'wchar_t', 'char8_t', 'char16_t', 'char32_t', 'float', 'double', 'long double'];
function template(string $name, array $values = []): string {
    static $templates = [];
    $source = $templates[$name] ??= readFile(__DIR__ . '/../templates/' . $name . '.cpp.tpl');
    return preg_replace_callback('/@([a-zA-Z_][a-zA-Z_0-9]*)@/', function(array $match) use ($values, $name):string {
        requireThat(array_key_exists($match[1], $values), "Missing template argument {$match[1]} in $name"); $v = $values[$match[1]]; return is_bool($v) ? ($v ? 'true':'false'):(string)$v;
    }, $source);
}

function includeHeader(string $path): string {
    return "#include \"$path\"\n";
}

function scalarInput(string $source): string {
    return $source === 'string' ? 'const string&':$source;
}

function scalarRepresentation(array $plan, string $name): string {
    foreach($plan['types'] as $t) {
        if($t['name'] === $name) {
            return $t['representation'];
        }
    }
    $native = scalarNativeIdentity($plan, $name);
    requireThat($native !== $name, "Missing prepared scalar representation: $name");
    return $native;
}

/** Scalar declarations are placed by their canonical export, never by an old header slot. */
function renderScalarGroups(array $plan, array &$groups): void {
    $declarations = [];
    $exports = [];
    foreach (array_merge($plan['types'], $plan['values']) as $type) {
        $name = $type['name'];
        $provider = typeExport($plan['submodules'], $name)['provider'];
        $exports[$provider][] = $name;
        $declarations[$provider][] = "using $name = " . scalarRepresentation($plan, $name) . ";\n";
        if (($type['kind'] ?? '') === 'null') $declarations[$provider][] = "inline constexpr $name null = nullptr;\n";
    }
    foreach (object($plan['aliases']) as $alias => $target) {
        $provider = typeExport($plan['submodules'], $target)['provider'];
        $exports[$provider][] = $alias;
        $declarations[$provider][] = "using $alias = $target;\n";
    }
    foreach ($declarations as $provider => $parts) {
        $aliases = implode('', array_map(static fn($name) => "using ::scpp::$name;\n", $exports[$provider]));
        $groups[] = ['provider' => $provider, 'declarations' => "namespace scpp {\n" . implode('', $parts) . "}\nnamespace scpp::types {\n$aliases}\n", 'system' => []];
    }
}

/** Existing non-partitioned consumers retain their aggregate of selected scalar declarations. */
function scalarProviderHeaders(array $plan): array {
    $providers = exportProviders($plan['submodules'], array_column(array_merge($plan['types'], $plan['values']), 'name'));
    $headers = array_map(fn(string $provider): string => providerHeader($plan['submodules'], $provider), $providers);
    sort($headers, SORT_STRING);
    return $headers;
}

function scalarProviderIncludes(array $plan): string {
    return implode('', array_map(__NAMESPACE__ . '\\includeHeader', scalarProviderHeaders($plan)));
}

function render(array $plan): array {
    $valueErrors = array_intersect(STRING_VALUE_ERROR_IMPLEMENTATIONS, array_column($plan['string_functions'] ?? [], 'implementation'));
    $groups = [];
    $headers = $plan['headers'];
    $ops = $headers['operators'];
    $d = BANNER . includeHeader($ops) . includeHeader('scalar_semantics.hpp');
    if(isset($plan['string'])) {
        $d .= includeHeader($plan['string']['header']) . includeHeader('sequence_semantics.hpp');
    }
    if(isset($plan['vectors_header'])) {
        $d .= includeHeader($plan['vectors_header']) . includeHeader('vector_buffer.hpp');
    }
    if(isset($plan['carriers_header'])) {
        $d .= includeHeader($plan['carriers_header']) . includeHeader('carrier_semantics.hpp');
    }
    if (isset($plan['checked_conversions'])) $d .= includeHeader($plan['checked_conversions']['header']);
    $files = [];
    if ($valueErrors || in_array('1', array_column($plan['vectors'] ?? [], 'enable_reserve'), true)) {
        $files['public/scpp/value_error.hpp'] = BANNER . "#pragma once\n#include <stdexcept>\nnamespace scpp { class ValueError : public std::invalid_argument { public: using std::invalid_argument::invalid_argument; }; }\n";
        $d .= includeHeader('scpp/value_error.hpp');
    }
    $d .= "namespace scpp {\n";
    renderSequences($plan, $files, $d, $groups);
    renderCheckedConversions($plan, $groups, $d);
    renderScalarGroups($plan, $groups);
    if (hasValue($plan, 'float64')) $d .= template('scalars/float_assertions');
    foreach ($plan['conversions'] as $conversion) {
        if ($conversion['target'] !== 'string') continue;
        $source = $conversion['source'];
        $implementation = $conversion['implementation'];
        $d .= "string::string($source value) { detail::string_access::init(*this, detail::$implementation(value)); }\n";
    }
    foreach($plan['native_inputs'] as $n) {
        $d .= "static_assert(std::numeric_limits<{$n['cpp']}>::digits == {$n['digits']});\nstatic_assert(std::numeric_limits<{$n['cpp']}>::is_signed == " . ($n['signed'] ? 'true':'false') . ");\n";
    }
    foreach($plan['types'] as $t) {
        $n = $t['name'];
        $rep = $t['representation'];
        $d .= template('scalars/integer_assertions', compact('n', 'rep') + ['digits' => digits($t)]);
    }
    $operators = BANNER . "#pragma once\n" . scalarProviderIncludes($plan) . "namespace scpp {\n";
    // Builtin scalar aliases use native operators. Checked source operations are emitted by the compiler.
    $short = BANNER . "#pragma once\n" . includeHeader($ops);
    if(isset($plan['string'])) {
        $short .= includeHeader($plan['string']['header']) . "using scpp::string;\n";
    }
    foreach(['vectors', 'carriers'] as $family) {
        if(isset($plan[$family . '_header'])) {
            $short .= includeHeader($plan[$family . '_header']);
        }
        foreach($plan[$family] as $item) {
            $short .= "using scpp::{$item['name']};\n";
        }
    }
    if(isset($plan['hashes'])) {
        $short .= includeHeader($plan['hashes']['header']);
        foreach($plan['hashes']['specializations'] as $item) {
            $short .= "using scpp::{$item['name']};\n";
        }
    }
    foreach(array_merge($plan['types'], $plan['values']) as $t) {
        $short .= "using scpp::{$t['name']};\n";
        if(($t['kind'] ?? '') === 'null') {
            $short .= "using scpp::null;\n";
        }
    }
    foreach($plan['short_aliases'] as $alias) {
        $short .= "using scpp::$alias;\n";
    }
    if (isset($plan['boxed'])) {
        $short .= includeHeader($plan['boxed']['header']);
        foreach ($plan['boxed']['families'] as $family) $short .= "using scpp::{$family['name']};\n";
    }
    // Concrete optional-width helpers reuse the private integer algorithms; no app templates.
    $integerHelpers = '';
    foreach ($plan['types'] as $type) {
        if ($type['bits'] >= 64 || $type['name'] === 'uint8') continue;
        $name = $type['name'];
        $cpp = $type['representation'];
        foreach (['divide', 'remainder', 'shift_left', 'shift_right'] as $operation) {
            $right = str_starts_with($operation, 'shift_') ? '::int64_t' : $cpp;
            $signature = "$cpp {$name}_$operation($cpp left, $right right)";
            $integerHelpers .= $signature . ";\n";
            $d .= $signature . " { return detail::integer_$operation(left, right); }\n";
        }
    }
    $files += ['public/' . $ops => $operators . "}\n", 'public/' . $headers['short_names'] => $short, 'src/runtime.cpp' => $d . "}\n", 'prepared.json' => encode($plan) . "\n"];
    $files['src/runtime.cpp'] .= template('core/entry_exception_boundary', [
        'exception_header' => isset($plan['exception']) ? includeHeader($plan['exception']['header']) : '',
        'language_exception_handler' => isset($plan['exception']) ? template('core/entry_language_exception') : '',
    ]);
    renderOwnership($plan, $files);
    renderHashes($plan, $files, $groups);
    renderDatetime($plan, $files);
    renderJson($plan, $files);
    foreach (FUNCTION_MODULES as $family) renderModuleFunctions($family, $plan, $files, $groups);
    renderBoxed($plan, $files, $groups);
    $groups[] = ['provider' => $plan['submodules']['groups']['output']['provider'], 'declarations' => template('core/output_declaration', [
        'string_output' => isset($plan['string']) ? "class string;\nvoid echo_string(const string& value);\n" : '',
    ]), 'system' => ['stddef.h', 'stdint.h']];
    $files['private/output_provider.hpp'] = BANNER . '#pragma once' . "\n"
        . includeHeader(providerHeader($plan['submodules'], $plan['submodules']['groups']['output']['provider']));
    if (isset($plan['string'])) {
        $files['private/output_provider.hpp'] .= includeHeader($plan['string']['header']) . "#define SCPP_OUTPUT_HAS_STRING 1\n";
    }
    requireThat(!isset($files['public/scpp/owner_access.hpp']), 'The owner access header path is reserved');
    requireThat(!isset($files['public/scpp/fixed_array.hpp']), 'The fixed-array header path is reserved');
    if ($plan['mt_segments'] ?? false) $files['public/scpp/mt_segments.hpp'] = BANNER . template('collections/mt_segments');
    $files['public/scpp/fixed_array.hpp'] = BANNER . template('collections/fixed_array_index');
    $files['public/scpp/owner_access.hpp'] = BANNER . template('ownership/application_control');
    if (isset($plan['exception'])) {
        $path = 'public/' . $plan['exception']['header'];
        requireThat(!isset($files[$path]), 'The core Exception header path is reserved');
        $files[$path] = BANNER . template('core/exception_declaration',
            ['string_header' => $plan['string']['header']]);
    }
    requireThat(!isset($files['public/scpp/entry.hpp']), 'The core entry header path is reserved');
    $files['public/scpp/entry.hpp'] = BANNER . template('core/entry_declaration');
    requireThat(!isset($files['public/scpp/arithmetic.hpp']), 'The core arithmetic header path is reserved');
    $files['public/scpp/arithmetic.hpp'] = BANNER . template('scalars/arithmetic_declaration', ['optional_helpers' => $integerHelpers]);
    if ($plan['async_frames'] ?? false) {
        $timerType = new RuntimeConcreteType($plan, 'int64');
        $files['public/scpp/async_timer.hpp'] = BANNER . template('core/async_timer', ['scalar_header' => $timerType->headers()[0]]);
        $files['public/scpp/async/frame.hpp'] = BANNER . template('core/async_frame');
    }
    assembleSubmodules($plan['submodules'], $groups, $files);
    ksort($files, SORT_STRING);
    $files['inventory.json'] = encode(['generator' => 'scpp-api', 'version' => OUTPUT_VERSION, 'files' => array_keys($files)]) . "\n";
    ksort($files, SORT_STRING);
    return $files;
}

/** One consumer-owned header per demanded result, including its inline calls to the erased frame ABI. */
function asyncTaskHeader(ConcreteType $type, string $task, bool $value, string $namespace): string {
    $parts = asyncTaskParts($type, $task, $value, $namespace, true);
    return $parts['declaration'] . "\n#include <scpp/async/frame.hpp>\n" . $parts['implementation'];
}

/** Shared recipe fragments for inline headers and application-owned out-of-line definitions. */
function asyncTaskParts(ConcreteType $type, string $task, bool $value, string $namespace, bool $inline): array {
    $parts = concreteTemplate('core/async_task', ['result' => $type], [
        'task' => $task, 'has_value' => $value ? '1' : '', 'no_value' => $value ? '' : '1',
        'includes' => implode('', array_map(includeHeader(...), $type->headers())) . ($value ? "#include <new>\n" : ''),
        'extract' => $value ? '    return static_cast<' . $type->cppType($namespace) . '&&>(promise.value);' : '',
        'open' => $namespace === '' ? '' : "namespace $namespace {",
        'close' => $namespace === '' ? '' : '}', 'inline' => $inline ? 'inline ' : '',
    ], $namespace);
    $methods = $parts['implementation'];
    if ($namespace !== '') $methods = "namespace $namespace {\n" . $methods . "}\n";
    return ['declaration' => $parts['declaration'], 'implementation' => $methods];
}
