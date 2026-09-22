<?php
declare(strict_types=1);
namespace resolve_symbols;
const LOCAL_READ = 1;
const LOCAL_WRITE = 2;

/** @scpp-struct */
final class Symbol_Binding {
    public int $use_node_id /** uint32 */ = 0;
    public int $target_symbol_id /** uint32 */ = 0;
}
/** @scpp-struct */
final class Lexical_Scope {
    public int $block_node_id /** uint32 */ = 0;
    public int $parent_scope_id /** uint32 */ = 0;
}
/** @scpp-struct */
final class Local_Record {
    public int $declaration_node_id /** uint32 */ = 0;
    public int $scope_id /** uint32 */ = 0;
    public bool $receiver = false;
}
/** @scpp-struct */
final class Local_Binding {
    public int $use_node_id /** uint32 */ = 0;
    public int $local_id /** uint32 */ = 0;
    public int $access /** uint32 */ = 0;
}
/** @scpp-struct */
final class Member_Call_Binding {
    public int $use_node_id /** uint32 */ = 0;
    public int $receiver_node_id /** uint32 */ = 0;
}
/** @scpp-struct */
final class Template_Parameter {
    public int $declaration_node_id /** uint32 */ = 0;
    public int $name_node_id /** uint32 */ = 0;
    public int $type_syntax_id /** uint32 */ = 0;
    public int $contract /** uint32 */ = 0;
}
/** @scpp-struct */
final class Scoped_Constant {
    public int $declaration_node_id /** uint32 */ = 0;
    public int $scope_id /** uint32 */ = 0;
}

/** Explicit value copies at publication and read boundaries. */
final class Binding_Rows {
    public static function symbol_binding(Symbol_Binding $row): Symbol_Binding {
        $out = new Symbol_Binding();
        $out->use_node_id = $row->use_node_id;
        $out->target_symbol_id = $row->target_symbol_id;
        return $out;
    }
    public static function lexical_scope(Lexical_Scope $row): Lexical_Scope {
        $out = new Lexical_Scope();
        $out->block_node_id = $row->block_node_id;
        $out->parent_scope_id = $row->parent_scope_id;
        return $out;
    }
    public static function local_record(Local_Record $row): Local_Record {
        $out = new Local_Record();
        $out->declaration_node_id = $row->declaration_node_id;
        $out->scope_id = $row->scope_id;
        $out->receiver = $row->receiver;
        return $out;
    }
    public static function local_binding(Local_Binding $row): Local_Binding {
        $out = new Local_Binding();
        $out->use_node_id = $row->use_node_id;
        $out->local_id = $row->local_id;
        $out->access = $row->access;
        return $out;
    }
    public static function member_call_binding(Member_Call_Binding $row): Member_Call_Binding {
        $out = new Member_Call_Binding();
        $out->use_node_id = $row->use_node_id;
        $out->receiver_node_id = $row->receiver_node_id;
        return $out;
    }
    public static function template_parameter(Template_Parameter $row): Template_Parameter {
        $out = new Template_Parameter();
        $out->declaration_node_id = $row->declaration_node_id;
        $out->name_node_id = $row->name_node_id;
        $out->type_syntax_id = $row->type_syntax_id;
        $out->contract = $row->contract;
        return $out;
    }
    public static function scoped_constant(Scoped_Constant $row): Scoped_Constant {
        $out = new Scoped_Constant();
        $out->declaration_node_id = $row->declaration_node_id;
        $out->scope_id = $row->scope_id;
        return $out;
    }
}

/** Retain the exact definition whose ordered formal roles were checked. */
final class Template_Application_Binding {
    public function __construct(public readonly int $use_node_id, public readonly \collect_symbols\Symbol_Record $definition) {}
}
