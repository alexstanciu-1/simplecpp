<?php
declare(strict_types=1);
namespace read_sources;

/** Fresh observation; positions are not persistent file IDs. */
final class Source_File {
    public string $path = '';
    public string $relative_path = '';
    public int $root_index = 0;
    public int $mtime = 0;
    public int $size = 0;
}
final class Source_Listing {
    public array $roots /** vector<string> */ = [];
    public array $files /** vector<Source_File> */ = [];
    public int $entry_index = 0;
}
/** Internal breadth-first work item. */
final class Scan_Directory {
    public int $root_index = 0;
    public string $relative_path = '';
}
