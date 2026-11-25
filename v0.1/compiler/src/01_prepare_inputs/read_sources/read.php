<?php
declare(strict_types=1);
namespace read_sources;

final class Snapshot_Reader {
    public static function read(Source_File $file): Source_Buffer {
        $content = fs_read_snapshot($file->path, $file->mtime, $file->size);
        $buffer = new Source_Buffer();
        $buffer->path = $file->path;
        $buffer->mtime = $file->mtime;
        $buffer->content = $content;
        return $buffer;
    }
}
