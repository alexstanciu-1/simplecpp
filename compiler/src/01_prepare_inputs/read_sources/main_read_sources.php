<?php
declare(strict_types=1);
namespace read_sources;

/** One complete read selection; failed reads never publish a partial result. */
final class Source_Reader {
    public static function read(Source_Listing $listing): Source_Texts {
        if ($listing->entry_index < 0) { throw new \InvalidArgumentException('Missing source entry'); }
        if ($listing->entry_index >= q_count($listing->files)) { throw new \InvalidArgumentException('Invalid source entry position'); }
        $texts = new Source_Texts();
        $texts->entry_index = $listing->entry_index;
        foreach ($listing->files as $file) {
            $texts->buffers[] = Snapshot_Reader::read($file);
        }
        return $texts;
    }
}
