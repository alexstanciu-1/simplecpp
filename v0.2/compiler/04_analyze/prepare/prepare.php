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
	/** Native non-owning innermost active loop within this callable. */
	private ?ast_repeating_statement $loop = null;
	/** Native non-owning nearest enclosing loop outside the current finalizer. */
	private ?ast_repeating_statement $finalizer_outer_loop = null;
	/** Inherited by nested statements inside a finalizer. */
	private bool $in_finalizer = false;
	/** Traversal state: current-instance access is unavailable while preparing base arguments. */
	private bool $initializing_base = false;
	/** Transient initialization proof, owned only while preparing a constructor. */
	private ?Readonly_Initialization $readonly_initialization = null;
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
		// Declaration collection owns duplicate chains on the authored body, including shared parameters.
		$authored = $syntax->parent->instance?->method->declaration();
		$errors = Declaration_Diagnostics::locals($authored?->body() ?? $syntax);
		if ($errors !== []) {
			throw new diagnostic_group($errors);
		}
		try {
			$this->body($syntax->statements, $syntax->source(),
				($target === null ? $syntax->byte_end() : strlen($syntax->source()->content)), $target,
				$syntax);
		} finally {
			$this->readonly_initialization = null;
		}
		Last_Use_Transfer::prepare($syntax);
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
		$this->readonly_initialization = $function->is_constructor() && $function->parent instanceof ast_record_declaration
			? new Readonly_Initialization($function->parent) : null;
		foreach ($function->parameters as $parameter) {
			$this->local_frames[0][$parameter->declaration_key()] = $parameter;
		}
		$this->statement_visits = 0;
		$this->expression_visits = 0;
		$this->name_lookups = 0;
		$this->loop = null;
		$completion = $this->sequence($syntax, $source, $target, $function);
		if (($completion & statement_completion::fallthrough) !== 0) $this->readonly_initialization?->complete($owner);
		if (($target === null) && ($completion & statement_completion::fallthrough) !== 0 && ($function->return_type_def() !== type_void::instance())
			&& !($function->return_type_def() instanceof generator_type_definition)) {
			throw new diagnostic($source, $end, 'Non-void function requires a value return');
		}
		if (($target !== null) && (($completion & statement_completion::fallthrough) !== 0)) {
			throw new diagnostic($source, $end, 'Entry slice requires an explicit value return');
		}
	}

	/** One traversal per block; completion is transient and never a copied prepared statement tree.
	 * @param ast_statement[] $syntax
	 */
	private function sequence(array $syntax /** list<ast_statement> */, source_file $source,
		?entry_target $target, ast_function_declaration $function): int /** uint8 */
	{
		$generator = $function->return_type_def();
		$completion = statement_completion::fallthrough;
		foreach ($syntax as $statement)
		{
			$this->statement_visits++;
			if (($completion & statement_completion::fallthrough) === 0) {
				throw new diagnostic($statement->source(), $statement->byte_start(),
					$completion === statement_completion::returns ? 'Statements after return are outside the entry slice'
					: 'Statements after unconditional control transfer are not supported');
			}
			if ($generator instanceof generator_type_definition
				&& !($statement instanceof ast_yield_statement || $statement instanceof ast_echo_statement
					|| $statement instanceof ast_call_statement || $statement instanceof ast_return_statement
					|| $statement instanceof ast_local_declaration || $statement instanceof ast_storage_write
					|| $statement instanceof ast_foreach_statement || $statement instanceof ast_if_statement || $statement instanceof ast_loop_statement
					|| $statement instanceof ast_break_statement || $statement instanceof ast_continue_statement)) {
				throw new diagnostic($source, $statement->byte_start(), 'Yield slice supports locals, writes, conditionals, loops, yields, echo, calls and bare return only');
			}
			$next = statement_completion::fallthrough;
			if ($statement instanceof ast_yield_statement) {
				if (!($generator instanceof generator_type_definition)) {
					throw new diagnostic($source, $statement->byte_start(), 'Yield requires an Iterator return contract');
				}
				if (($statement->key === null) !== ($generator->iteration_key_type() === null)) {
					throw new diagnostic($source, $statement->byte_start(), 'Yield key must match the Iterator contract');
				}
				if ($statement->key !== null) {
					$this->value($statement->key);
					$statement->key_conversion = $generator->iteration_key_type()->prepare_conversion($statement->key, conversion_context::typed_destination);
				}
				$this->value($statement->value);
				$statement->conversion = $generator->iteration_value_type()->prepare_conversion($statement->value, conversion_context::typed_destination);
				$this->forget_values();
			}
			elseif ($statement instanceof ast_base_statement) {
				$this->base_initialization($statement->value);
			}
			elseif ($statement instanceof ast_loop_transfer) {
				if ($this->loop === null) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Break/continue requires an enclosing loop');
				}
				if ($this->loop === $this->finalizer_outer_loop) {
					throw new diagnostic($source, $statement->byte_start(), 'Break/continue cannot leave a finally block');
				}
				$statement->target = $this->loop;
				$next = $statement instanceof ast_break_statement ? statement_completion::breaks : statement_completion::continues;
			}
			elseif ($statement instanceof ast_foreach_statement) {
				$next = $this->foreach_loop($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_for_statement) {
				$next = $this->for_loop($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_loop_statement) {
				$next = $this->loop($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_throw_statement) {
				$this->prepare_throw($statement->operation);
				$next = statement_completion::throws;
			}
			elseif ($statement instanceof ast_try_statement) {
				$next = $this->try_statement($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_if_statement) {
				$next = $this->conditional($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_local_declaration) {
				$this->declaration($statement);
				if ($generator instanceof generator_type_definition && $statement->type_def() instanceof generator_type_definition) {
					throw new diagnostic($source, $statement->byte_start(), 'Nested cursor locals are deferred');
				}
			}
			elseif ($statement instanceof ast_field_assignment || $statement instanceof ast_property_compound) {
				$initializing_field = $this->writable_property($statement->target, $statement instanceof ast_field_assignment);
				$this->value($statement->value);
				$type = $statement->target->type_def();
				if ($statement instanceof ast_property_compound) $type->prepare_compound($statement);
				else $statement->conversion = $type->prepare_conversion($statement->value, conversion_context::assignment);
				if ($initializing_field !== null) $this->readonly_initialization->states[$initializing_field->short_name] = 1;
			}
			elseif ($statement instanceof ast_index_compound) {
				$receiver = $statement->target->receiver;
				$this->value($statement->target);
				Readonly_Access::write($receiver, true);
				if (!($receiver->type_def() instanceof indexed_mutation_type)) {
					throw new diagnostic($receiver->source(), $receiver->byte_start(), 'Indexed compounds require a mutable collection');
				}
				$this->value($statement->value);
				$statement->target->type_def()->prepare_compound($statement);
			}
			elseif ($statement instanceof ast_index_assignment) {
				$binding = null;
				$receiver = $statement->receiver;
				if ($receiver instanceof ast_field_expression) {
					$this->writable_property($receiver, payload: true);
				} else {
					$this->value($receiver);
					$binding = $receiver->declaration();
					if (!($binding instanceof ast_local_binding)
						&& !($binding instanceof ast_global_declaration && $binding->parent instanceof ast_record_declaration)) {
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
				if ($statement->receiver instanceof ast_field_expression) {
					$this->writable_property($statement->receiver, payload: true);
				} else {
					$this->value($statement->receiver);
					if (!($statement->receiver->declaration() instanceof ast_local_binding)) {
						throw new diagnostic($statement->source(), $statement->byte_start(), 'Append requires writable local storage');
					}
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
				if ($this->in_finalizer) {
					throw new diagnostic($source, $statement->byte_start(), 'Return cannot leave a finally block');
				}
				$this->readonly_initialization?->complete($statement);
				if ($target === null) {
					$return_type = $function->return_type_def();
					if ($return_type === type_void::instance() || $return_type instanceof generator_type_definition) {
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

	/** Compose protected exits with the independent finalizer, whose abrupt exits replace pending exits. */
	private function try_statement(ast_try_statement $syntax, source_file $source, ?entry_target $target,
		ast_function_declaration $function): int /** uint8 */
	{
		if (array_filter($this->readonly_initialization?->states ?? [], static fn(int $state): bool => $state !== 1) !== []) {
			throw new diagnostic($source, $syntax->byte_start(), 'Try during readonly field initialization is deferred');
		}
		$outer_loop = $this->finalizer_outer_loop;
		$in_finalizer = $this->in_finalizer;
		try {
			$handler = $syntax->handler;
			if ($handler !== null) {
				$caught = Type_Resolution::local($handler->type, $this->symbols, $this->owner, $this->dependencies);
				$this->require_exception($caught, $handler->type, true);
				$handler->binding->settle_type($caught);
			}
			$completion = $this->exception_block($syntax->body, $target, $function);
			if ($handler !== null) $completion |= $this->exception_block($handler->body, $target, $function, $handler->binding);
			$syntax->protected_completion = $completion;
			if ($syntax->finalizer !== null) {
				if (($completion & statement_completion::loop_transfers) !== 0) $syntax->transfer_target = $this->loop;
				$this->finalizer_outer_loop = $this->loop;
				$this->in_finalizer = true;
				$final = $this->exception_block($syntax->finalizer, $target, $function);
				$completion = ($final & ~statement_completion::fallthrough)
					| (($final & statement_completion::fallthrough) !== 0 ? $completion : 0);
			}
			return $syntax->completion = $completion;
		} finally {
			$this->finalizer_outer_loop = $outer_loop;
			$this->in_finalizer = $in_finalizer;
		}
	}

	/** A handler or finalizer starts without facts from partial writes in a sibling region. */
	private function exception_block(ast_block $body, ?entry_target $target, ast_function_declaration $function,
		?ast_produced_binding $binding = null): int /** uint8 */
	{
		$this->local_frames[] = $binding === null ? [] : [$binding->declaration_key() => $binding];
		try {
			return $this->sequence($body->statements, $body->source(), $target, $function);
		} finally {
			array_pop($this->local_frames);
			$this->forget_values();
		}
	}

	/** Observe ancestry through the selected payload's stable symbol and layout invalidation. */
	private function require_exception(?type_definition $type, ast_node $site, bool $catch = false): void
	{
		if ($type instanceof type_exception) return;
		if ($type instanceof shared_record_type_definition) {
			$this->dependencies?->watch($this->symbols->entry($type->payload->declaration->symbol_key()), $this->owner);
			if ((!$catch || $type->required) && $type->payload->exception_descendant()) return;
		}
		throw new diagnostic($site->source(), $site->byte_start(), 'Throw and catch require core Exception or a shared descendant');
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
		$before = $this->readonly_initialization?->states;
		$joined = null;
		foreach ($statement->arms as $arm) {
			if ($before !== null) $this->readonly_initialization->states = $before;
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
			if ($before !== null && ($arm_completion & statement_completion::fallthrough) !== 0) {
				$states = $this->readonly_initialization->states;
				if ($joined === null) $joined = $states;
				else foreach ($states as $name => $state) if ($joined[$name] !== $state) $joined[$name] = 2;
			}
		}
		if ($before !== null) {
			if (!$has_else) {
				if ($joined === null) $joined = $before;
				else foreach ($before as $name => $state) if ($joined[$name] !== $state) $joined[$name] = 2;
			}
			$this->readonly_initialization->states = $joined ?? $before;
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

	/** Resolve produced types and ordinary assignments once; no iteration-dependent analysis or snapshots. */
	private function foreach_loop(ast_foreach_statement $statement, source_file $source, ?entry_target $target,
		ast_function_declaration $function): int /** uint8 */
	{
		$this->value($statement->source_value);
		$type = $statement->source_value->type_def()->iteration();
		if ($type === null) throw new diagnostic($source, $statement->source_value->byte_start(), 'Foreach requires an iterable collection');
		if ($function->return_type_def() instanceof generator_type_definition) {
			$receiver = $statement->source_value;
			if ($receiver->type_def() instanceof generator_type_definition) {
				throw new diagnostic($source, $receiver->byte_start(), 'Nested cursor traversal is deferred');
			}
			while ($receiver instanceof ast_field_expression) $receiver = $receiver->receiver;
			if (!(($receiver instanceof ast_name_expression && $receiver->declaration() instanceof ast_local_binding)
				|| $receiver instanceof ast_call_expression || $receiver instanceof ast_construction_expression)) {
				throw new diagnostic($source, $receiver->byte_start(), 'Suspended foreach requires cursor-owned storage, a temporary or a retained shared field');
			}
		}
		if (($method = $type->iteration_method()) !== null) {
			Readonly_Access::write($statement->source_value, true);
			$this->dependencies?->watch($this->symbols->entry($method->symbol_key()), $this->owner);
		}
		$destination = $this->find_local($statement->value->short_name);
		$statement->value->settle_type($type->iteration_value_type());
		$statement->destination = $destination ?? $statement->value;
		$statement->conversion = $statement->destination->type_def()->prepare_conversion($statement->value,
			$destination === null ? conversion_context::inference : conversion_context::assignment);
		$this->forget_values();
		$this->local_frames[] = [];
		try {
			if ($destination === null) $this->local_frames[count($this->local_frames) - 1][$statement->value->short_name] = $statement->value;
			if ($statement->key !== null) {
				$key_type = $type->iteration_key_type();
				if ($key_type === null) throw new diagnostic($source, $statement->key->byte_start(), 'Foreach key binding is unavailable for this traversal');
				$statement->key->settle_type($key_type);
				$this->local_frames[count($this->local_frames) - 1][$statement->key->short_name] = $statement->key;
			}
			$this->local_frames[] = [];
			$outer_loop = $this->loop;
			$this->loop = $statement;
			try {
				$completion = $this->sequence($statement->body->statements, $source, $target, $function);
			}
			finally {
				$this->loop = $outer_loop;
				array_pop($this->local_frames);
			}
		}
		finally {
			array_pop($this->local_frames);
		}
		$this->forget_values();
		return ($completion & (statement_completion::returns | statement_completion::throws)) | statement_completion::fallthrough;
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
				if ($function->return_type_def() instanceof generator_type_definition && $statement->initializer->type_def() instanceof generator_type_definition) {
					throw new diagnostic($source, $statement->initializer->byte_start(), 'Nested cursor locals are deferred');
				}
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
		$outer_loop = $this->loop;
		$this->loop = $statement;
		try {
			$completion = $this->sequence($statement->body->statements, $source, $target, $function);
		}
		finally {
			$this->loop = $outer_loop;
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
		// A post-test loop enters its body once; return and throw are terminal exits.
		$terminal = statement_completion::returns | statement_completion::throws;
		return $post_test && ($completion & ~$terminal) === 0 ? $completion
			: (($completion & $terminal) | statement_completion::fallthrough);
	}

	/** Establish a binding only after its initializer succeeds; collection grants no forward visibility. */
	private function declaration(ast_local_declaration $declaration): void
	{
		$name = $declaration->declaration_key();
		$source = $declaration->source();
		$root = $declaration->lexical_root();
		if (isset($this->local_frames[count($this->local_frames) - 1][$name])) {
			throw new diagnostic($source, $root->byte_at($declaration->name_position()),
				'Duplicate local: ' . $name);
		}
		$type = null;
		if ($declaration->type_reference !== null) {
			$type = Type_Resolution::local($declaration->type_reference, $this->symbols, $this->owner, $this->dependencies);
			if ($type instanceof record_type_definition) $type->require_complete_value($declaration->type_reference);
			if ($type === null) {
				throw new diagnostic($source, $root->byte_at($declaration->type_position()),
					'Unknown or unsupported type: ' . $declaration->type_reference?->spelling());
			}
		}
		if ($declaration->initializer instanceof ast_struct_default) {
			if (!($type instanceof record_type_definition) && !($type instanceof record_handle_definition) && !($type instanceof type_exception)) {
				throw new diagnostic($source, $declaration->byte_start(), 'A local without an initializer requires a struct type');
			}
			if ($type instanceof shared_record_type_definition && $type->required) {
				throw new diagnostic($source, $declaration->byte_start(), 'Required class locals need an initializer in this slice');
			}
			$this->expression_visits++;
			$this->construction($declaration->initializer, $type, $name);
		}
		elseif ($declaration->initializer instanceof ast_keyed_collection_expression) {
			if (!($type instanceof keyed_initializer_type)) throw new diagnostic($source, $declaration->initializer->byte_start(), 'Keyed initializer requires an explicitly typed published keyed destination');
			$this->expression_visits++;
			foreach ($declaration->initializer->entries as $entry) {
				$this->value($entry->key, $name);
				$type->prepare_key($entry->key);
				$this->value($entry->value, $name);
				$entry->conversion = $type->write_type()->prepare_conversion($entry->value, conversion_context::typed_destination);
			}
			$declaration->initializer->settle_collection($type);
		}
		elseif (($declaration->initializer instanceof ast_collection_expression) && $type !== null) {
			if (!($type instanceof indexed_collection_type)) {
				throw new diagnostic($source, $declaration->initializer->byte_start(), 'Destination has no published collection initialization capability');
			}
			$this->collection($declaration->initializer, $type, $name);
		}
		else {
			$this->value($declaration->initializer, $name);
		}
		if ($type === null) {
			$type = $declaration->initializer->type_def();
			if ($type instanceof record_type_definition) $this->require_inline_cleanup($type, $declaration);
			if (!$type->supports_inferred_local()) {
				throw new diagnostic($source, $declaration->initializer->byte_start(),
					'Cannot infer a local type from null; write an explicit type');
			}
		}
		$conversion = $type->prepare_conversion($declaration->initializer, $declaration->type_reference !== null ? conversion_context::typed_destination : conversion_context::inference);
		$declaration->settle_type($type, $type->evaluate_constant($conversion, $declaration->initializer), true, $conversion);
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
		if (!($property instanceof ast_global_declaration) || !($property->parent instanceof ast_record_declaration)) {
			throw new diagnostic($target->source(), $target->byte_start(), 'Assignment requires a writable static property');
		}
	}

	/** Resolve a qualified data name once; PHP spells properties separately from constants. */
	private function bind_static_data(ast_name_expression $syntax, string $qualifier, string $name): bool
	{
		$type = $this->symbols->entry($syntax->lexical_root()->type_key($qualifier));
		$this->dependencies?->watch($type, $this->owner);
		$record = $type->declaration();
		if (!($record instanceof ast_record_declaration)) return false;
		return $this->bind_record_data($syntax, $record->definition, $name, $qualifier);
	}

	/** Bind the selected declaration from the lookup starting type, observing each visited record. */
	private function bind_record_data(ast_name_expression $syntax, record_type_definition $lookup, string $name, string $qualifier): bool
	{
		$properties = !$syntax->constant_only;
		$constants = !$properties || $syntax->source()->flavor !== flavor::php;
		$member = $lookup->static_data_member($name, $properties, $constants, $this->symbols,
			$this->owner, $this->dependencies);
		if ($member === null) {
			$message = $constants ? 'Unknown constant: ' : 'Unknown static property: ';
			throw new diagnostic($syntax->source(), $syntax->byte_start(), $message . $qualifier . '::' . $name);
		}
		return $this->bind_literal($syntax, $this->symbols->entry($member->symbol_key()));
	}

	/** Watch missing and inaccessible members so visibility edits also recover an unchanged caller. */
	private function bind_literal(ast_name_expression $syntax, symbol_entry $symbol): bool
	{
		$this->dependencies?->watch($symbol, $this->owner);
		$member = $symbol->declaration();
		if ($member === null) return false;
		if ($member->visibility !== member_visibility::public) {
			$context = $this->owner->parent->parent;
			if ($member->visibility === member_visibility::protected) {
				$allowed = $member->parent->definition->permits_protected_access($context, watcher: $this->access_watcher());
			}
			else {
				$allowed = $context === $member->parent;
			}
			if (!$allowed) {
				$kind = $member instanceof ast_constant_declaration ? 'constant' : 'static property';
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Cannot access ' . $member->visibility->name . ' ' . $kind . ' ' . $member->name());
			}
		}
		if ($member instanceof ast_constant_declaration) $syntax->settle_constant($symbol);
		else $syntax->settle_global($symbol);
		return true;
	}

	/** Check writable instance paths and qualified static members for all property writes. */
	private function writable_property(ast_name_expression|ast_field_expression $root, bool $initialize = false, bool $payload = false): ?ast_instance_field
	{
		if ($root instanceof ast_name_expression) {
			if ($root instanceof ast_called_type_name && $payload) throw new diagnostic($root->source(), $root->byte_start(), 'Indexed late-static property writes are deferred');
			if ($root instanceof ast_qualified_data && $payload) throw new diagnostic($root->source(), $root->byte_start(), 'Indexed expression-qualified static property writes are deferred');
			$this->static_destination($root);
			return null;
		}
		$this->value($root);
		$field = $initialize ? $this->readonly_initialization?->assignment($root, $this->loop !== null) : null;
		Readonly_Access::write($root, $payload, $field);
		$root = $root->receiver;
		while ($root instanceof ast_field_expression) $root = $root->receiver;
		if (!($root instanceof ast_this_expression)
			&& (!($root instanceof ast_name_expression) || !($root->declaration() instanceof ast_local_binding))) {
			throw new diagnostic($root->source(), $root->byte_start(), 'Field assignment requires writable local storage');
		}
		return $field;
	}

	/** Resolve established storage once; generated admission precedes publication and latest-value invalidation. */
	private function write(ast_storage_write $statement): void
	{
		if ($statement instanceof ast_index_update) {
			$this->value($statement->target);
			Readonly_Access::write($statement->target->receiver, true);
			if (!($statement->target->receiver->type_def() instanceof indexed_mutation_type)) {
				throw new diagnostic($statement->source(), $statement->byte_start(), 'Indexed updates require a mutable collection');
			}
			$statement->require_update_type($statement->target->type_def());
			return;
		}
		if ($statement instanceof ast_property_update) {
			$this->writable_property($statement->target);
			$statement->require_update_type($statement->target->type_def());
			return;
		}
		$this->name_lookups++;
		$local = $this->find_local($statement->name);
		if ($local === null) {
			if (($statement->source()->flavor !== flavor::php)
				&& ($this->symbols->resolve($statement->lexical_root()->constant_key($statement->name)) instanceof ast_constant_declaration)) {
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
		if ($statement->operator === binary_operator::coalesce) {
			if (!($local instanceof ast_local_declaration) || $statement->parent instanceof ast_for_statement) {
				throw new diagnostic($statement->source(), $statement->byte_start(), 'Coalescing assignment requires a plain local statement');
			}
			if (!($type instanceof carrier_type_definition) || !$type->supports_coalescing()) {
				throw new diagnostic($statement->source(), $statement->byte_start(), 'Coalescing assignment requires a published nullable carrier');
			}
		}
		if (!$statement->calculates_value()) {
			$conversion = $type->prepare_conversion($statement->value, conversion_context::assignment);
			$statement->settle_assignment($local, $conversion);
		}
		else {
			$statement->prepare_local_compound($local);
		}
		$this->latest_writes[$local->storage_id()] = $statement;
	}

	/** Prepare ordered elements once using the destination contract; nested literals inherit their element destination. */
	private function collection(ast_collection_expression $syntax, type_definition&indexed_collection_type $type, ?string $initializing): void
	{
		$this->expression_visits++;
		if ($syntax->elements === []) {
			$syntax->settle_collection($type);
			return;
		}
		if (!($type instanceof sequence_initializer_type)) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Populated literal requires a sequence-initializable destination');
		}
		$destination = $type->write_type();
		$conversions = [];
		foreach ($syntax->elements as $element) {
			if ($element instanceof ast_collection_expression && $destination instanceof indexed_collection_type) {
				$this->collection($element, $destination, $initializing);
			} else $this->value($element, $initializing);
			$conversions[] = $destination->prepare_conversion($element, conversion_context::typed_destination);
		}
		$syntax->settle_collection($type, $conversions);
	}

	/** Statement and fallback throws use the same admission and settled root conversion. */
	private function prepare_throw(ast_throw_expression $throw, ?string $initializing = null): void
	{
		$operand = $throw->operand;
		$this->value($operand, $initializing);
		$this->require_exception($operand->type_def(), $operand);
		$throw->conversion = type_registry::exception()->prepare_conversion($operand, conversion_context::typed_destination);
	}

	/** Check one lazy arm without retaining a second result-type or branch-fact structure. */
	private function match_result(ast_match_arm $arm, ?type_definition &$result, ?string $initializing): void
	{
		$this->value($arm->result, $initializing);
		if ($arm->result instanceof ast_throw_expression) return;
		$type = $arm->result->type_def();
		if (!$type->supports_inferred_local() || $type instanceof generator_type_definition) throw new diagnostic($arm->source(), $arm->byte_start(), 'Match arm requires a supported value result');
		if ($result !== null && $result !== $type) throw new diagnostic($arm->source(), $arm->byte_start(), 'Match result types must be identical');
		$result = $type;
	}

	/** Resolve each expression once; a reference retains its exact declaration binding. */
	private function value(ast_expression $syntax, ?string $initializing = null): void
	{
		if ($syntax instanceof ast_type_marker) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type marker is only valid as an enum_from_value or object_cast target');
		$this->expression_visits++;
		if ($syntax instanceof ast_throw_expression) {
			$this->prepare_throw($syntax, $initializing);
			return;
		}
		if ($syntax instanceof ast_keyed_collection_expression) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Keyed initializer requires an explicitly typed local; nested keyed input is deferred');
		if ($syntax instanceof ast_collection_expression) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), ($syntax->elements === [] ? 'Empty collection requires an explicitly typed local initializer (vector, hash or boxed container)' : 'Populated collection requires an explicitly typed local initializer'));
		}
		if ($syntax instanceof ast_match_expression) {
			$this->value($syntax->subject, $initializing);
			$type = $syntax->subject->type_def();
			$conditional = ast_match_expression::is_condition_subject($syntax->subject);
			if (!$conditional && !($type instanceof enum_type_definition) && !(defined($type::class . '::MINIMUM') && defined($type::class . '::MAXIMUM'))) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Match subject requires an integer or enum, or literal true');
			}
			$seen = [];
			$default = null;
			$result = null;
			foreach ($syntax->arms as $arm) {
				if ($arm->label === null) {
					if ($default !== null) throw new diagnostic($arm->source(), $arm->byte_start(), 'Duplicate match default');
					$default = $arm;
					continue;
				}
				$this->value($arm->label, $initializing);
				if ($arm->label->type_def() !== $type) throw new diagnostic($arm->source(), $arm->byte_start(), 'Match label type must equal subject type');
				if (!$conditional) {
					$binding = $arm->label instanceof ast_name_expression ? $arm->label->declaration() : null;
					if ($type instanceof enum_type_definition && $binding instanceof ast_enum_case) $key = $binding->short_name;
					elseif (($arm->label instanceof ast_integer_expression || $binding instanceof ast_constant_declaration || $binding instanceof runtime_integer_constant)
						&& is_int($arm->label->constant())) $key = $arm->label->constant();
					else throw new diagnostic($arm->source(), $arm->byte_start(), 'Match label requires an integer literal, named constant or enum case');
					if (isset($seen[$key])) throw new diagnostic($arm->source(), $arm->byte_start(), 'Duplicate match label');
					$seen[$key] = true;
				}
				$this->match_result($arm, $result, $initializing);
			}
			if ($default === null) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Match requires a default arm');
			// Fallback runs after conditions, even when written first. Preserve reference-call invalidation.
			$this->match_result($default, $result, $initializing);
			if ($result === null) {
				throw new diagnostic($default->source(), $default->byte_start(), 'Throwing match default requires a value arm');
			}
			$syntax->settle_match($result);
			return;
		}
		if ($syntax instanceof ast_object_access_expression) {
			$this->value($syntax->operand, $initializing);
			Object_Access_Preparation::prepare($syntax, $this->symbols, $this->owner, $this->dependencies);
			return;
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
			if (!($record instanceof ast_method_owner)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'this requires an instance method');
			}
			$parent = $syntax->parent;
			$is_receiver = ($parent instanceof ast_field_expression || $parent instanceof ast_call_expression
				|| $parent instanceof ast_qualified_data) && $parent->receiver === $syntax;
			if (!$is_receiver && $this->owner->parent->is_destructor()) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Copying this during destruction is forbidden');
			if (!$is_receiver && $this->owner->parent->is_constructor() && !($parent instanceof ast_return_statement)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Copying this during construction is deferred');
			}
			if (!$is_receiver && $record instanceof ast_record_declaration) {
				if ($record->kind !== record_kind::structure) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Owning this values for classes are deferred');
				$record->definition->require_complete_value($syntax);
			}
			// Receiver facts depend on the current layout, including inherited types and access.
			$this->dependencies?->watch($this->symbols->entry($record->symbol_key()), $this->owner);
			$syntax->settle_receiver($record->definition);
			return;
		}
		if ($syntax instanceof ast_new_expression) {
			if ($syntax->target instanceof ast_called_type_reference) {
				$record = $this->called_record($syntax, 'Late-static construction');
				if ($record->kind !== record_kind::class_type) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Late-static construction requires a class');
				$payload = $record->definition;
			} else {
				$payload = Type_Resolution::local($syntax->target, $this->symbols, $this->owner, $this->dependencies);
			}
			if ($payload instanceof shared_record_type_definition && $payload->required) $payload = $payload->payload;
			if (!($payload instanceof record_type_definition)) {
				throw new diagnostic($syntax->source(), $syntax->target->byte_start(), 'Shared construction requires a record: ' . $syntax->target->name);
			}
			$this->construction($syntax, $payload->required, $initializing);
			return;
		}
		if ($syntax instanceof ast_field_expression) {
			$this->value($syntax->receiver, $initializing);
			$type = $syntax->receiver->type_def();
			if ($type instanceof shared_record_type_definition) $type = $type->payload;
			if (!($type instanceof record_type_definition)) {
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
			if ($field->is_protected && !$field->parent->definition->permits_protected_access($context, $type, $this->access_watcher())) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Cannot access protected field ' . $syntax->field);
			}
			$syntax->settle_field($field->type_def());
			if (!(($syntax->parent instanceof ast_field_assignment) && $syntax->parent->target === $syntax)) {
				$this->readonly_initialization?->read($syntax);
			}
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
			if ($syntax->operator === binary_operator::coalesce) {
				$carrier = $syntax->left->type_def();
				if (!($carrier instanceof carrier_type_definition) || !$carrier->supports_coalescing()) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Coalescing requires a published nullable carrier');
				}
				if (!($syntax->right instanceof ast_throw_expression) && $syntax->right->type_def() !== $carrier->payload_type) {
					throw new diagnostic($syntax->right->source(), $syntax->right->byte_start(), 'Coalescing fallback requires the exact payload type');
				}
				$syntax->settle_binary($carrier->payload_type);
				return;
			}
			if ($syntax->operator->logical()) {
				$syntax->left->type_def()->prepare_condition($syntax->left);
				$syntax->right->type_def()->prepare_condition($syntax->right);
				$syntax->settle_binary(type_bool::instance());
				return;
			}
			$left = $syntax->left->type_def();
			$right = $syntax->right->type_def();
			if ($left instanceof shared_record_type_definition || $right instanceof shared_record_type_definition) {
				$owner = $left instanceof shared_record_type_definition ? $left : $right;
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
			if ($syntax instanceof ast_ancestor_name || $syntax instanceof ast_called_type_name || $syntax instanceof ast_qualified_data) {
				$lookup = match (true) {
					$syntax instanceof ast_called_type_name => $this->called_record($syntax, 'Late-static access')->definition,
					$syntax instanceof ast_ancestor_name => $this->lexical_base($syntax, 'Ancestor access'),
					default => $this->static_qualifier($syntax->receiver, $initializing),
				};
				$this->bind_record_data($syntax, $lookup, $name, $lookup->name);
				return;
			}
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
			if (Enum_Preparation::reference($syntax, $this->symbols, $this->owner, $this->dependencies)) return;
			$scope_separator = strrpos($name, '::');
			if ($scope_separator !== false && $this->bind_static_data($syntax, substr($name, 0, $scope_separator),
				substr($name, $scope_separator + 2))) return;
			if (!$syntax->constant_only) {
				$key = 'var:' . Source_Names::resolve($name, $this->owner->scope->namespace);
				$symbol = $this->symbols->entry($key);
				if ($this->bind_literal($syntax, $symbol)) return;
				if ($syntax->source()->flavor === flavor::php && str_contains($name, '::')) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown static property: ' . $name);
				}
			}
			if ($syntax->constant_only || ($syntax->source()->flavor !== flavor::php)) {
				$symbol = $this->symbols->entry($syntax->lexical_root()->constant_key($name));
				if ($this->bind_literal($syntax, $symbol)) return;
				// C++ and JS share the namespace separator with type-qualified members.
				if ($separator !== false && $syntax->source()->flavor !== flavor::php && ($last = strrpos($name, '\\')) > 0) {
					if ($this->bind_static_data($syntax, substr($name, 0, $last), substr($name, $last + 1))) return;
				}
				if ($syntax->constant_only && $syntax->source()->flavor === flavor::php) {
					$constant = runtime_constants::integer($syntax->lexical_root()->runtime_constant_key($name));
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
	private function runtime_call(ast_call_expression $call, runtime_function|runtime_overloads $runtime, ?ast_expression $prepared = null): void
	{
		$operands = $call->runtime_operands($runtime instanceof runtime_function ? $runtime : null);
		$prepared ??= $call->receiver;
		if ($runtime instanceof runtime_overloads) {
			$variants = $runtime->by_arity[count($operands)] ?? null;
			if ($variants === null) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
			}
			$selection_index = $runtime->argument_index;
			$selected = $operands[$selection_index];
			if ($selected !== $prepared) {
				$this->value($selected);
				$prepared = $selected;
			}
			$runtime = $variants[$selected->type_def()::class] ?? null;
			if ($runtime === null) {
				throw new diagnostic($selected->source(), $selected->byte_start(), ($selection_index === 0 ? 'Unsupported first argument type for ' : 'Unsupported argument type for ') . $call->name);
			}
		}
		elseif (count($operands) < $runtime->required_parameters || count($operands) > count($runtime->parameters)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
		}
		$writes = [];
		foreach ($operands as $index => $argument) {
			if ($argument !== $prepared && $argument !== $call->receiver) {
				$this->value($argument);
			}
			$type = $runtime->parameters[$index];
			if ($index === 0 && $runtime->receiver_method) {
				// Receiver preparation already checked field visibility; expression temporaries own their lifetime.
				if ($runtime->is_reference(0)) Readonly_Access::write($argument, true);
				if ($runtime->is_reference(0) && $argument instanceof ast_name_expression) {
					$this->bind_reference($argument, $type, $writes);
				}
			}
			elseif ($runtime->is_reference($index)) {
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
		$file = $receiver->lexical_root();
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

	/** Evaluate the qualifier in preparation order and select its declared record without a handle presence check. */
	private function static_qualifier(ast_expression $receiver, ?string $initializing): record_type_definition
	{
		$this->value($receiver, $initializing);
		$type = $receiver->type_def();
		if ($type instanceof record_handle_definition) $type = $type->payload;
		if (!($type instanceof record_type_definition) || $type->declaration->kind === record_kind::interface_type) {
			throw new diagnostic($receiver->source(), $receiver->byte_start(), 'Static qualifier requires a concrete record type');
		}
		return $type;
	}

	/** Resolve only the published signature, then validate and convert arguments in source order. */
	private function call(ast_call_expression $call, ?string $initializing = null): void
	{
		if ($call->qualifier === call_qualifier::lexical_parent) {
			$this->ancestor_call($call, $call->name, null, $initializing);
			return;
		}
		$receiver = $call->receiver;
		if ($receiver !== null) {
			$separator = strrpos($call->name, '\\');
			$member = $separator === false;
			if (!$member && $receiver instanceof ast_name_expression) {
				$member = $this->has_receiver($receiver);
			}
			if ($member) {
				$name = $separator === false ? $call->name : substr($call->name, $separator + 1);
				$static = $call->expression_qualified();
				if ($static) $record = $this->static_qualifier($receiver, $initializing);
				else {
					$this->value($receiver, $initializing);
					$record = $receiver->type_def();
					if ($record instanceof shared_record_type_definition) $record = $record->payload;
				}
				if ($record instanceof record_type_definition || $record instanceof enum_type_definition) {
					if ($call->source()->flavor === flavor::cpp && $call->member_separator === token_kind::dot) {
						throw new diagnostic($call->source(), $call->byte_start(), 'Struct methods use -> in the C++ flavor');
					}
					$symbol = $record instanceof record_type_definition
						? $record->method_symbol($name, $this->symbols, $this->owner, $this->dependencies)
						: $this->symbols->entry('method:' . $record->name . '::' . $name);
					if ($symbol !== null) $this->dependencies?->watch($symbol, $this->owner);
					$method = $symbol?->declaration();
					if ($method === null && !$static && $record instanceof record_type_definition && $record->exception_descendant()) {
						$binding = type_registry::exception()->runtime_method($name);
						if ($binding !== null) {
							if ($receiver instanceof ast_this_expression) $this->readonly_initialization?->complete($call);
							$this->runtime_call($call, $binding);
							return;
						}
					}
					if ($method === null || $method->is_constructor()) throw new diagnostic($call->source(), $call->byte_start(), 'Unknown method ' . $record->name . '::' . $name);
					if ($static !== $method->is_static) {
						$message = $static ? 'Expression-qualified call requires a static method: ' : 'Static method requires a type-qualified call: ';
						throw new diagnostic($call->source(), $call->byte_start(), $message . $method->name());
					}
					if (!$static) {
						Readonly_Access::write($receiver, true);
						if ($receiver instanceof ast_this_expression) $this->readonly_initialization?->complete($call);
					}
					$this->source_call($call, $symbol, $method, $initializing, called: $static ? $record : null);
					$call->interface_dispatch = !$static && $record instanceof record_type_definition && $record->interface_dispatch($name);
					return;
				}
				$key = $call->source()->flavor->name . ':' . $receiver->type_def()->name . ':' . $name;
				$runtime = $receiver->type_def()->runtime_method($name) ?? runtime_functions::resolve($key);
				if ($runtime === null) {
					throw new diagnostic($call->source(), $call->byte_start(), 'Member calls: unknown method ' . $name . ' on ' . $receiver->type_def()->name);
				}
				if ($runtime->receiver_method) {
					$expected = $call->source()->flavor === flavor::js ? token_kind::dot : token_kind::member;
					if ($call->member_separator !== $expected) {
						throw new diagnostic($call->source(), $call->byte_start(), 'Collection methods use -> in PHP/C++ and . in JS');
					}
				}
				elseif ($call->member_separator === token_kind::member) {
					throw new diagnostic($call->source(), $call->byte_start(), 'Arrow method call requires a struct or collection receiver');
				}
				$this->runtime_call($call, $runtime);
				return;
			}
		}
		$this->name_lookups++;
		$file = $call->lexical_root();
		if ($call->member_separator === token_kind::scope) {
			$this->qualified_call($call, $call->name, $initializing, true);
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
				if ($this->qualified_call($call, $qualified, $initializing, false)) return;
			}
			$runtime = runtime_functions::free($file->runtime_function_key($call->name, $key), $file->source()->flavor);
			if ($runtime !== null) {
				$this->runtime_call($call, $runtime);
				return;
			}
			$builtin = $file->imported_function_target($call->name) === null ? ltrim($call->name, '\\') : null;
			if ($builtin === 'hash_contains' || $builtin === 'hash_find') {
				if (count($call->arguments) !== 2) throw new diagnostic($call->source(), $call->byte_start(), $builtin . ' requires a collection and key');
				$receiver = $call->arguments[0];
				$this->value($receiver, $initializing);
				$type = $receiver->type_def();
				$binding = match ($builtin) {
					'hash_contains' => $type instanceof key_presence_type ? $type->presence_function() : null,
					'hash_find' => $type instanceof key_lookup_type ? $type->lookup_function() : null,
				};
				if ($binding === null) throw new diagnostic($receiver->source(), $receiver->byte_start(), $builtin === 'hash_contains'
					? 'hash_contains requires a key-presence collection' : 'hash_find requires a collection with published optional lookup');
				$this->runtime_call($call, $binding, $receiver);
				return;
			}
			if ($builtin === 'enum_from_value') {
				if (count($call->arguments) !== 2 || !($call->arguments[0] instanceof ast_type_marker)) {
					throw new diagnostic($call->source(), $call->byte_start(), 'enum_from_value requires an enum type marker and one integer');
				}
				$marker = $call->arguments[0];
				$entry = $this->symbols->entry($file->type_key($marker->name));
				$this->dependencies?->watch($entry, $this->owner, dependency_use::enum_from_value->value);
				$enum = $entry->declaration();
				if (!($enum instanceof ast_enum_declaration)) throw new diagnostic($marker->source(), $marker->byte_start(), 'enum_from_value target must be an enum');
				$argument = $call->arguments[1];
				$this->value($argument, $initializing);
				$input = $argument->type_def();
				if ($enum->definition->backing() === type_string::instance()) {
					if ($input !== type_string::instance()) throw new diagnostic($argument->source(), $argument->byte_start(), 'enum_from_value requires a string');
				} elseif (!defined($input::class . '::MINIMUM') || !defined($input::class . '::MAXIMUM')) {
					throw new diagnostic($argument->source(), $argument->byte_start(), 'enum_from_value requires an integer');
				}
				$conversion = $enum->definition->from_value_function()->parameters[0]->prepare_conversion($argument, conversion_context::typed_destination);
				$call->settle_runtime($enum->definition->from_value_function(), [$conversion]);
				return;
			}
			if ($builtin === 'enum_value' || $builtin === 'enum_name') {
				if (count($call->arguments) !== 1) throw new diagnostic($call->source(), $call->byte_start(), $builtin . ' requires one enum argument');
				$argument = $call->arguments[0];
				$this->value($argument, $initializing);
				$type = $argument->type_def();
				if (!($type instanceof enum_type_definition)) throw new diagnostic($argument->source(), $argument->byte_start(), $builtin . ' requires an enum');
				$use = $builtin === 'enum_name' ? dependency_use::enum_name
					: ($type->backing() === type_string::instance() ? dependency_use::enum_value : null);
				$this->dependencies?->watch($this->symbols->entry($type->declaration->symbol_key()), $this->owner, $use?->value ?? 0);
				$call->settle_runtime($use === null ? $type->value_function() : $type->helper_function($use));
				return;
			}
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

	/** Read the called type attached to this prepared static body. */
	private function called_record(ast_node $site, string $operation): ast_record_declaration
	{
		return $this->owner->parent->instance?->called->declaration()
			?? throw new diagnostic($site->source(), $site->byte_start(), $operation . ' requires a called type in a static method');
	}

	/** Exact type-qualified calls require a method; ambiguous namespace paths may continue lookup. */
	private function qualified_call(ast_call_expression $call, string $qualified, ?string $initializing, bool $required): bool
	{
		$late = $call->qualifier === call_qualifier::called_type;
		if ($late) {
			$record = $this->called_record($call, 'Late-static call');
			$name = $call->name;
		} else {
			$separator = strrpos($qualified, '::');
			$written_type = substr($qualified, 0, $separator);
			$type_symbol = $this->symbols->entry($call->lexical_root()->type_key($written_type));
			$this->dependencies?->watch($type_symbol, $this->owner);
			$record = $type_symbol->declaration();
			$name = substr($qualified, $separator + 2);
			if ($record === null) {
				$core = Type_Resolution::core_class($call->lexical_root(), $written_type);
				if ($core !== null && $name === $core->factory->name()) {
					$this->runtime_call($call, $core->factory);
					return true;
				}
			}
		}
		if ($record instanceof ast_record_declaration) {
			$symbol = $record->definition->method_symbol($name, $this->symbols, $this->owner, $this->dependencies);
		}
		else {
			$symbol = $this->symbols->entry('method:' . Source_Names::resolve($qualified, $this->owner->scope->namespace));
			$this->dependencies?->watch($symbol, $this->owner);
		}
		$method = $symbol?->declaration();
		if (!($method instanceof ast_function_declaration)) {
			if ($late) throw new diagnostic($call->source(), $call->byte_start(), 'Unknown static method: ' . $record->name() . '::' . $name);
			if ($record instanceof ast_record_declaration && $call->source()->flavor === flavor::cpp) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Unknown type-qualified method: ' . $qualified);
			}
			if ($required) throw new diagnostic($call->source(), $call->byte_start(), 'Unknown static method: ' . $qualified);
			return false;
		}
		if (!$method->is_static && !$late && $record instanceof ast_record_declaration && $call->source()->flavor === flavor::cpp) {
			$this->ancestor_call($call, $name, $record->definition, $initializing, $symbol);
			return true;
		}
		if (!$method->is_static) throw new diagnostic($call->source(), $call->byte_start(), 'Instance method requires a receiver: ' . $method->name());
		$this->source_call($call, $symbol, $method, $initializing, called: $record->definition);
		return true;
	}

	/** Watch the lexical base selection even when the member lookup fails. */
	private function lexical_base(ast_expression $syntax, string $operation): record_type_definition
	{
		$context = $this->owner->parent->parent;
		if (!($context instanceof ast_record_declaration)) throw new diagnostic($syntax->source(), $syntax->byte_start(), $operation . ' requires a declaring record');
		$this->dependencies?->watch($this->symbols->entry($context->symbol_key()), $this->owner);
		return $context->definition->base() ?? throw new diagnostic($syntax->source(), $syntax->byte_start(), $operation . ' requires a base type');
	}

	/** Select the ancestor declaration before applying instance-specific receiver checks. */
	private function ancestor_call(ast_call_expression $call, string $name, ?record_type_definition $lookup, ?string $initializing, ?symbol_entry $selected = null): void
	{
		if ($lookup === null) {
			$lookup = $this->lexical_base($call, 'Ancestor call');
		}
		else {
			$context = $this->owner->parent->parent;
			if (!($context instanceof ast_record_declaration)) throw new diagnostic($call->source(), $call->byte_start(), 'Instance method requires a receiver: ancestor call has no current instance');
			for ($type = $context->definition; $type !== null; $type = $type->base()) {
				$this->dependencies?->watch($this->symbols->entry($type->declaration->symbol_key()), $this->owner);
			}
			if ($lookup === $context->definition || !$context->definition->derives_from($lookup)) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Instance method requires a receiver: ancestor qualification must name a strict base type');
			}
		}
		$symbol = $selected ?? $lookup->method_symbol($name, $this->symbols, $this->owner, $this->dependencies);
		$method = $symbol?->declaration();
		if (!($method instanceof ast_function_declaration) || $method->is_constructor()) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Unknown ancestor method: ' . $name);
		}
		if ($method->is_static) {
			$this->source_call($call, $symbol, $method, $initializing, called: $lookup);
			return;
		}
		if ($this->owner->parent->is_static) throw new diagnostic($call->source(), $call->byte_start(), 'Instance method requires a receiver: ancestor call has no current instance');
		if ($this->initializing_base) throw new diagnostic($call->source(), $call->byte_start(), 'Current instance is unavailable during base initialization');
		if ($method->is_abstract) throw new diagnostic($call->source(), $call->byte_start(), 'Cannot call an abstract ancestor implementation');
		$this->source_call($call, $symbol, $method, $initializing, true);
	}

	/** Standalone preparation has no subscription to retain. */
	private function access_watcher(): ?dependency_watcher
	{
		return $this->dependencies === null ? null : $this->owner;
	}

	/** Shared positional admission, conversions and reference invalidation for source calls. */
	private function source_call(ast_call_expression $call, symbol_entry $symbol, ast_function_declaration $function, ?string $initializing, bool $ancestor_dispatch = false, ?record_type_definition $called = null): void
	{
		if ($function->is_destructor()) throw new diagnostic($call->source(), $call->byte_start(), 'Explicit destructor invocation is forbidden');
		$context = $this->owner->parent->parent;
		$result = $function->return_type_def();
		if ($result instanceof record_type_definition) $this->require_inline_cleanup($result, $call);
		$receiver = null;
		if ($function->is_protected && !$function->is_static) {
			$receiver = $ancestor_dispatch && $context instanceof ast_record_declaration ? $context->definition : $call->receiver?->type_def();
			if ($receiver instanceof shared_record_type_definition) $receiver = $receiver->payload;
		}
		$function->require_access($context, $call, $receiver instanceof record_type_definition ? $receiver : null, $this->access_watcher());
		$conversions = $this->bind_arguments($call, $function, $initializing);
		$instance = null;
		if ($called !== null && $this->dependencies !== null) {
			$forwarded = $call->qualifier === call_qualifier::ordinary ? null : $this->owner->parent->instance?->called;
			$instance = $this->dependencies->static_call($this->owner, $symbol,
				$forwarded ?? $this->symbols->entry($called->declaration->symbol_key()));
		}
		$call->settle_function($symbol, $conversions, $ancestor_dispatch, $instance);
	}

	/** Inline and shared construction use the same published signature and argument checks. */
	private function construction(ast_construction_expression $site, type_definition $type, ?string $initializing): void
	{
		$payload = $type instanceof shared_record_type_definition && $site instanceof ast_new_expression ? $type->payload : $type;
		if ($payload instanceof record_type_definition && !($site instanceof ast_base_initialization)) $payload->require_complete_value($site, $site instanceof ast_new_expression);
		$constructor = $payload instanceof record_type_definition ? $payload->constructor() : null;
		if ($constructor === null && $site->arguments !== []) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Only zero-argument initialization is available for ' . $type->name);
		}
		if ($constructor !== null) {
			$this->dependencies?->watch($this->symbols->entry($constructor->symbol_key()), $this->owner);
			$constructor->require_access($this->owner->parent->parent, $site, watcher: $this->access_watcher());
		}
		$site->settle_construction($type, $constructor === null ? [] : $this->bind_arguments($site, $constructor, $initializing));
	}

	/** Base arguments use the ordinary call boundary, before the current instance becomes available. */
	private function base_initialization(ast_base_initialization $site): void
	{
		$record = $this->owner->parent->parent;
		$base = $record->definition->base();
		$this->dependencies?->watch($this->symbols->entry($record->symbol_key()), $this->owner);
		if ($base === null) {
			if ($site->explicit) throw new diagnostic($site->source(), $site->byte_start(), 'Base initialization requires a base');
			// A C++ colon edge can resolve to an interface: omitted base initialization then does no work.
			$site->settle_construction(type_void::instance(), []);
			return;
		}
		if ($site->target !== null) {
			$target = Type_Resolution::named($site->target, $this->symbols, $record->namespace, $this->owner, $this->dependencies);
			if ($target instanceof shared_record_type_definition && $target->required) $target = $target->payload;
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

	/** Value temporaries and inferred copies watch the payload even when its name is absent at the call site. */
	private function require_inline_cleanup(record_type_definition $type, ast_node $site): void
	{
		$this->dependencies?->watch($this->symbols->entry($type->declaration->symbol_key()), $this->owner);
		$type->require_destruction($site);
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
			if ($parameter->mode === passing_mode::reference) {
				$this->bind_reference($argument, $parameter->type_def(), $writes);
				$conversions[] = value_conversion::identity;
			}
			else {
				$type = $parameter->type_def();
				if ($type instanceof record_type_definition) $this->require_inline_cleanup($type, $argument);
				$conversions[] = $type->prepare_conversion($argument, conversion_context::typed_destination);
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
