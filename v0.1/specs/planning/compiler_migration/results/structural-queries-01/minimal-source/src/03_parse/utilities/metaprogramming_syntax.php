<?php
declare(strict_types=1);

/*
 * Role: Expose template and constant syntax roles without binding or evaluation.
 * Used by: Syntax_Access (static structural query methods composed on this owner)
 * Call map:
 *   template_parts(); template_parameter_parts(); template_application_parts(); constant_parts()
 *     -> type_syntax(); leaf(); expression() [shared structural validation]
 */

namespace parse;
// <scpp-imports>
use function scpp\sequence_require_strings as sequence_require_strings;
use function scpp\fs_is_link as fs_is_link;
use function scpp\fs_is_dir as fs_is_dir;
use function scpp\fs_is_file as fs_is_file;
use function scpp\fs_size as fs_size;
use function scpp\fs_mtime as fs_mtime;
use function scpp\fs_scan as fs_scan;
use function scpp\json_quote as json_quote;
use function scpp\string_byte_from_int as string_byte_from_int;
use function scpp\enum_name as enum_name;
use function scpp\lock_empty as lock_empty;
use function scpp\lock_try as lock_try;
use function scpp\lock_release as lock_release;
use function scpp\lock_transfer as lock_transfer;
use function scpp\process_spawn as process_spawn;
use function scpp\process_poll as process_poll;
use function scpp\process_output as process_output;
use function scpp\process_stop as process_stop;
use function scpp\process_close as process_close;
use function scpp\sequence_map as sequence_map;
use function scpp\sequence_filter as sequence_filter;
use function scpp\keyed_map as keyed_map;
use function scpp\keyed_filter as keyed_filter;
use function scpp\string_byte_len as string_byte_len;
use function scpp\string_byte_starts_with as string_byte_starts_with;
use function scpp\string_byte_ends_with as string_byte_ends_with;
use function scpp\string_utf8_is_valid as string_utf8_is_valid;
use function scpp\string_codepoint_at as string_codepoint_at;
use function scpp\compat\substr as substr;
use function scpp\compat\strpos as strpos;
use function scpp\compat\strrpos as strrpos;
use function scpp\same_exception as same_exception;
use function scpp\string_byte_at as string_byte_at;
use function scpp\take_nullable as take_nullable;
use function scpp\take_false as take_false;
use function scpp\take_bool as take_bool;
use function scpp\compat\str_starts_with as str_starts_with;
use function scpp\compat\str_ends_with as str_ends_with;
use function scpp\compat\strlen as strlen;
use function scpp\string_byte_slice as string_byte_slice;
// </scpp-imports>

/** @compiler-internal Structural accessors composed by Syntax_Access; inputs remain immutable. */
trait Metaprogramming_Syntax
{
    /** Return the ordinary declaration beneath template/evaluation wrappers, without changing their syntax. */
    public static function underlying_declaration(Syntax_Tree $tree, int $id): int
    {
        if (Syntax_Access::is_kind($tree, $id, syntax_kind::template_declaration)) {
            $id = Syntax_Access::template_parts($tree, $id)->declaration_id;
        }
        if (Syntax_Access::is_kind($tree, $id, syntax_kind::method_declaration)) {
            $id = $tree->nodes[$id - 1]->first_child_id;
        }
        if ((Syntax_Access::is_kind($tree, $id, syntax_kind::constexpr_declaration) || Syntax_Access::is_kind($tree, $id, syntax_kind::consteval_declaration))) {
            $id = Syntax_Access::evaluated_function($tree, $id);
        }
        return $id;
    }

    /** @compiler-api Validate the root of type syntax; nested arguments retain their unresolved expression form. */
    public static function type_syntax(Syntax_Tree $tree, int $id): syntax_node
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed type syntax');
        if ($node->kind === syntax_kind::name) {
            return Syntax_Access::leaf($tree, $id, syntax_kind::name);
        }
        if ($node->kind !== syntax_kind::template_application) {
            throw new \LogicException('Expected parsed type syntax');
        }
        Syntax_Access::template_application_parts($tree, $id);
        return $node;
    }

    /** @compiler-api Read an application's name and first argument; siblings preserve argument order and arity. */
    public static function template_application_parts(Syntax_Tree $tree, int $id): template_application_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed template application');
        if ($node->kind !== syntax_kind::template_application) {
            throw new \LogicException('Expected parsed template application');
        }
        $name = Syntax_Access::leaf($tree, $node->first_child_id, syntax_kind::name);
        if ($name->next_sibling_id === 0) {
            throw new \LogicException('Expected nonempty template arguments');
        }
        Syntax_Access::expression($tree, $name->next_sibling_id);
        return new \parse\template_application_parts($node->first_child_id, $name->next_sibling_id);
    }

    /** @compiler-api Expose the parameter list and wrapped declaration, preserving its ordinary source shape. */
    public static function template_parts(Syntax_Tree $tree, int $id): template_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed template declaration');
        if ($node->kind !== syntax_kind::template_declaration) {
            throw new \LogicException('Expected parsed template declaration');
        }
        $parameters = Syntax_Access::require_node($tree, $node->first_child_id, 'Expected nonempty template parameter list');
        if (($parameters->kind !== syntax_kind::template_parameter_list) || ($parameters->first_child_id === 0)) {
            throw new \LogicException('Expected nonempty template parameter list');
        }
        Syntax_Access::template_parameter_parts($tree, $parameters->first_child_id);
        $declaration = Syntax_Access::require_node($tree, $parameters->next_sibling_id, 'Invalid wrapped template declaration');
        if (($declaration->next_sibling_id !== 0)
            || !(($declaration->kind === syntax_kind::struct_declaration) || ($declaration->kind === syntax_kind::function_declaration)
            || ($declaration->kind === syntax_kind::constexpr_declaration) || ($declaration->kind === syntax_kind::consteval_declaration))) {
            throw new \LogicException('Invalid wrapped template declaration');
        }
        return new \parse\template_parts($node->first_child_id, $parameters->next_sibling_id);
    }

    /** @compiler-api Distinguish type parameters from typed values without classifying their names semantically. */
    public static function template_parameter_parts(Syntax_Tree $tree, int $id): template_parameter_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed template parameter');
        if (!(($node->kind === syntax_kind::type_parameter_declaration) || ($node->kind === syntax_kind::value_parameter_declaration))) {
            throw new \LogicException('Expected parsed template parameter');
        }
        $name_id = $node->first_child_id;
        $type_id = 0;
        if ($node->kind === syntax_kind::value_parameter_declaration) {
            $type_id = $name_id;
            $name_id = Syntax_Access::type_syntax($tree, $type_id)->next_sibling_id;
        }
        $name = Syntax_Access::leaf($tree, $name_id, syntax_kind::name);
        if ($name->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected template parameter child');
        }
        return new \parse\template_parameter_parts($name_id, $type_id);
    }

    /** @compiler-api Return the ordinary function wrapped by one constexpr/consteval declaration specifier. */
    public static function evaluated_function(Syntax_Tree $tree, int $id): int
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed function evaluation specifier');
        if (!(($node->kind === syntax_kind::constexpr_declaration) || ($node->kind === syntax_kind::consteval_declaration))) {
            throw new \LogicException('Expected parsed function evaluation specifier');
        }
        Syntax_Access::function_parts($tree, $node->first_child_id);
        if ($tree->nodes[$node->first_child_id - 1]->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected function evaluation specifier child');
        }
        return $node->first_child_id;
    }

    /** @compiler-api Read a constant initializer and optional type annotation without demanding its value. */
    public static function constant_parts(Syntax_Tree $tree, int $id): constant_parts
    {
        $node = Syntax_Access::require_node($tree, $id, 'Expected parsed constant declaration');
        if ($node->kind !== syntax_kind::constant_declaration) {
            throw new \LogicException('Expected parsed constant declaration');
        }
        $name = Syntax_Access::leaf($tree, $node->first_child_id, syntax_kind::name);
        $initializer = $name->next_sibling_id;
        $type_id = 0;
        if (Syntax_Access::is_kind($tree, $initializer, syntax_kind::type_annotation))
        {
            $annotation = $tree->nodes[$initializer - 1];
            $type_id = $annotation->first_child_id;
            if (Syntax_Access::type_syntax($tree, $type_id)->next_sibling_id !== 0) {
                throw new \LogicException('Unexpected constant type child');
            }
            $initializer = $annotation->next_sibling_id;
        }
        if (Syntax_Access::expression($tree, $initializer)->next_sibling_id !== 0) {
            throw new \LogicException('Unexpected constant declaration child');
        }
        return new \parse\constant_parts($node->first_child_id, $type_id, $initializer);
    }
}
