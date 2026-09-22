<?php
declare(strict_types=1);
namespace discovery_test;
final class Probe {
    public static function run(string $path): void {
        try {
            $manifest = \read_manifest\Manifest_Reader::read($path);
            $listing = \read_sources\Source_Discovery::discover($manifest);
            echo '{"entry":', $listing->entry_index, ',"roots":[';
            $first = true;
            foreach ($listing->roots as $root) {
                if (!$first) { echo ','; }
                $first = false;
                echo json_quote($root);
            }
            echo '],"files":[';
            $first = true;
            foreach ($listing->files as $file) {
                if (!$first) { echo ','; }
                $first = false;
                echo '{"path":', json_quote($file->path), ',"relative":', json_quote($file->relative_path),
                    ',"root":', $file->root_index, ',"size":', $file->size, ',"mtime":', $file->mtime, '}';
            }
            echo "]}\n";
        } catch (\Exception $error) { echo "{\"error\":true}\n"; }
    }
    public static function syntax(string $path): void {
        echo '{"posix":', \read_sources\Source_Paths::is_absolute($path, false) ? 'true' : 'false',
            ',"windows":', \read_sources\Source_Paths::is_absolute($path, true) ? 'true' : 'false',
            ',"normalized":', json_quote(\read_sources\Source_Paths::normalize($path, true)),
            ',"preserved":', json_quote(\read_sources\Source_Paths::normalize($path, false)), "}\n";
    }
    public static function host(): void {
        echo '{"windows_host":', fs_is_windows() ? 'true' : 'false', "}\n";
    }
}
