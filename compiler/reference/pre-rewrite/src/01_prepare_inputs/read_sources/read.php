<?php
declare(strict_types=1);

/*
 * Role: Read and verify one selected file snapshot.
 * Used by: Source_Reader::run()
 * Call map:
 *   Snapshot_Reader::read()
 *     -> require_read_version(); [action] read bytes and close handle
 */

namespace read_sources;

/** @compiler-api Read one fixed source task into a private buffer; no shared source-state mutation. */
class Snapshot_Reader
{
    /**
     * @compiler-api Read one selected regular file, checking path/open-handle identity, mtime and size.
     * Returns a private immutable buffer or throws for read/version failure. Whole-second
     * mtime plus size still has the documented edit-timing limitation.
     */
    public static function read(source_read_task $task): Source_Buffer
    {
        clearstatcache(true);
        $before = @lstat($task->path);
        if (($before === false) || (($before['mode'] & 0170000) !== 0100000)) {
            throw new \Exception("Source path is not a regular file: " . $task->path);
        }
        self::require_read_version($task, $before, $before);
        $handle = @fopen($task->path, 'rb');
        if ($handle === false) {
            throw new \Exception("Cannot read source file: " . $task->path);
        }
        try
        {
            $opened = @fstat($handle);
            self::require_read_version($task, $before, $opened);

            // One extra byte detects growth without reading an unbounded stream.
            $content = @stream_get_contents($handle, $task->size + 1);
            if ($content === false) {
                throw new \Exception("Cannot read source file: " . $task->path);
            }

            // Reject both mid-read changes and replacement of the path behind the open handle.
            $after = @fstat($handle);
            clearstatcache(true, $task->path);
            $named = @lstat($task->path);
            self::require_read_version($task, $before, $after);
            self::require_read_version($task, $before, $named);
            if (strlen($content) !== $task->size) {
                throw new \Exception("Source changed during reading; retry compilation: " . $task->path);
            }
            return new Source_Buffer($task->source_file_id, $task->path, $task->mtime, $content);
        }
        finally {
            fclose($handle);
        }
    }

    private static function require_read_version(source_read_task $task, array $before, array|false $observed): void
    {
        if (($observed === false) || (($observed['mode'] & 0170000) !== 0100000)
            || ($observed['mtime'] !== $task->mtime) || ($observed['size'] !== $task->size)
            || ($observed['dev'] !== $before['dev']) || ($observed['ino'] !== $before['ino'])) {
            throw new \Exception("Source changed during reading; retry compilation: " . $task->path);
        }
    }
}
