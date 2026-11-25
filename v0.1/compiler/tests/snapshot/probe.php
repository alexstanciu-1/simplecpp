<?php
declare(strict_types=1);
namespace snapshot_test;
final class Probe {
    public static function file(string $path, int $mtime, int $size): void {
        $file = new \read_sources\Source_File();
        $file->path = $path;
        $file->mtime = $mtime;
        $file->size = $size;
        try {
            $buffer = \read_sources\Snapshot_Reader::read($file);
            echo '{"path":', json_quote($buffer->path), ',"mtime":', $buffer->mtime, ',"bytes":[';
            for ($i /** int */ = 0; $i < string_byte_len($buffer->content); ++$i) {
                if ($i !== 0) { echo ','; }
                echo string_byte_at($buffer->content, $i);
            }
            echo "]}\n";
        } catch (\Exception $error) { echo "{\"error\":true}\n"; }
    }
    public static function project(string $path): void {
        try {
            $manifest = \read_manifest\Manifest_Reader::read($path);
            $listing = \read_sources\Source_Discovery::discover($manifest);
            $texts = \read_sources\Source_Reader::read($listing);
            echo '{"entry":', $texts->entry_index, ',"contents":[';
            $first = true;
            foreach ($texts->buffers as $buffer) {
                if (!$first) { echo ','; }
                $first = false;
                echo json_quote($buffer->content);
            }
            // Returned bytes stay owned when a second result is changed.
            $second = \read_sources\Source_Reader::read($listing);
            $other = $second->buffers[0];
            $other->content = 'changed';
            $retained = $texts->buffers[0];
            echo '],"retained":', json_quote($retained->content), "}\n";
        } catch (\Exception $error) { echo "{\"error\":true}\n"; }
    }
    public static function invalid_listing(int $entry): void {
        $listing = new \read_sources\Source_Listing();
        $listing->entry_index = $entry;
        try {
            $texts = \read_sources\Source_Reader::read($listing);
            echo "{\"unexpected\":true}\n";
        } catch (\Exception $error) { echo "{\"error\":true}\n"; }
    }
    public static function partial(string $path, int $mtime): void {
        $listing = new \read_sources\Source_Listing();
        $good = new \read_sources\Source_File();
        $good->path = $path;
        $good->mtime = $mtime;
        $good->size = 3;
        $bad = new \read_sources\Source_File();
        $bad->path = $path . '.missing';
        $listing->files[] = $good;
        $listing->files[] = $bad;
        try {
            $texts = \read_sources\Source_Reader::read($listing);
            echo "{\"unexpected\":true}\n";
        } catch (\Exception $error) {
            echo '{"partial_failed":true,"input_count":', q_count($listing->files), ',"input_size":', $good->size, "}\n";
        }
    }
}
