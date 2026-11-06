<?php
declare(strict_types=1);
namespace Scpp\Api;

/** Concrete public declarations and private calls to the same algorithms used by wrapper constructors. */
function renderCheckedConversions(array $plan, array &$files, string &$definitions): void {
    if (!isset($plan['checked_conversions'])) return;
    $binding = $plan['checked_conversions'];
    $header = BANNER . "#pragma once\nnamespace scpp {\n";
    $asserted = [];
    foreach ($binding['functions'] as $function) {
        $source = $binding['representations'][$function['source']];
        $target = $binding['representations'][$function['target']];
        $symbol = $function['symbol'];
        $noexcept = $function['can_fail'] ? '' : ' noexcept';
        $signature = "{$target['cpp']} $symbol({$source['cpp']} value)$noexcept";
        $header .= "$signature;\n";
        // A type alias shares its overload identity with its exact native type, not every equal-width type.
        foreach (NATIVES as $native) {
            if ($native !== $source['native_name']) $header .= "void $symbol($native) = delete;\n";
        }
        $header .= "void $symbol(decltype(nullptr)) = delete;\nvoid $symbol(const void*) = delete;\n";
        $header .= "#ifdef __SIZEOF_INT128__\nvoid $symbol(__int128) = delete;\nvoid $symbol(unsigned __int128) = delete;\n#endif\n";
        foreach ([$source, $target] as $representation) {
            if (isset($asserted[$representation['cpp']])) continue;
            $definitions .= "static_assert(std::is_same_v<{$representation['cpp']}, {$representation['native_name']}>);\n";
            $asserted[$representation['cpp']] = true;
        }
        $definitions .= "$signature { return detail::{$function['implementation']}<{$target['cpp']}>(value); }\n";
    }
    $files['public/' . $binding['header']] = $header . "}\n";
}
