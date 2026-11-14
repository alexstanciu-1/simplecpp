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
	/** Traversal state: current-instance access is unavailable while preparing base arguments. */
	private bool $initializing_base = false;
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
			if ($statement instanceof ast_base_statement) {
				$this->base_initialization($statement->value);
			}
			elseif (($statement instanceof ast_break_statement) || ($statement instanceof ast_continue_statement)) {
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
			elseif ($statement instanceof ast_field_assignment || $statement instanceof ast_property_compound) {
				$this->writable_property($statement->target);
				$this->value($statement->value);
				$type = $statement->target->type_def();
				if ($statement instanceof ast_property_compound) $type->prepare_compound($statement);
				else $statement->conversion = $type->prepare_conversion($statement->value, conversion_context::assignment);
			}
			elseif ($statement instanceof ast_index_assignment) {
				$binding = null;
				$receiver = $statement->receiver;
				if ($receiver instanceof ast_field_expression) {
					$this->writable_property($receiver);
				} else {
					$this->value($receiver);
					$binding = $receiver->declaration();
					if (!($binding instanceof ast_local_binding)
						&& !($binding instanceof ast_global_declaration && $binding->parent instanceof ast_struct_declaration)) {
						throw new diagnostic($statement->source(), $statement->byte_start(), 'Keyed write requires writable local storage');
					}
				}
				$type = $statement->receiver->type_def();
				if (!($type instanceof index_write_type)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Keyed write requires a string, vector, hash or boxed container');
				}
				$this->value($statement->index);
				$type->prepare_key($statement->index);
				$this->value($statement->value);
				$statement->settle_write($type->write_type()->prepare_conversion($statement->value, conversion_context::typed_destination));
				if ($binding instanceof ast_local_binding) $this->latest_writes[$binding->storage_id()] = null;
			}
			elseif ($statement instanceof ast_append_statement) {
				$this->value($statement->receiver);
				if (!($statement->receiver->declaration() instanceof ast_local_binding)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Append requires writable local storage');
				}
				$type = $statement->receiver->type_def();
				if (!($type instanceof appendable_collection_type)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Append requires an appendable collection');
				}
				$this->value($statement->value);
				$statement->settle_append($type->write_type()->prepare_conversion($statement->value, conversion_context::typed_destination));
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
			$type = Type_Resolution::local($declaration->type_reference, $this->symbols, $this->owner, $this->dependencies);
			if ($type === null) {
				throw new diagnostic($source, $root->byte_at($declaration->type_position()),
					'Unknown or unsupported type: ' . $declaration->type_reference?->spelling());
			}
		}
		if ($declaration->initializer instanceof ast_struct_default) {
			if (!($type instanceof struct_type_definition) && !($type instanceof struct_handle_definition)) {
				throw new diagnostic($source, $declaration->byte_start(), 'A local without an initializer requires a struct type');
			}
			$this->expression_visits++;
			$this->construction($declaration->initializer, $type, $name);
		}
		elseif (($declaration->initializer instanceof ast_empty_collection_expression) && ($type instanceof indexed_collection_type)) {
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

	/** Resolve both forms of type-qualified writes through the ordinary watched name lookup. */
	private function static_destination(ast_name_expression $target): void
	{
		$this->value($target);
		$property = $target->declaration();
		if (!($property instanceof ast_global_declaration) || !($property->parent instanceof ast_struct_declaration)) {
			throw new diagnostic($target->source(), $target->byte_start(), 'Assignment requires a writable static property');
		}
	}

	/** Watch missing and inaccessible members so visibility edits also recover an unchanged caller. */
	private function bind_literal(ast_name_expression $syntax, symbol_entry $symbol): bool
	{
		$this->dependencies?->watch($symbol, $this->owner);
		$member = $symbol->declaration();
		if ($member === null) return false;
		if ($member->is_private && $this->owner->parent->parent !== $member->parent) {
			$kind = $member instanceof ast_constant_declaration ? 'constant' : 'static property';
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Cannot access private ' . $kind . ' ' . $member->name());
		}
		if ($member instanceof ast_constant_declaration) $syntax->settle_constant($symbol);
		else $syntax->settle_global($symbol);
		return true;
	}

	/** Check writable instance paths and qualified static members for all property writes. */
	private function writable_property(ast_name_expression|ast_field_expression $root): void
	{
		if ($root instanceof ast_name_expression) {
			$this->static_destination($root);
		} else {
			$this->value($root);
			$root = $root->receiver;
			while ($root instanceof ast_field_expression) $root = $root->receiver;
			if (!($root instanceof ast_this_expression)
				&& (!($root instanceof ast_name_expression) || !($root->declaration() instanceof ast_local_binding))) {
				throw new diagnostic($root->source(), $root->byte_start(), 'Field assignment requires writable local storage');
			}
		}
	}

	/** Resolve established storage once; generated admission precedes publication and latest-value invalidation. */
	private function write(ast_storage_write $statement): void
	{
		if ($statement instanceof ast_property_update) {
			$this->writable_property($statement->target);
			$statement->require_update_type($statement->target->type_def());
			return;
		}
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
			$statement->require_update_type($type);
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
			$statement->prepare_local_compound($local);
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
		if ($syntax instanceof ast_ownership_expression) {
			$this->value($syntax->operand, $initializing);
			$syntax->prepare();
			return;
		}
		if ($syntax instanceof ast_this_expression) {
			if ($this->initializing_base) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Current instance is unavailable during base initialization');
			if ($this->owner->parent->is_static) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Static method has no this receiver');
			$record = $this->owner->parent->parent;
			if (!($record instanceof ast_struct_declaration)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'this requires an instance method');
			}
			if ($this->owner->parent->is_constructor()) {
				$parent = $syntax->parent;
				$is_receiver = ($parent instanceof ast_field_expression || $parent instanceof ast_call_expression) && $parent->receiver === $syntax;
				if (!$is_receiver && !($parent instanceof ast_return_statement)) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Copying this during construction is deferred');
				}
			}
			// Receiver facts depend on the current layout, including inherited types and access.
			$this->dependencies?->watch($this->symbols->entry($record->symbol_key()), $this->owner);
			$syntax->settle_receiver($record->definition);
			return;
		}
		if ($syntax instanceof ast_new_expression) {
			$payload = Type_Resolution::local($syntax->target, $this->symbols, $this->owner, $this->dependencies);
			if (!($payload instanceof struct_type_definition)) {
				throw new diagnostic($syntax->source(), $syntax->target->byte_start(), 'Shared construction requires a struct: ' . $syntax->target->name);
			}
			$this->construction($syntax, $payload->shared, $initializing);
			return;
		}
		if ($syntax instanceof ast_field_expression) {
			$this->value($syntax->receiver, $initializing);
			$type = $syntax->receiver->type_def();
			if ($type instanceof shared_struct_type_definition) $type = $type->payload;
			if (!($type instanceof struct_type_definition)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Member reads are not supported in this slice for type ' . $type->name);
			}
			$field = $type->field($syntax->field);
			if ($field === null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown field ' . $type->name . '.' . $syntax->field);
			}
			if ($field->is_private && $this->owner->parent->parent !== $field->parent) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Cannot access private field ' . $type->name . '::' . $syntax->field);
			}
			$context = $this->owner->parent->parent;
			if ($field->is_protected && (!($context instanceof ast_struct_declaration)
				|| !$context->definition->derives_from($field->parent->definition)
				|| !$type->derives_from($context->definition))) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Cannot access protected field ' . $syntax->field);
			}
			$syntax->settle_field($field->type_def());
			return;
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
			if ($left instanceof shared_struct_type_definition || $right instanceof shared_struct_type_definition) {
				$owner = $left instanceof shared_struct_type_definition ? $left : $right;
				$owner->prepare_binary($syntax);
				return;
			}
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
				if ($this->bind_literal($syntax, $symbol)) return;
				if ($syntax->source()->flavor === flavor::php && str_contains($name, '::')) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown static property: ' . $name);
				}
			}
			if ($syntax->constant_only || ($syntax->source()->flavor !== flavor::php)) {
				$symbol = $this->symbols->entry($syntax->root()->constant_key($name));
				if ($this->bind_literal($syntax, $symbol)) return;
				// C++ and JS share the namespace separator with type-qualified members.
				if ($separator !== false && $syntax->source()->flavor !== flavor::php && ($last = strrpos($name, '\\')) > 0) {
					$qualified = substr($name, 0, $last) . '::' . substr($name, $last + 1);
					$property = $this->symbols->entry('var:' . Source_Names::resolve($qualified, $this->owner->scope->namespace));
					if ($this->bind_literal($syntax, $property)) return;
					$symbol = $this->symbols->entry($syntax->root()->constant_key($qualified));
					if ($this->bind_literal($syntax, $symbol)) return;
					if ($this->symbols->resolve('class:' . Source_Names::resolve(substr($name, 0, $last), $this->owner->scope->namespace))
						instanceof ast_struct_declaration) {
						throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown constant: ' . $qualified);
					}
				}
				if ($syntax->constant_only && $syntax->source()->flavor === flavor::php) {
					$constant = runtime_constants::integer($syntax->root()->runtime_constant_key($name));
					if ($constant !== null) {
						$syntax->settle_runtime_constant($constant);
						return;
					}
				}
			}
			throw new diagnostic($syntax->source(), $syntax->byte_start(),
				$syntax->constant_only ? 'Unknown constant: ' . $name : 'Unknown or not-yet-initialized local/global: ' . $name);
		}
		throw new \LogicException('Unhandled expression preparation');
	}

	/**
	 * Validate mutable storage and defer invalidation until every argument has been evaluated.
	 * @param int[] $writes Local storage IDs invalidated by the call.
	 */
	private function bind_reference(ast_expression $argument, type_definition $type, array &$writes /** list<uint32> */): void
	{
		$storage = $argument instanceof ast_name_expression ? $argument->declaration() : null;
		if ($storage instanceof ast_constant_declaration || $storage instanceof runtime_integer_constant) {
			throw new diagnostic($argument->source(), $argument->byte_start(), 'Constant cannot bind a mutable reference');
		}
		if (!($storage instanceof ast_local_binding)) {
			throw new diagnostic($argument->source(), $argument->byte_start(),
				'Reference argument requires stable variable storage: a named local or parameter');
		}
		if ($argument->type_def() !== $type) {
			throw new diagnostic($argument->source(), $argument->byte_start(), 'Reference argument requires exact storage type');
		}
		$writes[] = $storage->storage_id();
	}

	/** Validate supplied runtime arguments against their generated signature. */
	private function runtime_call(ast_call_expression $call, runtime_function|runtime_overloads $runtime): void
	{
		$operands = $call->runtime_operands();
		$prepared = $call->receiver;
		if ($runtime instanceof runtime_overloads) {
			$variants = $runtime->by_arity[count($operands)] ?? null;
			if ($variants === null) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
			}
			$first = $operands[0];
			if ($first !== $prepared) {
				$this->value($first);
				$prepared = $first;
			}
			$runtime = $variants[$first->type_def()::class] ?? null;
			if ($runtime === null) {
				throw new diagnostic($first->source(), $first->byte_start(), 'Unsupported first argument type for ' . $call->name);
			}
		}
		elseif (count($operands) < $runtime->required_parameters || count($operands) > count($runtime->parameters)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
		}
		$writes = [];
		foreach ($operands as $index => $argument) {
			if ($argument !== $prepared) {
				$this->value($argument);
			}
			$type = $runtime->parameters[$index];
			if ($runtime->is_reference($index)) {
				$this->bind_reference($argument, $type, $writes);
			}
			elseif ($argument->type_def() !== $type) {
				throw new diagnostic($argument->source(), $argument->byte_start(), $call->name . ' requires ' . $type->name);
			}
		}
		foreach ($writes as $id) {
			$this->latest_writes[$id] = null;
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
				$this->value($receiver, $initializing);
				$name = $separator === false ? $call->name : substr($call->name, $separator + 1);
				$record = $receiver->type_def();
				if ($record instanceof shared_struct_type_definition) $record = $record->payload;
				if ($record instanceof struct_type_definition) {
					if ($call->source()->flavor === flavor::cpp && $call->member_separator === token_kind::dot) {
						throw new diagnostic($call->source(), $call->byte_start(), 'Struct methods use -> in the C++ flavor');
					}
					$method = $record->declaration->methods[$name] ?? null;
					$symbol = $this->symbols->entry('method:' . $record->name . '::' . $name);
					$this->dependencies?->watch($symbol, $this->owner);
					if ($method === null || $method->is_constructor()) throw new diagnostic($call->source(), $call->byte_start(), 'Unknown method ' . $record->name . '::' . $name);
					if ($method->is_static) throw new diagnostic($call->source(), $call->byte_start(), 'Static method requires a type-qualified call: ' . $method->name());
					$this->source_call($call, $symbol, $method, $initializing);
					return;
				}
				if ($call->member_separator === token_kind::member) {
					throw new diagnostic($call->source(), $call->byte_start(), 'Arrow method call requires a struct receiver');
				}
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
		if ($call->member_separator === token_kind::scope) {
			$this->static_call($call, $call->name, $initializing, true);
			return;
		}
		$key = $file->function_key($call->name);
		$symbol = $this->symbols->entry($key);
		$this->dependencies?->watch($symbol, $this->owner);
		$function = $symbol->declaration();
		if (!($function instanceof ast_function_declaration)) {
			// PHP has distinct type qualification. C++ and JS first try an existing namespace function.
			if ($call->source()->flavor !== flavor::php && ($last = strrpos($call->name, '\\')) > 0) {
				$qualified = substr($call->name, 0, $last) . '::' . substr($call->name, $last + 1);
				if ($this->static_call($call, $qualified, $initializing, false)) return;
			}
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
		$this->source_call($call, $symbol, $function, $initializing);
	}

	/** Exact type-qualified calls require a method; ambiguous namespace paths may continue lookup. */
	private function static_call(ast_call_expression $call, string $qualified, ?string $initializing, bool $required): bool
	{
		$symbol = $this->symbols->entry('method:' . Source_Names::resolve($qualified, $this->owner->scope->namespace));
		$this->dependencies?->watch($symbol, $this->owner);
		$method = $symbol->declaration();
		if (!($method instanceof ast_function_declaration)) {
			if ($required) throw new diagnostic($call->source(), $call->byte_start(), 'Unknown static method: ' . $qualified);
			return false;
		}
		if (!$method->is_static) throw new diagnostic($call->source(), $call->byte_start(), 'Instance method requires a receiver: ' . $method->name());
		$this->source_call($call, $symbol, $method, $initializing);
		return true;
	}

	/** Shared positional admission, conversions and reference invalidation for source calls. */
	private function source_call(ast_call_expression $call, symbol_entry $symbol, ast_function_declaration $function, ?string $initializing): void
	{
		$function->require_access($this->owner->parent->parent, $call);
		$call->settle_function($symbol, $this->bind_arguments($call, $function, $initializing));
	}

	/** Inline and shared construction use the same published signature and argument checks. */
	private function construction(ast_construction_expression $site, type_definition $type, ?string $initializing): void
	{
		$payload = $type instanceof shared_struct_type_definition && $site instanceof ast_new_expression ? $type->payload : $type;
		$constructor = $payload instanceof struct_type_definition ? $payload->constructor() : null;
		if ($constructor === null && $site->arguments !== []) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Only zero-argument initialization is available for ' . $type->name);
		}
		if ($constructor !== null) {
			$this->dependencies?->watch($this->symbols->entry($constructor->symbol_key()), $this->owner);
			$constructor->require_access($this->owner->parent->parent, $site);
		}
		$site->settle_construction($type, $constructor === null ? [] : $this->bind_arguments($site, $constructor, $initializing));
	}

	/** Base arguments use the ordinary call boundary, before the current instance becomes available. */
	private function base_initialization(ast_base_initialization $site): void
	{
		$record = $this->owner->parent->parent;
		$base = $record->definition->base();
		$this->dependencies?->watch($this->symbols->entry($record->symbol_key()), $this->owner);
		if ($base === null) throw new diagnostic($site->source(), $site->byte_start(), 'Base initialization requires a base');
		if ($site->target !== null) {
			$target = Type_Resolution::named($site->target, $this->symbols, $record->namespace, $this->owner, $this->dependencies);
			if ($target !== $base) throw new diagnostic($site->source(), $site->byte_start(), 'Initializer must select the immediate base');
		}
		if ($site->explicit && $base->constructor() === null) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Explicit parent call requires an authored base constructor');
		}
		$this->initializing_base = true;
		try {
			$this->construction($site, $base, null);
		} finally {
			$this->initializing_base = false;
		}
	}

	/** Share arity, conversions and reference effects between functions and constructors. @return int[] */
	private function bind_arguments(ast_call_expression|ast_construction_expression $call, ast_function_declaration $function,
		?string $initializing): array /** list<uint16> */
	{
		$supplied = count($call->arguments);
		if (!$function->accepts_argument_count($supplied)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $function->name());
		}
		$conversions = [];
		$writes = [];
		foreach ($call->arguments as $index => $argument) {
			$this->value($argument, $initializing);
			$parameter = $function->parameters[$index];
			if ($parameter->type_def() instanceof hash_type_definition) {
				throw new diagnostic($argument->source(), $argument->byte_start(), 'Hash argument passing is deferred');
			}
			if ($parameter->type_def() instanceof vector_type_definition) {
				throw new diagnostic($argument->source(), $argument->byte_start(), 'Vector argument passing is deferred');
			}
			if ($parameter->mode === passing_mode::reference) {
				$this->bind_reference($argument, $parameter->type_def(), $writes);
				$conversions[] = value_conversion::identity;
			}
			else {
				$conversions[] = $parameter->type_def()->prepare_conversion($argument, conversion_context::typed_destination);
			}
		}
		// Invocation follows every argument evaluation. Preserve earlier snapshots, invalidate future reads.
		foreach ($writes as $id) {
			$this->latest_writes[$id] = null;
		}
		return $conversions;
	}

	/** Prevent C++ implicit scalar conversions from widening the supported echo/entry contracts. */
	private function require_integer(ast_expression $value, string $message): void
	{
		if ($value->type_def() !== type_registry::integer()) {
			throw new diagnostic($value->source(), $value->byte_start(), $message);
		}
	}

}
