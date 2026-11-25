<?php
declare(strict_types=1);
namespace load_runtime;

/** Prepared-package syntax normalization; no I/O, canonical IDs or inferred permissions. */
final class Package_Syntax {
    public static function address_abi(\scpp\Json_View $position): void {
        if ($position->kind() !== 'object') { throw new \RuntimeException('Expected runtime address ABI object'); }
        if (!$position->has('type')) { throw new \RuntimeException('Runtime address ABI requires a default-address-space pointer'); }
        if (!$position->has('attributes')) { throw new \RuntimeException('Unsupported runtime address attributes'); }
        Package_Syntax::address_parts($position->member('type'), $position->member('attributes'));
    }
    public static function address_parts(\scpp\Json_View $type, \scpp\Json_View $attributes): void {
        if ($type->kind() !== 'string') { throw new \RuntimeException('Runtime address ABI requires a default-address-space pointer'); }
        if ($type->text() !== 'ptr') { throw new \RuntimeException('Runtime address ABI requires a default-address-space pointer'); }
        if ($attributes->kind() !== 'string') { throw new \RuntimeException('Unsupported runtime address attributes'); }
        $value = Package_Syntax::trim_attributes($attributes->text());
        if (($value !== '') && ($value !== 'noundef')) { throw new \RuntimeException('Unsupported runtime address attributes'); }
    }
    public static function integer_abi(\scpp\Json_View $spelling, \scpp\Json_View $attributes, Runtime_Type $type): \type_model\Runtime_Abi_Position {
        $bits = 0;
        if (!take_nullable($bits, $type->integer_bits)) { throw new \RuntimeException('Runtime integer ABI does not match its semantic type'); }
        if (($spelling->kind() !== 'string') || ($attributes->kind() !== 'string')) { throw new \RuntimeException('Runtime integer ABI does not match its semantic type'); }
        if ($spelling->text() !== 'i' . $bits) { throw new \RuntimeException('Runtime integer ABI does not match its semantic type'); }
        $text = Package_Syntax::trim_attributes($attributes->text());
        $extension = \type_model\ABI_EXTENSION_NONE; $offset = 0;
        while ($offset < string_byte_len($text)) {
            if (Package_Syntax::attribute_space(string_byte_at($text, $offset))) { $offset = $offset + 1; continue; }
            $start = $offset;
            while ($offset < string_byte_len($text)) {
                if (Package_Syntax::attribute_space(string_byte_at($text, $offset))) { break; }
                $offset = $offset + 1;
            }
            $attribute = string_byte_slice($text, $start, $offset - $start);
            if ($attribute === 'noundef') { continue; }
            $candidate = \type_model\ABI_EXTENSION_NONE;
            if ($attribute === 'signext') { $candidate = \type_model\ABI_EXTENSION_SIGN; }
            elseif ($attribute === 'zeroext') { $candidate = \type_model\ABI_EXTENSION_ZERO; }
            else { throw new \RuntimeException('Unsupported runtime ABI attribute: ' . $attribute); }
            if ($extension !== \type_model\ABI_EXTENSION_NONE) { throw new \RuntimeException('Unsupported runtime ABI attribute: ' . $attribute); }
            $extension = $candidate;
        }
        return \type_model\Runtime_Abi_Position::integer($bits, $extension);
    }
    private static function attribute_space(int $byte): bool { return ($byte === 32) || (($byte >= 9) && ($byte < 14)); }
    private static function trim_space(int $byte): bool { return ($byte === 0) || ($byte === 32) || ($byte === 9) || ($byte === 10) || ($byte === 11) || ($byte === 13); }
    /** Match PHP trim's default byte set, including NUL only at the edges. */
    private static function trim_attributes(string $text): string {
        $start = 0; $end = string_byte_len($text);
        while ($start < $end) { if (!Package_Syntax::trim_space(string_byte_at($text, $start))) { break; } $start = $start + 1; }
        while ($end > $start) { if (!Package_Syntax::trim_space(string_byte_at($text, $end - 1))) { break; } $end = $end - 1; }
        return string_byte_slice($text, $start, $end - $start);
    }
    public static function language_name(\scpp\Json_View $value): \type_model\Type_Reference {
        if ($value->kind() !== 'object') { throw new \RuntimeException('Unsupported runtime language name'); }
        if ((!$value->has('name')) || (!$value->has('namespace'))) { throw new \RuntimeException('Unsupported runtime language name'); }
        $name = $value->member('name'); $namespace_text = $value->member('namespace');
        if (($name->kind() !== 'string') || ($namespace_text->kind() !== 'string')) { throw new \RuntimeException('Unsupported runtime language name'); }
        if ($namespace_text->text() !== '') { throw new \RuntimeException('Unsupported runtime language name'); }
        $text = $name->text();
        Package_Syntax::require_identifier_spelling($text);
        return \type_model\Type_Reference::named($text, '');
    }
    public static function require_identifier_spelling(string $text): void {
        if (string_byte_len($text) === 0) { throw new \RuntimeException('Unsupported runtime language name'); }
        for ($index = 0; $index < string_byte_len($text); $index++) {
            $byte = string_byte_at($text, $index);
            $letter = (($byte >= 65) && ($byte < 91)) || (($byte >= 97) && ($byte < 123)) || ($byte === 95);
            if (!$letter) {
                if (($index === 0) || ($byte < 48) || ($byte > 57)) { throw new \RuntimeException('Unsupported runtime language name'); }
            }
        }
    }
    public static function identifier(\scpp\Json_View $value): string {
        if ($value->kind() !== 'string') { throw new \RuntimeException('Missing runtime identity'); }
        $text = $value->text(); if ($text === '') { throw new \RuntimeException('Missing runtime identity'); } return $text;
    }
    public static function positive(\scpp\Json_View $value, string $description): int {
        $number = 0;
        try { $number = $value->integer(); } catch (\RuntimeException $error) { throw new \RuntimeException('Invalid runtime ' . $description); }
        if ($number < 1) { throw new \RuntimeException('Invalid runtime ' . $description); }
        return $number;
    }
    public static function rows(\scpp\Json_View $value, string $description): array /** vector<\scpp\Json_View> */ {
        if ($value->kind() !== 'array') { throw new \RuntimeException('Expected runtime ' . $description . ' list'); }
        $rows /** vector<\scpp\Json_View> */ = [];
        for ($index = 0; $index < $value->size(); $index++) {
            $row = $value->at($index);
            if ($row->kind() !== 'object') { throw new \RuntimeException('Expected runtime ' . $description . ' record'); }
            $rows[] = $row;
        }
        return $rows;
    }
}
