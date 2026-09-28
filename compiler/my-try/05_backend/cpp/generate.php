<?php

/* Emit prepared declarations and straight-line bodies using specialization dispatch. */
namespace scpp\compiler;

final class CPP_Generator
{
	private cpp_program $program;

	/** Standalone callers get private storage; Compiler supplies the retained program. */
	public function __construct(?cpp_program $program = null)
	{
		$this->program = $program ?? new cpp_program();
	}

	/** Select independent fragments, then assemble the existing single-file layout. */
	public function generate(prepared_file $prepared): cpp_module
	{
		$source = $prepared->source;
		$ordered /** Storage<preparation_owner> */ = new Storage();
		$child = $source->root->first_child();
		while ($child !== null)
		{
			$node /** ast_node */ = $child;
			if (($node->kind() === node_kind::function_declaration) || ($node->kind() === node_kind::struct_declaration)) {
				$entry = $node->payload()->occurrence();
				$ordered->append(object_cast($entry->preparation, preparation_owner::class));
				if ($node->kind() === node_kind::function_declaration) {
					$ordered->append(object_cast(Syntax_Nodes::function_data($node)->body_preparation, preparation_owner::class));
				}
			}
			$child = $node->next();
		}
		$ordered->append(object_cast($source->body_preparation, preparation_owner::class));

		// Deletion remains pending until assembly succeeds, but stale fragments leave storage immediately.
		foreach ($source->preparation_changes as $owner /** @object-key */) {
			if ($owner->change_status === change_state::deleted) {
				unset($this->program->fragments[$owner]);
			}
		}
		$definitions /** Storage<preparation_owner> */ = new Storage();
		$bodies /** Storage<preparation_owner> */ = new Storage();
		foreach ($ordered as $owner)
		{
			if (($owner->change_status !== change_state::unchanged) || $owner->failed) {
				throw new \RuntimeException('C++ generation requires completed preparation');
			}
			if (!isset($this->program->fragments[$owner])) {
				$this->program->fragments[$owner] = new cpp_fragment();
			}
			$fragment = $this->program->fragments[$owner];
			if (($fragment->version !== $owner->version) || ($fragment->change_status !== change_state::unchanged)) {
				$fragment->change_status = change_state::changed;
				if ($owner->kind === preparation_kind::declaration) {
					$definitions->append($owner);
				}
				else {
					$bodies->append($owner);
				}
			}
		}
		foreach ($definitions as $owner) {
			$this->render($owner);
		}
		foreach ($bodies as $owner) {
			$this->render($owner);
		}

		$context = new cpp_generation_context();
		$entry_text = '';
		foreach ($ordered as $owner)
		{
			$fragment = $this->program->fragments[$owner];
			foreach ($fragment->headers as $header => $used) {
				$context->headers[$header] = true;
			}
			if ($owner->kind === preparation_kind::file_body) {
				$entry_text = $fragment->text;
			}
			elseif ($owner->kind === preparation_kind::function_body) {
				$entry = object_cast($owner->declaration, collected_name::class);
				$signature_owner = object_cast($entry->preparation, preparation_owner::class);
				$context->functions .= $this->program->fragments[$signature_owner]->text . "\n{\n" . $fragment->text . "}\n\n";
			}
			else {
				$entry = object_cast($owner->declaration, collected_name::class);
				if ($entry->kind === collected_name_kind::struct_declaration) {
					$this->assemble_record($entry, $context);
				}
				else {
					$context->prototypes .= $fragment->text . ";\n";
				}
			}
		}
		$text = '';
		foreach ($context->headers as $header => $used) {
			$text .= '#include "' . $header . '"' . "\n";
		}
		$text .= $context->records . $context->prototypes . $context->functions;
		$text .= "\nint main()\n{\n" . $entry_text . "\treturn 0;\n}\n";
		$result = new cpp_module();
		$result->file_name = 'main.cpp';
		$result->text = $text;
		$source->preparation_changes = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		return $result;
	}

	/** Deleted owners cannot retain fragments even when there is no remaining file to emit. */
	public static function discard_deleted(cpp_program $program): void
	{
		$removed /** Storage<preparation_owner> */ = new Storage();
		foreach ($program->fragments as $owner /** @object-key */) {
			if (($owner->change_status === change_state::deleted) || $owner->source->deleted) {
				$removed->append($owner);
			}
		}
		foreach ($removed as $owner) {
			unset($program->fragments[$owner]);
		}
	}

	/** Each body owns its temporary numbering; publish a fragment only after successful rendering. */
	private function render(preparation_owner $owner): void
	{
		$context = new cpp_generation_context();
		$context->expand_records = false;
		$next = new cpp_fragment();
		if ($owner->kind === preparation_kind::file_body) {
			$next->text = self::generate_file_body($owner->source->root, $context);
		}
		else
		{
			$entry = object_cast($owner->declaration, collected_name::class);
			if ($owner->kind === preparation_kind::function_body) {
				$syntax = Syntax_Nodes::function_data($entry->node);
				$context->return_type = $syntax->require_preparation()->return_type;
				$next->text = self::generate_statements($syntax->body, $context);
			}
			elseif ($entry->kind === collected_name_kind::function_declaration) {
				$next->text = CPP_Declarations::signature(Syntax_Nodes::function_data($entry->node), $context);
			}
			else
			{
				$syntax = Syntax_Nodes::struct_data($entry->node);
				CPP_Declarations::generate_struct($syntax, $context);
				$next->text = $context->records;
				$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
				$records /** Storage<collected_name> */ = $next->records;
				foreach ($fields->items() as $field) {
					if ($field->type->kind === type_kind::record) {
						$records->append(object_cast($field->type->declaration, collected_name::class));
					}
				}
			}
		}
		$next->headers = $context->headers;
		$next->version = $owner->version;
		$next->change_status = change_state::unchanged;
		$this->program->fragments[$owner] = $next;
	}

	/** Dependency ordering is file assembly policy; cached record text never embeds another record. */
	private function assemble_record(collected_name $entry, cpp_generation_context $context): void
	{
		$key = $entry->name;
		if (isset($context->record_states[$key])) {
			if ($context->record_states[$key] === cpp_record_state::visiting) {
				throw new \RuntimeException('Cyclic C++ record assembly dependency');
			}
			return;
		}
		$context->record_states[$key] = cpp_record_state::visiting;
		$owner = object_cast($entry->preparation, preparation_owner::class);
		$fragment = $this->program->fragments[$owner];
		$records /** Storage<collected_name> */ = $fragment->records;
		foreach ($records as $dependency) {
			$this->assemble_record($dependency, $context);
		}
		$context->records .= $fragment->text;
		$context->record_states[$key] = cpp_record_state::complete;
	}

	/** File executable statements form one body; declarations have their own fragments. */
	private static function generate_file_body(ast_node $root, cpp_generation_context $context): string
	{
		$text = '';
		$child = $root->first_child();
		while ($child !== null)
		{
			$node /** ast_node */ = $child;
			if (($node->kind() !== node_kind::function_declaration) && ($node->kind() !== node_kind::struct_declaration)) {
				$text .= $node->payload()->generate_cpp_statement($node, $context);
			}
			$child = $node->next();
		}
		return $text;
	}

	/** The worker visits executable children once; declaration hooks collect separate output sections. */
	public static function generate_statements(ast_node $body, cpp_generation_context $context): string
	{
		$text = '';
		$child = $body->first_child();
		while ($child !== null) {
			$node /** ast_node */ = $child;
			$text .= $node->payload()->generate_cpp_statement($node, $context);
			$child = $node->next();
		}
		return $text;
	}

	/** Prepared declarations and member targets share the same typed assignment boundary. */
	public static function generate_binding(binding_structure $syntax, cpp_generation_context $context): string
	{
		$binding = $syntax->require_preparation();
		$prefix = $binding->resolved_kind === binding_kind::declaration ? 'auto ' : '';
		$name = '';
		if ($syntax->target !== null) {
			$target /** ast_node */ = $syntax->target;
			$name = $target->payload()->generate_cpp_expression($target, $context);
		}
		else {
			$declaration = object_cast(weakref_get($binding->declaration), collected_name::class);
			$name = self::local_name($declaration);
		}

		// Typed declarations without initializers retain their normal C++ default construction.
		if ($syntax->value === null) {
			return "\t" . CPP_Declarations::type($binding->type, $context) . ' ' . $name . ";\n";
		}

		$initializer /** ast_node */ = $syntax->value;
		$value = $initializer->payload()->generate_cpp_expression($initializer, $context);
		if (($syntax->type_syntax !== null) || ($binding->resolved_kind === binding_kind::assignment)) {
			$value = CPP_Declarations::value($value, $binding->type, $context);
		}
		return "\t" . $prefix . $name . ' = ' . $value . ";\n";
	}

	/** Function returns retain their declared value type; entry returns become native exit codes. */
	public static function generate_return(return_structure $syntax, cpp_generation_context $context): string
	{
		if ($syntax->expression === null) {
			return $context->return_type === null ? "\treturn 0;\n" : "\treturn;\n";
		}

		$expression /** ast_node */ = $syntax->expression;
		$value = $expression->payload()->generate_cpp_expression($expression, $context);
		if ($context->return_type !== null) {
			$type /** type_definition */ = $context->return_type;
			return "\treturn " . CPP_Declarations::value($value, $type, $context) . ";\n";
		}
		return "\treturn static_cast<int>((" . $value . ").native_value());\n";
	}

	public static function generate_expression_statement(expression_statement_structure $syntax, cpp_generation_context $context): string
	{
		$expression = $syntax->expression;
		return "\t" . $expression->payload()->generate_cpp_expression($expression, $context) . ";\n";
	}

	/** Emit exact signed integer magnitude using its canonical representation. */
	public static function generate_integer(prepared_integer_literal $literal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($literal->type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::signed_integer) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}

		return 'static_cast<' . $mapping->spelling . '>(' . $literal->decimal . 'LL)';
	}

	/** Preserve decimal spelling until the target toolchain performs floating conversion. */
	public static function generate_float(prepared_float_literal $literal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($literal->type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::floating) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}

		return 'static_cast<' . $mapping->spelling . '>(' . $literal->decimal . ')';
	}

	/** Boolean source normalization is already complete before backend spelling. */
	public static function generate_boolean(prepared_boolean_literal $literal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($literal->type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::boolean) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}

		$spelling = $literal->value ? 'true' : 'false';

		return 'static_cast<' . $mapping->spelling . '>(' . $spelling . ')';
	}

	public static function generate_reference(prepared_variable_reference $reference, cpp_generation_context $context): string
	{
		$target = object_cast(weakref_get($reference->declaration), collected_name::class);
		return self::local_name($target);
	}

	/** Source names are scope-local, stable under movement and prefixed against C++ keywords. */
	public static function local_name(collected_name $declaration): string
	{
		return 'local_' . $declaration->name;
	}
}
