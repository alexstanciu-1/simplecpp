<?php
declare(strict_types=1);
namespace read_manifest;

/** JSON schema validation only; does not resolve or read configured source paths. */
final class Manifest_Syntax {
    public static function parse(string $path, string $content): Project_Manifest {
        $root = json_read($content);
        if ($root->kind() !== 'object') { throw new \InvalidArgumentException('Manifest must be an object'); }
        if (!$root->has('source_folders')) { throw new \InvalidArgumentException('Missing source_folders'); }
        if (!$root->has('entry')) { throw new \InvalidArgumentException('Missing entry'); }
        for ($i /** int */ = 0; $i < $root->size(); ++$i) {
            $key = $root->key($i);
            if (($key !== 'source_folders') && ($key !== 'entry')) {
                throw new \InvalidArgumentException('Unknown manifest setting: ' . $key);
            }
        }
        $folders = $root->member('source_folders');
        if ($folders->kind() !== 'array') { throw new \InvalidArgumentException('source_folders must be a list'); }
        if ($folders->size() === 0) { throw new \InvalidArgumentException('source_folders must be nonempty'); }
        $manifest = new Project_Manifest();
        $manifest->path = $path;
        $manifest->content = $content;
        $seen /** hash<bool> */ = [];
        for ($i /** int */ = 0; $i < $folders->size(); ++$i) {
            $folder = Manifest_Syntax::path($folders->at($i));
            if (isset($seen[$folder])) { throw new \InvalidArgumentException('Duplicate source folder: ' . $folder); }
            $seen[$folder] = true;
            $manifest->source_folders[] = $folder;
        }
        $manifest->entry = Manifest_Syntax::path($root->member('entry'));
        return $manifest;
    }

    private static function path(\scpp\Json_View $node): string {
        if ($node->kind() !== 'string') { throw new \InvalidArgumentException('Expected a string path'); }
        $value = $node->text();
        if (string_byte_len($value) === 0) { throw new \InvalidArgumentException('Expected a nonempty path'); }
        for ($i /** int */ = 0; $i < string_byte_len($value); ++$i) {
            if (string_byte_at($value, $i) === 0) { throw new \InvalidArgumentException('Path contains NUL'); }
        }
        return $value;
    }
}
