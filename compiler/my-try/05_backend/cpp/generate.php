<?php

/* Emit prepared declarations and straight-line bodies using specialization dispatch. */
namespace scpp\compiler;

final class CPP_Generator
{
	/** Keep exception implementation headers in compiled middleware. */
	private static function compiled_entry(string $body): string
	{
		return "\nstatic int application_body()\n{\n" . $body . "\treturn 0;\n}\n"
			. "int main(int argc, char** argv) { return scpp::internal::run_application(argc, argv, application_body); }\n";
	}

	private static function native_entry(string $body): string
	{
		$modules /** Keyed_Storage<runtime_module_spec> */ = Model::require_runtime_metadata()->modules;
		if (isset($modules['tasks'])) {
			return CPP_Entry_Support::base_body(false) . self::compiled_entry($body);
		}
		return "\nint main(int argc, char** argv)\n{\n// SCPP-ENTRY-TRY\n\ttry {\n\tscpp::php::set_cli_args(argc, argv);\n"
			. $body . "\treturn 0;\n// SCPP-ENTRY-CATCH\n"
			. "\t} catch (const std::exception& failure) {\n\t\tscpp::print_runtime_exception(failure);\n\t\treturn 1;\n\t}\n// SCPP-ENTRY-END\n}\n";
	}

	private cpp_program $program;
	private ?CPP_Specializations $specializations = null;
	private cpp_lowering_policy $policy;

	/** Standalone callers get private storage; Compiler supplies the retained program. */
	public function __construct(?cpp_program $program = null, ?cpp_lowering_options $options = null, ?cpp_lowering_policy $policy = null)
	{
		$this->program = $program ?? new cpp_program();
		$this->policy = $policy ?? new cpp_lowering_policy();
		$effective_options = $options ?? new cpp_lowering_options();
		$enabled = $effective_options->prelower_templates;
		if ($this->program->prelower_templates !== $enabled) {
			$this->program->fragments = new \SplObjectStorage /** hash<cpp_fragment, preparation_owner> */();
			$this->program->prelower_templates = $enabled;
		}
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
		$this->specializations = null;
		if ($this->program->prelower_templates) { $this->specializations = new CPP_Specializations(); }
		// Policy changes invalidate only backend fragments, even with unchanged preparation.
		if ($this->program->policy_identity !== $this->policy->identity()) {
			$this->program->fragments = new \SplObjectStorage /** hash<cpp_fragment, preparation_owner> */();
			$this->program->policy_identity = $this->policy->identity();
		}
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
		$allocations /** hash<bool> */ = [];
		if ($this->specializations === null) { $allocations = CPP_Shared_Allocations::select($ordered, $this->policy); }
		// Eligibility is a backend placement decision, not a semantic body dependency.
		// Re-render on shape changes so cached callers cannot reference retired helpers.
		if (CPP_Shared_Allocations::changed($this->program->outlined_shared_allocations, $allocations)) {
			$this->program->fragments = new \SplObjectStorage /** hash<cpp_fragment, preparation_owner> */();
		}
		$this->program->outlined_shared_allocations = $allocations;
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
			if (($fragment->source_context_digest !== '')
				&& ($fragment->source_context_digest !== CPP_Callsites::digest($owner->source))) {
				$fragment->change_status = change_state::changed;
			}
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
		$record_context = new cpp_generation_context($this->specializations);
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
				$bridge_context = new cpp_generation_context($this->specializations);
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
				$text .= "};\n\n" . $fragment->allocation_declaration_text;
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
			$artifact = CPP_Artifacts::described($identity, cpp_artifact_kind::header, $path,
				"#pragma once\n" . CPP_Artifacts::preamble($artifact_headers, $artifact_declarations, $path) . $text, $artifact_headers);
			$artifact->header_dependencies = $artifact_headers;
			unset($artifact->header_dependencies[$path]);
			$result->append($artifact);
		}
		$groups /** Storage<cpp_implementation_group> */ = CPP_Implementation_Groups::assemble($files, $ordered, $this->program, $this->specializations);
		foreach ($groups as $group) {
			$result->append(CPP_Artifacts::implementation($group));
		}
		$entry_owner = object_cast($entry_file->executable_body()->work(), preparation_owner::class);
		$entry_fragment = $this->program->fragments[$entry_owner];
		// Executable ABI initialization belongs to artifact assembly, not call preparation.
		$entry_headers /** hash<bool> */ = $entry_fragment->headers;
		$entry_text = '';
		if ($this->specializations !== null) {
			$entry_headers['specializations/base.hpp'] = true;
			$entry_text = self::compiled_entry($entry_fragment->text);
		}
		else {
			$entry_headers['lang/php/cli_context.hpp'] = true;
			$entry_headers['scpp/runtime_error.hpp'] = true;
			$entry_text = self::native_entry($entry_fragment->text);
		}
		$result->append(CPP_Artifacts::described('entry', cpp_artifact_kind::translation_unit, 'entry.cpp',
			CPP_Artifacts::preamble($entry_headers, $entry_fragment->forward_declarations, '') . $entry_text, $entry_headers));
		$helpers /** hash<prepared_compiler_call> */ = [];
		foreach ($ordered as $owner) {
			foreach ($this->program->fragments[$owner]->compiler_helpers as $key => $request) {
				$helpers[$key] = $request;
			}
		}
		foreach ($helpers as $request) { CPP_Compiler_Functions::append_artifacts($request, $result); }
		if ($this->specializations !== null) {
			foreach ($ordered as $owner) {
				foreach ($this->program->fragments[$owner]->runtime_operations as $request) {
					$this->specializations->request_operation($request);
				}
				foreach ($this->program->fragments[$owner]->specializations as $type) {
					$this->specializations->request($type);
				}
			}
			$this->specializations->append_artifacts($result);
		}
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

	/** Keep native payload implementations out of application translation units. */
	private function render(preparation_owner $owner): void
	{
		Preparation_Status::require_active($owner);
		if (($owner->state !== preparation_state::ready) || $owner->failed) {
			throw new \LogicException('C++ generation requires successfully prepared work');
		}
		$context = new cpp_generation_context($this->specializations);
		$context->outlined_shared_allocations = $this->program->outlined_shared_allocations;
		$next = new cpp_fragment();
		if ($owner instanceof body_work) {
			$body_owner = object_cast($owner, body_work::class);
			if ($body_owner instanceof source_callable_body_work) {
				$function = object_cast($body_owner, source_callable_body_work::class)->callable_definition()->callable_syntax();
				$function_signature = $function->require_preparation()->signature;
				$context->asynchronous = $function_signature->async_value_type !== null;
				$context->return_type = $function_signature->async_value_type ?? $function_signature->return_type;
				$definition = object_cast($body_owner, source_callable_body_work::class)->callable_definition();
				if ($definition instanceof collected_method) {
					$next->initializer_text = CPP_Declarations::constructor_initializers(object_cast($definition, collected_method::class), $context);
				}
			}
			$next->text = self::generate_statements($body_owner->executable_body(), $context);
			if ($context->asynchronous) {
				if (Type_Queries::canonical($context->return_type)->family() === type_family::no_value) { $next->text .= "\tco_return;\n"; }
			}
		}
		else
		{
			$entry = object_cast($owner->declaration(), collected_name::class);
			if ($entry instanceof collected_source_callable) {
				$function_syntax = object_cast($entry, collected_source_callable::class)->callable_syntax();
				$definition_context = new cpp_generation_context($this->specializations);
				$definition_context->outlined_shared_allocations = $this->program->outlined_shared_allocations;
				$async_bridge = ($this->specializations !== null) && ($function_syntax->require_preparation()->signature->async_value_type !== null);
				$next->text = CPP_Declarations::signature($function_syntax, $definition_context, false, $async_bridge);
				if ($async_bridge) { $next->async_bridge_text = CPP_Async::bridge($function_syntax, $definition_context); }
				$next->definition_headers = $definition_context->headers;
				if ($definition_context->reads_source_context) { $context->reads_source_context = true; }
				foreach ($definition_context->runtime_operations as $key => $request) { $context->runtime_operations[$key] = $request; }
				foreach ($definition_context->specializations as $id => $type) {
					$context->specializations[$id] = $type;
				}
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
				$outline = CPP_Default_Constructors::eligible(object_cast($entry, collected_record::class), $this->policy);
				CPP_Declarations::generate_record($syntax, $context, $outline);
				$next->text = $context->records;
				$definition_context = new cpp_generation_context($this->specializations);
				$definition_context->outlined_shared_allocations = $this->program->outlined_shared_allocations;
				$next->record_definition_text = CPP_Declarations::static_definitions(object_cast($entry, collected_record::class), $definition_context);
				if ($outline) {
					$next->record_definition_text .= CPP_Default_Constructors::definition(object_cast($entry, collected_record::class), $definition_context);
				}
				$allocation_record = object_cast($entry, collected_record::class);
				$allocation_name = $allocation_record->qualified_name();
				if (isset($this->program->outlined_shared_allocations[$allocation_name])) {
					$next->allocation_declaration_text = CPP_Shared_Allocations::signature($allocation_record) . ";\n\n";
					$context->headers['scpp/shared_p.hpp'] = true;
					$next->record_definition_text .= CPP_Shared_Allocations::definition($allocation_record, $definition_context);
				}
				$next->definition_headers = $definition_context->headers;
				if ($definition_context->reads_source_context) { $context->reads_source_context = true; }
				foreach ($definition_context->runtime_operations as $key => $request) { $context->runtime_operations[$key] = $request; }
				foreach ($definition_context->specializations as $id => $type) {
					$context->specializations[$id] = $type;
				}
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
		$next->specializations = $context->specializations;
		$next->runtime_operations = $context->runtime_operations;
		$next->headers = $context->headers;
		$next->compiler_helpers = $context->compiler_helpers;
		$next->forward_declarations = $context->forward_declarations;
		if ($context->reads_source_context) { $next->source_context_digest = CPP_Callsites::digest($owner->source); }
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
			return CPP_Declarations::type_for($binding->type, $context, cpp_type_requirement::stored_value) . ' ' . $name;
		}

		$initializer /** expression_node */ = $initializer;
		$previous = $context->suspending_expression;
		$context->suspending_expression = $initializer->require_preparation()->suspends;
		try {
			$value = (new CPP_Syntax($context))->expression($initializer);
			return self::generate_storage_value($binding, $name, $value, $explicit_type, $context);
		}
		finally { $context->suspending_expression = $previous; }
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
		// An inferred copy needs the stored representation, not necessarily a shared payload layout.
		$requirement = $binding->alias_target !== null ? cpp_type_requirement::declaration : cpp_type_requirement::stored_value;
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
		$value = self::return_value($syntax, $context);
		if ($context->finally_exit !== null) {
			$pending /** cpp_finally_exit */ = $context->finally_exit;
			return CPP_Finally::capture_return($pending, $value);
		}
		return "\t" . ($context->asynchronous ? 'co_return' : 'return') . ($value === '' ? '' : ' ' . $value) . ";\n";
	}

	/** Evaluate the ordinary prepared conversion once, before any delayed-exit storage. */
	public static function return_value(return_node $syntax, cpp_generation_context $context): string
	{
		if ($syntax->expression === null) {
			return $context->return_type === null ? '0' : '';
		}
		$expression /** expression_node */ = $syntax->expression;
		$value = (new CPP_Syntax($context))->expression($expression);
		if ($context->return_type !== null) {
			if ($syntax->require_preparation()->reference_target !== null) {
				return $value;
			}
			$decision = $syntax->require_preparation()->require_conversion();
			$previous = $context->suspending_expression;
			$context->suspending_expression = $expression->require_preparation()->suspends;
			try { return CPP_Declarations::conversion($value, $decision, $context); }
			finally { $context->suspending_expression = $previous; }
		}
		return 'static_cast<int>((' . $value . ').native_value())';
	}

	public static function generate_expression_statement(expression_statement_node $syntax, cpp_generation_context $context): string
	{
		$expression = $syntax->expression;
		if ($expression instanceof assignment_expression_node) {
			$previous = $context->suspending_expression;
			$context->suspending_expression = $expression->require_preparation()->suspends;
			try { return self::generate_assignment_statement(object_cast($expression, assignment_expression_node::class), $context); }
			finally { $context->suspending_expression = $previous; }
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
		return CPP_Sequence::open($result_type, $context) . $write . '; ' . CPP_Sequence::result($name, $context) . CPP_Sequence::close($context);
	}

	/** Flatten right-associative writes so introduced locals remain in the surrounding body. */
	private static function generate_assignment_statement(assignment_expression_node $syntax, cpp_generation_context $context): string
	{
		$facts = $syntax->require_write_preparation();
		if ($facts instanceof prepared_index_assignment) {
			return "\t" . CPP_Index_Write::generate($syntax, object_cast($facts, prepared_index_assignment::class), $context, false) . "\n";
		}
		if (($facts instanceof prepared_property_assignment) && ($syntax->target instanceof field_access_node)) {
			$field = object_cast($syntax->target, field_access_node::class);
			if (!$field->require_preparation()->addressable() || CPP_Provider_Properties::applies($field, $context)) {
				return "\t" . self::generate_retained_property_write($field, object_cast($facts, prepared_property_assignment::class),
					(new CPP_Syntax($context))->expression($syntax->value), $context) . ";\n";
			}
		}
		return self::generate_assignment_sequence($syntax, $context)->statements;
	}

	/** Retain an evaluated receiver for one write; no heap-interior reference escapes it. */
	private static function generate_retained_property_write(field_access_node $target, prepared_property_assignment $facts,
		string $value, cpp_generation_context $context): string
	{
		if (CPP_Provider_Properties::applies($target, $context)) {
			return CPP_Provider_Properties::write($target, $facts, $value, $context);
		}
		$receiver = CPP_Declarations::bind_storage_receiver($target, $context);
		$name = 'property_target_' . $context->next_temporary;
		$context->next_temporary++;
		return CPP_Sequence::open(CPP_Declarations::type($facts->destination_type(), $context), $context)
			. $receiver->statements . 'auto& ' . $name . ' = ' . $receiver->value . '; '
			. self::property_store($name, $value, $facts, $context)
			. CPP_Sequence::result($name, $context) . CPP_Sequence::close($context);
	}

	/** Payload replacement starts union member lifetime only after a successful RHS snapshot. */
	private static function property_store(string $target, string $value,
		prepared_property_assignment $facts, cpp_generation_context $context): string
	{
		$converted = CPP_Declarations::conversion($value, $facts->conversion, $context);
		if (!$facts->initializes_union_member) { return $target . ' = ' . $converted . ';'; }
		$context->headers['memory'] = true;
		$snapshot = 'union_payload_' . $context->next_temporary;
		$context->next_temporary++;
		return 'auto ' . $snapshot . ' = ' . $converted . '; '
			. 'std::construct_at(std::addressof(' . $target . '), ' . $snapshot . ');';
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
			if ($target->require_preparation()->addressable()) {
				$name = (new CPP_Syntax($context))->expression($target);
			}
		}
		else {
			$name = self::storage_name($syntax->require_assignment_preparation()->binding, null, $context);
		}
		$statements .= $source->statements;
		$write_facts = $syntax->require_write_preparation();
		if ($write_facts instanceof prepared_property_assignment) {
			$property_write = object_cast($write_facts, prepared_property_assignment::class);
			CPP_Declarations::type($property_write->destination_type(), $context);
			if ($target instanceof field_access_node) {
				$field_target = object_cast($target, field_access_node::class);
				if (!$field_target->require_preparation()->addressable() || CPP_Provider_Properties::applies($field_target, $context)) {
					$result_name = 'property_result_' . $context->next_temporary;
					$context->next_temporary++;
					$result = new cpp_assignment_sequence();
					$result->statements = "\tauto " . $result_name . ' = '
						. self::generate_retained_property_write($field_target, $property_write, $source->value, $context) . ";\n";
					$result->value = $result_name;
					return $result;
				}
			}
			$statements .= "\t" . self::property_store($name, $source->value, $property_write, $context) . "\n";
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
		$mapping = CPP_Types::representation($type, $context);
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
			// The immutable no-error value is the ordinary default error state.
			if ($context->concrete_types()) { return CPP_Declarations::type_for($value->type, $context, cpp_type_requirement::stored_value) . '{}'; }
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
		if ($facts->indexed_target !== null) {
			return CPP_Index_Write::generate_compound($syntax, $facts, $context);
		}
		$target = CPP_Declarations::bind_storage_receiver($syntax->target, $context);
		$place = 'target_' . $context->next_temporary;
		$context->next_temporary++;
		$previous = 'previous_' . $context->next_temporary;
		$context->next_temporary++;
		$prefix = $target->statements . 'auto& ' . $place . ' = ' . $target->value . '; ';
		$prefix .= 'auto ' . $previous . ' = ' . $place . '; ';
		$right = (new CPP_Syntax($context))->expression($syntax->value);
		$prefix .= self::compute_compound($facts, $previous, $right, $context);
		$result_type = CPP_Declarations::type($facts->type, $context);
		return CPP_Sequence::open($result_type, $context) . $prefix
			. $place . ' = ' . $previous . '; ' . CPP_Sequence::result($place, $context) . CPP_Sequence::close($context);
	}

	/** Compute/validate into an owned old-value snapshot before either destination protocol commits. */
	public static function compute_compound(prepared_compound_assignment_expression $facts,
		string $previous, string $right, cpp_generation_context $context): string
	{
		$value = self::render_binary_decision($facts->decision, $previous, $right, $context);
		$value = CPP_Declarations::conversion($value, $facts->write_back, $context);
		if ($facts->write_back_mode === compound_write_back::boxed_stored_kind) {
			$context->headers['operators/arithmetic/boxed_numeric.hpp'] = true;
			return 'scpp::detail::assign_numeric_result(' . $previous . ', ' . $value . '); ';
		}
		return $previous . ' = ' . $value . '; ';
	}

	/** Mutate the resolved place once and copy the selected old/new result into a value. */
	public static function generate_mutation(mutation_expression_node $syntax, cpp_generation_context $context): string
	{
		if (($syntax->target instanceof field_access_node)
			&& CPP_Provider_Properties::applies(object_cast($syntax->target, field_access_node::class), $context)) {
			throw new \RuntimeException('prelower_templates: provider property updates require separate write-back transport');
		}
		$decision = $syntax->require_mutation_preparation()->decision;
		if ($context->specialization_engine === null) {
			self::record_operator_headers($decision, $context);
		}
		$prefix = '';
		$suffix = '';
		if (($decision->operation === operator_operation::scalar_pre_increment)
			|| ($decision->operation === operator_operation::wrapper_pre_increment)
			|| ($decision->operation === operator_operation::boxed_pre_increment)) {
			$prefix = '++';
		}
		elseif (($decision->operation === operator_operation::scalar_pre_decrement)
			|| ($decision->operation === operator_operation::wrapper_pre_decrement)
			|| ($decision->operation === operator_operation::boxed_pre_decrement)) {
			$prefix = '--';
		}
		elseif (($decision->operation === operator_operation::scalar_post_increment)
			|| ($decision->operation === operator_operation::wrapper_post_increment)
			|| ($decision->operation === operator_operation::boxed_post_increment)) {
			$suffix = '++';
		}
		elseif (($decision->operation === operator_operation::scalar_post_decrement)
			|| ($decision->operation === operator_operation::wrapper_post_decrement)
			|| ($decision->operation === operator_operation::boxed_post_decrement)) {
			$suffix = '--';
		}
		else {
			throw new \LogicException('Unsupported prepared mutation operation');
		}
		if ($context->concrete_types()) {
			CPP_Concrete_Mutations::require_interface($decision, $syntax->target->require_preparation()->type,
				$prefix === '' ? $suffix : $prefix, $context);
		}
		$target = (new CPP_Syntax($context))->expression($syntax->target);
		$result_type = CPP_Declarations::type($decision->result_type, $context);
		if ($context->specialization_engine === null) {
			$context->headers['scpp/generated/operators.hpp'] = true;
		}
		return 'static_cast<' . $result_type . '>(' . $prefix . $target . $suffix . ')';
	}

	/** Render the prepared operation recursively; token spelling has no backend authority. */
	public static function generate_unary(unary_expression_node $syntax, cpp_generation_context $context): string
	{
		$decision = $syntax->require_unary_preparation()->decision;
		if ($context->specialization_engine === null) {
			self::record_operator_headers($decision, $context);
		}
		$operand = (new CPP_Syntax($context))->expression($syntax->operand);
		$operand = self::extract_operator_operand($operand, $decision, 0, $context);
		if ($decision->operation === operator_operation::condition_not)
		{
			$truthiness /** truthiness_decision */ = $decision->truthiness;
			return CPP_Declarations::type($decision->result_type, $context) . '(!(' . CPP_Declarations::truthiness($operand, $truthiness, $context) . '))';
		}
		$spelling = '';
		if (($decision->operation === operator_operation::integer_positive)
			|| ($decision->operation === operator_operation::floating_positive)
			|| ($decision->operation === operator_operation::boxed_positive)) {
			$spelling = '+';
		}
		elseif (($decision->operation === operator_operation::integer_negative)
			|| ($decision->operation === operator_operation::floating_negative)
			|| ($decision->operation === operator_operation::boxed_negative)) {
			$spelling = '-';
		}
		elseif (($decision->operation === operator_operation::integer_complement)
			|| ($decision->operation === operator_operation::boxed_complement)) {
			$spelling = '~';
		}
		elseif ($decision->operation === operator_operation::boolean_not) {
			$spelling = '!';
		}
		else {
			throw new \RuntimeException('C++ unary operation is not supported');
		}
		$operand = CPP_Declarations::conversion($operand, $decision->operands[0], $context);
		if ($context->specialization_engine !== null) {
			return CPP_Declarations::type($decision->result_type, $context) . '(' . $spelling . '(' . $operand . ').native_value())';
		}
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
		if ($context->specialization_engine === null) {
			self::record_operator_headers($decision, $context);
		}
		$left = self::extract_operator_operand($left, $decision, 0, $context);
		$right = self::extract_operator_operand($right, $decision, 1, $context);
		if (($decision->operation === operator_operation::condition_and)
			|| ($decision->operation === operator_operation::condition_or)) {
			$logical_spelling = $decision->operation === operator_operation::condition_and ? '&&' : '||';
			$logical_left = CPP_Declarations::truthiness($left, $decision->operand_truthiness[0], $context);
			$logical_right = CPP_Declarations::truthiness($right, $decision->operand_truthiness[1], $context);
			// Both operands are native bool: C++ must skip the RHS when the left decides the result.
			return CPP_Declarations::type($decision->result_type, $context) . '((' . $logical_left . ') ' . $logical_spelling . ' (' . $logical_right . '))';
		}
		if (($decision->operation === operator_operation::presence_is_null)
			|| ($decision->operation === operator_operation::presence_not_null)) {
			// Preparation admits one nullable/owning handle and one non-storable null sentinel.
			// Presence is independent of payload identity and evaluates the value once.
			$value = $left;
			$value_index = 0;
			if (Type_Queries::canonical($decision->operands[0]->source_type)->family() === type_family::null_sentinel) {
				$value = $right;
				$value_index = 1;
			}
			if ($context->concrete_types()) {
				$presence = CPP_Runtime_Operations::wrapper_presence(
					$decision->operands[$value_index]->source_type, $value, $context);
				if ($decision->operation === operator_operation::presence_is_null) {
					$presence = '!(' . $presence . ')';
				}
				return CPP_Declarations::type($decision->result_type, $context) . '(' . $presence . ')';
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
		elseif (($decision->operation === operator_operation::boolean_xor)
			|| ($decision->operation === operator_operation::condition_xor)) {
			$spelling = '!=';
		}
		elseif (($decision->operation === operator_operation::integer_addition)
			|| ($decision->operation === operator_operation::floating_addition)
			|| ($decision->operation === operator_operation::boxed_addition)
			|| ($decision->operation === operator_operation::string_concatenation)) {
			$spelling = '+';
		}
		elseif (($decision->operation === operator_operation::integer_subtraction)
			|| ($decision->operation === operator_operation::floating_subtraction)
			|| ($decision->operation === operator_operation::boxed_subtraction)) {
			$spelling = '-';
		}
		elseif ($decision->operation === operator_operation::integer_power) {
			$spelling = 'scpp::pow';
		}
		elseif ($decision->operation === operator_operation::floating_three_way) {
			$spelling = 'scpp::numeric_compare';
		}
		elseif (($decision->operation === operator_operation::integer_multiplication)
			|| ($decision->operation === operator_operation::floating_multiplication)
			|| ($decision->operation === operator_operation::boxed_multiplication)) {
			$spelling = '*';
		}
		elseif (($decision->operation === operator_operation::integer_division)
			|| ($decision->operation === operator_operation::floating_division)
			|| ($decision->operation === operator_operation::boxed_division)) {
			$spelling = '/';
		}
		elseif (($decision->operation === operator_operation::integer_remainder)
			|| ($decision->operation === operator_operation::boxed_remainder)) {
			$spelling = '%';
		}
		elseif (($decision->operation === operator_operation::integer_bitwise_and)
			|| ($decision->operation === operator_operation::boxed_bitwise_and)) {
			$spelling = '&';
		}
		elseif (($decision->operation === operator_operation::integer_bitwise_or)
			|| ($decision->operation === operator_operation::boxed_bitwise_or)) {
			$spelling = '|';
		}
		elseif (($decision->operation === operator_operation::integer_bitwise_xor)
			|| ($decision->operation === operator_operation::boxed_bitwise_xor)) {
			$spelling = '^';
		}
		elseif (($decision->operation === operator_operation::integer_shift_left)
			|| ($decision->operation === operator_operation::boxed_shift_left)) {
			$spelling = '<<';
		}
		elseif (($decision->operation === operator_operation::integer_shift_right)
			|| ($decision->operation === operator_operation::boxed_shift_right)) {
			$spelling = '>>';
		}
		elseif (($decision->operation === operator_operation::integer_equal)
			|| ($decision->operation === operator_operation::wrapper_equal)
			|| ($decision->operation === operator_operation::string_equal)
			|| ($decision->operation === operator_operation::floating_equal)
			|| ($decision->operation === operator_operation::boolean_equal)
			|| ($decision->operation === operator_operation::enum_equal)
			|| ($decision->operation === operator_operation::boxed_equal)) {
			$spelling = '==';
		}
		elseif (($decision->operation === operator_operation::integer_not_equal)
			|| ($decision->operation === operator_operation::wrapper_not_equal)
			|| ($decision->operation === operator_operation::string_not_equal)
			|| ($decision->operation === operator_operation::floating_not_equal)
			|| ($decision->operation === operator_operation::boolean_not_equal)
			|| ($decision->operation === operator_operation::enum_not_equal)
			|| ($decision->operation === operator_operation::boxed_not_equal)) {
			$spelling = '!=';
		}
		elseif (($decision->operation === operator_operation::integer_identical)
			|| ($decision->operation === operator_operation::wrapper_identical)
			|| ($decision->operation === operator_operation::scalar_identical)
			|| ($decision->operation === operator_operation::shared_identical)
			|| ($decision->operation === operator_operation::boxed_identical)) {
			$spelling = 'scpp::php::identical';
		}
		elseif (($decision->operation === operator_operation::integer_not_identical)
			|| ($decision->operation === operator_operation::wrapper_not_identical)
			|| ($decision->operation === operator_operation::scalar_not_identical)
			|| ($decision->operation === operator_operation::shared_not_identical)
			|| ($decision->operation === operator_operation::boxed_not_identical)) {
			$spelling = 'scpp::php::not_identical';
		}
		elseif (($decision->operation === operator_operation::integer_less)
			|| ($decision->operation === operator_operation::string_less)
			|| ($decision->operation === operator_operation::floating_less)
			|| ($decision->operation === operator_operation::boxed_less)) {
			$spelling = '<';
		}
		elseif (($decision->operation === operator_operation::integer_less_equal)
			|| ($decision->operation === operator_operation::string_less_equal)
			|| ($decision->operation === operator_operation::floating_less_equal)
			|| ($decision->operation === operator_operation::boxed_less_equal)) {
			$spelling = '<=';
		}
		elseif (($decision->operation === operator_operation::integer_greater)
			|| ($decision->operation === operator_operation::string_greater)
			|| ($decision->operation === operator_operation::floating_greater)
			|| ($decision->operation === operator_operation::boxed_greater)) {
			$spelling = '>';
		}
		elseif (($decision->operation === operator_operation::integer_greater_equal)
			|| ($decision->operation === operator_operation::string_greater_equal)
			|| ($decision->operation === operator_operation::floating_greater_equal)
			|| ($decision->operation === operator_operation::boxed_greater_equal)) {
			$spelling = '>=';
		}
		elseif (($decision->operation === operator_operation::integer_three_way)
			|| ($decision->operation === operator_operation::string_three_way)) {
			$spelling = '<=>';
		}
		else {
			throw new \RuntimeException('C++ binary operation is not supported yet');
		}
		if ($decision->operation === operator_operation::condition_xor)
		{
			if (q_count($decision->operand_truthiness) !== 2) {
				throw new \LogicException('Numeric XOR requires two prepared truth conversions');
			}
			$left = CPP_Declarations::truthiness($left, $decision->operand_truthiness[0], $context);
			$right = CPP_Declarations::truthiness($right, $decision->operand_truthiness[1], $context);
		}
		else
		{
			if (q_count($decision->operands) !== 2) {
				throw new \LogicException('C++ binary operation requires two prepared operands');
			}
			$left = CPP_Declarations::conversion($left, $decision->operands[0], $context);
			$right = CPP_Declarations::conversion($right, $decision->operands[1], $context);
		}

		if (($decision->operation === operator_operation::boolean_and)
			|| ($decision->operation === operator_operation::boolean_or)) {
			return CPP_Declarations::type($decision->result_type, $context) . '(static_cast<bool>(' . $left . ') ' . $spelling
				. ' static_cast<bool>(' . $right . '))';
		}

		// Materialize converted values, never aliases that the next operand can change.
		$left_name = 'operand_' . $context->next_temporary;
		$context->next_temporary++;
		$right_name = 'operand_' . $context->next_temporary;
		$context->next_temporary++;
		$result_type = CPP_Declarations::type($decision->result_type, $context);
		$prefix = CPP_Sequence::open($result_type, $context)
			. 'auto ' . $left_name . ' = ' . $left . '; '
			. 'auto ' . $right_name . ' = ' . $right . '; ';
		return $prefix . CPP_Sequence::result(self::render_eager_binary($decision, $spelling, $left_name, $right_name, $context), $context) . CPP_Sequence::close($context);
	}

	/** Compose extraction inside the operand expression so lazy RHS guards remain lazy. */
	private static function extract_operator_operand(string $expression, operator_decision $decision,
		int $position, cpp_generation_context $context): string
	{
		if (q_count($decision->operand_extractions) === 0) {
			return $expression;
		}
		return CPP_Declarations::conversion($expression, $decision->operand_extractions[$position], $context);
	}

	/** Compute from already converted, ordered values; no source expression is repeated. */
	public static function render_eager_binary(operator_decision $decision, string $spelling,
		string $left, string $right, cpp_generation_context $context): string
	{
		if ($context->specialization_engine !== null) {
			return CPP_Runtime_Operations::binary($decision, $spelling, $left, $right, $context);
		}
		// Read native bytes only after both source values have been copied in order.
		if (($decision->operation === operator_operation::string_equal)
			|| ($decision->operation === operator_operation::string_not_equal)
			|| ($decision->operation === operator_operation::string_less)
			|| ($decision->operation === operator_operation::string_less_equal)
			|| ($decision->operation === operator_operation::string_greater)
			|| ($decision->operation === operator_operation::string_greater_equal)
			|| ($decision->operation === operator_operation::string_three_way))
		{
			$left .= '.native_value()';
			$right .= '.native_value()';
			if ($decision->operation !== operator_operation::string_three_way) {
				return CPP_Declarations::type($decision->result_type, $context) . '(' . $left . ' ' . $spelling . ' ' . $right . ')';
			}
		}
		if ($decision->operation === operator_operation::floating_three_way) {
			$context->headers['operators/ordering/numeric_compare.hpp'] = true;
			return $spelling . '(' . $left . ', ' . $right . ')';
		}
		if ($decision->operation === operator_operation::integer_power) {
			$context->headers['operators/arithmetic/power.hpp'] = true;
			return $spelling . '(' . $left . ', ' . $right . ')';
		}
		if (($decision->operation === operator_operation::boolean_xor)
			|| ($decision->operation === operator_operation::condition_xor)) {
			return CPP_Declarations::type($decision->result_type, $context) . '(static_cast<bool>(' . $left . ') != static_cast<bool>(' . $right . '))';
		}
		if (($decision->operation === operator_operation::integer_three_way)
			|| ($decision->operation === operator_operation::string_three_way)) {
			$context->headers['scpp/generated/operators.hpp'] = true;
			$result_type = CPP_Declarations::type($decision->result_type, $context);
			return $result_type . '(static_cast<bool>(' . $left . ' < ' . $right . ') ? -1 : '
				. '(static_cast<bool>(' . $left . ' > ' . $right . ') ? 1 : 0))';
		}
		if (($decision->operation === operator_operation::integer_identical)
			|| ($decision->operation === operator_operation::wrapper_identical)
			|| ($decision->operation === operator_operation::scalar_identical)
			|| ($decision->operation === operator_operation::shared_identical)
			|| ($decision->operation === operator_operation::wrapper_not_identical)
			|| ($decision->operation === operator_operation::scalar_not_identical)
			|| ($decision->operation === operator_operation::shared_not_identical)
			|| ($decision->operation === operator_operation::integer_not_identical)
			|| ($decision->operation === operator_operation::boxed_identical)
			|| ($decision->operation === operator_operation::boxed_not_identical)) {
			$context->headers['lang/php/operators/identity/strict_identity.hpp'] = true;
			return $spelling . '(' . $left . ', ' . $right . ')';
		}
		if (($decision->operation === operator_operation::enum_equal)
			|| ($decision->operation === operator_operation::enum_not_equal)) {
			return CPP_Declarations::type($decision->result_type, $context) . '(' . $left . ' ' . $spelling . ' ' . $right . ')';
		}
		$context->headers['scpp/generated/operators.hpp'] = true;
		return '(' . $left . ' ' . $spelling . ' ' . $right . ')';
	}

	/** Runtime includes follow the prepared operation, not source spelling or boxed family. */
	public static function record_operator_headers(operator_decision $decision, cpp_generation_context $context): void
	{
		$operation /** operator_operation */ = $decision->operation;
		switch ($operation) {
			case operator_operation::boxed_pre_increment:
			case operator_operation::boxed_post_increment:
			case operator_operation::boxed_pre_decrement:
			case operator_operation::boxed_post_decrement:
			case operator_operation::boxed_bitwise_and:
			case operator_operation::boxed_bitwise_or:
			case operator_operation::boxed_bitwise_xor:
			case operator_operation::boxed_shift_left:
			case operator_operation::boxed_shift_right:
			case operator_operation::boxed_complement:
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
		$mapping = CPP_Types::representation($type, $context);
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
		$mapping = CPP_Types::representation($type, $context);
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
		$text = CPP_Sequence::open($type, $context) . $type . ' ' . $name . '; ';
		$worker = new CPP_Syntax($context);
		$parts /** Storage<interpolation_part_node> */ = $syntax->parts;
		foreach ($parts as $part) {
			$text .= $name . '.append(' . $worker->interpolation_part($part) . '); ';
		}
		return $text . CPP_Sequence::result($name, $context) . CPP_Sequence::close($context);
	}

	/** Render decoded bytes as a C++ literal, retaining length when a C string would truncate. */
	public static function generate_string(prepared_string_literal $literal, cpp_generation_context $context): string
	{
		return self::generate_string_value($literal->type, $literal->value, $context);
	}

	public static function generate_string_value(canonical_type_use $type, string $value, cpp_generation_context $context): string
	{
		$mapping = CPP_Types::representation($type, $context);
		CPP_Types::record_dependencies($mapping, $context);
		if ($mapping->literal !== cpp_literal_kind::string_value) {
			throw new \RuntimeException('C++ string literal emission requires the canonical string type');
		}

		$spelling = self::cpp_string_literal($value);
		if ($mapping->opaque) { return $mapping->spelling . '(' . $spelling . ', ' . string_byte_len($value) . ')'; }
		if (!self::contains_zero_byte($value)) {
			return $mapping->spelling . '(' . $spelling . ')';
		}
		return $mapping->spelling . '(std::string(' . $spelling . ', ' . string_byte_len($value) . '))';
	}

	/** Escape every byte without allowing a hexadecimal escape to consume its neighbor. */
	public static function cpp_string_literal(string $value): string
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
		if ($reference->operation === variable_reference_operation::receiver) { return 'this'; }
		if ($reference->operation === variable_reference_operation::receiver_value) { return '(*this)'; }
		if ($reference->operation === variable_reference_operation::shared_self) { return CPP_Self::acquire($reference, $context); }
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
