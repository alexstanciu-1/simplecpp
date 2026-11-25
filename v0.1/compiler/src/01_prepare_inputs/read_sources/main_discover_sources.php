<?php
declare(strict_types=1);
namespace read_sources;

/** Publish only a complete selection; caller inputs and earlier results stay untouched. */
final class Source_Discovery {
    public static function discover(\read_manifest\Project_Manifest $manifest): Source_Listing {
        $listing = new Source_Listing();
        $listing->entry_index = -1;
        if ($manifest->single_source) {
            $entry = Source_Paths::resolve($manifest->directory, $manifest->entry);
            $listing->roots[] = fs_dirname($entry);
            $listing->files[] = Source_Scanner::file($entry, fs_basename($entry), 0);
            $listing->entry_index = 0;
            return $listing;
        }
        foreach ($manifest->source_folders as $configured) {
            $root = Source_Paths::resolve($manifest->directory, $configured);
            if (!fs_is_dir($root)) { throw new \RuntimeException('Source root is not a directory: ' . $root); }
            foreach ($listing->roots as $other) {
                if (Source_Paths::overlaps($root, $other)) { throw new \InvalidArgumentException('Overlapping source roots'); }
            }
            $listing->roots[] = $root;
        }
        Source_Scanner::scan($listing);
        $entry = Source_Paths::resolve($manifest->directory, $manifest->entry);
        foreach ($listing->files as $index => $file) {
            if ($file->path === $entry) { $listing->entry_index = $index; }
        }
        if ($listing->entry_index < 0) { throw new \InvalidArgumentException('Entry is not a participating source file'); }
        return $listing;
    }
}
