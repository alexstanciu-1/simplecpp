<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Settles function return types before body analysis and resets source-owned facts for sequential reanalysis. */
final class Function_Preparation
{
	/** Reset source-owned facts before a new sequential run; no foreign bodies are traversed. */
	public static function reset(ast_function_declaration $function): void
	{
		$function->reset_preparation();
		if ($function->optional_body() !== null) self::reset_body($function->body());
	}

	/** Reset every supported body fact before a new traversal, including expression occurrences. */
	public static function reset_body(ast_function_body $body): void
	{
		$body->root()->begin_preparation();
		$body->reset_preparation();
		self::reset_block($body);
	}

	/** Reset nested source syntax once; the function remains the only dependency/completion owner. */
	private static function reset_block(ast_statement_block $body): void
	{
		self::reset_statements($body->statements);
	}

	/** Reset one ordered list of owned body or header statements. @param ast_statement[] $statements */
	private static function reset_statements(array $statements /** list<ast_statement> */): void
	{
		foreach ($statements as $statement)
		{
			if ($statement instanceof ast_loop_transfer) {
				$statement->target = null;
				continue;
			}
			if ($statement instanceof ast_yield_statement) {
				$statement->conversion = $statement->key_conversion = null;
				self::expression($statement->value);
				if ($statement->key !== null) self::expression($statement->key);
				continue;
			}
			if ($statement instanceof ast_foreach_statement) {
				$statement->destination = null;
				$statement->conversion = null;
				$statement->keys_only = false;
				$statement->key?->reset_preparation();
				$statement->value->reset_preparation();
				self::expression($statement->source_value);
				self::reset_block($statement->body);
				continue;
			}
			if ($statement instanceof ast_loop_statement) {
				if ($statement instanceof ast_for_statement) {
					self::reset_statements($statement->initializers);
					foreach ($statement->conditions as $test) self::expression($test);
					self::reset_statements($statement->updates);
				} else self::expression($statement->condition);
				if ($statement instanceof ast_while_else_statement) self::reset_block($statement->otherwise);
				self::reset_block($statement->body);
				continue;
			}
			if ($statement instanceof ast_try_statement) {
				$statement->protected_completion = $statement->completion = null;
				$statement->suspending_handlers = false;
				$statement->break_target = $statement->continue_target = null;
				self::reset_block($statement->body);
				foreach ($statement->handlers as $handler) {
					$handler->binding->reset_preparation();
					self::reset_block($handler->body);
				}
				if ($statement->finalizer !== null) self::reset_block($statement->finalizer);
				continue;
			}
			if ($statement instanceof ast_throw_statement) {
				self::expression($statement->operation);
				continue;
			}
			if ($statement instanceof ast_block_statement) {
				self::reset_block($statement->body);
				continue;
			}
			if ($statement instanceof ast_branch_statement) {
				if ($statement instanceof ast_switch_statement) self::expression($statement->subject);
				foreach ($statement->arms as $arm) {
					if ($arm instanceof ast_switch_arm) $arm->falls_through = false;
					if ($arm->condition !== null) {
						self::expression($arm->condition);
					}
					self::reset_block($arm->body);
				}
				continue;
			}
			if ($statement instanceof ast_field_assignment) {
				$statement->transfer = false;
				$statement->conversion = null;
				self::expression($statement->target);
				self::expression($statement->value);
				continue;
			}
			if (($statement instanceof ast_append_statement) || ($statement instanceof ast_index_assignment)) {
				$statement->reset_preparation();
				self::expression($statement->receiver);
				if ($statement instanceof ast_index_assignment) {
					self::expression($statement->index);
				}
				self::expression($statement->value);
				continue;
			}
			if (($statement instanceof ast_local_declaration)
				|| ($statement instanceof ast_return_statement) || ($statement instanceof ast_storage_write)) {
				$statement->reset_preparation();
			}
			if ($statement instanceof ast_property_compound || $statement instanceof ast_index_compound || $statement instanceof ast_property_update || $statement instanceof ast_index_update) self::expression($statement->target);
			if ($statement instanceof ast_local_declaration) {
				self::expression($statement->initializer);
			}
			elseif ($statement instanceof ast_echo_statement) {
				foreach ($statement->values as $value) {
					self::expression($value);
				}
			}
			elseif (($statement instanceof ast_return_statement)
				|| ($statement instanceof ast_assignment_statement) || ($statement instanceof ast_delay_statement) || ($statement instanceof ast_await_statement) || ($statement instanceof ast_call_statement) || ($statement instanceof ast_base_statement)) {
				if ($statement->value !== null) {
					self::expression($statement->value);
				}
			}
		}
	}

	/** Reset expression occurrences recursively; invocation targets remain foreign read-only contracts. */
	private static function expression(ast_expression $value): void
	{
		$value->reset_preparation();
		if ($value instanceof ast_lambda_expression) {
			foreach ($value->parameters as $parameter) $parameter->reset_preparation();
			foreach ($value->captures as $capture) {
				$capture->reset_preparation();
				$capture->initializer->reset_preparation();
			}
			if ($value->body instanceof ast_block) self::reset_block($value->body);
			else self::expression($value->body);
			return;
		}
		if ($value instanceof ast_condition_assignment) {
			self::expression($value->target);
			self::expression($value->value);
			return;
		}
		if ($value instanceof ast_ternary_expression) {
			self::expression($value->condition);
			self::expression($value->when_true);
			self::expression($value->when_false);
			return;
		}
		if ($value instanceof ast_match_expression) {
			self::expression($value->subject);
			foreach ($value->arms as $arm) {
				foreach ($arm->labels as $label) self::expression($label);
				self::expression($arm->result);
			}
			return;
		}
		if ($value instanceof ast_keyed_collection_expression) {
			foreach ($value->entries as $entry) {
				$entry->conversion = null;
				self::expression($entry->key);
				self::expression($entry->value);
			}
			return;
		}
		if ($value instanceof ast_collection_expression) {
			foreach ($value->elements as $element) self::expression($element);
			return;
		}
		if ($value instanceof ast_interpolated_string) {
			foreach ($value->parts as $part) {
				self::expression($part);
			}
			return;
		}
		if (($value instanceof ast_await_expression) || ($value instanceof ast_object_access_expression) || ($value instanceof ast_ownership_expression) || ($value instanceof ast_isset_expression) || ($value instanceof ast_not_expression) || ($value instanceof ast_cast_expression)
			|| ($value instanceof ast_unary_expression) || ($value instanceof ast_throw_expression)) {
			self::expression($value->operand);
		}
		if ($value instanceof ast_field_expression || $value instanceof ast_qualified_data) {
			self::expression($value->receiver);
		}
		if ($value instanceof ast_index_expression) {
			self::expression($value->receiver);
			self::expression($value->index);
		}
		if ($value instanceof ast_comparison_chain) {
			foreach ($value->links as $link) self::expression($link);
			return;
		}
		if ($value instanceof ast_binary_expression) {
			if (!($value instanceof ast_continued_comparison)) self::expression($value->left);
			self::expression($value->right);
		}
		if ($value instanceof ast_inline_job && $value->iteration !== null) self::expression($value->iteration);
		if ($value instanceof ast_call_expression || $value instanceof ast_construction_expression) {
			if ($value instanceof ast_call_expression && $value->receiver !== null) {
				self::expression($value->receiver);
			}
			foreach ($value->arguments as $argument) {
				self::expression($argument);
			}
		}
	}

	/** Settle reset declarations before body work; callable recursion has no signature dependency cycle. */
	public static function declarations(scope $symbols, ?Dependency_Watches $dependencies = null): scope
	{
		$errors = Declaration_Diagnostics::ordered(Declaration_Diagnostics::published($symbols));
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		/** @var record_type_definition[] $iterators */
		$iterators = [];
		foreach ($symbols->declarations as $declaration)
		{
			if ($declaration instanceof ast_record_declaration && $declaration->interface_references !== []) {
				$iterators[] = $declaration->definition;
			}
			if (!($declaration instanceof ast_function_declaration)) {
				continue;
			}
			if ($declaration->return_type_ready()) {
				continue;
			}
			$dependencies?->forget($declaration);
			self::signature($declaration, $symbols, $declaration, $dependencies);
		}
		foreach ($symbols->declarations as $declaration) {
			if ($declaration instanceof ast_function_declaration) Method_Preparation::contract($declaration, $symbols, $dependencies);
		}
		foreach ($symbols->declarations as $declaration) {
			if ($declaration instanceof ast_record_declaration) Method_Preparation::record_contract($declaration->definition, $symbols, $dependencies);
		}
		foreach ($iterators as $iterator) $iterator->prepare_iterator($symbols);
		return $symbols;
	}

	/** Settle ordinary callable syntax. Trait hook type dependencies belong to the consuming record contract. */
	public static function signature(ast_function_declaration $declaration, scope $symbols, dependency_watcher $watcher, ?Dependency_Watches $dependencies): void
	{
		$type = $declaration->return_type_reference === null ? null
			: Type_Resolution::signature($declaration->return_type_reference, $symbols, $declaration, $dependencies, true, $watcher);
		if ($type === null) {
			throw new diagnostic($declaration->source(), $declaration->lexical_root()->byte_at($declaration->return_type_position()),
				'Unknown or unsupported return type: ' . $declaration->return_type_reference?->spelling());
		}
		if ($declaration->is_async && $type->async_task() === null) {
			throw new diagnostic($declaration->source(), $declaration->byte_start(), 'Unpublished async result: ' . $type->name);
		}
		$optional = false;
		foreach ($declaration->parameters as $parameter) {
			if ($type instanceof generator_type_definition && $parameter->mode !== passing_mode::value) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Yield producers require by-value parameters');
			}
			$parameter->reset_preparation();
			if (in_array($parameter->type_reference->name, ['segment', 'read_segment', 'hash_segment', 'read_hash_segment'], true) && $parameter->type_reference->first_child !== null) {
				if (!type_registry::HAS_MT_SEGMENTS) throw new diagnostic($parameter->source(), $parameter->byte_start(), 'MT segments unavailable in this runtime');
				$child = $parameter->type_reference->first_child;
				$hash = in_array($parameter->type_reference->name, ['hash_segment', 'read_hash_segment'], true);
				$key_syntax = $child->next_sibling;
				if (($key_syntax !== null) !== $hash || $key_syntax?->next_sibling !== null || $parameter->mode !== passing_mode::value
					|| $parameter->default_value !== null || $declaration->is_async || $type instanceof generator_type_definition || $declaration->parent instanceof ast_method_owner) {
					throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Segment requires an ordinary free-function parameter');
				}
				$element = Type_Resolution::signature($child, $symbols, $declaration, $dependencies, watcher: $watcher);
				if ($element === null || !Mt_Rules::isolated($element)) throw new diagnostic($child->source(), $child->byte_start(), 'Segment requires isolated inline elements');
				$key = $hash ? Type_Resolution::signature($key_syntax, $symbols, $declaration, $dependencies, watcher: $watcher) : null;
				if ($hash && $key !== type_int64::instance() && $key !== type_string::instance())
					throw new diagnostic($key_syntax->source(), $key_syntax->byte_start(), 'Hash segment keys must be int or string');
				$parameter_type = new segment_type($element, str_starts_with($parameter->type_reference->name, 'read_'), $key);
			} else $parameter_type = $parameter->type_reference->name === 'callback' && $parameter->type_reference->first_child !== null
				? Type_Resolution::callback($parameter, $symbols, $declaration, $watcher, $dependencies)
				: Type_Resolution::signature($parameter->type_reference, $symbols, $declaration, $dependencies, watcher: $watcher);
			if ($parameter_type === null) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Unknown or unsupported parameter type: ' . $parameter->type_reference->spelling());
			}
			if ($declaration->is_async && ($parameter->mode !== passing_mode::value || !$parameter_type->supports_async_parameter())) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Async parameters require a supported owned by-value type: ' . $parameter_type->name);
			}
			if ($type instanceof generator_type_definition && $parameter_type instanceof callback_type_definition) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Callback parameters on yield producers are deferred');
			}
			if (($parameter_type instanceof intersection_type_definition || $parameter_type instanceof class_union_type_definition) && ($parameter->mode !== passing_mode::value || $parameter->default_value !== null || $type instanceof generator_type_definition)) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), ($parameter_type instanceof class_union_type_definition ? 'Union' : 'Intersection') . ' requires an ordinary required by-value parameter');
			}
			if ($parameter_type instanceof record_type_definition && $parameter->mode === passing_mode::reference) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Writable inline struct parameters are deferred');
			}
			if ($parameter->mode === passing_mode::readonly_reference) {
				$collection = Type_Resolution::readonly_collection_input($parameter_type);
				if ($declaration->parent instanceof ast_method_owner) {
					if (!$collection || !($declaration->parent instanceof ast_record_declaration)
						|| $declaration->is_constructor() || $declaration->is_destructor()) {
						throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Const method references require a vector or hash on an ordinary class, struct or interface method');
					}
				} elseif (!$collection && !($parameter_type instanceof text_builder_type)) {
					throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Const references require a vector, hash or text builder parameter');
				}
			}
			if ($parameter->type_reference instanceof ast_variadic_type_reference) {
				if (($declaration->parent instanceof ast_enum_declaration && !$declaration->is_static)
					|| $declaration->is_destructor() || $declaration->is_async || $type instanceof generator_type_definition) {
					throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Variadic parameters require a synchronous function or ordinary method with a supported vector element');
				}
				self::require_pack_type($parameter, $parameter_type);
			}
			$parameter->settle_type($parameter_type);
			if (($optional || $parameter->default_value !== null) && !($parameter->type_reference instanceof ast_variadic_type_reference)) {
				self::parameter_default($parameter, $symbols, $watcher, $dependencies);
				$optional = true;
			}
		}
		$reference_origin = $declaration->returns_reference ? self::reference_return_origin($declaration, $type) : null;
		$declaration->settle_return_type($type, $reference_origin);
	}

	/** Named and contextual variadics share the same owned vector storage contract. */
	public static function require_pack_type(ast_parameter_declaration $parameter, ?type_definition $type): void
	{
		if (!($type instanceof vector_type_definition && $type instanceof sequence_initializer_type)) {
			throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Variadic parameter requires a supported vector element');
		}
	}

	/** Validate the optional suffix and prepare a default once against its declared type. */
	public static function parameter_default(ast_parameter_declaration $parameter, scope $symbols, dependency_watcher $watcher, ?Dependency_Watches $dependencies): void
	{
		$default = $parameter->default_value;
		if ($default === null) throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Required parameter follows optional parameter');
		$parameter_type = $parameter->type_def();
		if ($parameter->mode !== passing_mode::value) {
			throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Reference parameter defaults are deferred');
		}
		if ($parameter_type instanceof enum_type_definition) {
			if (!($default instanceof ast_name_expression)
				|| !Enum_Preparation::reference($default, $symbols, $watcher, $dependencies)) {
				throw new diagnostic($default->source(), $default->byte_start(), 'Enum parameter default requires an enum case');
			}
		} else {
			if (!(($default instanceof ast_integer_expression) || ($default instanceof ast_float_expression)
				|| ($default instanceof ast_keyword_expression) || ($default instanceof ast_string_expression))) {
				throw new diagnostic($default->source(), $default->byte_start(), 'Parameter defaults require literals');
			}
			type_registry::prepare_literal($default);
		}
		$parameter->settle_default($parameter_type->prepare_conversion($default, conversion_context::typed_destination));
	}

	/** A reference result borrows exactly one fixed writable parameter of the same type. */
	public static function reference_return_origin(ast_function_declaration|ast_lambda_expression $declaration, type_definition $type): int /** uint32 */
	{
		if ($declaration instanceof ast_function_declaration && $declaration->parent instanceof ast_method_owner && !$declaration->is_direct_final_method()) {
			throw new diagnostic($declaration->source(), $declaration->byte_start(), 'Method reference return requires a final non-overriding instance method');
		}
		$origin = null;
		foreach ($declaration->parameters as $index => $parameter) {
			if ($parameter->mode !== passing_mode::reference) continue;
			if ($origin !== null || $parameter->type_def() !== $type) {
				throw new diagnostic($declaration->source(), $declaration->byte_start(), 'Reference return requires exactly one writable parameter of the return type');
			}
			$origin = $index;
		}
		return $origin ?? throw new diagnostic($declaration->source(), $declaration->byte_start(),
			'Reference return requires exactly one writable parameter of the return type');
	}

	/** Prepare only this source's bodies, reading foreign signatures but never foreign executable syntax. */
	public static function bodies(ast_file_node $file, scope $symbols, ?Dependency_Watches $dependencies = null): void
	{
		foreach ($file->functions() as $declaration) {
			if ($declaration->is_static) continue;
			$node = $declaration;
			$node->return_type_def();
			if ($node->optional_body() !== null) (new Preparation())->function_body($node->body(), $symbols, null, $dependencies);
		}
	}
}
