<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Chooses the entry return conversion from known value facts and the native target range. */
final class Exit_Preparation
{
	/** Decide the native exit boundary once; unknown values retain a required runtime check. */
	public static function decide(?int $constant /** int64 */, entry_target $target, ast_node $span): exit_conversion
	{
		if ($constant === null) {
			return exit_conversion::checked;
		}
		if (!$target->contains($constant)) {
			throw new diagnostic($span->source(), $span->byte_start(),
				'Entry return is outside native int range');
		}
		return exit_conversion::proven;
	}
}

/**
 * Prepares a body in source order, resolving locals and calls and retaining only the additional facts lowering needs.
 */
final class Preparation
{
	/**
	 * Source-order visibility index, not a declaration collector; native non-owning declaration references.
	 *
	 * @var array<string,ast_local_binding>[]
	 */
	private array $local_frames /** list<array<string,ast_local_binding>> */ = [];
	/** Initializer facts are unavailable after a branch barrier unless re-established by a local write. */
	private bool $unknown_initializers = false;
	/**
	 * Latest completed writes; null means unknown after a mutable borrow or update, absent means original initializer.
	 * Transient non-owning body-local index; no alias graph or callee effects are retained.
	 * @var (ast_assignment_statement|ast_local_declaration|null)[]
	 */
	private array $latest_writes /** array<uint32,ast_assignment_statement|ast_local_declaration|null> */ = [];
	private int $statement_visits /** uint32 */ = 0;
	private int $expression_visits /** uint32 */ = 0;
	private int $name_lookups /** uint32 */ = 0;
	/** Active loop bodies within this function; reset before each preparation. */
	private int $loop_depth /** uint32 */ = 0;
	/** Native non-owning context for this body's variable lookup and dependency registration. */
	private scope $symbols;
	/** Native non-owning owner of the facts being prepared. */
	private ast_function_body $owner;
	/** Native non-owning; null when the caller does not collect usage relations. */
	private ?Dependency_Watches $dependencies = null;

	/** Standalone clients use the same declaration-before-body orchestration as multifile programs. */
	public function prepare(ast_file_node $file, entry_target $target): prepared_entry
	{
		return Program_Preparation::prepare('entry', ['entry' => $file], $target)->entry;
	}

	/** Prepare only the selected executable body, consuming already settled callable contracts. */
	public function entry(ast_file_node $file, entry_target $target, scope $symbols, ?Dependency_Watches $dependencies = null): void
	{
		$source = $file->tokens->source;
		$main = $file->main();
		if ($main === null) {
			throw new diagnostic($source, strlen($source->content),
				'Entry slice requires an explicit value return');
		}
		$this->function_body($main->body(), $symbols, $target, $dependencies);
	}

	/** Reset one body, reject duplicate locals, and settle its facts and traversal statistics. */
	public function function_body(ast_function_body $syntax, scope $symbols, ?entry_target $target = null, ?Dependency_Watches $dependencies = null): void
	{
		$this->symbols = $symbols;
		$this->owner = $syntax;
		$this->dependencies = $dependencies;
		$dependencies?->forget($syntax);
		Function_Preparation::reset_body($syntax);
		$errors = Declaration_Diagnostics::locals($syntax);
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		$this->body($syntax->statements, $syntax->source(),
			($target === null ? $syntax->byte_end() : strlen($syntax->source()->content)), $target,
			$syntax);
		$syntax->settle_preparation($this->statement_visits, $this->expression_visits, $this->name_lookups,
			$target);
	}

	/**
	 * Initialize one function traversal; a null exit target denotes an ordinary function with an explicit return type.
	 *
	 * @param ast_statement[] $syntax
	 */
	private function body(array $syntax /** list<ast_statement> */,
		source_file $source, int $end /** uint32 */, ?entry_target $target, ast_function_body $owner): void
	{
		$this->local_frames = [[]];
		$this->unknown_initializers = false;
		$this->latest_writes = [];
		/** @var ast_function_declaration $function */
		$function = $owner->parent;
		foreach ($function->parameters as $parameter) {
			$this->local_frames[0][$parameter->declaration_key()] = $parameter;
		}
		$this->statement_visits = 0;
		$this->expression_visits = 0;
		$this->name_lookups = 0;
		$this->loop_depth = 0;
		$completion = $this->sequence($syntax, $source, $target, $function);
		if (($target === null) && ($completion !== statement_completion::returns) && ($function->return_type_def() !== type_void::instance())) {
			throw new diagnostic($source, $end, 'Non-void function requires a value return');
		}
		if (($target !== null) && ($completion !== statement_completion::returns)) {
			throw new diagnostic($source, $end, 'Entry slice requires an explicit value return');
		}
	}

	/** One traversal per block; completion is transient and never a copied prepared statement tree.
	 * @param ast_statement[] $syntax
	 */
	private function sequence(array $syntax /** list<ast_statement> */, source_file $source,
		?entry_target $target, ast_function_declaration $function): int /** uint8 */
	{
		$completion = statement_completion::fallthrough;
		foreach ($syntax as $statement)
		{
			$this->statement_visits++;
			if (($completion & statement_completion::fallthrough) === 0) {
				throw new diagnostic($statement->source(), $statement->byte_start(),
					$completion === statement_completion::returns ? 'Statements after return are outside the entry slice'
					: 'Statements after unconditional control transfer are not supported');
			}
			$next = statement_completion::fallthrough;
			if (($statement instanceof ast_break_statement) || ($statement instanceof ast_continue_statement)) {
				if ($this->loop_depth === 0) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Break/continue requires an enclosing loop');
				}
				$next = $statement instanceof ast_break_statement ? statement_completion::breaks : statement_completion::continues;
			}
			elseif ($statement instanceof ast_for_statement) {
				$next = $this->for_loop($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_loop_statement) {
				$next = $this->loop($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_if_statement) {
				$next = $this->conditional($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_local_declaration) {
				$this->declaration($statement);
			}
			elseif ($statement instanceof ast_index_assignment) {
				$this->value($statement->receiver);
				if (!($statement->receiver->declaration() instanceof ast_local_binding)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Keyed write requires writable local storage');
				}
				$type = $statement->receiver->type_def();
				if (!($type instanceof index_write_type)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Keyed write requires a string, vector, hash or boxed container');
				}
				$this->value($statement->index);
				$type->prepare_key($statement->index);
				$this->value($statement->value);
				$statement->settle_write($type->write_type()->prepare_conversion($statement->value, conversion_context::typed_destination));
				$this->latest_writes[$statement->receiver->declaration()->storage_id()] = null;
			}
			elseif ($statement instanceof ast_append_statement) {
				$this->value($statement->receiver);
				if (!($statement->receiver->declaration() instanceof ast_local_binding)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Append requires writable local storage');
				}
				$type = $statement->receiver->type_def();
				if (!($type instanceof vector_type_definition)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Append requires a vector');
				}
				$this->value($statement->value);
				$statement->settle_append($type->prepare_append($statement->value));
			}
			elseif ($statement instanceof ast_storage_write) {
				$this->write($statement);
			}
			elseif ($statement instanceof ast_echo_statement)
			{
				foreach ($statement->values as $value) {
					$this->value($value);
					if (!$value->type_def()->supports_output()) {
						throw new diagnostic($value->source(), $value->byte_start(), 'Echo currently supports only int64 and string values');
					}
				}
			}
			elseif ($statement instanceof ast_call_statement) {
				$this->expression_visits++;
				$this->call($statement->value);
			}
			elseif ($statement instanceof ast_return_statement)
			{
				if ($target === null) {
					$return_type = $function->return_type_def();
					if ($return_type === type_void::instance()) {
						if ($statement->value !== null) {
							throw new diagnostic($source, $statement->byte_start(), 'Void function cannot return a value');
						}
					}
					else {
						if ($statement->value === null) {
							throw new diagnostic($source, $statement->byte_start(), 'Non-void function requires a value return');
						}
						$this->value($statement->value);
						$statement->settle_conversion($return_type->prepare_conversion($statement->value, conversion_context::typed_destination));
					}
				}
				else
				{
					if ($statement->value === null) {
						throw new diagnostic($source, $statement->byte_start(),
							'Entry requires a value return');
					}
					$this->value($statement->value);
					$this->require_integer($statement->value, 'Entry return requires int64');
					$statement->settle_conversion(Exit_Preparation::decide($statement->value->constant(),
							$target, $statement->value));
				}
				$next = statement_completion::returns;
			}
			else {
				throw new \LogicException('Unhandled statement preparation');
			}
			$completion = ($completion & ~statement_completion::fallthrough) | $next;
		}
		return $completion;
	}

	/** Resolve visible locals without copying outer indexes into child blocks. */
	private function find_local(string $name): ?ast_local_binding
	{
		for ($index = count($this->local_frames) - 1; $index >= 0; --$index) {
			if (isset($this->local_frames[$index][$name])) {
				return $this->local_frames[$index][$name];
			}
		}
		return null;
	}

	/** Discard only value knowledge; types, bindings and previous occurrence snapshots remain intact. */
	private function forget_values(): void
	{
		$this->latest_writes = [];
		$this->unknown_initializers = true;
	}

	/** Prepare all alternatives once, with lexical frames and conservative value barriers at alternatives and join. */
	private function conditional(ast_if_statement $statement, source_file $source, ?entry_target $target,
		ast_function_declaration $function): int /** uint8 */
	{
		$completion = 0;
		$has_else = false;
		foreach ($statement->arms as $arm) {
			$this->forget_values();
			if ($arm->condition !== null) {
				$this->condition($arm->condition);
			}
			else {
				$has_else = true;
			}
			$this->local_frames[] = [];
			$arm_completion = $this->sequence($arm->body->statements, $source, $target, $function);
			array_pop($this->local_frames);
			$completion |= $arm_completion;
		}
		$this->forget_values();
		return $completion | ($has_else ? 0 : statement_completion::fallthrough);
	}

	/** Shared generated condition admission; preparation evaluates no runtime condition. */
	private function condition(ast_expression $condition): void
	{
		$this->value($condition);
		$condition->type_def()->prepare_condition($condition);
	}

	/** Prepare initialization once in its own lexical frame, then reuse the repeating-region worker. */
	private function for_loop(ast_for_statement $statement, source_file $source, ?entry_target $target,
		ast_function_declaration $function): int /** uint8 */
	{
		$this->local_frames[] = [];
		try {
			$this->statement_visits++;
			if ($statement->initializer instanceof ast_local_declaration) {
				$this->declaration($statement->initializer);
			}
			else {
				$this->write($statement->initializer);
			}
			return $this->loop($statement, $source, $target, $function);
		}
		finally {
			array_pop($this->local_frames);
		}
	}

	/** Prepare each repeating region once with unknown incoming values; no iteration or fixed-point analysis. */
	private function loop(ast_loop_statement $statement, source_file $source, ?entry_target $target,
		ast_function_declaration $function): int /** uint8 */
	{
		$post_test = $statement instanceof ast_do_while_statement;
		$this->forget_values();
		if (!$post_test) {
			$this->condition($statement->condition);
			$this->forget_values();
		}
		$this->local_frames[] = [];
		$this->loop_depth++;
		try {
			$completion = $this->sequence($statement->body->statements, $source, $target, $function);
		}
		finally {
			$this->loop_depth--;
			array_pop($this->local_frames);
		}
		$this->forget_values();
		if ($post_test) {
			$this->condition($statement->condition);
			$this->forget_values();
		}
		if ($statement instanceof ast_for_statement) {
			$this->statement_visits++;
			$this->write($statement->update);
			$this->forget_values();
		}
		// Only a post-test body with exclusively return exits guarantees return; transfers end at this loop.
		return ($post_test && ($completion === statement_completion::returns)) ? statement_completion::returns
			: (($completion & statement_completion::returns) | statement_completion::fallthrough);
	}

	/** Establish a binding only after its initializer succeeds; collection grants no forward visibility. */
	private function declaration(ast_local_declaration $declaration): void
	{
		$name = $declaration->declaration_key();
		$source = $declaration->source();
		$root = $declaration->root();
		if (isset($this->local_frames[count($this->local_frames) - 1][$name])) {
			throw new diagnostic($source, $root->byte_at($declaration->name_position()),
				'Duplicate local: ' . $name);
		}
		$type = null;
		if ($declaration->type_reference !== null) {
			$type = Type_Resolution::resolve($declaration->type_reference);
			if ($type === null) {
				throw new diagnostic($source, $root->byte_at($declaration->type_position()),
					'Unknown or unsupported type: ' . $declaration->type_reference?->spelling());
			}
		}
		if (($declaration->initializer instanceof ast_empty_collection_expression) && ($type instanceof indexed_collection_type)) {
			$this->expression_visits++;
			$declaration->initializer->settle_collection($type);
		}
		else {
			$this->value($declaration->initializer, $name);
		}
		if ($type === null) {
			$type = $declaration->initializer->type_def();
			if (!$type->supports_inferred_local()) {
				throw new diagnostic($source, $declaration->initializer->byte_start(),
					'Cannot infer a local type from null; write an explicit type');
			}
		}
		$conversion = $type->prepare_conversion($declaration->initializer, $declaration->type_reference !== null ? conversion_context::typed_destination : conversion_context::inference);
		$declaration->settle_type($type, $type->convert_constant($conversion, $declaration->initializer->constant()),
			$declaration->initializer->constant_known() && !($type instanceof carrier_type_definition) && !($type instanceof boxed_type_definition), $conversion);
		$this->local_frames[count($this->local_frames) - 1][$name] = $declaration;
		if ($this->unknown_initializers) {
			$this->latest_writes[$declaration->storage_id()] = $declaration;
		}
	}

	/** Resolve established storage once; generated admission precedes publication and latest-value invalidation. */
	private function write(ast_storage_write $statement): void
	{
		$this->name_lookups++;
		$local = $this->find_local($statement->name);
		if ($local === null) {
			if (($statement->source()->flavor !== flavor::php)
				&& ($this->symbols->resolve($statement->root()->constant_key($statement->name)) instanceof ast_constant_declaration)) {
				throw new diagnostic($statement->source(), $statement->byte_start(), 'Cannot assign to a constant');
			}
			throw new diagnostic($statement->source(), $statement->byte_start(),
				'Assignment requires an existing local; global writes are deferred: ' . $statement->name);
		}
		$type = $local->type_def();
		if ($statement instanceof ast_update_statement) {
			if (!$type->supports_update($statement->operator)) {
				throw new diagnostic($statement->source(), $statement->byte_start(), 'Unsupported update type: ' . $type->name);
			}
			$statement->settle_update($local);
			$this->latest_writes[$local->storage_id()] = null;
			return;
		}
		$this->value($statement->value);
		if ($statement->operator === null) {
			$conversion = $type->prepare_conversion($statement->value, conversion_context::assignment);
			$statement->settle_assignment($local, $conversion);
		}
		else {
			$type->prepare_compound($statement, $local);
		}
		$this->latest_writes[$local->storage_id()] = $statement;
	}

	/** Resolve each expression once; a reference retains its exact declaration binding. */
	private function value(ast_expression $syntax, ?string $initializing = null): void
	{
		$this->expression_visits++;
		if ($syntax instanceof ast_empty_collection_expression) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Empty collection requires an explicitly typed local initializer (vector, hash or boxed container)');
		}
		if ($syntax instanceof ast_index_expression) {
			$this->value($syntax->receiver, $initializing);
			$type = $syntax->receiver->type_def();
			if (!($type instanceof index_read_type)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Indexed read requires a vector, hash, boxed container or readable string');
			}
			$this->value($syntax->index, $initializing);
			$type->prepare_index($syntax);
			return;
		}
		if ($syntax instanceof ast_interpolated_string) {
			$string = type_registry::string();
			foreach ($syntax->parts as $part) {
				$this->value($part, $initializing);
				if ($part instanceof ast_interpolation_name) {
					$part->prepare_text($string);
				}
			}
			$syntax->settle_string($string);
			return;
		}
		if ($syntax instanceof ast_cast_expression) {
			$this->value($syntax->operand, $initializing);
			$type = Type_Resolution::resolve($syntax->type_reference);
			if ($type === null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unsupported cast target: ' . $syntax->type_reference->spelling());
			}
			$syntax->settle_cast($type, $type->prepare_conversion($syntax->operand, conversion_context::explicit_cast));
			return;
		}
		if ($syntax instanceof ast_unary_expression) {
			$this->value($syntax->operand, $initializing);
			if ($syntax->operand->type_def() instanceof carrier_type_definition) {
				$syntax->operand->type_def()->require_extraction($syntax->operand, conversion_context::assignment);
			}
			$syntax->operand->value_type()->prepare_unary($syntax);
			return;
		}
		if ($syntax instanceof ast_isset_expression) {
			$this->value($syntax->operand, $initializing);
			if (!($syntax->operand->type_def() instanceof carrier_type_definition)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'This isset slice requires a carrier');
			}
			$syntax->settle_presence();
			return;
		}
		if ($syntax instanceof ast_not_expression) {
			$this->value($syntax->operand, $initializing);
			$syntax->operand->type_def()->prepare_condition($syntax->operand);
			$syntax->settle_not();
			return;
		}
		if ($syntax instanceof ast_binary_expression) {
			$this->value($syntax->left, $initializing);
			// A lazy RHS is still checked; any reference-call invalidation conservatively survives the join.
			$this->value($syntax->right, $initializing);
			if ($syntax->operator->logical()) {
				$syntax->left->type_def()->prepare_condition($syntax->left);
				$syntax->right->type_def()->prepare_condition($syntax->right);
				$syntax->settle_binary(type_bool::instance());
				return;
			}
			$left = $syntax->left->type_def();
			$right = $syntax->right->type_def();
			$box = $left instanceof boxed_type_definition ? $left : $right;
			if ($box instanceof boxed_type_definition) {
				$box->prepare_binary($syntax);
				return;
			}
			if ($syntax->left->type_def() instanceof carrier_type_definition) {
				$syntax->left->type_def()->require_extraction($syntax->left, conversion_context::assignment);
			}
			if ($syntax->right->type_def() instanceof carrier_type_definition) {
				$syntax->right->type_def()->require_extraction($syntax->right, conversion_context::assignment);
			}
			$syntax->left->value_type()->prepare_binary($syntax);
			return;
		}
		if ($syntax instanceof ast_call_expression) {
			$this->call($syntax, $initializing);
			if ($syntax->type_def() === type_void::instance()) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Void call cannot be used as a value');
			}
			return;
		}
		if (($syntax instanceof ast_integer_expression) || ($syntax instanceof ast_float_expression)
			|| ($syntax instanceof ast_keyword_expression) || ($syntax instanceof ast_string_expression)) {
			type_registry::prepare_literal($syntax);
			return;
		}
		if ($syntax instanceof ast_name_expression)
		{
			$this->name_lookups++;
			$name = $syntax->name;
			if (!$syntax->constant_only && ($name === $initializing)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(),
					'Cannot read local during its initialization: ' . $name);
			}
			$binding = $syntax->constant_only ? null : $this->find_local($name);
			if ($binding !== null) {
				$id = $binding->storage_id();
				// Reference parameters can alias each other or globals; never propagate their write constants.
				$unknown = (($binding instanceof ast_parameter_declaration) && ($binding->mode === passing_mode::reference))
					|| (array_key_exists($id, $this->latest_writes) ? ($this->latest_writes[$id] === null) : $this->unknown_initializers);
				$write = $this->latest_writes[$id] ?? null;
				$syntax->settle_local($binding, $write instanceof ast_assignment_statement ? $write : null, $unknown);
				return;
			}
			if ($syntax instanceof ast_interpolation_name) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown interpolation local or parameter: ' . $name);
			}
			$separator = strpos($name, '\\');
			if ($syntax->member_syntax && ($separator !== false)
				&& ($this->find_local(substr($name, 0, $separator)) !== null)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Member reads are not supported in this slice');
			}
			if (!$syntax->constant_only) {
				$key = 'var:' . Source_Names::resolve($name, $this->owner->scope->namespace);
				$symbol = $this->symbols->entry($key);
				$this->dependencies?->watch($symbol, $this->owner);
				if ($symbol->declaration() !== null) {
					$syntax->settle_global($symbol);
					return;
				}
			}
			if ($syntax->constant_only || ($syntax->source()->flavor !== flavor::php)) {
				$symbol = $this->symbols->entry($syntax->root()->constant_key($name));
				$this->dependencies?->watch($symbol, $this->owner);
				if ($symbol->declaration() !== null) {
					$syntax->settle_constant($symbol);
					return;
				}
			}
			throw new diagnostic($syntax->source(), $syntax->byte_start(),
				$syntax->constant_only ? 'Unknown constant: ' . $name : 'Unknown or not-yet-initialized local/global: ' . $name);
		}
		throw new \LogicException('Unhandled expression preparation');
	}

	/** Validate fixed-arity runtime arguments against their generated signature. */
	private function runtime_call(ast_call_expression $call, runtime_function $runtime): void
	{
		$operands = $call->runtime_operands();
		if (count($operands) !== count($runtime->parameters)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
		}
		foreach ($operands as $index => $argument) {
			if ($argument !== $call->receiver) {
				$this->value($argument);
			}
			$type = $runtime->parameters[$index];
			if ($argument->type_def() !== $type) {
				throw new diagnostic($argument->source(), $argument->byte_start(), $call->name . ' requires ' . $type->name);
			}
		}
		$call->settle_runtime($runtime);
	}

	/** Check each JS namespace prefix until a visible value owns the rest of the dotted path. */
	private function has_receiver(ast_name_expression $receiver): bool
	{
		$file = $receiver->root();
		$prefix = '';
		foreach (explode('\\', $receiver->name) as $part) {
			$local = $prefix === '' && $this->find_local($part) !== null;
			$prefix = $prefix === '' ? $part : $prefix . '\\' . $part;
			if (!$local) {
				$found = false;
				foreach (['var:' . Source_Names::resolve($prefix, $this->owner->scope->namespace),
					$file->constant_key($prefix)] as $key) {
					$entry = $this->symbols->entry($key);
					$this->dependencies?->watch($entry, $this->owner);
					if ($entry->declaration() !== null) {
						$found = true;
						break;
					}
				}
				if (!$found) {
					continue;
				}
			}
			if ($prefix !== $receiver->name) {
				throw new diagnostic($receiver->source(), $receiver->byte_start(), 'Member reads are not supported in this slice');
			}
			return true;
		}
		return false;
	}

	/** Resolve only the published signature, then validate and convert arguments in source order. */
	private function call(ast_call_expression $call, ?string $initializing = null): void
	{
		$receiver = $call->receiver;
		if ($receiver !== null) {
			$separator = strrpos($call->name, '\\');
			$member = $separator === false;
			if (!$member && $receiver instanceof ast_name_expression) {
				$member = $this->has_receiver($receiver);
			}
			if ($member) {
				$this->value($receiver);
				$name = $separator === false ? $call->name : substr($call->name, $separator + 1);
				$key = $call->source()->flavor->name . ':' . $receiver->type_def()->name . ':' . $name;
				$runtime = runtime_functions::resolve($key);
				if ($runtime === null) {
					throw new diagnostic($call->source(), $call->byte_start(), 'Member calls: unknown method ' . $name . ' on ' . $receiver->type_def()->name);
				}
				$this->runtime_call($call, $runtime);
				return;
			}
		}
		$this->name_lookups++;
		$file = $call->root();
		$key = $file->function_key($call->name);
		$symbol = $this->symbols->entry($key);
		$this->dependencies?->watch($symbol, $this->owner);
		$function = $symbol->declaration();
		if (!($function instanceof ast_function_declaration)) {
			$runtime = runtime_functions::free($file->runtime_function_key($call->name, $key), $file->source()->flavor);
			if ($runtime !== null) {
				$this->runtime_call($call, $runtime);
				return;
			}
			$builtin = $file->imported_function_target($call->name) === null ? ltrim($call->name, '\\') : null;
			$operation = match ($builtin) {
				'count' => collection_cardinality::count,
				'empty' => collection_cardinality::empty,
				default => null,
			};
			if ($operation === null) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Unknown function: ' . $call->name);
			}
			if (count($call->arguments) !== 1) {
				throw new diagnostic($call->source(), $call->byte_start(), $operation->name . ' requires one collection argument');
			}
			$receiver = $call->arguments[0];
			$this->value($receiver, $initializing);
			$type = $receiver->type_def();
			if (!($type instanceof counted_collection_type)) {
				throw new diagnostic($receiver->source(), $receiver->byte_start(), $operation->name . ' requires a published vector or hash');
			}
			$call->settle_cardinality($operation, $type);
			return;
		}
		$supplied = count($call->arguments);
		// Settled signatures have a trailing optional suffix: checking its first omitted slot is sufficient.
		if (($supplied > count($function->parameters))
			|| (isset($function->parameters[$supplied]) && ($function->parameters[$supplied]->default_value === null))) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
		}
		$conversions = [];
		$references = false;
		foreach ($call->arguments as $index => $argument) {
			$this->value($argument);
			$parameter = $function->parameters[$index];
			if ($parameter->type_def() instanceof hash_type_definition) {
				throw new diagnostic($argument->source(), $argument->byte_start(), 'Hash argument passing is deferred');
			}
			if ($parameter->type_def() instanceof vector_type_definition) {
				throw new diagnostic($argument->source(), $argument->byte_start(), 'Vector argument passing is deferred');
			}
			if ($parameter->mode === passing_mode::reference) {
				if (!($argument instanceof ast_name_expression)) {
					throw new diagnostic($argument->source(), $argument->byte_start(), 'Reference argument requires stable variable storage');
				}
				if ($argument->declaration() instanceof ast_constant_declaration) {
					throw new diagnostic($argument->source(), $argument->byte_start(), 'Constant cannot bind a mutable reference');
				}
				if ($argument->type_def() !== $parameter->type_def()) {
					throw new diagnostic($argument->source(), $argument->byte_start(), 'Reference argument requires exact storage type');
				}
				$conversions[] = value_conversion::identity;
				$references = true;
			}
			else {
				$conversions[] = $parameter->type_def()->prepare_conversion($argument, conversion_context::typed_destination);
			}
		}
		// Invocation follows every argument evaluation. Preserve earlier snapshots, invalidate future reads.
		if ($references) {
			foreach ($call->arguments as $index => $argument) {
				if ($function->parameters[$index]->mode === passing_mode::reference) {
					/** @var ast_name_expression $argument */
					$binding = $argument->declaration();
					if ($binding instanceof ast_local_binding) {
						$this->latest_writes[$binding->storage_id()] = null;
					}
				}
			}
		}
		$call->settle_function($symbol, $conversions);
	}

	/** Prevent C++ implicit scalar conversions from widening the supported echo/entry contracts. */
	private function require_integer(ast_expression $value, string $message): void
	{
		if ($value->type_def() !== type_registry::integer()) {
			throw new diagnostic($value->source(), $value->byte_start(), $message);
		}
	}

}
