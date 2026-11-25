<?php
declare(strict_types=1);

/*
 * Role: Selected literal-constant preparation and exact source-result acceptance.
 * Used by: Concrete_Preparation::run()
 * Call map: Constant_Worker::resolve(); Constant_Join::join()
 */
namespace instantiate;

final class Constant_Worker
{
    /** Validate a global literal constant against the configured language integer contract. */
    public static function resolve(\collect_symbols\symbol_record $owner, \resolve_symbols\Resolution_Set $names,
        \type_model\Type_Catalog $catalog): template_argument
    {
        $parts = \parse\Syntax_Access::constant_parts($owner->frontend->syntax, $owner->declaration_node_id);
        $type = self::type($owner, $parts->type_syntax_id, $catalog, $names);
        return Bindings::literal($owner, $parts->initializer_id, $type);
    }

    /** Integer widths come from named contracts; literals are range-checked without expression evaluation. */
    public static function type(\collect_symbols\symbol_record $owner, int $annotation,
        \type_model\Type_Catalog $catalog, ?\resolve_symbols\Resolution_Set $names): \type_model\named_type_definition
    {
        $type = $annotation === 0 ? $catalog->integer_literal_type
            : \resolve_types\Annotation_Types::bound_definition($owner, $annotation, $catalog,
                $names ?? throw new \LogicException('Annotated constant needs fixed bindings'));
        if ($type?->representation->kind !== \type_model\representation_kind::integer) {
            Bindings::fail($owner, $annotation, 'Only integer literal constants are supported');
        }
        return $type;
    }

}

final class Constant_Join implements \compile\Join
{
    /** @param array<int, \collect_symbols\symbol_record> $tasks Selected constants keyed by definition ID. */
    public function __construct(private readonly array $tasks, private readonly \type_model\Type_Catalog $catalog,
        private readonly ?\resolve_symbols\Resolution_Set $names = null)
    {
    }

    /** Accept keyed private outputs without repeating decoding/range calculation. */
    public function join(array $results): array
    {
        if (count($results) !== count($this->tasks)) {
            throw new \LogicException('Incomplete constant preparation');
        }
        $ordered = [];
        foreach ($this->tasks as $id => $owner)
        {
            $parts = \parse\Syntax_Access::constant_parts($owner->frontend->syntax, $owner->declaration_node_id);
            $node = $owner->frontend->syntax->nodes[$parts->initializer_id - 1];
            $text = substr($owner->frontend->tokens->source->content, $node->start, $node->length);
            if (($node->kind !== \parse\syntax_kind::integer_literal) || !isset($results[$id]) || ($results[$id]->type !== Constant_Worker::type($owner, $parts->type_syntax_id, $this->catalog, $this->names))
                || ($results[$id]->value !== (ltrim($text, '0') ?: '0'))) {
                throw new \LogicException('Stale literal constant result');
            }
            $ordered[$id] = $results[$id];
        }
        return $ordered;
    }
}
