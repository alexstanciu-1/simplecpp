<?php
declare(strict_types=1);
namespace prepare_backend;

/** Reader for the probe's folded i64 globals, not a general LLVM parser. */
final class Layout_Facts {
    private static function find(string $text, string $needle, int $start): int {
        $limit = string_byte_len($text) - string_byte_len($needle);
        for ($index = $start; $index < $limit + 1; $index++) {
            if (string_byte_slice($text,$index,string_byte_len($needle)) === $needle) { return $index; }
        }
        return -1;
    }
    private static function integer(string $text, string $name): int {
        $marker = Layout_Facts::find($text,'constant i64 ',0);
        if ($marker < 0) { throw new \RuntimeException('Target did not resolve layout fact: ' . $name); }
        // LLVM linkage/visibility and addrspace/thread-local prefixes are unquoted.
        // This extracts folded globals; it does not validate the full LLVM declaration grammar.
        for ($index = 0; $index < $marker; $index++) {
            $byte = string_byte_at($text,$index);
            if (($byte === 32) || ($byte === 95) || ($byte === 40) || ($byte === 41)) { continue; }
            if (($byte > 96) && ($byte < 123)) { continue; }
            if (($byte > 47) && ($byte < 58)) { continue; }
            throw new \RuntimeException('Invalid layout fact prefix: ' . $name);
        }
        if ($marker > 0) {
            if (string_byte_at($text,$marker-1) !== 32) { throw new \RuntimeException('Invalid layout fact prefix: ' . $name); }
        }
        $start = $marker + 13; $end = $start;
        while ($end < string_byte_len($text)) {
            $byte = string_byte_at($text,$end);
            if (($byte < 48) || ($byte > 57)) { break; }
            $end = $end + 1;
        }
        if ($end === $start) { throw new \RuntimeException('Target did not resolve layout fact: ' . $name); }
        $digits = string_byte_slice($text,$start,$end-$start);
        if (string_byte_len($digits) > 1) {
            if (string_byte_at($digits,0) === 48) { throw new \RuntimeException('Invalid layout integer: ' . $name); }
        }
        if ($end < string_byte_len($text)) {
            $byte = string_byte_at($text,$end);
            if (($byte !== 32) && ($byte !== 9) && ($byte !== 44) && ($byte !== 59)) { throw new \RuntimeException('Invalid layout integer: ' . $name); }
        }
        while ($end < string_byte_len($text)) {
            $byte = string_byte_at($text,$end); if (($byte !== 32) && ($byte !== 9)) { break; }
            $end = $end + 1;
        }
        if ($end < string_byte_len($text)) {
            $byte = string_byte_at($text,$end);
            if (($byte !== 44) && ($byte !== 59)) { throw new \RuntimeException('Invalid layout fact suffix: ' . $name); }
        }
        $maximum = '9223372036854775807'; $length = string_byte_len($digits);
        if ($length > 19) { throw new \RuntimeException('Target layout exceeds host index capacity'); }
        if ($length === 19) {
            for ($index = 0; $index < 19; $index++) {
                $left = string_byte_at($digits,$index); $right = string_byte_at($maximum,$index);
                if ($left > $right) { throw new \RuntimeException('Target layout exceeds host index capacity'); }
                if ($left < $right) { break; }
            }
        }
        $value = 0;
        for ($index = 0; $index < $length; $index++) { $value = $value * 10 + (string_byte_at($digits,$index) - 48); }
        return $value;
    }
    public static function read(string $output, Backend_Configuration $configuration, array $names /** vector<string> */): array /** hash<int> */ {
        $wanted /** hash<bool> */ = []; $facts /** hash<int> */ = [];
        foreach ($names as $name) { $wanted[$name] = true; }
        $triple = false; $layout = false; $cursor = 0; $length = string_byte_len($output);
        while ($cursor < $length) {
            $end = Layout_Facts::find($output,"\n",$cursor); if ($end < 0) { $end = $length; }
            $line = string_byte_slice($output,$cursor,$end-$cursor); $cursor = $end+1;
            if (string_byte_ends_with($line,"\r")) { $line = string_byte_slice($line,0,string_byte_len($line)-1); }
            if (string_byte_starts_with($line,'target triple = ')) {
                if ($triple) { throw new \RuntimeException('Duplicate layout target triple'); }
                if ($line !== 'target triple = ' . LLVM_Text::quote($configuration->target_triple)) { throw new \RuntimeException('Layout probe changed the selected target configuration'); }
                $triple = true;
            } elseif (string_byte_starts_with($line,'target datalayout = ')) {
                if ($layout) { throw new \RuntimeException('Duplicate layout target data layout'); }
                if ($line !== 'target datalayout = ' . LLVM_Text::quote($configuration->data_layout)) { throw new \RuntimeException('Layout probe changed the selected target configuration'); }
                $layout = true;
            } elseif (string_byte_starts_with($line,'@')) {
                $equal = Layout_Facts::find($line,' = ',1); if ($equal < 0) { continue; }
                $name = string_byte_slice($line,1,$equal-1); if (!isset($wanted[$name])) { continue; }
                if (isset($facts[$name])) { throw new \RuntimeException('Duplicate layout fact: ' . $name); }
                $facts[$name] = Layout_Facts::integer(string_byte_slice($line,$equal+3,string_byte_len($line)-$equal-3),$name);
            }
        }
        if ((!$triple) || (!$layout)) { throw new \RuntimeException('Layout probe changed the selected target configuration'); }
        foreach ($names as $name) { if (!isset($facts[$name])) { throw new \RuntimeException('Target did not resolve layout fact: ' . $name); } }
        return $facts;
    }
}
