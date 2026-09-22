<?php
declare(strict_types=1);

/*
 * Role: Validate the current file/frontend identity.
 * Used by: Collection selection and Declaration_Join
 * Call map:
 *   Frontend_Validation::current_frontend()
 *     -> [action] check exact source, token and frontend association
 */

namespace collect_symbols;

use parse\File_Frontend;
use parse\Frontend_Set;
use read_sources\Source_Set;
use read_sources\file_change;

/** @compiler-internal Shared current-frontend validation for declaration selection and joining. */
class Frontend_Validation
{
    /** Require a live source and frontend backed by its exact current source buffer. */
    public static function current_frontend(Source_Set $sources, Frontend_Set $frontends, int $id): File_Frontend
    {
        $file = $sources->file_by_id($id);
        $frontend = $frontends->for_file($id);
        if (($file->change_state === file_change::deleted) || ($frontend === null)
            || ($frontend->tokens->source !== $file->buffer)) {
            throw new \LogicException('Removed, missing or stale frontend for declaration collection');
        }
        return $frontend;
    }
}
