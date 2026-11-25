<?php
declare(strict_types=1);
namespace load_runtime;

/** Version-1 producer schema. Expected hashes are parsed here, never treated as verified digests. */
final class Manifest_Reader {
    private static function object(\scpp\Json_View $value): void {
        if ($value->kind() !== 'object') { throw new \RuntimeException('Expected runtime JSON object'); }
    }
    private static function identity(\scpp\Json_View $value): string { return Package_Syntax::identifier($value); }
    private static function version(\scpp\Json_View $value): void {
        Manifest_Reader::object($value);
        if ($value->member('schema_version')->integer() !== 1) { throw new \RuntimeException('Unsupported runtime schema version'); }
    }
    public static function digest(\scpp\Json_View $value): string {
        $text = $value->text();
        if (string_byte_len($text) !== 64) { throw new \RuntimeException('Invalid expected SHA-256 digest'); }
        for ($index = 0; $index < 64; $index++) {
            $byte = string_byte_at($text,$index);
            if (($byte >= 48) && ($byte < 58)) { continue; }
            if (($byte >= 97) && ($byte < 103)) { continue; }
            throw new \RuntimeException('Invalid expected SHA-256 digest');
        }
        return $text;
    }
    public static function artifact_name(string $name): string {
        if ($name === '') { throw new \RuntimeException('Invalid runtime artifact name'); }
        if (($name === '.') || ($name === '..')) { throw new \RuntimeException('Invalid runtime artifact name'); }
        for ($index = 0; $index < string_byte_len($name); $index++) {
            $byte = string_byte_at($name,$index);
            if (($byte === 0) || ($byte === 47) || ($byte === 92)) { throw new \RuntimeException('Invalid runtime artifact name'); }
        }
        return $name;
    }
    private static function hashes(\scpp\Json_View $value, bool $artifacts): array /** hash<string> */ {
        Manifest_Reader::object($value);
        if ($value->size() === 0) { throw new \RuntimeException('Missing runtime artifact or toolchain hash list'); }
        $result /** hash<string> */ = [];
        for ($index = 0; $index < $value->size(); $index++) {
            $key = $value->key($index);
            if ($artifacts) { Manifest_Reader::artifact_name($key); }
            else { if ($key === '') { throw new \RuntimeException('Missing toolchain path'); } }
            $result[$key] = Manifest_Reader::digest($value->member($key));
        }
        return $result;
    }
    public static function target(\scpp\Json_View $value): Package_Target {
        Manifest_Reader::object($value);
        if ($value->size() !== 2) { throw new \RuntimeException('Unsupported runtime target schema'); }
        return new Package_Target(Manifest_Reader::identity($value->member('triple')),Manifest_Reader::identity($value->member('data_layout')));
    }
    public static function pointer(\scpp\Json_View $value): Package_Pointer {
        Manifest_Reader::version($value);
        if ($value->member('manifest')->text() !== 'package/manifest.json') { throw new \RuntimeException('Unsupported runtime package pointer format'); }
        return new Package_Pointer(Manifest_Reader::identity($value->member('input_key')),Manifest_Reader::digest($value->member('manifest_sha256')));
    }
    public static function manifest(\scpp\Json_View $value, Package_Pointer $pointer, bool $project): Package_Manifest {
        Manifest_Reader::version($value);
        $provider = Manifest_Reader::identity($value->member('provider')); $key = Manifest_Reader::identity($value->member('input_key'));
        if ($key !== $pointer->input_key) { throw new \RuntimeException('Runtime publication input key mismatch'); }
        $validation = $value->member('validation');
        if (!$validation->member('defined_abi_signatures')->boolean()) { throw new \RuntimeException('Runtime ABI signatures were not validated'); }
        if ($validation->member('native_link_no_undefined')->boolean() === $project) { throw new \RuntimeException('Runtime link validation mode mismatch'); }
        $kind = 'runtime';
        if ($value->has('module_kind')) { if ($value->member('module_kind')->kind() !== 'null') { $kind = $value->member('module_kind')->text(); } }
        if ($project) {
            if ($kind !== 'project') { throw new \RuntimeException('Runtime module mode mismatch'); }
            if (!$validation->member('source_imports_validated')->boolean()) { throw new \RuntimeException('Unvalidated project runtime manifest'); }
        } else { if ($kind !== 'runtime') { throw new \RuntimeException('Runtime module mode mismatch'); } }
        $target = Manifest_Reader::target($value->member('target')); $artifacts = Manifest_Reader::hashes($value->member('artifacts'),true);
        $metadata = Manifest_Reader::artifact_name($value->member('metadata')->text());
        if (!isset($artifacts[$metadata])) { throw new \RuntimeException('Unlisted runtime metadata artifact'); }
        if ($project) { if (!isset($artifacts['project.json'])) { throw new \RuntimeException('Missing project source receipt'); } }
        $modules /** hash<string> */ = []; $rows = Package_Syntax::rows($value->member('modules'),'modules');
        foreach ($rows as $row) {
            $format = $row->member('format')->text(); $path = Manifest_Reader::artifact_name($row->member('path')->text());
            if (!isset($artifacts[$path])) { throw new \RuntimeException('Unlisted runtime module artifact'); }
            if ($format === 'llvm_text') { continue; }
            $module = '';
            if ($format === 'llvm_bitcode') { $module = 'ordinary'; }
            elseif ($format === 'full_lto_bitcode') { $module = 'full_lto'; }
            elseif ($format === 'thin_lto_bitcode') { $module = 'thin_lto'; }
            else { throw new \RuntimeException('Unsupported runtime module format'); }
            if (isset($modules[$module])) { throw new \RuntimeException('Duplicate runtime module variant'); }
            $modules[$module] = $path;
        }
        if (!isset($modules['ordinary'])) { throw new \RuntimeException('Missing ordinary runtime implementation'); }
        $driver = $value->member('link_driver'); $executable = Manifest_Reader::identity($driver->member('executable'));
        $arguments = $driver->member('arguments');
        if ($arguments->kind() !== 'array') { throw new \RuntimeException('Invalid runtime link arguments'); }
        if ($arguments->size() !== 2) { throw new \RuntimeException('Invalid runtime link arguments'); }
        if (($arguments->at(0)->text() !== '--driver-mode=g++') || ($arguments->at(1)->text() !== '--target=' . $target->triple)) { throw new \RuntimeException('Invalid runtime link arguments'); }
        $args /** vector<string> */ = [$arguments->at(0)->text(),$arguments->at(1)->text()];
        $hashes = Manifest_Reader::hashes($value->member('inputs')->member('clang'),false);
        return new Package_Manifest($provider,$key,$target,$project,$artifacts,$metadata,$modules,$executable,$args,$hashes);
    }
    public static function metadata(\scpp\Json_View $value, Package_Manifest $manifest): Package_Metadata {
        Manifest_Reader::version($value);
        $target = Manifest_Reader::target($value->member('target'));
        if (($value->member('provider')->text() !== $manifest->provider) || ($target->triple !== $manifest->target->triple)
            || ($target->data_layout !== $manifest->target->data_layout)) { throw new \RuntimeException('Runtime metadata and manifest disagree'); }
        return new Package_Metadata(Package_Syntax::rows($value->member('types'),'types'),Package_Syntax::rows($value->member('operations'),'operations'));
    }
}
