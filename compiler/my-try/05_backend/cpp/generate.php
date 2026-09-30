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
		$nodes /** Storage<declaration_node> */ = $source->root->declarations;
		foreach ($nodes as $node)
		{
			if (($node->kind() === node_kind::function_declaration) || ($node->kind() === node_kind::struct_declaration)) {
				$entry = $node->occurrence();
				$ordered->append(object_cast($entry->preparation_work_owner(), preparation_owner::class));
				if ($node->kind() === node_kind::function_declaration) {
					$ordered->append(object_cast(object_cast($node, function_node::class)->body->work(), preparation_owner::class));
				}
			}
		}
		$ordered->append(object_cast($source->root->body->work(), preparation_owner::class));

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
			Preparation_Worker::require_active($owner);
			if (($owner->change_status !== change_state::unchanged) || $owner->failed) {
				throw new \RuntimeException('C++ generation requires completed preparation');
			}
			if (!isset($this->program->fragments[$owner])) {
				$this->program->fragments[$owner] = new cpp_fragment();
			}
			$fragment = $this->program->fragments[$owner];
			if (($fragment->version !== $owner->version) || ($fragment->change_status !== change_state::unchanged))
			{
				$fragment->change_status = change_state::changed;
				if ($owner->kind() === preparation_kind::declaration) {
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
			Preparation_Worker::require_active($owner);
			$fragment = $this->program->fragments[$owner];
			foreach ($fragment->headers as $header => $used) {
				$context->headers[$header] = true;
			}
			if ($owner->kind() === preparation_kind::file_body) {
				$entry_text = $fragment->text;
			}
			elseif ($owner->kind() === preparation_kind::function_body) {
				$entry = object_cast($owner->declaration(), collected_name::class);
				$signature_owner = object_cast($entry->preparation_work_owner(), preparation_owner::class);
				$context->functions .= $this->program->fragments[$signature_owner]->text . "\n{\n" . $fragment->text . "}\n\n";
			}
			else
			{
				$entry = object_cast($owner->declaration(), collected_name::class);
				if ($entry instanceof collected_struct) {
					$this->assemble_record(object_cast($entry, collected_struct::class), $context);
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
		Preparation_Worker::require_active($owner);
		if (($owner->state !== preparation_state::ready) || $owner->failed) {
			throw new \LogicException('C++ generation requires successfully prepared work');
		}
		$context = new cpp_generation_context();
		$context->expand_records = false;
		$next = new cpp_fragment();
		if ($owner->kind() === preparation_kind::file_body) {
			$next->text = self::generate_file_body($owner->source->root, $context);
		}
		else
		{
			$entry = object_cast($owner->declaration(), collected_name::class);
			if ($owner->kind() === preparation_kind::function_body) {
				$syntax = object_cast($entry, collected_function::class)->syntax();
				$context->return_type = $syntax->require_preparation()->return_type;
				$next->text = self::generate_statements($syntax->body, $context);
			}
			elseif ($entry instanceof collected_function) {
				$next->text = CPP_Declarations::signature(object_cast($entry, collected_function::class)->syntax(), $context);
			}
			else
			{
				$syntax = object_cast($entry, collected_struct::class)->syntax();
				CPP_Declarations::generate_struct($syntax, $context);
				$next->text = $context->records;
				$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
				$records /** Storage<collected_struct> */ = $next->records;
				foreach ($fields->items() as $field) {
					if ($field->type->kind === type_kind::record) {
						$record /** collected_struct */ = $field->type->declaration;
						$records->append($record);
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
	private function assemble_record(collected_struct $entry, cpp_generation_context $context): void
	{
		$key = $entry->name;
		if (isset($context->record_states[$key])) {
			if ($context->record_states[$key] === cpp_record_state::visiting) {
				throw new \RuntimeException('Cyclic C++ record assembly dependency');
			}
			return;
		}
		$context->record_states[$key] = cpp_record_state::visiting;
		$owner = object_cast($entry->preparation_work_owner(), preparation_owner::class);
		$fragment = $this->program->fragments[$owner];
		$records /** Storage<collected_struct> */ = $fragment->records;
		foreach ($records as $dependency) {
			$this->assemble_record($dependency, $context);
		}
		$context->records .= $fragment->text;
		$context->record_states[$key] = cpp_record_state::complete;
	}

	/** File executable statements form one body; declarations have their own fragments. */
	private static function generate_file_body(file_node $root, cpp_generation_context $context): string
	{
		return self::generate_statements($root->body, $context);
	}

	/** The worker visits executable children once; declaration hooks collect separate output sections. */
	public static function generate_statements(function_body_node $body, cpp_generation_context $context): string
	{
		$text = '';
		$statements /** Storage<statement_node> */ = $body->statements;
		$worker = new CPP_Syntax($context);
		foreach ($statements as $node) {
			$text .= $node->generate_cpp($worker);
		}
		return $text;
	}

	/** Prepared declarations and member targets share the same typed assignment boundary. */
	public static function generate_storage(prepared_binding $binding, ?assignable_expression_node $target, ?expression_node $initializer, bool $explicit_type, cpp_generation_context $context): string
	{
		$name = self::storage_name($binding, $target, $context);

		// Typed declarations without initializers retain their normal C++ default construction.
		if ($initializer === null) {
			return CPP_Declarations::type($binding->type, $context) . ' ' . $name;
		}

		$initializer /** expression_node */ = $initializer;
		$value = $initializer->generate_cpp(new CPP_Syntax($context));
		$value_type = $initializer->require_preparation()->type;
		return self::generate_storage_value($binding, $name, $value, $value_type, $explicit_type, $context);
	}

	/** Select the declared local or concrete assignable target without rendering its value. */
	private static function storage_name(prepared_binding $binding, ?assignable_expression_node $target, cpp_generation_context $context): string
	{
		if ($target !== null) {
			$target_node /** ast_node */ = $target;
			return $target_node->generate_cpp(new CPP_Syntax($context));
		}
		$declaration = object_cast(weakref_get($binding->declaration), collected_name::class);
		return self::local_name($declaration);
	}

	/** Render one declaration or assignment from an already evaluated value expression. */
	private static function generate_storage_value(prepared_binding $binding, string $name, string $value, type_definition $value_type, bool $explicit_type, cpp_generation_context $context): string
	{
		$prefix = '';
		if ($binding->resolved_kind === binding_kind::declaration) {
			$prefix = $explicit_type ? CPP_Declarations::type($binding->type, $context) . ' ' : 'auto ';
		}
		if (($explicit_type || ($binding->resolved_kind === binding_kind::assignment)) &&
			($binding->type !== $value_type)) {
			$value = CPP_Declarations::value($value, $binding->type, $context);
		}
		return $prefix . $name . ' = ' . $value;
	}

	/** Function returns retain their declared value type; entry returns become native exit codes. */
	public static function generate_return(return_node $syntax, cpp_generation_context $context): string
	{
		if ($syntax->expression === null) {
			return $context->return_type === null ? "\treturn 0;\n" : "\treturn;\n";
		}

		$expression /** ast_node */ = $syntax->expression;
		$value = $expression->generate_cpp(new CPP_Syntax($context));
		if ($context->return_type !== null) {
			$type /** type_definition */ = $context->return_type;
			return "\treturn " . CPP_Declarations::value($value, $type, $context) . ";\n";
		}
		return "\treturn static_cast<int>((" . $value . ").native_value());\n";
	}

	public static function generate_expression_statement(expression_statement_node $syntax, cpp_generation_context $context): string
	{
		$expression = $syntax->expression;
		if ($expression instanceof assignment_expression_node) {
			return self::generate_assignment_statement(object_cast($expression, assignment_expression_node::class), $context);
		}
		return "\t" . $expression->generate_cpp(new CPP_Syntax($context)) . ";\n";
	}

	/** Flatten right-associative writes so introduced locals remain in the surrounding body. */
	private static function generate_assignment_statement(assignment_expression_node $syntax, cpp_generation_context $context): string
	{
		return self::generate_assignment_sequence($syntax, $context)->statements;
	}

	/** Emit inner writes first and return their stored value to the enclosing assignment. */
	private static function generate_assignment_sequence(assignment_expression_node $syntax, cpp_generation_context $context): cpp_assignment_sequence
	{
		$value_node = $syntax->value;
		$source = new cpp_assignment_sequence();
		if ($value_node instanceof assignment_expression_node) {
			$source = self::generate_assignment_sequence(object_cast($value_node, assignment_expression_node::class), $context);
		}
		else {
			$source->value = $value_node->generate_cpp(new CPP_Syntax($context));
			$source->type = $value_node->require_preparation()->type;
		}

		$binding = $syntax->require_assignment_preparation()->binding;
		$target /** nullable<assignable_expression_node> */ = $syntax->target;
		if ($target instanceof variable_reference_node) {
			$target = null;
		}
		$name = self::storage_name($binding, $target, $context);
		$statements = $source->statements;
		$statements .= "\t" . self::generate_storage_value($binding, $name, $source->value, $source->type, false, $context) . ";\n";

		$result = new cpp_assignment_sequence();
		$result->statements = $statements;
		$result->value = $name;
		$result->type = $binding->type;
		return $result;
	}

	/** Emit exact signed integer magnitude using its canonical representation. */
	public static function generate_integer(prepared_integer_literal $literal, cpp_generation_context $context): string
	{
		return self::generate_integer_value($literal->type, $literal->decimal, $context);
	}

	/** Constants and source literals share exact integer representation and header selection. */
	private static function generate_integer_value(type_definition $type, string $decimal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::signed_integer) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}

		return 'static_cast<' . $mapping->spelling . '>(' . $decimal . 'LL)';
	}

	/** Lower semantic constant values without preserving runtime- or host-specific names. */
	public static function generate_constant(prepared_constant_reference $reference, cpp_generation_context $context): string
	{
		$definition = $reference->definition;
		if (!($definition instanceof integer_constant_definition)) {
			throw new \RuntimeException('C++ constant emission is not implemented for this definition');
		}
		$integer = object_cast($definition, integer_constant_definition::class);
		return self::generate_integer_value($integer->type, $integer->decimal, $context);
	}

	/** Render the prepared operation recursively; token spelling has no backend authority. */
	public static function generate_binary(binary_expression_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_binary_preparation();
		if ($facts->operation !== binary_operation::addition) {
			throw new \RuntimeException('C++ binary operation is not supported yet');
		}

		$worker = new CPP_Syntax($context);
		$left = $syntax->left->generate_cpp($worker);
		$right = $syntax->right->generate_cpp($worker);
		$context->headers['scpp/generated/operators.hpp'] = true;
		return '(' . $left . ' + ' . $right . ')';
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

	/** Render decoded bytes as a C++ literal, retaining length when a C string would truncate. */
	public static function generate_string(prepared_string_literal $literal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($literal->type);
		$context->headers[$mapping->header] = true;
		if ($mapping->literal !== cpp_literal_kind::string_value) {
			throw new \RuntimeException('C++ string literal emission requires the canonical string type');
		}

		$spelling = self::cpp_string_literal($literal->value);
		if (!self::contains_zero_byte($literal->value)) {
			return $mapping->spelling . '(' . $spelling . ')';
		}
		return $mapping->spelling . '(std::string(' . $spelling . ', ' . string_byte_len($literal->value) . '))';
	}

	/** Escape every byte without allowing a hexadecimal escape to consume its neighbor. */
	private static function cpp_string_literal(string $value): string
	{
		$result = '"';
		$hex = '0123456789ABCDEF';
		$length = string_byte_len($value);
		for ($index = 0; $index < $length; $index++)
		{
			$byte = string_byte_at($value, $index);
			if ($byte === 34) {
				$result .= '\\"';
			}
			elseif ($byte === 92) {
				$result .= '\\\\';
			}
			elseif (($byte >= 32) && ($byte < 127)) {
				$result .= string_byte_from_int($byte);
			}
			else {
				$result .= '\\x' . string_byte_slice($hex, (int) ($byte / 16), 1)
					. string_byte_slice($hex, $byte % 16, 1) . '" "';
			}
		}
		return $result . '"';
	}

	/** Select the length-aware constructor only when ordinary C-string construction would truncate. */
	private static function contains_zero_byte(string $value): bool
	{
		for ($index = 0; $index < string_byte_len($value); $index++) {
			if (string_byte_at($value, $index) === 0) {
				return true;
			}
		}
		return false;
	}

	public static function generate_reference(prepared_variable_reference $reference, cpp_generation_context $context): string
	{
		$target = object_cast(weakref_get($reference->declaration), collected_name::class);
		return self::local_name($target);
	}

	/** Derive one reversible, C++-safe spelling without changing frontend identities. */
	public static function source_name(string $role, string $name): string
	{
		$suffix = '';
		for ($index = 0; $index < string_byte_len($name); $index++) {
			$byte = string_byte_at($name, $index);
			if ($byte === 85) {
				$suffix .= 'UU';
			}
			elseif ($byte === 95) {
				$suffix .= 'U_';
			}
			else {
				$suffix .= string_byte_from_int($byte);
			}
		}
		return $role . '_' . $suffix;
	}

	/** Local and parameter references share the declaration-derived backend spelling. */
	public static function local_name(collected_name $declaration): string
	{
		return self::source_name('local', $declaration->name);
	}
}
