<?php
declare(strict_types=1);
namespace prepare_backend;

/** Byte-preserving LLVM quoted identifiers/strings, shared by probes and target checks. */
final class LLVM_Text {
    public static function quote(string $value): string {
        $out = '"'; $hex = '0123456789ABCDEF';
        for ($index = 0; $index < string_byte_len($value); $index++) {
            $byte = string_byte_at($value,$index);
            if (($byte > 31) && ($byte < 127) && ($byte !== 34) && ($byte !== 92)) {
                $out = $out . string_byte_slice($value,$index,1);
            } else {
                $out = $out . '\\' . string_byte_slice($hex,(int)($byte / 16),1) . string_byte_slice($hex,$byte % 16,1);
            }
        }
        return $out . '"';
    }
}
