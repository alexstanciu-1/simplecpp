<?php
declare(strict_types=1);

/*
 * Role: Source-anchored compiler errors.
 * Used by: Parser and semantic workers; main.php catches failures
 * Call map:
 *   Source_Error::__construct()
 *     -> [action] compute source location and format diagnostic
 */

namespace diagnostics;

// A source error carries an anchor without retaining the entire failed snapshot.
/**
 * @compiler-api Shared exception contract used by source/semantic producers and caught by CLI.
 * Readable source_file_id, path, start, length identify a byte span in the failed
 * input snapshot; getMessage supplies the reason. No AST/source buffer is retained,
 * so do not resolve its span against a later file version.
 */
class Source_Error extends \RuntimeException
{
    /**
     * @compiler-api Create and throw a diagnostic with the current input's file identity/path/byte span.
     * Constructing the exception performs no source lookup or publication.
     */
    public function __construct(
        public readonly int $source_file_id,
        public readonly string $path,
        public readonly int $start,
        public readonly int $length,
        string $reason
    )
    {
        parent::__construct($path . ': byte ' . $start . ': ' . $reason);
    }
}
