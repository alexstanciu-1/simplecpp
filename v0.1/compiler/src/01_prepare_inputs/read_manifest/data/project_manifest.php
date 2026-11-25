<?php
declare(strict_types=1);
namespace read_manifest;

/** Normalized immutable-by-usage stage result; original bytes support later change detection. */
final class Project_Manifest {
    public string $path = '';
    public string $directory = '';
    public string $content = '';
    public bool $single_source = false;
    public array $source_folders /** vector<string> */ = [];
    public array $source_files /** vector<string> */ = [];
    public string $entry = '';
}
