<?php
declare(strict_types=1);
namespace Scpp\Api;
require_once __DIR__ . '/render_boxed.php';

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
    if(isset($plan['string'])) {
        $out .= "class string;\n";
    }
    foreach(array_merge($plan['types'], $plan['values']) as $t) {
        $out .= "class {$t['name']};\n";
    }
    foreach($plan['values'] as $v) {
        $n = $v['name'];
        $out .= "class $n final {\npublic:\n    constexpr $n() noexcept = default;\n";
        foreach($plan['conversions'] as $c) {
            if($c['target'] === $n) {
                $out .= "    explicit $n(" . scalarInput($c['source']) . " value)" . ($c['can_fail'] ? '':' noexcept') . ";\n";
            }
        }
        if($v['kind'] === 'floating') {
            foreach(NATIVES as $native) {
                if(!hasCast($plan, $n, $native, true)) {
                    $out .= "    explicit $n($native) = delete;\n";
                }
            }
            $out .= template('int128_fences', ['name' => $n]) . template('float_storage');
        }
        elseif($v['kind'] === 'boolean') {
            $out .= "    explicit constexpr $n(bool value) noexcept : value_(value) {}\n";
            foreach(NATIVES as $native) {
                if($native !== 'bool') {
                    $out .= "    explicit $n($native) = delete;\n";
                }
            }
            $out .= template('int128_fences', ['name' => $n]) . "    explicit $n(const void*) = delete;\n" . template('bool_storage');
        }
        $out .= "};\n";
    }
    foreach($plan['values'] as $v) {
        $n = $v['name'];
        if($v['kind'] === 'null') {
            $out .= "inline constexpr $n null{};\n";
        }
        foreach($v['operations'] as $op) {
            if($op === 'not') {
                $out .= "constexpr bool_t operator!($n value) noexcept { return bool_t{!value.native_value()}; }\n";
            }
            else {
                $symbol = $op === 'equal' ? '==':'!=';
                $out .= $v['kind'] === 'null' ? "constexpr bool_t operator$symbol($n, $n) noexcept { return bool_t{" . ($op === 'equal' ? 'true':'false') . "}; }\n" :"constexpr bool_t operator$symbol($n left, $n right) noexcept { return bool_t{left.native_value() $symbol right.native_value()}; }\n";
            }
        }
    }
    foreach(object($plan['aliases']) as $alias => $target) {
        if(hasValue($plan, $target)) {
            $out .= "using $alias = $target;\n";
        }
    }
    return $out . "}\n";
}

function signature(array $op): string {
    $form = $op['form'];
    $out = $op['result'] . (in_array($form, ['compound', 'prefix'], true) ? '&':'') . ' operator' . $op['symbol'] . '(' . $op['left']['cpp'] . (in_array($form, ['compound', 'prefix', 'postfix'], true) ? '&':'') . ' left';
    if(isset($op['right'])) {
        $out .= ', ' . $op['right']['cpp'] . ' right';
    }
    if($form === 'postfix') {
        $out .= ', int';
    }
    return $out . ')';
}

function render(array $plan): array {
    $headers = $plan['headers'];
    $types = $headers['types'];
    $vals = $headers['values'];
    $ops = $headers['operators'];
    $h = BANNER . "#pragma once\nnamespace scpp {\nnamespace detail { struct integer_access; }\n";
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
    $d .= "namespace scpp {\n";
    $files = [];
    renderSequences($plan, $files, $d);
    $d .= template('integer_access');
    if(isset($plan['string'])) {
        $h .= "class string;\n";
    }
    foreach(array_merge($plan['types'], $plan['values']) as $t) {
        $h .= "class {$t['name']};\n";
    }
    if(hasValue($plan, 'float64')) {
        $d .= template('float_assertions');
    }
    foreach($plan['conversions'] as $c) {
        $target = $c['target'];
        $source = $c['source'];
        $impl = $c['implementation'];
        if($source === 'string') {
            $suffix = $impl === 'parse_decimal' ? '<' . scalarRepresentation($plan, $target) . '>':'';
            $d .= "$target::$target(const string& value) : value_(detail::$impl$suffix(detail::string_access::read(value))) {}\n";
        }
        elseif($target === 'string') {
            $d .= "string::string($source value) : storage_(new storage{detail::$impl(value.native_value())}) {}\n";
        }
        else {
            $noexcept = $c['can_fail'] ? '':' noexcept';
            $rep = scalarRepresentation($plan, $target);
            $arg = $c['native_source'] ? 'value':'value.native_value()';
            $d .= "$target::$target($source value)$noexcept : value_(detail::$impl<$rep>($arg)) {}\n";
        }
    }
    foreach($plan['native_inputs'] as $n) {
        $d .= "static_assert(std::numeric_limits<{$n['cpp']}>::digits == {$n['digits']});\nstatic_assert(std::numeric_limits<{$n['cpp']}>::is_signed == " . ($n['signed'] ? 'true':'false') . ");\n";
    }
    foreach($plan['types'] as $t) {
        $n = $t['name'];
        $rep = $t['representation'];
        $h .= "class $n final {\npublic:\n    constexpr $n() noexcept = default;\n";
        foreach($plan['conversions'] as $c) {
            if($c['target'] === $n) {
                $h .= "    explicit $n(" . scalarInput($c['source']) . " value)" . ($c['can_fail'] ? '':' noexcept') . ";\n";
            }
        }
        foreach(NATIVES as $native) {
            if(!hasNative($plan, $native)) {
                $h .= "    explicit $n($native) = delete;\n";
            }
        }
        $h .= template('int128_fences', ['name' => $n]) . template('integer_storage', compact('n', 'rep'));
        $d .= template('integer_assertions', compact('n', 'rep') + ['digits' => digits($t)]);
    }
    foreach(object($plan['aliases']) as $alias => $target) {
        if(hasType($plan, $target)) {
            $h .= "using $alias = $target;\n";
        }
    }
    $h .= "}\n";
    $operators = BANNER . "#pragma once\n" . includeHeader($types) . includeHeader($vals) . "namespace scpp {\n";
    $bridge = BANNER . "#pragma once\n" . includeHeader($ops) . "namespace scpp {\n";
    $emitted = [];
    $symbols = [];
    foreach($plan['operations'] as $op) {
        $symbol = $op['symbol'];
        $emitted[encode([$symbol, $op['left']['cpp'], $op['right']['cpp'] ?? ''])] = true;
        $decl = signature($op);
        $execution = $op['execution'];
        if($op['left']['native'] || ($op['right']['native'] ?? false)) {
            $symbols[$symbol] = true;
            $left = $op['form'] === 'compound' ? 'left':"static_cast<$execution>(left)";
            $bridge .= "inline $decl {\n    return $left $symbol static_cast<$execution>(right);\n}\n";
            continue;
        }
        $operators .= "$decl;\n";
        $impl = $op['implementation'];
        $result = $op['result'];
        if($op['promotion'] === 'exact_integer_comparison') {
            $d .= "$decl {\n    return $result{detail::$impl(left.native_value(), right.native_value())};\n}\n";
            continue;
        }
        $rep = scalarRepresentation($plan, $execution);
        $args = !isset($op['right']) ? "static_cast<$rep>(left.native_value())":($op['promotion'] === 'left_with_checked_count' ? 'left.native_value(), right.native_value()':"static_cast<$rep>(left.native_value()), static_cast<$rep>(right.native_value())");
        $call = "detail::$impl<$rep>($args)";
        if($result === 'bool_t') {
            $d .= "$decl {\n    return bool_t{{$call}};\n}\n";
            continue;
        }
        $value = $execution === 'float64' ? "float64{{$call}}":"detail::integer_access::make<$execution>($call)";
        $d .= "$decl {\n";
        $d .= match($op['form']) {
            'compound' => "    left = static_cast<{$op['left']['cpp']}>($value);\n    return left;\n", 'prefix' => "    left = $value;\n    return left;\n", 'postfix' => "    const auto old = left;\n    left = $value;\n    return old;\n", default => "    return $value;\n"
        };
        $d .= "}\n";
    }
    ksort($symbols, SORT_STRING);
    $numeric = array_merge(array_column($plan['types'], 'name'), hasValue($plan, 'float64') ? ['float64']:[]);
    foreach(array_keys($symbols) as $symbol) {
        foreach($numeric as $name) {
            foreach([NATIVES, ['__int128', 'unsigned __int128']] as $index => $natives) {
                if($index === 1) {
                    $bridge .= "#ifdef __SIZEOF_INT128__\n";
                }
                foreach($natives as $native) {
                    if(!isset($emitted[encode([$symbol, $name, $native])])) {
                        $bridge .= "void operator$symbol($name, $native) = delete;\n";
                    }
                    if(!isset($emitted[encode([$symbol, $native, $name])])) {
                        $bridge .= "void operator$symbol($native, $name) = delete;\n";
                    }
                }
                if($index === 1) {
                    $bridge .= "#endif\n";
                }
            }
        }
    }
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
    $files += ['public/' . $types => $h, 'public/' . $vals => renderValues($plan), 'public/' . $ops => $operators . "}\n", 'public/' . $headers['native_bridge'] => $bridge . "}\n", 'public/' . $headers['short_names'] => $short, 'src/runtime.cpp' => $d . "}\n", 'prepared.json' => encode($plan) . "\n"];
    renderOwnership($plan, $files);
    renderHashes($plan, $files);
    renderDatetime($plan, $files);
    renderBoxed($plan, $files);
    ksort($files, SORT_STRING);
    $files['inventory.json'] = encode(array_keys($files)) . "\n";
    ksort($files, SORT_STRING);
    return $files;
}
