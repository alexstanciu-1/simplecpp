<?php
declare(strict_types=1);
namespace read_sources;

/** Owned bytes from a verified read; treat as immutable once published. */
final class Source_Buffer {
    public string $path = '';
    public string $content = '';
    public int $mtime = 0;
}
final class Source_Texts {
    public array $buffers /** vector<Source_Buffer> */ = [];
    public int $entry_index = 0;
}
