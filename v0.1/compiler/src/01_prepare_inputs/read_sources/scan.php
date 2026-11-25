<?php
declare(strict_types=1);
namespace read_sources;

/** Filesystem observations only; no file contents or shared publication. */
final class Source_Scanner {
    public static function file(string $path, string $relative, int $root_index): Source_File {
        if (fs_is_link($path)) { throw new \RuntimeException('Symbolic links inside source roots are unsupported: ' . $path); }
        if (!fs_is_file($path)) { throw new \RuntimeException('Source path is not a regular file: ' . $path); }
        $mtime /** int */ = 0;
        $size /** int */ = 0;
        if (!take_false($mtime, fs_mtime($path))) { throw new \RuntimeException('Cannot read source mtime: ' . $path); }
        if (!take_false($size, fs_size($path))) { throw new \RuntimeException('Cannot read source size: ' . $path); }
        $file = new Source_File();
        $file->path = $path;
        $file->relative_path = $relative;
        $file->root_index = $root_index;
        $file->mtime = $mtime;
        $file->size = $size;
        return $file;
    }
    public static function scan(Source_Listing $listing): void {
        $queue /** vector<Scan_Directory> */ = [];
        foreach ($listing->roots as $index => $root) {
            $task = new Scan_Directory();
            $task->root_index = $index;
            $queue[] = $task;
        }
        for ($head /** int */ = 0; $head < q_count($queue); ++$head) {
            $task = $queue[$head];
            $root = $listing->roots[$task->root_index];
            $directory = $task->relative_path === '' ? $root : Source_Paths::join($root, $task->relative_path);
            if (fs_is_link($directory)) { throw new \RuntimeException('Symbolic source directory: ' . $directory); }
            $entries /** vector<string> */ = [];
            if (!take_false($entries, fs_scan($directory))) { throw new \RuntimeException('Cannot scan source directory: ' . $directory); }
            foreach ($entries as $name) {
                $relative = $task->relative_path === '' ? $name : Source_Paths::join($task->relative_path, $name);
                $path = Source_Paths::join($root, $relative);
                if (fs_is_link($path)) { throw new \RuntimeException('Symbolic links inside source roots are unsupported: ' . $path); }
                if (fs_is_dir($path)) {
                    $child = new Scan_Directory();
                    $child->root_index = $task->root_index;
                    $child->relative_path = $relative;
                    $queue[] = $child;
                } else if (Source_Paths::is_source($path)) {
                    $listing->files[] = Source_Scanner::file($path, $relative, $task->root_index);
                }
            }
        }
    }
}
