<?php
declare(strict_types=1);

/*
 * Role: Observe one fixed source selection: directory discovery or explicit files.
 * Used by: Source_Discovery::run()
 * Call map:
 *   Source_Scanner::scan()
 *     -> [action] inspect entries and return Source_Scan_Result
 */

namespace read_sources;

/**
 * @compiler-internal Discovery worker implementation; invoked by Source_Discovery, not other stages.
 * A scan task is an independently executable source selection; it owns no global IDs.
 */
class Baseline_Source_Scanner
{
    /**
     * @compiler-internal Observe a fixed file selection or a sorted directory listing, retaining the exact task.
     * No shared writes or recursive scheduling; filesystem failures throw.
     */
    public static function scan(source_scan_task $task): Source_Scan_Result
    {
        // A worker can be reused across refreshes. Discard cached metadata.
        clearstatcache(true);
        $directory = Source_Paths::join($task->root, $task->relative_directory);

        // Recheck a queued directory before scanning it, too.
        if (is_link($directory)) {
            throw new \Exception("Symbolic links inside source roots are unsupported: " . $directory);
        }
        $entries = $task->file_names ?? @scandir($directory);
        if ($entries === false) {
            throw new \Exception("Cannot scan source directory: " . $directory);
        }
        $files = [];
        $directories = [];
        foreach ($entries as $name)
        {
            if (($name === ".") || ($name === "..")) {
                continue;
            }
            $relative = $task->relative_directory === "" ? $name
                : Source_Paths::join($task->relative_directory, $name);
            $path = Source_Paths::join($task->root, $relative);
            if (is_link($path)) {
                throw new \Exception("Symbolic links inside source roots are unsupported: " . $path);
            }
            if (($task->file_names === null) && is_dir($path)) {
                $directories[] = $relative;
            }
            else if (($task->file_names !== null) || Source_Paths::is_source($path))
            {
                if (!is_file($path)) {
                    throw new \Exception("Source path is not a regular file: " . $path);
                }
                $mtime = @filemtime($path);
                $size = @filesize($path);
                if (($mtime === false) || ($size === false)) {
                    throw new \Exception("Cannot read source metadata: " . $path);
                }
                $files[] = new scanned_source_file($relative, $mtime, $size);
            }
        }
        return new Source_Scan_Result($task, $files, $directories);
    }
}
