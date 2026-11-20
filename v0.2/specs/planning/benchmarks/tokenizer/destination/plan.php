<?php
declare(strict_types=1);
namespace scpp\compiler;

/** Isolated experiment. The plan owns links only for the duration of a retained file generation. */
final class Destination_Trial
{
    public static ?self $selected = null;
    public array $skip = [];
    public array $writes = [];
    public ?ast_append_statement $append = null;

    private function __construct(private ast_file_node $file, private int $revision) {}

    public static function discover(ast_function_body $body, bool $provider): self
    {
        $file = $body->root();
        $plan = new self($file, $file->prepared_revision());
        if ($provider && !($body->parent->return_type_def() instanceof generator_type_definition)) $plan->visit($body);
        return $plan;
    }

    public function current(ast_file_node $file, bool $provider): bool
    {
        return $provider && $file === $this->file && $file->prepared_revision() === $this->revision;
    }

    private function visit(ast_statement_block $block): void
    {
        $statements = $block->statements;
        $append = $statements[array_key_last($statements)] ?? null;
        if ($append instanceof ast_append_statement && $this->candidate($statements, $append)) return;
        foreach ($statements as $statement) {
            if ($statement instanceof ast_loop_statement) $this->visit($statement->body);
            if ($statement instanceof ast_if_statement) {
                foreach ($statement->arms as $arm) $this->visit($arm->body);
            }
            if ($this->append !== null) return;
        }
    }

    /** Admit one terminal contiguous group, so the fresh local cannot escape or be used later. */
    private function candidate(array $statements, ast_append_statement $append): bool
    {
        if (!($append->receiver instanceof ast_name_expression)
            || !($append->receiver->declaration() instanceof ast_local_declaration)
            || !($append->receiver->type_def() instanceof application_vector_type)
            || !($append->value instanceof ast_name_expression)
            || $append->conversion() !== value_conversion::identity) return false;
        $local = $append->value->declaration();
        $type = $append->value->type_def();
        if (!($local instanceof ast_local_declaration) || !($local->initializer instanceof ast_struct_default)
            || $local->initializer->arguments !== [] || !($type instanceof record_type_definition)
            || $append->receiver->type_def()->write_type() !== $type) return false;
        $record = $type->declaration;
        if ($record->kind !== record_kind::structure || $record->methods !== [] || $record->base_reference !== null
            || $record->interface_references !== [] || $record->fields === []) return false;
        $start = count($statements) - count($record->fields) - 2;
        if (($statements[$start] ?? null) !== $local) return false;
        $writes = [];
        foreach ($record->fields as $field) {
            $write = $statements[++$start];
            if ($field->type_def() !== type_registry::integer() || $field->initializer !== null || $field->is_private
                || $field->is_protected || $field->is_readonly || !($write instanceof ast_field_assignment)
                || !($write->target instanceof ast_field_expression) || $write->target->field !== $field->short_name
                || !($write->target->receiver instanceof ast_name_expression) || !$write->target->receiver->refers_to($local)
                || $write->conversion !== value_conversion::identity || !self::scalar_input($write->value)) return false;
            $writes[] = $write;
        }
        $this->append = $append;
        $this->writes = $writes;
        foreach ([$local, ...$writes] as $statement) $this->skip[spl_object_id($statement)] = true;
        return true;
    }

    private static function scalar_input(ast_expression $value): bool
    {
        if ($value->type_def() !== type_registry::integer()) return false;
        if ($value instanceof ast_name_expression) return $value->declaration() instanceof ast_local_declaration;
        return $value instanceof ast_binary_expression && $value->operator === binary_operator::subtract
            && $value->left_conversion === value_conversion::identity && $value->right_conversion === value_conversion::identity
            && self::scalar_input($value->left) && self::scalar_input($value->right);
    }
}
