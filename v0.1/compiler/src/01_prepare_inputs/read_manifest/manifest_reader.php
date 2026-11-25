<?php
declare(strict_types=1);
namespace read_manifest;

/** One read produces one complete result, with no shared publication/lifecycle state. */
final class Manifest_Reader {
    public static function read(string $path): Project_Manifest {
        $resolved = fs_require_realpath($path);
        if (!fs_is_file($resolved)) { throw new \RuntimeException('Project input must be a regular file: ' . $path); }
        if (string_byte_ends_with($path, '.phs')) {
            $manifest = new Project_Manifest();
            $manifest->path = $path;
            $manifest->directory = fs_dirname($resolved);
            $manifest->single_source = true;
            $manifest->entry = fs_basename($resolved);
            $manifest->source_files[] = $manifest->entry;
            return $manifest;
        }
        $content = fs_read_text($resolved);
        $manifest = Manifest_Syntax::parse($path, $content);
        $manifest->directory = fs_dirname($resolved);
        return $manifest;
    }
}
