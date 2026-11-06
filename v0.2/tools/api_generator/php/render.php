<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/publication.php';
require_once __DIR__ . '/render_boxed.php';
require_once __DIR__ . '/render_json.php';
require_once __DIR__ . '/render_conversions.php';
require_once __DIR__ . '/scalar_abi.php';

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
    return match($name) {
        'bool_t' => 'bool', 'float64' => 'double', default => throw new \RuntimeException("Missing prepared scalar representation: $name")
    };
}

function renderValues(array $plan): string {
    $out = BANNER . "#pragma once\nnamespace scpp {\n";
    foreach ($plan['values'] as $value) {
        $name = $value['name'];
        if ($value['kind'] !== 'null') {
            $out .= "using $name = " . scalarRepresentation($plan, $name) . ";\n";
        } else {
            $out .= "class $name final { public: constexpr $name() noexcept = default; };\ninline constexpr $name null{};\n";
            foreach ($value['operations'] as $operation) {
                $symbol = $operation === 'equal' ? '==' : '!=';
                $result = $operation === 'equal' ? 'true' : 'false';
                $out .= "constexpr bool operator$symbol($name, $name) noexcept { return $result; }\n";
            }
        }
    }
    foreach (object($plan['aliases']) as $alias => $target) {
        if (hasValue($plan, $target)) $out .= "using $alias = $target;\n";
    }
    return $out . "}\n";
}

function render(array $plan): array {
    $headers = $plan['headers'];
    $types = $headers['types'];
    $vals = $headers['values'];
    $ops = $headers['operators'];
    $h = BANNER . "#pragma once\nnamespace scpp {\n";
    $d = BANNER . includeHeader($ops) . includeHeader('scalar_semantics.hpp');
    if(isset($plan['string'])) {
        $d .= includeHeader($plan['string']['header']) . includeHeader('sequence_semantics.hpp');
    }
    if(isset($plan['vectors_header'])) {
        $d .= includeHeader($plan['vectors_header']) . includeHeader('sequence_semantics.hpp') . "#include <vector>\n#include <algorithm>\n";
    }
    if(isset($plan['carriers_header'])) {
        $d .= includeHeader($plan['carriers_header']) . includeHeader('carrier_semantics.hpp');
    }
    if (isset($plan['checked_conversions'])) $d .= includeHeader($plan['checked_conversions']['header']);
    $d .= "namespace scpp {\n";
    $files = [];
    renderSequences($plan, $files, $d);
    renderCheckedConversions($plan, $files, $d);
    if (hasValue($plan, 'float64')) $d .= template('float_assertions');
    foreach ($plan['conversions'] as $conversion) {
        if ($conversion['target'] !== 'string') continue;
        $source = $conversion['source'];
        $implementation = $conversion['implementation'];
        $d .= "string::string($source value) : storage_(new storage{detail::$implementation(value)}) {}\n";
    }
    foreach($plan['native_inputs'] as $n) {
        $d .= "static_assert(std::numeric_limits<{$n['cpp']}>::digits == {$n['digits']});\nstatic_assert(std::numeric_limits<{$n['cpp']}>::is_signed == " . ($n['signed'] ? 'true':'false') . ");\n";
    }
    foreach($plan['types'] as $t) {
        $n = $t['name'];
        $rep = $t['representation'];
        $h .= "using $n = $rep;\n";
        $d .= template('integer_assertions', compact('n', 'rep') + ['digits' => digits($t)]);
    }
    foreach(object($plan['aliases']) as $alias => $target) {
        if(hasType($plan, $target)) {
            $h .= "using $alias = $target;\n";
        }
    }
    $h .= "}\n";
    $operators = BANNER . "#pragma once\n" . includeHeader($types) . includeHeader($vals) . "namespace scpp {\n";
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
    $files += ['public/' . $types => $h, 'public/' . $vals => renderValues($plan), 'public/' . $ops => $operators . "}\n", 'public/' . $headers['short_names'] => $short, 'src/runtime.cpp' => $d . "}\n", 'prepared.json' => encode($plan) . "\n"];
    renderOwnership($plan, $files);
    renderHashes($plan, $files);
    renderDatetime($plan, $files);
    renderJson($plan, $files);
    renderBoxed($plan, $files);
    requireThat(!isset($files['public/scpp/output.hpp']), 'The core output header path is reserved');
    $files['public/scpp/output.hpp'] = BANNER . template('output_declaration');
    requireThat(!isset($files['public/scpp/entry.hpp']), 'The core entry header path is reserved');
    $files['public/scpp/entry.hpp'] = BANNER . template('entry_declaration');
    requireThat(!isset($files['public/scpp/arithmetic.hpp']), 'The core arithmetic header path is reserved');
    $files['public/scpp/arithmetic.hpp'] = BANNER . template('arithmetic_declaration');
    ksort($files, SORT_STRING);
    $files['inventory.json'] = encode(['generator' => 'scpp-api', 'version' => OUTPUT_VERSION, 'files' => array_keys($files)]) . "\n";
    ksort($files, SORT_STRING);
    return $files;
}
