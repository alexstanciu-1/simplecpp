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

	/** Assemble output without acknowledging shared changes; publication belongs to the caller. */
	public function generate(prepared_file $prepared): Storage /** Storage<cpp_artifact> */
	{
		$files /** Storage<prepared_file> */ = new Storage();
		$files->append($prepared);
		return $this->generate_program($files, $prepared);
	}

	/** One partitioning path for single-source and multi-source programs. */
	public function generate_program(Storage $files /** Storage<prepared_file> */, prepared_file $entry_file): Storage /** Storage<cpp_artifact> */
	{
		$ordered /** Storage<preparation_owner> */ = new Storage();
		$found_entry = false;
		foreach ($files as $prepared)
		{
			$source = $prepared->source;
			if (!Preparation_Status::imports_current($source)) {
				throw new \RuntimeException('C++ generation requires preparation after import changes');
			}
			$namespace_bodies /** Storage<namespace_body_node> */ = $source->root->namespace_bodies;
			foreach ($namespace_bodies as $namespace_body) {
				$nodes /** Storage<declaration_node> */ = $namespace_body->declarations;
				foreach ($nodes as $node) {
					$definition = $node->occurrence();
					if ($definition instanceof collected_trait) { continue; }
					if ($definition instanceof collected_member_owner) {
						$type_owner = object_cast($definition, collected_member_owner::class);
						$relationships = $type_owner->relationship_work;
						if ($relationships === null) { throw new \RuntimeException('C++ generation requires completed relationships'); }
						if (!Preparation_Status::complete($relationships)) { throw new \RuntimeException('C++ generation requires completed relationships'); }
						$contracts = $type_owner->contract_work;
						if ($contracts === null) { throw new \RuntimeException('C++ generation requires completed type contracts'); }
						if (!Preparation_Status::complete($contracts)) { throw new \RuntimeException('C++ generation requires completed type contracts'); }
					}
					if ($node instanceof namespace_constant_node) {
						if (!Preparation_Status::complete(object_cast($definition->preparation_work_owner(), preparation_owner::class))) {
							throw new \RuntimeException('C++ generation requires completed constant preparation');
						}
						// Scalar constants substitute settled values; no storage or standalone artifact.
						continue;
					}
					if ($definition instanceof collected_interface) {
						$ordered->append(object_cast(object_cast($definition, collected_interface::class)->contract_work, type_contract_work::class));
					}
					else { $ordered->append(object_cast($definition->preparation_work_owner(), preparation_owner::class)); }
					if ($node instanceof callable_declaration_node) {
						$ordered->append(object_cast(object_cast($node, callable_declaration_node::class)->require_body()->work(), preparation_owner::class));
					}
					elseif ($node instanceof type_declaration_node) {
						$methods /** Storage<method_node> */ = CPP_Callables::implementations(object_cast($definition, collected_member_owner::class));
						foreach ($methods as $method) {
							$ordered->append(object_cast($method->occurrence()->preparation_work_owner(), preparation_owner::class));
							if ($method->has_parsed_body()) { $ordered->append(object_cast($method->require_body()->work(), preparation_owner::class)); }
						}
					}
				}
				// Validate every body, including declaration-only contributions.
				if (!Preparation_Status::complete(object_cast($namespace_body->body->work(), preparation_owner::class))) {
					throw new \RuntimeException('C++ generation requires completed preparation');
				}
			}
			if ($prepared === $entry_file) { $found_entry = true; }
			foreach ($source->preparation_changes as $retired /** @object-key */) {
				if ($retired->change_status === change_state::deleted) { unset($this->program->fragments[$retired]); }
			}
		}
		if (!$found_entry) { throw new \RuntimeException('Prepared entry is outside the program'); }
		$ordered->append(object_cast($entry_file->executable_body()->work(), preparation_owner::class));
		$definitions /** Storage<preparation_owner> */ = new Storage();
		$bodies /** Storage<preparation_owner> */ = new Storage();
		foreach ($ordered as $owner)
		{
			// Closure bodies render inline in their source's executable fragments.
			// Until fragment-level nested dependencies are retained, invalidate those
			// fragments together when independently prepared anonymous work changes.
			if ($owner instanceof body_work) {
				foreach ($owner->source->preparation_changes as $changed /** @object-key */) {
					if (($changed instanceof anonymous_body_work) || ($changed instanceof anonymous_signature_work)) {
						if (isset($this->program->fragments[$owner])) { $this->program->fragments[$owner]->change_status = change_state::changed; }
					}
				}
			}
			if (!Preparation_Status::complete($owner)) {
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

		$result /** Storage<cpp_artifact> */ = new Storage();
		$record_context = new cpp_generation_context();
		foreach ($ordered as $owner)
		{
			Preparation_Status::require_active($owner);
			if ($owner->kind() !== preparation_kind::declaration) { continue; }
			$fragment = $this->program->fragments[$owner];
			$definition = object_cast($owner->declaration(), collected_definition::class);
			if ($definition instanceof collected_method) { continue; }
			$artifact_headers /** hash<bool> */ = $fragment->headers;
			$artifact_declarations /** hash<bool> */ = $fragment->forward_declarations;
			$path = '';
			$identity = '';
			$text = '';
			if ($definition instanceof collected_member_owner) {
				if ($definition instanceof collected_record) {
					$this->validate_record_dependencies(object_cast($definition, collected_record::class), $record_context);
				}
				$path = CPP_Artifacts::type_header($definition->qualified_name());
				$identity = 'type:' . $definition->qualified_name();
				$record = object_cast($definition, collected_member_owner::class);
				$bridge_context = new cpp_generation_context();
				$text = CPP_Declarations::type_opening($record, $bridge_context) . $fragment->text;
				$contract = $record->contract_work->require_facts();
				$dispatch /** Keyed_Storage<prepared_method_contract> */ = $contract->methods;
				$methods /** Storage<method_node> */ = CPP_Callables::implementations($record);
				foreach ($methods as $method) {
					$member_owner /** preparation_owner */ = $method->occurrence()->preparation_work_owner();
					$member_fragment = $this->program->fragments[$member_owner];
					$decision = $dispatch[$method->name];
					$ordinary_instance = !$method->require_preparation()->static_member
						&& ($method->name !== '__construct') && ($method->name !== '__destruct');
					$virtual_method = ($method->name === '__destruct') && $contract->polymorphic;
					$visibility = $method->name === '__construct' ? member_visibility::public_access : $method->require_preparation()->visibility;
					$text .= CPP_Record_Construction::key_declaration(object_cast($method->occurrence(), collected_method::class));
					$text .= CPP_Declarations::visibility($visibility);
					if (!$decision->abstract_method) {
						$text .= "\t" . ($virtual_method ? 'virtual ' : '') . $member_fragment->prototype_text . ";\n";
					}
					if ($ordinary_instance) { $text .= CPP_Dispatch::method($decision, $bridge_context, true); }
					foreach ($member_fragment->headers as $header => $used) { $artifact_headers[$header] = true; }
					foreach ($member_fragment->forward_declarations as $name => $used) { $artifact_declarations[$name] = true; }
				}
				$text .= CPP_Dispatch::bridges($contract, $record, $bridge_context, true);
				foreach ($bridge_context->headers as $header => $used) { $artifact_headers[$header] = true; }
				foreach ($bridge_context->forward_declarations as $name => $used) { $artifact_declarations[$name] = true; }
				if ($record instanceof collected_record) {
					$text .= CPP_Declarations::lifecycle_special_members(object_cast($record, collected_record::class), $contract);
				}
				else { $text .= "public:\n\tvirtual ~" . self::source_name('record', $record->qualified_name()) . "() noexcept = default;\n"; }
				$text .= "};\n\n";
			}
			elseif ($definition instanceof collected_enum) {
				$path = CPP_Artifacts::type_header($definition->qualified_name());
				$identity = 'type:' . $definition->qualified_name();
				$text = $fragment->text;
			}
			else {
				$path = CPP_Artifacts::callable_header($definition->qualified_name());
				$identity = 'callable:' . $definition->qualified_name();
				$text = $fragment->prototype_text . ";\n";
			}
			$result->append(new cpp_artifact($identity, cpp_artifact_kind::header, $path,
				"#pragma once\n" . CPP_Artifacts::preamble($artifact_headers, $artifact_declarations, $path) . $text));
		}
		foreach ($files as $prepared)
		{
			$headers /** hash<bool> */ = [];
			$declarations /** hash<bool> */ = [];
			$body_text = '';
			$dispatch_context = new cpp_generation_context();
			foreach ($ordered as $callable_owner) {
				if (($callable_owner instanceof record_definition_work) || ($callable_owner instanceof type_contract_work)) {
					if ($callable_owner->source === $prepared->source) {
						$dispatch_owner = object_cast($callable_owner->declaration(), collected_member_owner::class);
						$body_text .= CPP_Dispatch::definitions($dispatch_owner, $dispatch_context);
					}
				}
				if (($callable_owner instanceof record_definition_work) && ($callable_owner->source === $prepared->source)) {
					$layout = $this->program->fragments[$callable_owner];
					if ($layout->storage_text !== '') {
						$record = object_cast($callable_owner, record_definition_work::class)->record_definition();
						$headers[CPP_Artifacts::type_header($record->qualified_name())] = true;
						foreach ($layout->definition_headers as $header => $used) { $headers[$header] = true; }
						$body_text .= $layout->storage_text;
					}
				}
				if (!($callable_owner instanceof source_callable_signature_work)) { continue; }
				$definition = object_cast($callable_owner, source_callable_signature_work::class)->callable_definition();
				$implementation_source = $definition->collection;
				if ($definition instanceof collected_method) {
					$implementation_source = object_cast($definition, collected_method::class)->declaring_type()->collection;
				}
				if ($implementation_source !== $prepared->source) { continue; }
				$function = $definition->callable_syntax();
				if (!$function->has_parsed_body()) { continue; }
				$signature_owner = object_cast($definition->preparation_work_owner(), preparation_owner::class);
				$body_owner = object_cast($function->require_body()->work(), preparation_owner::class);
				$signature = $this->program->fragments[$signature_owner];
				$body = $this->program->fragments[$body_owner];
				if ($definition instanceof collected_method) {
					$headers[CPP_Artifacts::type_header(object_cast($definition, collected_method::class)->declaring_type()->qualified_name())] = true;
				}
				else { $headers[CPP_Artifacts::callable_header($definition->qualified_name())] = true; }
				foreach ($signature->definition_headers as $header => $used) {
					$headers[$header] = true;
				}
				foreach ($body->headers as $header => $used) { $headers[$header] = true; }
				foreach ($body->forward_declarations as $name => $used) {
					$declarations[$name] = true;
				}
				$body_text .= $signature->text . $body->initializer_text . "\n{\n" . $body->text . "}\n\n";
			}
			foreach ($dispatch_context->headers as $header => $used) { $headers[$header] = true; }
			foreach ($dispatch_context->forward_declarations as $name => $used) { $declarations[$name] = true; }
			if ($body_text !== '') {
				$path = CPP_Artifacts::unit_path($prepared->source);
				$result->append(new cpp_artifact('implementation:' . $path, cpp_artifact_kind::translation_unit,
					$path, CPP_Artifacts::preamble($headers, $declarations, '') . $body_text));
			}
		}
		$entry_owner = object_cast($entry_file->executable_body()->work(), preparation_owner::class);
		$entry_fragment = $this->program->fragments[$entry_owner];
		// Executable ABI initialization belongs to artifact assembly, not call preparation.
		$entry_headers /** hash<bool> */ = $entry_fragment->headers;
		$entry_headers['lang/php/cli_context.hpp'] = true;
		$result->append(new cpp_artifact('entry', cpp_artifact_kind::translation_unit, 'entry.cpp',
			CPP_Artifacts::preamble($entry_headers, $entry_fragment->forward_declarations, '') . "\nint main(int argc, char** argv)\n{\n"
			. "\tscpp::php::set_cli_args(argc, argv);\n"
			. $entry_fragment->text . "\treturn 0;\n}\n"));
		$helpers /** hash<prepared_compiler_call> */ = [];
		foreach ($ordered as $owner) {
			foreach ($this->program->fragments[$owner]->compiler_helpers as $key => $request) {
				$helpers[$key] = $request;
			}
		}
		foreach ($helpers as $request) { CPP_Compiler_Functions::append_artifacts($request, $result); }
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
		Preparation_Status::require_active($owner);
		if (($owner->state !== preparation_state::ready) || $owner->failed) {
			throw new \LogicException('C++ generation requires successfully prepared work');
		}
		$context = new cpp_generation_context();
		$next = new cpp_fragment();
		if ($owner instanceof body_work) {
			$body_owner = object_cast($owner, body_work::class);
			if ($body_owner instanceof source_callable_body_work) {
				$function = object_cast($body_owner, source_callable_body_work::class)->callable_definition()->callable_syntax();
				$context->return_type = $function->require_preparation()->signature->return_type;
				$definition = object_cast($body_owner, source_callable_body_work::class)->callable_definition();
				if ($definition instanceof collected_method) {
					$next->initializer_text = CPP_Declarations::constructor_initializers(object_cast($definition, collected_method::class), $context);
				}
			}
			$next->text = self::generate_statements($body_owner->executable_body(), $context);
		}
		else
		{
			$entry = object_cast($owner->declaration(), collected_name::class);
			if ($entry instanceof collected_source_callable) {
				$function_syntax = object_cast($entry, collected_source_callable::class)->callable_syntax();
				$definition_context = new cpp_generation_context();
				$next->text = CPP_Declarations::signature($function_syntax, $definition_context, false);
				$next->definition_headers = $definition_context->headers;
				$next->prototype_text = CPP_Declarations::signature($function_syntax, $context, true);
			}
			elseif ($entry instanceof collected_enum) {
				$next->text = CPP_Enums::declaration(object_cast($entry, collected_enum::class)->enum_syntax(), $context);
			}
			elseif ($entry instanceof collected_interface) {
				// Interfaces have no layout fragment; relationships/methods assemble from their owners.
				$next->text = '';
			}
			else
			{
				$syntax = object_cast($entry, collected_record::class)->syntax();
				CPP_Declarations::generate_record($syntax, $context);
				$next->text = $context->records;
				$definition_context = new cpp_generation_context();
				$next->storage_text = CPP_Declarations::static_definitions(object_cast($entry, collected_record::class), $definition_context);
				$next->definition_headers = $definition_context->headers;
				$fields /** Key_Storage_List<prepared_field> */ = $syntax->require_preparation()->fields;
				$records /** Storage<collected_record> */ = $next->records;
				foreach ($fields->items() as $field) {
					if (Type_Queries::is_nominal_handle($field->type)) {
						continue;
					}
					$source_record = Type_Queries::source_record($field->type);
					if ($source_record !== null) {
						$record /** collected_record */ = $source_record;
						$records->append($record);
					}
				}
			}
		}
		$next->headers = $context->headers;
		$next->compiler_helpers = $context->compiler_helpers;
		$next->forward_declarations = $context->forward_declarations;
		$next->version = $owner->version;
		$next->change_status = change_state::unchanged;
		$this->program->fragments[$owner] = $next;
	}

	/** Validate complete by-value edges; every record definition remains in its own header. */
	private function validate_record_dependencies(collected_record $entry, cpp_generation_context $context): void
	{
		$key = $entry->qualified_name();
		if (isset($context->record_states[$key])) {
			if ($context->record_states[$key] === cpp_record_state::visiting) {
				throw new \RuntimeException('Cyclic C++ record assembly dependency');
			}
			return;
		}
		$context->record_states[$key] = cpp_record_state::visiting;
		$owner = object_cast($entry->preparation_work_owner(), preparation_owner::class);
		$fragment = $this->program->fragments[$owner];
		$records /** Storage<collected_record> */ = $fragment->records;
		foreach ($records as $dependency) {
			$this->validate_record_dependencies($dependency, $context);
		}
		$context->record_states[$key] = cpp_record_state::complete;
	}

	/** The worker visits executable children once; declaration hooks collect separate output sections. */
	public static function generate_statements(statement_body_node $body, cpp_generation_context $context): string
	{
		$text = '';
		$statements /** Storage<statement_node> */ = $body->statements;
		$worker = new CPP_Syntax($context);
		$skip_first = false;
		if ($body instanceof function_body_node) {
			$initialization = object_cast($body, function_body_node::class)->base_initialization;
			if ($initialization !== null) { $skip_first = weakref_get($initialization->source_call) !== null; }
		}
		foreach ($statements as $index => $node) {
			if ($skip_first && ($index === 0)) { continue; }
			$text .= $worker->statement($node);
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
		$value = (new CPP_Syntax($context))->expression($initializer);
		return self::generate_storage_value($binding, $name, $value, $explicit_type, $context);
	}

	/** Select the declared local or concrete assignable target without rendering its value. */
	private static function storage_name(prepared_binding $binding, ?assignable_expression_node $target, cpp_generation_context $context): string
	{
		if ($target !== null) {
			$target_node /** assignable_expression_node */ = $target;
			return (new CPP_Syntax($context))->expression($target_node);
		}
		$declaration = object_cast(weakref_get($binding->declaration), collected_name::class);
		return self::local_name($declaration);
	}

	/** Render one declaration or assignment from an already evaluated value expression. */
	public static function generate_storage_value(prepared_binding $binding, string $name, string $value, bool $explicit_type, cpp_generation_context $context): string
	{
		// Even an inferred `auto` copy or replacement needs its concrete value definition.
		$requirement = $binding->alias_target !== null ? cpp_type_requirement::declaration : cpp_type_requirement::complete;
		$type_name = 'auto';
		if (!(Type_Queries::canonical($binding->type)->definition() instanceof closure_type_definition)) {
			$type_name = CPP_Declarations::type_for($binding->type, $context, $requirement);
		}
		$prefix = '';
		if ($binding->resolved_kind === binding_kind::declaration) {
			$prefix = $explicit_type ? $type_name . ' ' : 'auto ';
			if ($binding->alias_target !== null) {
				$place /** prepared_place */ = $binding->alias_target;
				$qualifier = $place->access() === storage_access::read_only ? 'const ' : '';
				$prefix = $qualifier . $type_name . '& ';
			}
		}
		if ($binding->conversion !== null) {
			$value = CPP_Declarations::conversion($value, $binding->conversion, $context);
		}
		return $prefix . $name . ' = ' . $value;
	}

	/** Function returns retain their declared value type; entry returns become native exit codes. */
	public static function generate_return(return_node $syntax, cpp_generation_context $context): string
	{
		if ($syntax->expression === null) {
			return $context->return_type === null ? "\treturn 0;\n" : "\treturn;\n";
		}

		$expression /** expression_node */ = $syntax->expression;
		$value = (new CPP_Syntax($context))->expression($expression);
		if ($context->return_type !== null) {
			if ($syntax->require_preparation()->reference_target !== null) {
				return "\treturn " . $value . ";\n";
			}
			$decision = $syntax->require_preparation()->require_conversion();
			return "\treturn " . CPP_Declarations::conversion($value, $decision, $context) . ";\n";
		}
		return "\treturn static_cast<int>((" . $value . ").native_value());\n";
	}

	public static function generate_expression_statement(expression_statement_node $syntax, cpp_generation_context $context): string
	{
		$expression = $syntax->expression;
		if ($expression instanceof assignment_expression_node) {
			return self::generate_assignment_statement(object_cast($expression, assignment_expression_node::class), $context);
		}
		return "\t" . (new CPP_Syntax($context))->expression($expression) . ";\n";
	}

	/** Render an explicit cast solely from its prepared decision and ordinary operand path. */
	public static function generate_cast(cast_expression_node $syntax,
		cpp_generation_context $context): string
	{
		$value = (new CPP_Syntax($context))->expression($syntax->operand);
		return CPP_Declarations::conversion(
			$value, $syntax->require_cast_preparation()->conversion, $context);
	}

	/** Evaluate and convert the RHS once, store once, then return the stored value by copy. */
	public static function generate_assignment_expression(assignment_expression_node $syntax,
		cpp_generation_context $context): string
	{
		$facts = $syntax->require_write_preparation();
		if ($facts instanceof prepared_index_assignment) {
			return CPP_Index_Write::generate($syntax, object_cast($facts, prepared_index_assignment::class), $context);
		}
		$binding = $syntax->require_assignment_preparation()->binding;
		if (($binding->resolved_kind !== binding_kind::assignment)
			|| !($syntax->target instanceof variable_reference_node)) {
			throw new \LogicException('Nested assignment requires prepared existing local storage');
		}
		$name = self::storage_name($binding, null, $context);
		$value = (new CPP_Syntax($context))->expression($syntax->value);
		$write = self::generate_storage_value($binding, $name, $value, false, $context);
		$result_type = CPP_Declarations::type($facts->destination_type(), $context);
		return '([&]() -> ' . $result_type . ' { ' . $write . '; return ' . $name . '; }())';
	}

	/** Flatten right-associative writes so introduced locals remain in the surrounding body. */
	private static function generate_assignment_statement(assignment_expression_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_write_preparation();
		if ($facts instanceof prepared_index_assignment) {
			return "\t" . CPP_Index_Write::generate($syntax, object_cast($facts, prepared_index_assignment::class), $context, false) . "\n";
		}
		return self::generate_assignment_sequence($syntax, $context)->statements;
	}

	/** Emit inner writes first and return their stored value to the enclosing assignment. */
	private static function generate_assignment_sequence(assignment_expression_node $syntax, cpp_generation_context $context): cpp_assignment_sequence
	{
		if ($syntax->require_write_preparation() instanceof prepared_index_assignment)
		{
			$result = new cpp_assignment_sequence();
			$name = 'write_result_' . $context->next_temporary;
			$context->next_temporary++;
			$result->statements = "\t" . CPP_Declarations::type($syntax->require_preparation()->type, $context)
				. ' ' . $name . ' = ' . self::generate_assignment_expression($syntax, $context) . ";\n";
			$result->value = $name;
			return $result;
		}
		$value_node = $syntax->value;
		$source = new cpp_assignment_sequence();
		if ($value_node instanceof assignment_expression_node) {
			$source = self::generate_assignment_sequence(object_cast($value_node, assignment_expression_node::class), $context);
		}
		else {
			$source->value = (new CPP_Syntax($context))->expression($value_node);
		}

		$target /** nullable<assignable_expression_node> */ = $syntax->target;
		if ($target instanceof variable_reference_node) {
			$target = null;
		}
		$name = '';
		$statements = '';
		if ($target instanceof static_member_reference_node) {
			$member = object_cast($target, static_member_reference_node::class);
			if ($member->qualifier !== null) {
				$statements = CPP_Declarations::qualifier_evaluation($member->qualifier, $context);
			}
			$name = CPP_Declarations::static_property(object_cast($member->require_preparation(), prepared_static_property::class), $context);
		}
		elseif ($target !== null) {
			$name = (new CPP_Syntax($context))->expression($target);
		}
		else {
			$name = self::storage_name($syntax->require_assignment_preparation()->binding, null, $context);
		}
		$statements .= $source->statements;
		$write_facts = $syntax->require_write_preparation();
		if ($write_facts instanceof prepared_property_assignment) {
			$property_write = object_cast($write_facts, prepared_property_assignment::class);
			CPP_Declarations::type($property_write->destination_type(), $context);
			$statements .= "\t" . $name . ' = '
				. CPP_Declarations::conversion($source->value, $property_write->conversion, $context) . ";\n";
		}
		else {
			$binding = $syntax->require_assignment_preparation()->binding;
			$statements .= "\t" . self::generate_storage_value($binding, $name, $source->value, false, $context) . ";\n";
		}

		$result = new cpp_assignment_sequence();
		$result->statements = $statements;
		$result->value = $name;
		return $result;
	}

	/** Emit exact signed integer magnitude using its canonical representation. */
	public static function generate_integer(prepared_integer_literal $literal, cpp_generation_context $context): string
	{
		return self::generate_integer_value($literal->type, $literal->decimal, $context);
	}

	/** Constants and source literals share exact integer representation and header selection. */
	private static function generate_integer_value(canonical_type_use $type, string $decimal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type);
		CPP_Types::record_dependencies($mapping, $context);
		if ($mapping->literal !== cpp_literal_kind::signed_integer) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}

		return 'static_cast<' . $mapping->spelling . '>(' . CPP_Integer_Literals::constant($decimal) . ')';
	}

	/** Lower semantic constant values without preserving runtime- or host-specific names. */
	public static function generate_constant(prepared_constant_reference $reference, cpp_generation_context $context): string
	{
		return self::generate_constant_value($reference->value, $context);
	}

	/** References and declaration defaults share the same immutable value lowering. */
	public static function generate_constant_value(constant_value $value, cpp_generation_context $context): string
	{
		if ($value instanceof enum_constant_value) {
			$enum_value = object_cast($value, enum_constant_value::class);
			return CPP_Declarations::type($enum_value->type, $context) . '::'
				. self::source_name('case', $enum_value->selected_case->name);
		}
		if ($value instanceof no_error_constant_value) {
			return CPP_Declarations::type($value->type, $context) . '::no_error';
		}
		if ($value instanceof sentinel_constant_value)
		{
			if ($value->type->matches(Model::$type_catalog->null_type())) {
				$context->headers['scpp/null_t.hpp'] = true;
				return 'scpp::null';
			}
			if ($value->type->matches(Model::$type_catalog->boolean_sentinel_type(false))) {
				$context->headers['scpp/false_sentinel_t.hpp'] = true;
				return 'scpp::false_sentinel';
			}
			if ($value->type->matches(Model::$type_catalog->boolean_sentinel_type(true))) {
				$context->headers['scpp/true_sentinel_t.hpp'] = true;
				return 'scpp::true_sentinel';
			}
			throw new \LogicException('Unknown canonical sentinel constant');
		}
		if ($value instanceof float_constant_value) {
			$floating = object_cast($value, float_constant_value::class);
			return self::generate_float_value($floating->type, $floating->decimal, $context);
		}
		if ($value instanceof boolean_constant_value) {
			$boolean = object_cast($value, boolean_constant_value::class);
			return self::generate_boolean_value($boolean->type, $boolean->value, $context);
		}
		if ($value instanceof string_constant_value) {
			$string = object_cast($value, string_constant_value::class);
			return self::generate_string_value($string->type, $string->value, $context);
		}
		if (!($value instanceof integer_constant_value)) {
			throw new \RuntimeException('C++ constant emission is not implemented for this value');
		}
		$integer = object_cast($value, integer_constant_value::class);
		return self::generate_integer_value($integer->type, $integer->decimal, $context);
	}

	/** Bind the target once, compute before writing, and return a copy of the updated value. */
	public static function generate_compound_assignment(compound_assignment_expression_node $syntax,
		cpp_generation_context $context): string
	{
		$facts = $syntax->require_compound_assignment_preparation();
		$target = (new CPP_Syntax($context))->expression($syntax->target);
		$place = 'target_' . $context->next_temporary;
		$context->next_temporary++;
		$right = (new CPP_Syntax($context))->expression($syntax->value);
		$value = self::render_binary_decision($facts->decision, $place, $right, $context);
		$value = CPP_Declarations::conversion($value, $facts->write_back, $context);
		$result_type = CPP_Declarations::type($facts->type, $context);
		if ($facts->write_back_mode === compound_write_back::boxed_stored_kind) {
			$context->headers['operators/arithmetic/boxed_numeric.hpp'] = true;
			return '([&]() -> ' . $result_type . ' { auto& ' . $place . ' = ' . $target
				. '; scpp::detail::assign_numeric_result(' . $place . ', ' . $value . '); return ' . $place . '; }())';
		}
		return '([&]() -> ' . $result_type . ' { auto& ' . $place . ' = ' . $target
			. '; ' . $place . ' = ' . $value . '; return ' . $place . '; }())';
	}

	/** Mutate the resolved place once and copy the selected old/new result into a value. */
	public static function generate_mutation(mutation_expression_node $syntax, cpp_generation_context $context): string
	{
		$decision = $syntax->require_mutation_preparation()->decision;
		self::record_operator_headers($decision, $context);
		$prefix = '';
		$suffix = '';
		if (($decision->operation === operator_operation::integer_pre_increment)
			|| ($decision->operation === operator_operation::boxed_pre_increment)) {
			$prefix = '++';
		}
		elseif (($decision->operation === operator_operation::integer_pre_decrement)
			|| ($decision->operation === operator_operation::boxed_pre_decrement)) {
			$prefix = '--';
		}
		elseif (($decision->operation === operator_operation::integer_post_increment)
			|| ($decision->operation === operator_operation::boxed_post_increment)) {
			$suffix = '++';
		}
		elseif (($decision->operation === operator_operation::integer_post_decrement)
			|| ($decision->operation === operator_operation::boxed_post_decrement)) {
			$suffix = '--';
		}
		else {
			throw new \LogicException('Unsupported prepared mutation operation');
		}
		$target = (new CPP_Syntax($context))->expression($syntax->target);
		$result_type = CPP_Declarations::type($decision->result_type, $context);
		$context->headers['scpp/generated/operators.hpp'] = true;
		return 'static_cast<' . $result_type . '>(' . $prefix . $target . $suffix . ')';
	}

	/** Render the prepared operation recursively; token spelling has no backend authority. */
	public static function generate_unary(unary_expression_node $syntax, cpp_generation_context $context): string
	{
		$decision = $syntax->require_unary_preparation()->decision;
		self::record_operator_headers($decision, $context);
		if ($decision->operation === operator_operation::condition_not)
		{
			$context->headers['scpp/bool_t.hpp'] = true;
			$truthiness /** truthiness_decision */ = $decision->truthiness;
			$operand = (new CPP_Syntax($context))->expression($syntax->operand);
			return 'scpp::bool_t(!(' . CPP_Declarations::truthiness($operand, $truthiness, $context) . '))';
		}
		$spelling = '';
		if (($decision->operation === operator_operation::integer_positive)
			|| ($decision->operation === operator_operation::boxed_positive)) {
			$spelling = '+';
		}
		elseif (($decision->operation === operator_operation::integer_negative)
			|| ($decision->operation === operator_operation::boxed_negative)) {
			$spelling = '-';
		}
		elseif ($decision->operation === operator_operation::integer_complement) {
			$spelling = '~';
		}
		elseif ($decision->operation === operator_operation::boolean_not) {
			$spelling = '!';
		}
		else {
			throw new \RuntimeException('C++ unary operation is not supported');
		}
		$worker = new CPP_Syntax($context);
		$operand = CPP_Declarations::conversion(
			$worker->expression($syntax->operand), $decision->operands[0], $context);
		$context->headers['scpp/generated/operators.hpp'] = true;
		return '(' . $spelling . $operand . ')';
	}

	/** Render the selected binary operation and its retained operand conversions. */
	public static function generate_binary(binary_expression_node $syntax, cpp_generation_context $context): string
	{
		$decision = $syntax->require_binary_preparation()->decision;
		$worker = new CPP_Syntax($context);
		$left = $worker->expression($syntax->left);
		$right = $worker->expression($syntax->right);
		return self::render_binary_decision($decision, $left, $right, $context);
	}

	/** Both value-only binaries and compound updates consume the same selected computation. */
	private static function render_binary_decision(operator_decision $decision,
		string $left, string $right, cpp_generation_context $context): string
	{
		self::record_operator_headers($decision, $context);
		if (($decision->operation === operator_operation::condition_and)
			|| ($decision->operation === operator_operation::condition_or)) {
			$logical_spelling = $decision->operation === operator_operation::condition_and ? '&&' : '||';
			$logical_left = CPP_Declarations::truthiness($left, $decision->operand_truthiness[0], $context);
			$logical_right = CPP_Declarations::truthiness($right, $decision->operand_truthiness[1], $context);
			$context->headers['scpp/bool_t.hpp'] = true;
			// Both operands are native bool: C++ must skip the RHS when the left decides the result.
			return 'scpp::bool_t((' . $logical_left . ') ' . $logical_spelling . ' (' . $logical_right . '))';
		}
		if (($decision->operation === operator_operation::presence_is_null)
			|| ($decision->operation === operator_operation::presence_not_null)) {
			// Preparation admits one nullable/owning handle and one non-storable null sentinel.
			// Presence is independent of payload identity and evaluates the value once.
			$value = $left;
			if (Type_Queries::canonical($decision->operands[0]->source_type)->family() === type_family::null_sentinel) {
				$value = $right;
			}
			$presence = '(' . $value . ').has_value()';
			if ($decision->operation === operator_operation::presence_is_null) {
				$context->headers['scpp/generated/operators.hpp'] = true;
				return '(!' . $presence . ')';
			}
			return $presence;
		}
		$spelling = '';
		if ($decision->operation === operator_operation::boolean_and) {
			$spelling = '&&';
		}
		elseif ($decision->operation === operator_operation::boolean_or) {
			$spelling = '||';
		}
		elseif ($decision->operation === operator_operation::boolean_xor) {
			$spelling = '!=';
		}
		elseif (($decision->operation === operator_operation::integer_addition)
			|| ($decision->operation === operator_operation::boxed_addition)
			|| ($decision->operation === operator_operation::string_concatenation)) {
			$spelling = '+';
		}
		elseif (($decision->operation === operator_operation::integer_subtraction)
			|| ($decision->operation === operator_operation::boxed_subtraction)) {
			$spelling = '-';
		}
		elseif ($decision->operation === operator_operation::integer_power) {
			$spelling = 'scpp::pow';
		}
		elseif (($decision->operation === operator_operation::integer_multiplication)
			|| ($decision->operation === operator_operation::boxed_multiplication)) {
			$spelling = '*';
		}
		elseif (($decision->operation === operator_operation::integer_division)
			|| ($decision->operation === operator_operation::boxed_division)) {
			$spelling = '/';
		}
		elseif (($decision->operation === operator_operation::integer_remainder)
			|| ($decision->operation === operator_operation::boxed_remainder)) {
			$spelling = '%';
		}
		elseif ($decision->operation === operator_operation::integer_bitwise_and) {
			$spelling = '&';
		}
		elseif ($decision->operation === operator_operation::integer_bitwise_or) {
			$spelling = '|';
		}
		elseif ($decision->operation === operator_operation::integer_bitwise_xor) {
			$spelling = '^';
		}
		elseif ($decision->operation === operator_operation::integer_shift_left) {
			$spelling = '<<';
		}
		elseif ($decision->operation === operator_operation::integer_shift_right) {
			$spelling = '>>';
		}
		elseif (($decision->operation === operator_operation::integer_equal)
			|| ($decision->operation === operator_operation::enum_equal)
			|| ($decision->operation === operator_operation::boxed_equal)) {
			$spelling = '==';
		}
		elseif (($decision->operation === operator_operation::integer_not_equal)
			|| ($decision->operation === operator_operation::enum_not_equal)
			|| ($decision->operation === operator_operation::boxed_not_equal)) {
			$spelling = '!=';
		}
		elseif (($decision->operation === operator_operation::integer_identical)
			|| ($decision->operation === operator_operation::boxed_identical)) {
			$spelling = 'scpp::php::identical';
		}
		elseif (($decision->operation === operator_operation::integer_not_identical)
			|| ($decision->operation === operator_operation::boxed_not_identical)) {
			$spelling = 'scpp::php::not_identical';
		}
		elseif (($decision->operation === operator_operation::integer_less)
			|| ($decision->operation === operator_operation::boxed_less)) {
			$spelling = '<';
		}
		elseif (($decision->operation === operator_operation::integer_less_equal)
			|| ($decision->operation === operator_operation::boxed_less_equal)) {
			$spelling = '<=';
		}
		elseif (($decision->operation === operator_operation::integer_greater)
			|| ($decision->operation === operator_operation::boxed_greater)) {
			$spelling = '>';
		}
		elseif (($decision->operation === operator_operation::integer_greater_equal)
			|| ($decision->operation === operator_operation::boxed_greater_equal)) {
			$spelling = '>=';
		}
		elseif ($decision->operation === operator_operation::integer_three_way) {
			$spelling = '<=>';
		}
		else {
			throw new \RuntimeException('C++ binary operation is not supported yet');
		}
		if (q_count($decision->operands) !== 2) {
			throw new \LogicException('C++ binary operation requires two prepared operands');
		}

		$left = CPP_Declarations::conversion($left, $decision->operands[0], $context);
		$right = CPP_Declarations::conversion($right, $decision->operands[1], $context);
		if ($decision->operation === operator_operation::integer_power) {
			$context->headers['operators/arithmetic/power.hpp'] = true;
			return $spelling . '(' . $left . ', ' . $right . ')';
		}
		if ($decision->operation === operator_operation::boolean_xor) {
			$left_name = 'operand_' . $context->next_temporary;
			$context->next_temporary++;
			$right_name = 'operand_' . $context->next_temporary;
			$context->next_temporary++;
			$result_type = CPP_Declarations::type($decision->result_type, $context);
			return '([&]() -> ' . $result_type . ' { bool ' . $left_name . ' = static_cast<bool>(' . $left
				. '); bool ' . $right_name . ' = static_cast<bool>(' . $right . '); return ' . $result_type
				. '(' . $left_name . ' != ' . $right_name . '); }())';
		}
		if ($decision->operation === operator_operation::integer_three_way)
		{
			$context->headers['scpp/generated/operators.hpp'] = true;
			$left_name = 'operand_' . $context->next_temporary;
			$context->next_temporary++;
			$right_name = 'operand_' . $context->next_temporary;
			$context->next_temporary++;
			$result_type = CPP_Declarations::type($decision->result_type, $context);
			return '([&]() -> ' . $result_type . ' { auto ' . $left_name . ' = ' . $left
				. '; auto ' . $right_name . ' = ' . $right . '; return ' . $result_type
				. '(static_cast<bool>(' . $left_name . ' < ' . $right_name . ') ? -1 : '
				. '(static_cast<bool>(' . $left_name . ' > ' . $right_name . ') ? 1 : 0)); }())';
		}
		if (($decision->operation === operator_operation::boolean_and)
			|| ($decision->operation === operator_operation::boolean_or)) {
			$context->headers['scpp/bool_t.hpp'] = true;
			return 'scpp::bool_t(static_cast<bool>(' . $left . ') ' . $spelling
				. ' static_cast<bool>(' . $right . '))';
		}
		if (($decision->operation === operator_operation::integer_identical)
			|| ($decision->operation === operator_operation::integer_not_identical)
			|| ($decision->operation === operator_operation::boxed_identical)
			|| ($decision->operation === operator_operation::boxed_not_identical)) {
			$context->headers['lang/php/operators/identity/strict_identity.hpp'] = true;
			return $spelling . '(' . $left . ', ' . $right . ')';
		}
		if (($decision->operation === operator_operation::enum_equal)
			|| ($decision->operation === operator_operation::enum_not_equal)) {
			$context->headers['scpp/bool_t.hpp'] = true;
			return 'scpp::bool_t(' . $left . ' ' . $spelling . ' ' . $right . ')';
		}
		$context->headers['scpp/generated/operators.hpp'] = true;
		return '(' . $left . ' ' . $spelling . ' ' . $right . ')';
	}

	/** Runtime includes follow the prepared operation, not source spelling or boxed family. */
	private static function record_operator_headers(operator_decision $decision, cpp_generation_context $context): void
	{
		$operation /** operator_operation */ = $decision->operation;
		switch ($operation) {
			case operator_operation::boxed_pre_increment:
			case operator_operation::boxed_post_increment:
			case operator_operation::boxed_pre_decrement:
			case operator_operation::boxed_post_decrement:
			case operator_operation::boxed_positive:
			case operator_operation::boxed_negative:
			case operator_operation::boxed_addition:
			case operator_operation::boxed_subtraction:
			case operator_operation::boxed_multiplication:
			case operator_operation::boxed_division:
			case operator_operation::boxed_remainder:
				$context->headers['operators/arithmetic/boxed_numeric.hpp'] = true;
				return;
			case operator_operation::boxed_equal:
			case operator_operation::boxed_not_equal:
				$context->headers['operators/identity/boxed_scalar_equality.hpp'] = true;
				return;
			case operator_operation::boxed_less:
			case operator_operation::boxed_less_equal:
			case operator_operation::boxed_greater:
			case operator_operation::boxed_greater_equal:
				$context->headers['operators/identity/boxed_ordering.hpp'] = true;
				return;
			default:
				return;
		}
	}

	/** Preserve decimal spelling until the target toolchain performs floating conversion. */
	public static function generate_float(prepared_float_literal $literal, cpp_generation_context $context): string
	{
		return self::generate_float_value($literal->type, $literal->decimal, $context);
	}

	public static function generate_float_value(canonical_type_use $type, string $decimal, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type);
		CPP_Types::record_dependencies($mapping, $context);
		if ($mapping->literal !== cpp_literal_kind::floating) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}

		return 'static_cast<' . $mapping->spelling . '>(' . $decimal . ')';
	}

	/** Boolean source normalization is already complete before backend spelling. */
	public static function generate_boolean(prepared_boolean_literal $literal, cpp_generation_context $context): string
	{
		return self::generate_boolean_value($literal->type, $literal->value, $context);
	}

	public static function generate_boolean_value(canonical_type_use $type, bool $value, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type);
		CPP_Types::record_dependencies($mapping, $context);
		if ($mapping->literal !== cpp_literal_kind::boolean) {
			throw new \RuntimeException('C++ literal emission is not implemented for this type');
		}

		$spelling = $value ? 'true' : 'false';

		return 'static_cast<' . $mapping->spelling . '>(' . $spelling . ')';
	}

	/** Append once per source part, with full-expression sequencing between conversions. */
	public static function generate_interpolated_string(interpolated_string_node $syntax,
		cpp_generation_context $context): string
	{
		$type = CPP_Declarations::type($syntax->require_preparation()->type, $context);
		$name = 'interpolation_' . $context->next_temporary;
		$context->next_temporary++;
		$text = '([&]() -> ' . $type . ' { ' . $type . ' ' . $name . '; ';
		$worker = new CPP_Syntax($context);
		$parts /** Storage<interpolation_part_node> */ = $syntax->parts;
		foreach ($parts as $part) {
			$text .= $name . '.append(' . $worker->interpolation_part($part) . '); ';
		}
		return $text . 'return ' . $name . '; }())';
	}

	/** Render decoded bytes as a C++ literal, retaining length when a C string would truncate. */
	public static function generate_string(prepared_string_literal $literal, cpp_generation_context $context): string
	{
		return self::generate_string_value($literal->type, $literal->value, $context);
	}

	public static function generate_string_value(canonical_type_use $type, string $value, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type);
		CPP_Types::record_dependencies($mapping, $context);
		if ($mapping->literal !== cpp_literal_kind::string_value) {
			throw new \RuntimeException('C++ string literal emission requires the canonical string type');
		}

		$spelling = self::cpp_string_literal($value);
		if (!self::contains_zero_byte($value)) {
			return $mapping->spelling . '(' . $spelling . ')';
		}
		return $mapping->spelling . '(std::string(' . $spelling . ', ' . string_byte_len($value) . '))';
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
		if ($target instanceof collected_method) { return 'this'; }
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
			elseif ($byte === 92) {
				$suffix .= 'Uq';
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
