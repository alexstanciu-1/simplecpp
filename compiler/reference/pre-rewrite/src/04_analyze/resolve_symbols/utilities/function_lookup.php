<?php
declare(strict_types=1);

/*
 * Role: Resolve a function name against the symbol store.
 * Used by: Resolution_Worker and Resolution_Validity
 * Call map:
 *   Function_Lookup::find()
 *     -> [action] apply function lookup rules
 */

namespace resolve_symbols;

use collect_symbols\Symbol_Store;
use collect_symbols\symbol_record;
use collect_symbols\symbol_kind;

/** @compiler-internal Project function lookup shared by callable workers and binding-reuse checks. */
class Function_Lookup
{
    /** @compiler-internal Read exact callee spelling in the owner's snapshot; return a project ID or zero. */
    public static function find(symbol_record $owner, int $name_id, Symbol_Store $symbols): int
    {
        $name = $owner->frontend->syntax->nodes[$name_id - 1];
        $text = substr($owner->frontend->tokens->source->content, $name->start, $name->length);

        // Unqualified calls in the supported grammar refer to project functions.
        $id = $symbols->find_symbol($text, $owner->namespace_name, symbol_kind::function_symbol);
        return $id !== 0 ? $id : $symbols->find_symbol($text, $owner->namespace_name, symbol_kind::template_function);
    }
}
