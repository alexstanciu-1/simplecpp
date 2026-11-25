<?php
declare(strict_types=1);
namespace syntax_test;
final class Probe {
    public static function read(string $path): \parse\Parse_Result {
        $source = new \read_sources\Source_Buffer();
        $source->content = fs_read_text($path);
        $result = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        if (!$result->valid) { throw new \LogicException('Invalid proof input'); }
        return $result;
    }
    private static function node(\parse\Syntax_Arena $tree, int $id): void {
        if ($id === 0) { echo 'null'; return; }
        $row = $tree->row($id);
        echo '[', $row->kind, ',', $row->start, ',', $row->length, ']';
    }
    public static function roles(string $path): void {
        $file = Probe::read($path);
        $tree = $file->tree;
        $queue /** vector<int> */ = [];
        $queue[] = $file->root;
        echo '[';
        for ($i /** int */ = 0; $i < q_count($queue); ++$i) {
            $id = $queue[$i];
            $node = $tree->row($id);
            $kind = (int)$node->kind;
            if ($i !== 0) { echo ','; }
            echo '[';
            if ($kind === \parse\SYNTAX_FUNCTION_DECLARATION) {
                $view_0 = \parse\Syntax_Access::function_parts($tree, $id);
                Probe::node($tree, (int)$view_0->name_id);
                echo ',';
                Probe::node($tree, (int)$view_0->parameters_id);
                echo ',';
                Probe::node($tree, (int)$view_0->return_type_id);
                echo ',';
                Probe::node($tree, (int)$view_0->body_id);
            }
            if ($kind === \parse\SYNTAX_STRUCT_DECLARATION) {
                $view_1 = \parse\Syntax_Access::struct_parts($tree, $id);
                Probe::node($tree, (int)$view_1->name_id);
                echo ',';
                Probe::node($tree, (int)$view_1->first_member_id);
            }
            if ($kind === \parse\SYNTAX_FIELD_DECLARATION) {
                $view_2 = \parse\Syntax_Access::field_declaration_parts($tree, $id);
                Probe::node($tree, (int)$view_2->type_syntax_id);
                echo ',';
                Probe::node($tree, (int)$view_2->variable_id);
                echo ',';
                Probe::node($tree, (int)$view_2->extent_id);
            }
            if ($kind === \parse\SYNTAX_PARAMETER_DECLARATION) {
                $view_3 = \parse\Syntax_Access::parameter_parts($tree, $id);
                Probe::node($tree, (int)$view_3->variable_id);
                echo ',';
                Probe::node($tree, (int)$view_3->type_syntax_id);
                echo ',';
                echo (int)$view_3->reference;
            }
            if ($kind === \parse\SYNTAX_LOCAL_DECLARATION) {
                $view_4 = \parse\Syntax_Access::local_declaration_parts($tree, $id);
                Probe::node($tree, (int)$view_4->variable_id);
                echo ',';
                Probe::node($tree, (int)$view_4->type_syntax_id);
                echo ',';
                Probe::node($tree, (int)$view_4->initializer_id);
            }
            if ($kind === \parse\SYNTAX_ASSIGNMENT_STATEMENT) {
                $view_5 = \parse\Syntax_Access::assignment_parts($tree, $id);
                Probe::node($tree, (int)$view_5->target_id);
                echo ',';
                Probe::node($tree, (int)$view_5->value_id);
            }
            if ($kind === \parse\SYNTAX_IF_STATEMENT) {
                $view_6 = \parse\Syntax_Access::control_parts($tree, $id);
                Probe::node($tree, (int)$view_6->condition);
                echo ',';
                Probe::node($tree, (int)$view_6->body);
                echo ',';
                Probe::node($tree, (int)$view_6->alternative);
            }
            if ($kind === \parse\SYNTAX_WHILE_STATEMENT) {
                $view_7 = \parse\Syntax_Access::control_parts($tree, $id);
                Probe::node($tree, (int)$view_7->condition);
                echo ',';
                Probe::node($tree, (int)$view_7->body);
                echo ',';
                Probe::node($tree, (int)$view_7->alternative);
            }
            if ($kind === \parse\SYNTAX_CONSTEXPR_IF_STATEMENT) {
                $view_8 = \parse\Syntax_Access::control_parts($tree, $id);
                Probe::node($tree, (int)$view_8->condition);
                echo ',';
                Probe::node($tree, (int)$view_8->body);
                echo ',';
                Probe::node($tree, (int)$view_8->alternative);
            }
            if ($kind === \parse\SYNTAX_CONSTEVAL_IF_STATEMENT) {
                $view_9 = \parse\Syntax_Access::control_parts($tree, $id);
                Probe::node($tree, (int)$view_9->condition);
                echo ',';
                Probe::node($tree, (int)$view_9->body);
                echo ',';
                Probe::node($tree, (int)$view_9->alternative);
            }
            if ($kind === \parse\SYNTAX_TEMPLATE_DECLARATION) {
                $view_10 = \parse\Syntax_Access::template_parts($tree, $id);
                Probe::node($tree, (int)$view_10->parameters_id);
                echo ',';
                Probe::node($tree, (int)$view_10->declaration_id);
            }
            if ($kind === \parse\SYNTAX_TYPE_PARAMETER_DECLARATION) {
                $view_11 = \parse\Syntax_Access::template_parameter_parts($tree, $id);
                Probe::node($tree, (int)$view_11->name_id);
                echo ',';
                Probe::node($tree, (int)$view_11->type_syntax_id);
            }
            if ($kind === \parse\SYNTAX_VALUE_PARAMETER_DECLARATION) {
                $view_12 = \parse\Syntax_Access::template_parameter_parts($tree, $id);
                Probe::node($tree, (int)$view_12->name_id);
                echo ',';
                Probe::node($tree, (int)$view_12->type_syntax_id);
            }
            if ($kind === \parse\SYNTAX_TEMPLATE_APPLICATION) {
                $view_13 = \parse\Syntax_Access::template_application_parts($tree, $id);
                Probe::node($tree, (int)$view_13->name_id);
                echo ',';
                Probe::node($tree, (int)$view_13->first_argument_id);
            }
            if ($kind === \parse\SYNTAX_CONSTANT_DECLARATION) {
                $view_14 = \parse\Syntax_Access::constant_parts($tree, $id);
                Probe::node($tree, (int)$view_14->name_id);
                echo ',';
                Probe::node($tree, (int)$view_14->type_syntax_id);
                echo ',';
                Probe::node($tree, (int)$view_14->initializer_id);
            }
            if ($kind === \parse\SYNTAX_CALL_EXPRESSION) {
                Probe::node($tree, \parse\Syntax_Access::call_target($tree, $id)); echo ',';
                Probe::node($tree, \parse\Syntax_Access::first_argument($tree, $id));
            }
            if ($kind === \parse\SYNTAX_PARAMETER_LIST) { Probe::node($tree, \parse\Syntax_Access::first_parameter($tree, $id)); }
            if (($kind === \parse\SYNTAX_RETURN_STATEMENT) || ($kind === \parse\SYNTAX_EXPRESSION_STATEMENT)) {
                Probe::node($tree, \parse\Syntax_Access::statement_expression($tree, $id));
            }
            if ($kind === \parse\SYNTAX_METHOD_DECLARATION) {
                echo \parse\Syntax_Access::const_receiver($tree, $id) ? 'true,' : 'false,';
                Probe::node($tree, \parse\Syntax_Access::underlying_declaration($tree, $id));
            }
            if (($kind === \parse\SYNTAX_CONSTEXPR_DECLARATION) || ($kind === \parse\SYNTAX_CONSTEVAL_DECLARATION)) {
                Probe::node($tree, \parse\Syntax_Access::evaluated_function($tree, $id));
            }
            if (($kind === \parse\SYNTAX_FIELD_EXPRESSION) || ($kind === \parse\SYNTAX_INDEX_EXPRESSION)) {
                Probe::node($tree, \parse\Syntax_Access::place_root($tree, $id));
            }
            echo ']';
            $child = (int)$node->first_child;
            while ($child !== 0) {
                $queue[] = $child;
                $row = $tree->row($child);
                $child = (int)$row->next_sibling;
            }
        }
        echo "]\n";
    }
    public static function compare(string $left_path, string $right_path, bool $definition): void {
        $left = Probe::read($left_path);
        $right = Probe::read($right_path);
        $left_id = $left->root;
        $right_id = $right->root;
        if ($definition) { $left_id = $left->definitions[0]; $right_id = $right->definitions[0]; }
        echo \parse\Syntax_Comparer::equal($left, $left_id, $right, $right_id) ? "true\n" : "false\n";
    }
    public static function checks(): void {
        $source = new \read_sources\Source_Buffer();
        $source->content = 'struct S { public int $x; public function f(): int { return 1; } public int $y; public const function g(): int { return 2; } }';
        $file = \parse\File_Parser::parse(\tokenize\File_Tokenizer::tokenize($source));
        $tree = $file->tree;
        $id = $file->definitions[0];
        $fields = \parse\Syntax_Access::struct_members($tree, $id, \parse\SYNTAX_FIELD_DECLARATION);
        $unpositioned = false;
        try { $fields->current(); } catch (\LogicException $error) { $unpositioned = true; }
        echo $unpositioned ? "true\n" : "false\n";
        $field_count /** int */ = 0;
        while ($fields->advance()) {
            $member = $fields->current();
            if ($fields->current() !== $member) { throw new \LogicException('Unstable cursor'); }
            $field_view = \parse\Syntax_Access::field_declaration_parts($tree, $member);
            ++$field_count;
        }
        echo ($field_count === 2) ? "true\n" : "false\n";
        echo !$fields->advance() ? "true\n" : "false\n";
        $methods = \parse\Syntax_Access::struct_members($tree, $id, \parse\SYNTAX_METHOD_DECLARATION);
        $method_count /** int */ = 0;
        $const_count /** int */ = 0;
        while ($methods->advance()) {
            ++$method_count;
            if (\parse\Syntax_Access::const_receiver($tree, $methods->current())) { ++$const_count; }
        }
        echo (($method_count === 2) && ($const_count === 1)) ? "true\n" : "false\n";
        $invalid = \parse\Syntax_Access::struct_members($tree, $file->root, \parse\SYNTAX_FIELD_DECLARATION);
        $failed = false;
        try { $invalid->advance(); } catch (\LogicException $error) { $failed = true; }
        echo $failed ? "true\n" : "false\n";
        echo !$invalid->advance() ? "true\n" : "false\n";
        $view = \parse\Syntax_Access::struct_parts($tree, $id);
        $saved = (int)$view->name_id;
        $view->name_id = 0;
        $fresh = \parse\Syntax_Access::struct_parts($tree, $id);
        echo ((int)$fresh->name_id === $saved) ? "true\n" : "false\n";
        echo \parse\Syntax_Comparer::equal($file, 0, $file, 0) ? "true\n" : "false\n";
        echo !\parse\Syntax_Comparer::equal($file, 0, $file, $id) ? "true\n" : "false\n";
        $bad_root = false;
        try { \parse\Syntax_Comparer::equal($file, -1, $file, $id); } catch (\LogicException $error) { $bad_root = true; }
        echo $bad_root ? "true\n" : "false\n";
        $unknown = $tree->add(999, 0, 0);
        $bad_kind = false;
        try { \parse\Syntax_Comparer::equal($file, $unknown, $file, $unknown); } catch (\LogicException $error) { $bad_kind = true; }
        echo $bad_kind ? "true\n" : "false\n";
        $bad_tree_0 = new \parse\Syntax_Arena();
        $bad_id_0 = $bad_tree_0->add(\parse\SYNTAX_FUNCTION_DECLARATION, 0, 0);
        $rejected_0 = false;
        try { \parse\Syntax_Access::function_parts($bad_tree_0, $bad_id_0); } catch (\LogicException $error) { $rejected_0 = true; }
        echo $rejected_0 ? "true\n" : "false\n";
        $bad_tree_1 = new \parse\Syntax_Arena();
        $bad_id_1 = $bad_tree_1->add(\parse\SYNTAX_STRUCT_DECLARATION, 0, 0);
        $rejected_1 = false;
        try { \parse\Syntax_Access::struct_parts($bad_tree_1, $bad_id_1); } catch (\LogicException $error) { $rejected_1 = true; }
        echo $rejected_1 ? "true\n" : "false\n";
        $bad_tree_2 = new \parse\Syntax_Arena();
        $bad_id_2 = $bad_tree_2->add(\parse\SYNTAX_FIELD_DECLARATION, 0, 0);
        $rejected_2 = false;
        try { \parse\Syntax_Access::field_declaration_parts($bad_tree_2, $bad_id_2); } catch (\LogicException $error) { $rejected_2 = true; }
        echo $rejected_2 ? "true\n" : "false\n";
        $bad_tree_3 = new \parse\Syntax_Arena();
        $bad_id_3 = $bad_tree_3->add(\parse\SYNTAX_PARAMETER_DECLARATION, 0, 0);
        $rejected_3 = false;
        try { \parse\Syntax_Access::parameter_parts($bad_tree_3, $bad_id_3); } catch (\LogicException $error) { $rejected_3 = true; }
        echo $rejected_3 ? "true\n" : "false\n";
        $bad_tree_4 = new \parse\Syntax_Arena();
        $bad_id_4 = $bad_tree_4->add(\parse\SYNTAX_LOCAL_DECLARATION, 0, 0);
        $rejected_4 = false;
        try { \parse\Syntax_Access::local_declaration_parts($bad_tree_4, $bad_id_4); } catch (\LogicException $error) { $rejected_4 = true; }
        echo $rejected_4 ? "true\n" : "false\n";
        $bad_tree_5 = new \parse\Syntax_Arena();
        $bad_id_5 = $bad_tree_5->add(\parse\SYNTAX_ASSIGNMENT_STATEMENT, 0, 0);
        $rejected_5 = false;
        try { \parse\Syntax_Access::assignment_parts($bad_tree_5, $bad_id_5); } catch (\LogicException $error) { $rejected_5 = true; }
        echo $rejected_5 ? "true\n" : "false\n";
        $bad_tree_6 = new \parse\Syntax_Arena();
        $bad_id_6 = $bad_tree_6->add(\parse\SYNTAX_IF_STATEMENT, 0, 0);
        $rejected_6 = false;
        try { \parse\Syntax_Access::control_parts($bad_tree_6, $bad_id_6); } catch (\LogicException $error) { $rejected_6 = true; }
        echo $rejected_6 ? "true\n" : "false\n";
        $bad_tree_7 = new \parse\Syntax_Arena();
        $bad_id_7 = $bad_tree_7->add(\parse\SYNTAX_WHILE_STATEMENT, 0, 0);
        $rejected_7 = false;
        try { \parse\Syntax_Access::control_parts($bad_tree_7, $bad_id_7); } catch (\LogicException $error) { $rejected_7 = true; }
        echo $rejected_7 ? "true\n" : "false\n";
        $bad_tree_8 = new \parse\Syntax_Arena();
        $bad_id_8 = $bad_tree_8->add(\parse\SYNTAX_CONSTEXPR_IF_STATEMENT, 0, 0);
        $rejected_8 = false;
        try { \parse\Syntax_Access::control_parts($bad_tree_8, $bad_id_8); } catch (\LogicException $error) { $rejected_8 = true; }
        echo $rejected_8 ? "true\n" : "false\n";
        $bad_tree_9 = new \parse\Syntax_Arena();
        $bad_id_9 = $bad_tree_9->add(\parse\SYNTAX_CONSTEVAL_IF_STATEMENT, 0, 0);
        $rejected_9 = false;
        try { \parse\Syntax_Access::control_parts($bad_tree_9, $bad_id_9); } catch (\LogicException $error) { $rejected_9 = true; }
        echo $rejected_9 ? "true\n" : "false\n";
        $bad_tree_10 = new \parse\Syntax_Arena();
        $bad_id_10 = $bad_tree_10->add(\parse\SYNTAX_TEMPLATE_DECLARATION, 0, 0);
        $rejected_10 = false;
        try { \parse\Syntax_Access::template_parts($bad_tree_10, $bad_id_10); } catch (\LogicException $error) { $rejected_10 = true; }
        echo $rejected_10 ? "true\n" : "false\n";
        $bad_tree_11 = new \parse\Syntax_Arena();
        $bad_id_11 = $bad_tree_11->add(\parse\SYNTAX_TYPE_PARAMETER_DECLARATION, 0, 0);
        $rejected_11 = false;
        try { \parse\Syntax_Access::template_parameter_parts($bad_tree_11, $bad_id_11); } catch (\LogicException $error) { $rejected_11 = true; }
        echo $rejected_11 ? "true\n" : "false\n";
        $bad_tree_12 = new \parse\Syntax_Arena();
        $bad_id_12 = $bad_tree_12->add(\parse\SYNTAX_VALUE_PARAMETER_DECLARATION, 0, 0);
        $rejected_12 = false;
        try { \parse\Syntax_Access::template_parameter_parts($bad_tree_12, $bad_id_12); } catch (\LogicException $error) { $rejected_12 = true; }
        echo $rejected_12 ? "true\n" : "false\n";
        $bad_tree_13 = new \parse\Syntax_Arena();
        $bad_id_13 = $bad_tree_13->add(\parse\SYNTAX_TEMPLATE_APPLICATION, 0, 0);
        $rejected_13 = false;
        try { \parse\Syntax_Access::template_application_parts($bad_tree_13, $bad_id_13); } catch (\LogicException $error) { $rejected_13 = true; }
        echo $rejected_13 ? "true\n" : "false\n";
        $bad_tree_14 = new \parse\Syntax_Arena();
        $bad_id_14 = $bad_tree_14->add(\parse\SYNTAX_CONSTANT_DECLARATION, 0, 0);
        $rejected_14 = false;
        try { \parse\Syntax_Access::constant_parts($bad_tree_14, $bad_id_14); } catch (\LogicException $error) { $rejected_14 = true; }
        echo $rejected_14 ? "true\n" : "false\n";
    }

}
