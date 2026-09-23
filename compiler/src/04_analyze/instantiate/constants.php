<?php
declare(strict_types=1);
namespace instantiate;
/** Resolve selected global literal constants without executing expressions. */
final class Constant_Worker {
    public static function require_owner(\collect_symbols\Symbol_Record $owner, Bindings $reader): void {
        if (!$owner->is_source()) { throw new \LogicException('Constant task requires source syntax'); }
        if ($owner->kind()!==\collect_symbols\SYMBOL_CONSTANT) { throw new \LogicException('Constant task requires a constant declaration'); }
        $reader->annotations->bindings($owner);
    }
    public static function resolve(\collect_symbols\Symbol_Record $owner, Bindings $reader): Template_Argument {
        Constant_Worker::require_owner($owner,$reader);
        $parts=\parse\Syntax_Access::constant_parts($owner->source_frontend()->tree,(int)$owner->source_fact()->declaration_node_id);
        $type=Constant_Worker::type($owner,(int)$parts->type_syntax_id,$reader);
        return $reader->literal($owner,(int)$parts->initializer_id,$type);
    }
    public static function type(\collect_symbols\Symbol_Record $owner, int $annotation, Bindings $reader): \type_model\Named_Definition {
        $type=$reader->catalog->integer_literal_type;
        if ($annotation!==0) {
            $declared=$reader->annotations->bound_definition($owner,$annotation);
            if ($declared===null) { $reader->annotations->fail($owner,$annotation,'Only integer literal constants are supported'); }
            $type=$declared;
        }
        if ($type->representation->kind()!==\type_model\REPRESENTATION_INTEGER) { $reader->annotations->fail($owner,$annotation,'Only integer literal constants are supported'); }
        return $type;
    }
}
/** Fixed tasks and complete keyed worker outputs; no retained state is mutated. */
final class Constant_Join {
    private array $tasks /** hash<\collect_symbols\Symbol_Record,int> */ = [];
    public function __construct(array $tasks /** vector<\collect_symbols\Symbol_Record> */, private readonly Bindings $reader) {
        foreach ($tasks as $owner) {
            Constant_Worker::require_owner($owner,$reader);
            if (isset($this->tasks[$owner->symbol_id])) { throw new \LogicException('Duplicate constant task'); }
            $this->tasks[$owner->symbol_id]=$owner;
        }
    }
    /** Verify exact spelling/type provenance without repeating integer decoding and range checks. */
    public function join(array $results /** hash<Template_Argument,int> */): array /** hash<Template_Argument,int> */ {
        if (q_count($results)!==q_count($this->tasks)) { throw new \LogicException('Incomplete constant preparation'); }
        $ordered /** hash<Template_Argument,int> */ = [];
        foreach ($this->tasks as $id=>$owner) {
            $frontend=$owner->source_frontend();
            $parts=\parse\Syntax_Access::constant_parts($frontend->tree,(int)$owner->source_fact()->declaration_node_id);
            $node=$frontend->tree->row((int)$parts->initializer_id);
            if ((int)$node->kind!==\parse\SYNTAX_INTEGER_LITERAL) { throw new \LogicException('Stale literal constant result'); }
            if (!isset($results[$id])) { throw new \LogicException('Missing constant result'); }
            $result=$results[$id];
            if ($result->type!==Constant_Worker::type($owner,(int)$parts->type_syntax_id,$this->reader)) { throw new \LogicException('Stale literal constant type'); }
            $text=string_byte_slice($frontend->tokens->source->content,(int)$node->start,(int)$node->length);
            $first=0;$length=string_byte_len($text);
            while ($first<$length) { if (string_byte_at($text,$first)!==48) { break; } $first++; }
            $normal='0';if($first<$length){$normal=string_byte_slice($text,$first,$length-$first);}
            if ($result->value!==$normal) { throw new \LogicException('Stale literal constant value'); }
            $ordered[$id]=$result;
        }
        return $ordered;
    }
}
