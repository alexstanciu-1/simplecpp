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
	/** PROPERTIES */
	/**
	 * Source-order visibility index, not a declaration collector; native non-owning declaration references.
	 *
	 * @var array<string,ast_local_binding>[]
	 */
	private array $local_frames /** list<array<string,ast_local_binding>> */ = [];
	/** Initializer facts are unavailable after a branch barrier unless re-established by a local write. */
	/** Definitely initialized fresh payloads by binding identity. @var bool[] */
	private array $take_ready = [];
	/** Status storage ID to its payload. A true bool or an absent Exception proves initialization. @var int[] */
	private array $take_status = [];
	/** Status slots reachable through another name. @var bool[] */
	private array $take_borrowed = [];
	/** Allocated only for a body that contains a native retaining call. */
	private ?Bound_Slots $bound_slots = null;
	private bool $unknown_initializers = false;
	private ?ast_lambda_expression $enclosing_lambda = null;
	private bool $borrows_receiver = false;
	/** Conservative sum across lexical scopes; reset at every body preparation. */
	private int $array_bytes /** uint32 */ = 0;
	private bool $has_lambda_local = false;
	/**
	 * Latest completed writes; null means unknown after a mutable borrow or update, absent means original initializer.
	 * Transient non-owning body-local index; no alias graph or callee effects are retained.
	 * @var (ast_assignment_statement|ast_local_declaration|null)[]
	 */
	private array $latest_writes /** array<uint32,ast_assignment_statement|ast_local_declaration|null> */ = [];
	/** Dominating test or remaining union arm by local storage ID. @var (ast_object_access_expression|shared_record_type_definition)[] */
	private array $guards /** array<uint32,ast_object_access_expression|shared_record_type_definition> */ = [];
	/** Guarded argument reads in the current expression, indexed by slot with the first read position. @var int[] */
	private array $guard_reads /** array<uint32,uint32> */ = [];
	private int $statement_visits /** uint32 */ = 0;
	private int $expression_visits /** uint32 */ = 0;
	private int $name_lookups /** uint32 */ = 0;
	/** Native non-owning innermost active loop within this callable. */
	private ?ast_repeating_statement $loop = null;
	/** Active writable collection loops, keyed by canonical local storage ID in this callable. @var array<int,true> */
	private array $iterated_collections /** array<uint32,bool> */ = [];
	/** Reachable nonlocal exits, keyed by destination token and direction. @var bool[] */
	private array $outward = [];
	/** Current finalizer boundary for lexical transfer checks. */
	private ?ast_block $finalizer_block = null;
	/** Nearest active switch; the later opening token selects the innermost break target. */
	private ?ast_switch_statement $switch = null;
	/** Readonly constructor states joined at each destination switch. @var array<int,int>[] */
	private array $switch_break_states = [];
	/** Inherited by nested statements inside a finalizer. */
	private bool $in_finalizer = false;
	/** Permission is lexical and restored before each sibling finally region. */
	private bool $in_catch = false;
	private int $suspension_count = 0;
	/** Traversal state: current-instance access is unavailable while preparing base arguments. */
	private bool $initializing_base = false;
	/** Transient initialization proof, owned only while preparing a constructor. */
	private ?Constructor_Initialization $constructor_initialization = null;
	/** Native non-owning context for this body's variable lookup and dependency registration. */
	private scope $symbols;
	/** Native non-owning owner of the facts being prepared. */
	private ast_function_body $owner;
	/** Native non-owning; null when the caller does not collect usage relations. */
	private ?Dependency_Watches $dependencies = null;

	/** METHODS */
	/** Standalone clients use the same declaration-before-body orchestration as multifile programs. */
	public function prepare(ast_file_node $file, entry_target $target): prepared_entry
	{
		return Program_Preparation::prepare('entry', ['entry' => $file], $target)->entry;
	}

	/** Prepare only the selected executable body, consuming already settled callable contracts. */
	public function entry(ast_file_node $file, entry_target $target, scope $symbols, ?Dependency_Watches $dependencies = null): void
	{
		$main = $file->main();
		if ($main === null) {
			return;
		}
		$this->function_body($main->body(), $symbols, $target, $dependencies);
	}

	/** Reset one body, reject duplicate locals, and settle its facts and traversal statistics. */
	public function function_body(ast_function_body $syntax, scope $symbols, ?entry_target $target = null, ?Dependency_Watches $dependencies = null): void
	{
		$this->symbols = $symbols;
		$this->owner = $syntax;
		$this->take_ready = $this->take_status = $this->take_borrowed = [];
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
			if ($this->bound_slots !== null) $this->bound_slots->verify($syntax);
		} finally {
			$this->bound_slots = null;
			$this->guards = [];
			$this->guard_reads = [];
			$this->constructor_initialization = null;
		}
		Scoped_Byte_View::prepare($syntax);
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
		$this->array_bytes = 0;
		$this->has_lambda_local = false;
		$this->enclosing_lambda = null;
		$this->borrows_receiver = false;
		$this->guards = [];
		$this->unknown_initializers = false;
		$this->latest_writes = [];
		/** @var ast_function_declaration $function */
		$function = $owner->parent;
		$this->constructor_initialization = $function->is_constructor() && $function->parent instanceof ast_record_declaration
			? new Constructor_Initialization($function->parent) : null;
		$view = null;
		foreach ($function->parameters as $parameter) {
			if ($parameter->type_def() instanceof record_type_definition && $parameter->mode === passing_mode::value) {
				$this->dependencies?->watch($this->symbols->entry($parameter->type_def()->declaration->symbol_key()), $owner);
				$this->reserve_array_bytes($parameter->type_def()->array_bytes(), $parameter);
			}
			if ($parameter->type_def() instanceof callback_view_type) $view = $parameter;
			$this->local_frames[0][$parameter->declaration_key()] = $parameter;
		}
		$this->statement_visits = 0;
		$this->expression_visits = 0;
		$this->name_lookups = 0;
		$this->loop = null;
		$this->switch_break_states = [];
		$this->outward = [];
		$completion = $this->sequence($syntax, $source, $target, $function, $view);
		if (($completion & statement_completion::fallthrough) !== 0) $this->constructor_initialization?->complete($owner);
		if (($target === null) && ($completion & statement_completion::fallthrough) !== 0 && ($function->return_type_def() !== type_void::instance())
			&& !($function->return_type_def() instanceof generator_type_definition)) {
			throw new diagnostic($source, $end, 'Non-void function requires a value return');
		}
		$owner->implicit_zero_exit = $target !== null && ($completion & statement_completion::fallthrough) !== 0;
		if ($owner->implicit_zero_exit && $source->flavor !== flavor::php) {
			throw new diagnostic($source, $end, 'Entry slice requires an explicit value return');
		}
	}

	/** One traversal per block; completion is transient and never a copied prepared statement tree.
	 * @param ast_statement[] $syntax
	 */
	private function sequence(array $syntax /** list<ast_statement> */, source_file $source,
		?entry_target $target, statement_return_target $function, ?ast_parameter_declaration $view = null): int /** uint8 */
	{
		$generator = $function->return_type_def();
		$completion = statement_completion::fallthrough;
		foreach ($syntax as $statement)
		{
			$this->statement_visits++;
			$this->guard_reads = [];
			if (($completion & statement_completion::fallthrough) === 0) {
				throw new diagnostic($statement->source(), $statement->byte_start(),
					$completion === statement_completion::returns ? 'Statements after return are outside the entry slice'
					: 'Statements after unconditional control transfer are not supported');
			}
			if ($view !== null && !($statement instanceof ast_call_statement || $statement instanceof ast_if_statement)) {
				throw new diagnostic($source, $statement->byte_start(), 'A callback view consumer permits only direct invocation or forwarding, optionally guarded by its bool parameter');
			}
			if ($generator instanceof generator_type_definition
				&& !($statement instanceof ast_yield_statement || $statement instanceof ast_echo_statement
					|| $statement instanceof ast_call_statement || ($statement instanceof ast_discard_statement && $statement->value instanceof ast_nullsafe_member) || $statement instanceof ast_return_statement
					|| $statement instanceof ast_local_declaration || $statement instanceof ast_storage_write
					|| $statement instanceof ast_block_statement || $statement instanceof ast_foreach_statement || $statement instanceof ast_if_statement || $statement instanceof ast_loop_statement
					|| $statement instanceof ast_break_statement || $statement instanceof ast_continue_statement)) {
				throw new diagnostic($source, $statement->byte_start(), 'Yield slice supports locals, writes, conditionals, loops, yields, echo, calls and bare return only');
			}
			$next = statement_completion::fallthrough;
			$barrier = $statement instanceof ast_repeating_statement || $statement instanceof ast_try_statement || $statement instanceof ast_switch_statement;
			if ($barrier) {
				$outer_ready = $this->take_ready;
				$this->take_status = [];
			}
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
				$this->value($statement->value, destination: $generator->iteration_value_type());
				$statement->conversion = $generator->iteration_value_type()->prepare_conversion($statement->value, conversion_context::typed_destination);
				$this->forget_values();
			}
			elseif ($statement instanceof ast_base_statement) {
				$this->base_initialization($statement->value);
			}
			elseif ($statement instanceof ast_loop_transfer) {
				$next = $this->transfer($statement, $source, $generator instanceof generator_type_definition);
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
			elseif ($statement instanceof ast_rethrow_statement) {
				if (!$this->in_catch) throw new diagnostic($source, $statement->byte_start(), 'Bare throw requires an enclosing catch body');
				$next = statement_completion::throws;
			}
			elseif ($statement instanceof ast_throw_statement) {
				$this->prepare_throw($statement->operation);
				$next = statement_completion::throws;
			}
			elseif ($statement instanceof ast_try_statement) {
				$next = $this->try_statement($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_switch_statement) {
				$next = $this->switch_statement($statement, $source, $target, $function);
			}
			elseif ($statement instanceof ast_block_statement) {
				$outer_guards = $this->guards;
				$this->local_frames[] = [];
				try {
					$next = $this->sequence($statement->body->statements, $source, $target, $function);
				} finally {
					array_pop($this->local_frames);
				}
				// A guard's native pointer is scoped to its declaration. Keep only surviving outer proofs.
				foreach ($this->guards as $id => $guard) {
					if (($outer_guards[$id] ?? null) !== $guard) unset($this->guards[$id]);
				}
			}
			elseif ($statement instanceof ast_if_statement) {
				$next = $this->conditional($statement, $source, $target, $function, $view);
			}
			elseif ($statement instanceof ast_local_alias) {
				$this->local_alias($statement);
			}
			elseif ($statement instanceof ast_local_declaration) {
				$this->declaration($statement);
				if ($generator instanceof generator_type_definition && $statement->type_def() instanceof generator_type_definition) {
					throw new diagnostic($source, $statement->byte_start(), 'Nested cursor locals are deferred');
				}
			}
			elseif ($statement instanceof ast_field_assignment || $statement instanceof ast_property_compound) {
				$initializing_field = $this->writable_property($statement->target, $statement instanceof ast_field_assignment ? $statement : null);
				$type = $statement->target->type_def();
				$before = $this->suspension_count;
				if ($statement instanceof ast_field_assignment && $statement->target instanceof ast_field_expression && $statement->target->key_write_root !== null) {
					$this->collection_element($statement->value, $type, null);
					$statement->conversion = $type->prepare_conversion($statement->value, conversion_context::typed_destination);
				} else {
					$this->value($statement->value, destination: $statement instanceof ast_field_assignment ? $type : null);
					if ($statement instanceof ast_property_compound) {
						$statement->prepare_storage_compound($type);
						if (!$statement->simple_compound_rhs() || $type instanceof carrier_type_definition) $this->retain_member_place($statement->target);
					}
					else {
						if ($before !== $this->suspension_count && $statement->target instanceof ast_field_expression) $this->retain_member_place($statement->target->receiver);
						if ($type instanceof class_union_type_definition) $this->watch_union_source($statement->value);
						$statement->conversion = $type->prepare_conversion($statement->value, conversion_context::assignment);
					}
				}
				if ($initializing_field !== null) $this->constructor_initialization->states[$initializing_field->short_name] = 1;
			}
			elseif ($statement instanceof ast_index_compound) {
				$receiver = $statement->target->receiver;
				$this->prepare_index_mutation($statement);
				if (!($receiver->type_def() instanceof indexed_mutation_type)) {
					throw new diagnostic($receiver->source(), $receiver->byte_start(), 'Indexed compounds require a mutable collection');
				}
				$this->value($statement->value);
				$this->retain_member_place($receiver);
				$statement->prepare_storage_compound($statement->target->type_def());
			}
			elseif ($statement instanceof ast_index_assignment) {
				$binding = null;
				$boxed_root = null;
				$receiver = $statement->receiver;
				if ($receiver instanceof ast_index_expression) {
					$this->value($receiver, deep_write_path: true);
					$collection_root = $source->flavor === flavor::python ? null : ast_index_expression::nested_collection_root($receiver);
					if ($collection_root === null) {
						$boxed_root = ast_index_expression::boxed_path_root($receiver);
						$collection_root = $boxed_root;
					}
					$binding = $collection_root instanceof ast_name_expression ? $collection_root->local_binding() : null;
					if ($collection_root !== null) $this->require_index_write_available($collection_root);
					$root = $collection_root ?? $receiver;
					while ($root instanceof ast_index_expression) {
						$owner = $root->receiver->type_def();
						if (!($owner instanceof fixed_array_type) && !($source->flavor === flavor::python
							&& $owner instanceof boxed_type_definition && $owner->mutates_referenced_value())) {
							$places = $source->flavor === flavor::python ? 'inline fixed-array storage or a shared Python container'
								: 'inline fixed-array storage or a typed vector/hash path on local or instance-field storage';
							throw new diagnostic($root->source(), $root->byte_start(), 'Nested indexed write requires ' . $places);
						}
						$root = $root->receiver;
					}
					if (!($root instanceof ast_name_expression && $root->local_binding() !== null) && !($root instanceof ast_field_expression)) {
						throw new diagnostic($root->source(), $root->byte_start(), 'Nested indexed write requires local or field storage');
					}
					Readonly_Access::write($root, true);
				}
				elseif ($receiver instanceof ast_field_expression) {
					$this->writable_property($receiver, payload: true);
					$boxed_root = $receiver->key_write_root;
				} else {
					$this->value($receiver);
					$binding = $receiver->declaration();
					$this->require_index_write_available($receiver);
					Readonly_Access::write($receiver, true);
					if (!($binding instanceof ast_local_binding)
						&& !($binding instanceof ast_global_declaration && $binding->parent instanceof ast_record_declaration)) {
						throw new diagnostic($statement->source(), $statement->byte_start(), 'Keyed write requires writable local storage');
					}
				}
				$type = $statement->receiver->type_def();
				if (!($type instanceof index_write_type)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Keyed write requires a string, vector, hash or boxed container');
				}
				if (!($type instanceof vector_type_definition || $type instanceof hash_type_definition || $type instanceof segment_type || $type instanceof boxed_type_definition) && $source->flavor === flavor::python) {
					throw new diagnostic($source, $statement->byte_start(), 'Python indexed write requires a typed vector or hash, or a boxed collection');
				}
				$this->value($statement->index);
				$type->prepare_key($statement->index);
				$this->collection_element($statement->value, $type->write_type(), null);
				$this->retain_member_place($receiver);
				$statement->settle_write($type->write_type()->prepare_conversion($statement->value, conversion_context::typed_destination), $boxed_root);
				if ($binding instanceof ast_local_binding) $this->latest_writes[$binding->storage_id()] = null;
			}
			elseif ($statement instanceof ast_append_statement) {
				$boxed_root = null;
				if ($statement->receiver instanceof ast_field_expression) {
					$this->writable_property($statement->receiver, payload: true);
					$boxed_root = $statement->receiver->key_write_root;
				} else {
					$this->value($statement->receiver, deep_write_path: true);
					$root = $statement->receiver;
					if ($root instanceof ast_index_expression) {
						$boxed_root = ast_index_expression::boxed_path_root($root);
						$root = $boxed_root ?? ast_index_expression::nested_collection_root($root);
					}
					$binding = $root instanceof ast_name_expression ? $root->local_binding() : null;
					if ($binding === null && !($root instanceof ast_field_expression)) {
						throw new diagnostic($statement->source(), $statement->byte_start(), 'Append requires writable local storage');
					}
					$this->require_collection_available($statement, $binding);
					Readonly_Access::write($root, true);
				}
				$type = $statement->receiver->type_def();
				if (!($type instanceof appendable_collection_type)) {
					throw new diagnostic($statement->source(), $statement->byte_start(), 'Append requires an appendable collection');
				}
				$this->collection_element($statement->value, $type->write_type(), null);
				$this->retain_member_place($statement->receiver);
				$statement->settle_append($type->write_type()->prepare_conversion($statement->value, conversion_context::typed_destination), $boxed_root);
			}
			elseif ($statement instanceof ast_storage_write) {
				$this->write($statement);
			}
			elseif ($statement instanceof ast_echo_statement)
			{
				foreach ($statement->values as $value) {
					$this->guard_reads = [];
					$this->value($value);
					if (!$value->type_def()->supports_output($source->flavor)) {
						throw new diagnostic($value->source(), $value->byte_start(), $source->flavor === flavor::php
							? 'Echo requires a printable scalar; received ' . $value->type_def()->name
							: 'Echo currently supports only int64 and string values');
					}
				}
			}
			elseif ($statement instanceof ast_discard_statement) {
				$this->value($statement->value);
			}
			elseif ($statement instanceof ast_delay_statement) {
				if (!$function->is_async) throw new diagnostic($source, $statement->byte_start(), 'Async timer requires an async function');
				++$this->suspension_count;
				$this->value($statement->value);
				if ($statement->value->type_def() !== type_registry::integer()) throw new diagnostic($source, $statement->byte_start(), 'Async timer requires int64 milliseconds');
			}
			elseif ($statement instanceof ast_take_statement) {
				$this->value($statement->value);
			}
			elseif ($statement instanceof ast_call_statement) {
				$this->expression_visits++;
				$this->call($statement->value);
				if ($view !== null && !$this->view_call($statement->value, $view, $function->parameters[0])) {
					throw new diagnostic($source, $statement->byte_start(), 'A callback view consumer permits only direct invocation or forwarding of its parameters');
				}
			}
			elseif ($statement instanceof ast_return_statement)
			{
				if ($this->in_finalizer) {
					throw new diagnostic($source, $statement->byte_start(), 'Return cannot leave a finally block');
				}
				$this->constructor_initialization?->complete($statement);
				if ($function->reference_return_origin !== null) {
					$origin = $function->parameters[$function->reference_return_origin];
					$this->reference_return($statement->value, $origin, $statement);
					$statement->settle_conversion(value_conversion::identity);
				}
				elseif ($target === null) {
					$return_type = $function->return_type_def();
					if ($statement->value === null) {
						if ($return_type !== type_void::instance() && !($return_type instanceof generator_type_definition)) {
							throw new diagnostic($source, $statement->byte_start(), 'Non-void function requires a value return');
						}
					} elseif ($return_type instanceof generator_type_definition) {
						throw new diagnostic($source, $statement->byte_start(), 'Void function cannot return a value');
					} else {
						$statement->settle_conversion($this->return_conversion($statement->value, $return_type));
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
							$target, $statement->value)->value);
				}
				$next = statement_completion::returns;
			}
			else {
				throw new \LogicException('Unhandled statement preparation');
			}
			if ($barrier) {
				$this->take_ready = $outer_ready;
				$this->take_status = [];
			}
			$completion = ($completion & ~statement_completion::fallthrough) | $next;
		}
		return $completion;
	}

	/** Compose protected exits with the independent finalizer, whose abrupt exits replace pending exits. */
	private function try_statement(ast_try_statement $syntax, source_file $source, ?entry_target $target,
		statement_return_target $function): int /** uint8 */
	{
		if (array_filter($this->constructor_initialization?->states ?? [], static fn(int $state): bool => $state !== 1) !== []) {
			throw new diagnostic($source, $syntax->byte_start(), 'Try during field initialization is deferred');
		}
		$outer_finalizer = $this->finalizer_block;
		$in_finalizer = $this->in_finalizer;
		$outer_catch = $this->in_catch;
		$prior = $this->outward;
		if ($syntax->finalizer !== null) $this->outward = [];
		try {
			$completion = $this->lexical_block($syntax->body, $target, $function);
			$seen = [];
			$before_handlers = $this->suspension_count;
			foreach ($syntax->handlers as $handler) {
				$caught = Type_Resolution::local($handler->type, $this->symbols, $this->owner, $this->dependencies);
				$this->require_exception($caught, $handler->type, true);
				if (count($syntax->handlers) > 1) {
					$this->check_catch_order($handler, $caught, $seen);
					$seen[spl_object_id($caught)] = true;
				}
				$handler->binding->settle_type($caught);
				$this->in_catch = true;
				$completion |= $this->lexical_block($handler->body, $target, $function, $handler->binding);
			}
			$this->in_catch = $outer_catch;
			$syntax->suspending_handlers = $before_handlers !== $this->suspension_count;
			$syntax->protected_completion = $completion;
			if ($syntax->finalizer !== null) {
				if ($function->returns_reference && ($completion & statement_completion::returns)) {
					throw new diagnostic($source, $syntax->byte_start(), 'Reference returns across finally are deferred');
				}
				if ($this->outward !== []) $syntax->protected_completion |= statement_completion::numbered;
				if ($completion & statement_completion::breaks) $syntax->break_target = $this->break_target();
				if ($completion & statement_completion::continues) $syntax->continue_target = $this->loop;
				$this->finalizer_block = $syntax->finalizer;
				$this->in_finalizer = true;
				$final = $this->lexical_block($syntax->finalizer, $target, $function);
				if ($final & statement_completion::fallthrough) {
					if ($this->outward !== []) $completion |= statement_completion::numbered;
					$this->outward += $prior;
				} else $this->outward = $prior;
				$completion = ($final & ~statement_completion::fallthrough)
					| (($final & statement_completion::fallthrough) !== 0 ? $completion : 0);
			}
			$syntax->completion = $completion;
			return $completion & ~statement_completion::numbered;
		} finally {
			$this->finalizer_block = $outer_finalizer;
			$this->in_finalizer = $in_finalizer;
			$this->in_catch = $outer_catch;
		}
	}

	/** A previous catch of this type, an ancestor or the runtime root covers the entire arm. @param bool[] $seen */
	private function check_catch_order(ast_catch_clause $handler, type_definition $caught, array $seen /** array<int,bool> */): void
	{
		if ($seen === []) return;
		$shadowed = isset($seen[spl_object_id(type_registry::exception())]);
		for ($record = $caught instanceof shared_record_type_definition ? $caught->payload : null;
			!$shadowed && $record !== null; $record = $record->base()) {
			$shadowed = isset($seen[spl_object_id($record->required)]);
		}
		if ($shadowed) throw new diagnostic($handler->source(), $handler->byte_start(), 'Catch is shadowed by an earlier handler: ' . $caught->name);
	}

	/** Resolve one destination. Nonlocal completion becomes visible when its target is reached. */
	private function transfer(ast_loop_transfer $statement, source_file $source, bool $generator): int
	{
		$destination = $statement instanceof ast_break_statement ? $this->break_target() : $this->loop;
		$nonlocal = false;
		if ($statement->numbered()) {
			if ($generator) throw new diagnostic($source, $statement->byte_start(), 'Numbered exits in yield producers are deferred');
			$depth = $statement->depth();
			$nonlocal = $depth > 1;
			for ($node = $statement->parent; !($node instanceof ast_function_body); $node = $node->parent) {
				if ($node instanceof ast_repeating_statement || ($statement instanceof ast_break_statement && $node instanceof ast_switch_statement)) {
					if (--$depth === 0) {
						$destination = $node;
						break;
					}
				}
			}
			if ($depth !== 0) throw new diagnostic($source, $statement->byte_start(), 'Exit depth exceeds enclosing eligible targets');
		}
		if ($destination === null) {
			throw new diagnostic($statement->source(), $statement->byte_start(), 'Break/continue requires an enclosing loop or, for break, a switch');
		}
		if ($this->finalizer_block !== null && $destination->start < $this->finalizer_block->start) {
			throw new diagnostic($source, $statement->byte_start(), 'Break/continue cannot leave a finally block');
		}
		if ($destination instanceof ast_switch_statement && $this->constructor_initialization !== null) {
			$key = $destination->start;
			$this->switch_break_states[$key] = self::join_initialization($this->switch_break_states[$key] ?? null, $this->constructor_initialization->states);
		}
		$statement->target = $destination;
		$next = $statement instanceof ast_break_statement ? statement_completion::breaks : statement_completion::continues;
		if ($nonlocal || ($statement instanceof ast_break_statement && $destination instanceof ast_while_else_statement)) {
			$this->outward[2 * $destination->start + (int)($statement instanceof ast_continue_statement)] = true;
			return 0;
		}
		return $next;
	}

	/** Consume only the nonlocal transfers arriving at this lexical destination. */
	private function arriving(ast_statement $target): int
	{
		$key = 2 * $target->start;
		$flags = (isset($this->outward[$key]) ? statement_completion::breaks : 0)
			| (isset($this->outward[$key + 1]) ? statement_completion::continues : 0);
		unset($this->outward[$key], $this->outward[$key + 1]);
		return $flags;
	}

	private function break_target(): ast_repeating_statement|ast_switch_statement|null
	{
		if ($this->switch !== null && ($this->loop === null || $this->switch->start > $this->loop->start)) return $this->switch;
		return $this->loop;
	}

	/** Prepare the switch labels and join initialization facts from reachable exits. */
	private function switch_statement(ast_switch_statement $syntax, source_file $source, ?entry_target $target,
		statement_return_target $function): int
	{
		$this->value($syntax->subject);
		$type = $syntax->subject->type_def()->dispatch_label_type();
		$dispatch = $type->constant_dispatch();
		if ($dispatch === null) throw new diagnostic($source, $syntax->byte_start(), 'Switch subject requires constant-label dispatch');
		$outer = $this->switch;
		$this->switch = $syntax;
		$entry = $this->constructor_initialization?->states;
		$seen = [];
		$default = false;
		$exits = 0;
		$last = statement_completion::fallthrough;
		try {
			foreach ($syntax->arms as $arm) {
				$this->forget_values();
				if ($arm->condition !== null) {
					$this->value($arm->condition);
					$this->case_label($arm->condition, $type, $dispatch, $seen, 'Switch');
				} else {
					if ($default) throw new diagnostic($source, $arm->byte_start(), 'Duplicate switch default');
					$default = true;
				}
				if ($entry !== null) {
					$this->constructor_initialization->states = $last & statement_completion::fallthrough
						? self::join_initialization($entry, $this->constructor_initialization->states) : $entry;
				}
				$last = $this->lexical_block($arm->body, $target, $function);
				$arm->falls_through = (bool)($last & statement_completion::fallthrough);
				$exits |= $last & ~statement_completion::fallthrough;
			}
			if ($entry !== null) {
				$after = $this->switch_break_states[$syntax->start] ?? null;
				if (!$default) $after = self::join_initialization($after, $entry);
				if ($last & statement_completion::fallthrough) $after = self::join_initialization($after, $this->constructor_initialization->states);
				$this->constructor_initialization->states = $after ?? $entry;
			}
		} finally {
			if ($entry !== null) unset($this->switch_break_states[$syntax->start]);
			$this->switch = $outer;
		}
		$this->forget_values();
		if ($this->outward !== []) $exits |= $this->arriving($syntax);
		$after = !$default || ($last & statement_completion::fallthrough) || ($exits & statement_completion::breaks);
		return ($exits & ~statement_completion::breaks) | ($after ? statement_completion::fallthrough : 0);
	}

	/**
	 * Check label type and uniqueness against the switch subject.
	 * @param array<int|string,bool> $seen
	 */
	private function case_label(ast_expression $label, type_definition $type, constant_dispatch $dispatch, array &$seen, string $construct): void
	{
		if ($label->type_def() !== $type) throw new diagnostic($label->source(), $label->byte_start(), $construct . ' label type must equal subject type');
		if ($dispatch === constant_dispatch::integer_switch) {
			$integer = constant_dispatch::integer_label($label) ?? throw $this->invalid_case_label($label, $construct);
			$duplicate = isset($seen[$integer]);
			$seen[$integer] = true;
		} else {
			$key = match ($dispatch) {
				constant_dispatch::enum_switch => constant_dispatch::enum_case_name($label),
				constant_dispatch::byte_equality => constant_dispatch::string_label_bytes($label),
			} ?? throw $this->invalid_case_label($label, $construct);
			$duplicate = isset($seen[$key]);
			$seen[$key] = true;
		}
		if ($duplicate) throw new diagnostic($label->source(), $label->byte_start(), 'Duplicate ' . strtolower($construct) . ' label');
	}

	private function invalid_case_label(ast_expression $label, string $construct): diagnostic
	{
		return new diagnostic($label->source(), $label->byte_start(), $construct . ' label requires a literal, named constant or enum case');
	}

	/** Prepare a separate lexical frame; facts from its writes are forgotten on exit. */
	private function lexical_block(ast_block $body, ?entry_target $target, statement_return_target $function,
		?ast_produced_binding $binding = null): int /** uint8 */
	{
		$ready = $this->take_ready;
		$this->local_frames[] = $binding === null ? [] : [$binding->declaration_key() => $binding];
		try {
			return $this->sequence($body->statements, $body->source(), $target, $function);
		} finally {
			array_pop($this->local_frames);
			$this->take_ready = $ready;
			$this->take_status = [];
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
		$this->guards = [];
	}

	/** A forwarding consumer passes only its own prepared bindings, in the callee's parameter slots. */
	private function view_call(ast_call_expression $call, ast_parameter_declaration $view, ast_parameter_declaration $first): bool
	{
		if ($call->callable_binding() === $view && $call->arguments === []) return true;
		if ($call->qualifier !== call_qualifier::ordinary || $call->receiver !== null || $call->member_separator !== null
			|| !$call->has_direct_signature() || !(($callee = $call->function_def()) instanceof ast_function_declaration)) return false;
		$view_slot = count($callee->parameters) - 1;
		if (!(($callee->parameters[$view_slot] ?? null)?->type_def() instanceof callback_view_type)) return false;
		foreach ($call->arguments as $index => $argument) {
			$slot = $call->named->slots[$index] ?? $index;
			if ($argument::class !== ast_name_expression::class || !$argument->refers_to($slot === $view_slot ? $view : $first)) return false;
		}
		return true;
	}

	/** Prepare all alternatives once, with lexical frames and conservative value barriers at alternatives and join. */
	private function conditional(ast_if_statement $statement, source_file $source, ?entry_target $target,
		statement_return_target $function, ?ast_parameter_declaration $view): int /** uint8 */
	{
		$completion = 0;
		$has_else = false;
		$before = $this->constructor_initialization?->states;
		$joined = null;
		$remaining = $this->guards;
		$surviving = $remaining;
		$remaining_ready = $this->take_ready;
		$remaining_status = $this->take_status;
		$ready_join = $status_join = null;
		$paired = count($statement->arms) === 2 && $statement->arms[1]->condition === null;
		foreach ($statement->arms as $arm) {
			if ($before !== null) $this->constructor_initialization->states = $before;
			$this->forget_values();
			$this->guards = $remaining;
			$this->take_ready = $remaining_ready;
			$this->take_status = $remaining_status;
			$guard = null;
			if ($arm->condition !== null) {
				$this->guard_reads = [];
				$this->condition($arm->condition);
				if ($view !== null && ($arm->condition::class !== ast_name_expression::class
					|| $view === $function->parameters[0] || !$arm->condition->refers_to($function->parameters[0]))) {
					throw new diagnostic($source, $arm->condition->byte_start(), 'A callback view condition requires its by-value bool parameter');
				}
				$remaining = $this->guards;
				$remaining_status = $this->take_status;
				$remaining_ready = $this->take_ready;
				$this->take_paths($arm->condition, $remaining_ready);
				if ($surviving !== []) $surviving = array_intersect_key($surviving, $remaining);
				$guard = $this->branch_guard($arm->condition, $function, $paired);
				if ($guard !== null && $paired && ($union = $guard->operand->type_def()) instanceof class_union_type_definition) {
					$id = $guard->operand->local_def()->storage_id();
					$other = $union->remaining_arm($guard->target_type()->payload);
					$this->guards[$id] = $guard === $arm->condition ? $guard : $other;
					$remaining[$id] = $guard === $arm->condition ? $other : $guard;
				} elseif ($guard !== null && $guard === $arm->condition) {
					$this->guards[$guard->operand->local_def()->storage_id()] = $guard;
				}
			}
			else {
				$has_else = true;
			}
			$this->local_frames[] = [];
			$arm_completion = $this->sequence($arm->body->statements, $source, $target, $function, $view);
			array_pop($this->local_frames);
			$completion |= $arm_completion;
			if (($arm_completion & statement_completion::fallthrough) !== 0) {
				$ready_join = $ready_join === null ? $this->take_ready : array_intersect_key($ready_join, $this->take_ready);
				$status_join = $status_join === null ? $this->take_status : array_intersect_assoc($status_join, $this->take_status);
				foreach ($surviving as $id => $previous) {
					if (($this->guards[$id] ?? null) !== $previous) unset($surviving[$id]);
				}
			} elseif ($guard !== null && count($statement->arms) === 1
				&& ($arm_completion & ~(statement_completion::returns | statement_completion::throws)) === 0) {
				$id = $guard->operand->local_def()->storage_id();
				if ($arm->condition instanceof ast_not_expression) $surviving[$id] = $guard;
				elseif (($union = $guard->operand->type_def()) instanceof class_union_type_definition) {
					$surviving[$id] = $union->remaining_arm($guard->target_type()->payload);
				}
			}
			if ($before !== null && ($arm_completion & statement_completion::fallthrough) !== 0) {
				$states = $this->constructor_initialization->states;
				$joined = self::join_initialization($joined, $states);
			}
		}
		if ($before !== null) {
			if (!$has_else) {
				$joined = self::join_initialization($joined, $before);
			}
			$this->constructor_initialization->states = $joined ?? $before;
		}
		$this->forget_values();
		$this->guards = $surviving;
		$this->take_ready = $has_else ? ($ready_join ?? []) : ($ready_join === null ? $remaining_ready : array_intersect_key($ready_join, $remaining_ready));
		$this->take_status = $has_else ? ($status_join ?? []) : ($status_join === null ? $remaining_status : array_intersect_assoc($status_join, $remaining_status));
		return $completion | ($has_else ? 0 : statement_completion::fallthrough);
	}

	/** Add the payload proof only on the edge where extraction succeeded. @param bool[] $otherwise */
	private function take_paths(ast_expression $condition, array &$otherwise /** array<uint32,bool> */): void
	{
		$positive = true;
		$proof = $this->take_guard($condition, $positive);
		if ($proof === null) return;
		if ($positive) $this->take_ready[$proof] = true;
		else $otherwise[$proof] = true;
	}

	/** Follow a status test to the payload initialized on its success edge. */
	private function take_guard(ast_expression $condition, bool &$positive): ?int
	{
		if ($condition instanceof ast_not_expression) {
			$positive = !$positive;
			return $this->take_guard($condition->operand, $positive);
		}
		if ($condition instanceof ast_binary_expression && in_array($condition->operator, [binary_operator::identical, binary_operator::not_identical], true)) {
			$literal = $condition->right instanceof ast_keyword_expression ? $condition->right : $condition->left;
			if (!$literal instanceof ast_keyword_expression || !in_array($literal->word, ['false', 'true'], true)) return null;
			if (($literal->word === 'false') !== ($condition->operator === binary_operator::not_identical)) $positive = !$positive;
			$operand = $condition->left === $literal ? $condition->right : $condition->left;
			return $operand->type_def() === type_bool::instance() ? $this->take_guard($operand, $positive) : null;
		}
		if ($condition instanceof ast_take_expression) return spl_object_id($condition->slots[0]);
		if (!$condition instanceof ast_name_expression || ($local = $condition->local_binding()) === null) return null;
		$id = $local->storage_id();
		if (!isset($this->take_status[$id]) || isset($this->take_borrowed[$id])) return null;
		if ($local->type_def() instanceof type_exception) $positive = !$positive;
		return $this->take_status[$id];
	}

	/** Recognize an interface or union-arm test on stable storage. */
	private function branch_guard(ast_expression $condition, statement_return_target $function, bool $paired): ?ast_object_access_expression
	{
		$test = $condition instanceof ast_not_expression ? $condition->operand : $condition;
		if (!($test instanceof ast_object_access_expression) || !$test->test || !($test->operand instanceof ast_name_expression)
			|| $function->return_type_def() instanceof generator_type_definition) return null;
		$local = $test->operand->local_binding();
		if ($local instanceof ast_parameter_declaration && $local->type_def() instanceof class_union_type_definition) {
			return ($paired || $condition === $test) && $test->operand->type_def() === $local->type_def() ? $test : null;
		}
		if ($test->target_type()->payload->declaration->kind !== record_kind::interface_type) return null;
		if ($local instanceof ast_parameter_declaration && $local->mode === passing_mode::value) return $test;
		return $local instanceof ast_local_declaration ? $test : null;
	}

	/** Shared generated condition admission; preparation evaluates no runtime condition. */
	private function condition(ast_expression $condition, ?string $initializing = null): void
	{
		$this->value($condition, $initializing);
		$condition->type_def()->prepare_condition($condition);
	}

	/** Check an already prepared logical operand against its source flavor's admission rule. */
	private function logical_operand(ast_expression $operand): void
	{
		$type = $operand->type_def();
		if ($type !== type_bool::instance() && ($source = $operand->source())->flavor === flavor::python) {
			throw new diagnostic($source, $operand->byte_start(), 'Python logical operand requires bool');
		}
		$type->prepare_condition($operand);
	}

	/** Resolve produced types and ordinary assignments once; no iteration-dependent analysis or snapshots. */
	private function foreach_loop(ast_foreach_statement $statement, source_file $source, ?entry_target $target,
		statement_return_target $function): int /** uint8 */
	{
		$this->value($statement->source_value);
		$this->retain_member_place($statement->source_value);
		$source_type = $statement->source_value->type_def();
		$collection = null;
		if ($statement->value instanceof ast_writable_element) {
			$collection = $statement->source_value instanceof ast_name_expression ? $statement->source_value->local_binding() : null;
			if (!($collection instanceof ast_local_declaration || $collection instanceof ast_parameter_declaration)
				|| ast_writable_element::origin($collection) !== null || !($source_type instanceof writable_iteration_type) || $function->is_async || $function->return_type_def() instanceof generator_type_definition) {
				throw new diagnostic($source, $statement->byte_start(), 'Writable foreach requires a named local or parameter of vector or hash type in a synchronous body');
			}
			Readonly_Access::require_writable($statement->source_value, $collection);
			$this->require_collection_available($statement, $collection);
		}
		$segment = $source_type instanceof segment_type && $source_type->key !== null;
		$type = $source_type->iteration();
		$python = $source->flavor === flavor::python;
		$paired = $python && $statement->key !== null;
		$hash = ($python || $statement->requires_key) && ($type instanceof key_iterable_type || $segment);
		$statement->keys_only = $hash && !$paired;
		if (!$hash && ($paired || $statement->requires_key)) {
			throw new diagnostic($source, $statement->source_value->byte_start(),
				$paired ? 'Python items traversal requires a typed hash' : 'JS for-in requires a typed hash');
		}
		if ($python && !$hash && !($source_type instanceof vector_type_definition || $source_type instanceof boxed_type_definition)) {
			throw new diagnostic($source, $statement->source_value->byte_start(), 'Python for requires a typed vector or hash, or a boxed collection');
		}
		if ($type === null && !$segment) throw new diagnostic($source, $statement->source_value->byte_start(), 'Foreach requires an iterable collection');
		if ($function->is_async || $function->return_type_def() instanceof generator_type_definition) {
			$receiver = $statement->source_value;
			if ($source_type instanceof generator_type_definition) {
				throw new diagnostic($source, $receiver->byte_start(), 'Nested cursor traversal is deferred');
			}
			while ($receiver instanceof ast_field_expression) $receiver = $receiver->receiver;
			if (!(($receiver instanceof ast_name_expression && $receiver->local_binding() !== null)
				|| $receiver instanceof ast_call_expression || $receiver instanceof ast_construction_expression
				|| ($function->is_async && $receiver instanceof ast_await_expression))) {
				throw new diagnostic($source, $receiver->byte_start(), 'Suspended foreach requires local storage, a temporary or a retained shared field');
			}
		}
		if (($method = $type?->iteration_method()) !== null) {
			Readonly_Access::write($statement->source_value, true);
			$this->dependencies?->watch($this->symbols->entry($method->symbol_key()), $this->owner);
		}
		$destination = $this->find_local($statement->value->short_name);
		if ($destination !== null) {
			$this->require_take_ready($statement, $destination);
			$this->require_collection_available($statement, $destination);
			if ($collection !== null || ast_writable_element::origin($destination) !== null) {
				throw new diagnostic($source, $statement->value->byte_start(), 'Writable foreach requires a fresh element binding');
			}
		}
		if ($segment && $destination !== null) throw new diagnostic($source, $statement->value->byte_start(), 'Hash segment traversal requires a fresh loop binding');
		Readonly_Access::require_writable($statement->value, $destination);
		$output = $segment ? ($statement->keys_only ? $source_type->key : $source_type->element)
			: ($statement->keys_only ? $type->iteration_key_type() : $type->iteration_value_type());
		if ($output instanceof record_type_definition) {
			$this->dependencies?->watch($this->symbols->entry($output->declaration->symbol_key()), $this->owner);
			$this->reserve_array_bytes($output->array_bytes(), $statement->value);
		}
		$statement->value->settle_type($output);
		$statement->destination = $destination ?? $statement->value;
		$statement->conversion = $statement->destination->type_def()->prepare_conversion($statement->value,
			$destination === null ? conversion_context::inference : conversion_context::assignment);
		$this->forget_values();
		$this->local_frames[] = [];
		try {
			if ($destination === null) $this->local_frames[count($this->local_frames) - 1][$statement->value->short_name] = $statement->value;
			if ($statement->key !== null) {
				$key_type = $segment ? $source_type->key : $type->iteration_key_type();
				if ($key_type === null) throw new diagnostic($source, $statement->key->byte_start(), 'Foreach key binding is unavailable for this traversal');
				$statement->key->settle_type($key_type);
				$this->local_frames[count($this->local_frames) - 1][$statement->key->short_name] = $statement->key;
			}
			$this->local_frames[] = [];
			$outer_loop = $this->loop;
			$this->loop = $statement;
			if ($collection !== null) {
				$outer_collections = $this->iterated_collections;
				$this->guard_iteration_source($collection, $function);
			}
			try {
				$completion = $this->sequence($statement->body->statements, $source, $target, $function);
			}
			finally {
				if ($collection !== null) $this->iterated_collections = $outer_collections;
				$this->loop = $outer_loop;
				array_pop($this->local_frames);
			}
		}
		finally {
			array_pop($this->local_frames);
		}
		$this->forget_values();
		if ($this->outward !== []) $this->arriving($statement);
		return ($completion & (statement_completion::returns | statement_completion::throws)) | statement_completion::fallthrough;
	}

	/** Prepare initialization once in its own lexical frame, then reuse the repeating-region worker. */
	private function for_loop(ast_for_statement $statement, source_file $source, ?entry_target $target,
		statement_return_target $function): int /** uint8 */
	{
		$this->local_frames[] = [];
		try {
			$this->sequence($statement->initializers, $source, $target, $function);
			return $this->loop($statement, $source, $target, $function);
		}
		finally {
			array_pop($this->local_frames);
		}
	}

	/** Prepare each repeating region once with unknown incoming values; no iteration or fixed-point analysis. */
	private function loop(ast_loop_statement $statement, source_file $source, ?entry_target $target,
		statement_return_target $function): int /** uint8 */
	{
		$post_test = $statement instanceof ast_do_while_statement;
		$this->forget_values();
		if (!$post_test) {
			if ($statement instanceof ast_for_statement) {
				$last = array_key_last($statement->conditions);
				foreach ($statement->conditions as $i => $test) {
					$this->guard_reads = [];
					if ($i === $last) $this->condition($test);
					elseif ($test instanceof ast_call_expression) {
						$this->expression_visits++;
						$this->call($test);
					} else $this->value($test);
				}
			} else {
				$this->condition($statement->condition);
				// Entry to the body follows a hit. The loop barrier restores entry readiness at exit.
				if ($statement->condition instanceof ast_take_expression) $this->take_ready[spl_object_id($statement->condition->slots[0])] = true;
			}
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
			$this->sequence($statement->updates, $source, $target, $function);
			$this->forget_values();
		}
		if ($this->outward !== []) $completion |= $this->arriving($statement);
		// A post-test loop enters its body once; return and throw are terminal exits.
		$terminal = statement_completion::returns | statement_completion::throws;
		if ($statement instanceof ast_while_else_statement) {
			$before = $this->constructor_initialization?->states;
			$otherwise = $this->lexical_block($statement->otherwise, $target, $function);
			if ($completion & statement_completion::breaks) {
				if ($before !== null) {
					$this->constructor_initialization->states = ($otherwise & statement_completion::fallthrough)
						? self::join_initialization($before, $this->constructor_initialization->states) : $before;
				}
				$otherwise |= statement_completion::fallthrough;
			}
			return ($completion & $terminal) | $otherwise;
		}
		if ($statement instanceof ast_for_statement && !$statement->conditions && !($completion & statement_completion::breaks)) {
			return $completion & $terminal;
		}
		return $post_test && ($completion & ~$terminal) === 0 ? $completion
			: (($completion & $terminal) | statement_completion::fallthrough);
	}

	/** Establish a binding only after its initializer succeeds; collection grants no forward visibility. */
	private function reserve_array_bytes(int $bytes /** uint32 */, ast_node $site): void
	{
		if ($bytes > fixed_array_type::FRAME_LIMIT - $this->array_bytes) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Function fixed-array storage exceeds 64 KiB');
		}
		$this->array_bytes += $bytes;
	}

	/** A caller can supply the same slot through multiple reference parameters or lexical captures. */
	private function guard_iteration_source(ast_local_binding $source, statement_return_target $function): void
	{
		$this->iterated_collections[$source->storage_id()] = true;
		if (!self::borrows_mutably($source)) return;
		$inputs = $function instanceof ast_lambda_expression ? [...$function->parameters, ...$function->captures] : $function->parameters;
		foreach ($inputs as $peer) {
			if (self::borrows_mutably($peer) && $peer->type_def() === $source->type_def()) {
				$this->iterated_collections[$peer->storage_id()] = true;
			}
		}
	}

	/** A mutation through a value-copy row requires a path back to writable storage. */
	private function prepare_index_mutation(ast_index_compound|ast_index_update $statement): void
	{
		$receiver = $statement->target->receiver;
		$this->value($statement->target, deep_write_path: true);
		if ($receiver instanceof ast_field_expression) $this->check_indexed_field_owner($receiver);
		$statement->boxed_root = ast_index_expression::boxed_path_root($receiver);
		if ($receiver instanceof ast_index_expression && $receiver->type_def() instanceof boxed_type_definition
			&& $statement->boxed_root === null && $statement->source()->flavor !== flavor::python) {
			throw new diagnostic($receiver->source(), $receiver->byte_start(), 'Nested boxed update requires a local or instance-field boxed root');
		}
		$root = $statement->boxed_root ?? ast_index_expression::nested_collection_root($receiver);
		if ($root !== null) {
			$this->require_index_write_available($root);
			Readonly_Access::write($root, true);
			if ($root instanceof ast_name_expression) $this->latest_writes[$root->local_binding()->storage_id()] = null;
			return;
		}
		if ($receiver instanceof ast_index_expression && !$receiver->type_def()->mutates_referenced_value()
			&& $receiver->receiver->type_def() instanceof nested_index_storage_type) {
			throw new diagnostic($receiver->source(), $receiver->byte_start(), 'Nested indexed update requires a writable local or instance-field vector/hash path');
		}
		$this->require_index_write_available($receiver);
		Readonly_Access::write($receiver, true);
	}

	/** A separate hash write could insert a key while the loop holds a selection. */
	private function require_index_write_available(ast_expression $receiver): void
	{
		if ($receiver instanceof ast_name_expression && $receiver->type_def() instanceof hash_type_definition) {
			$this->require_collection_available($receiver, $receiver->local_binding());
		}
	}

	/** Aliases resolve to the same storage as the original collection. */
	private function require_collection_available(ast_node $site, ?ast_local_binding $binding): void
	{
		if ($binding !== null && isset($this->iterated_collections[$binding->storage_id()])) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Cannot structurally mutate or borrow the collection during writable foreach');
		}
	}

	/** Only a bare variable can name lexical storage. Qualified data can share its final spelling. */
	private function lexical_reference_slot(?ast_expression $value): ?ast_local_binding
	{
		return $value !== null && $value::class === ast_name_expression::class && !$value->constant_only
			? $this->find_local($value->name) : null;
	}

	/** Publish a second lexical name for already-live storage without reading or copying it. */
	private function local_alias(ast_local_alias $declaration): void
	{
		$name = $declaration->declaration_key();
		$source = $declaration->source();
		if (($this->enclosing_lambda !== null && !$this->enclosing_lambda->returns_reference) || $this->owner->parent->return_type_def() instanceof generator_type_definition) {
			throw new diagnostic($source, $declaration->byte_start(), 'Local aliases are deferred in lambdas and generators');
		}
		if ($this->find_local($name) !== null) {
			throw new diagnostic($source, $declaration->byte_start(), 'Local alias must introduce a new name: ' . $name);
		}
		$target = $declaration->initializer;
		if ($target instanceof ast_call_expression) {
			$this->value($target, $name);
			$origin = $target->reference_origin();
			if ($origin === null) {
				throw new diagnostic($source, $target->byte_start(), 'Local alias call requires a reference return');
			}
		} else {
			$origin = $this->lexical_reference_slot($target);
		}
		$this->require_collection_available($target, $origin);
		if ($origin instanceof ast_local_declaration && $origin->initializer instanceof ast_lambda_expression && $origin->initializer->borrows_selected_element()) {
			throw new diagnostic($source, $target->byte_start(), 'A closure borrowing a foreach element requires direct local invocation');
		}
		if (ast_writable_element::origin($origin) !== null) throw new diagnostic($source, $target->byte_start(), 'Cannot alias a writable foreach element');
		$reference_parameter = $origin instanceof ast_parameter_declaration && $origin->mode !== passing_mode::value
			&& ($origin->mode === passing_mode::readonly_reference || $this->enclosing_lambda !== null
				|| !($this->owner->parent->parent instanceof ast_method_owner) || $this->owner->parent->is_direct_final_method());
		$take_slot = $origin instanceof ast_produced_binding && $origin->parent instanceof ast_take_expression;
		if ($take_slot) $this->require_take_ready($target, $origin);
		if (!($origin instanceof ast_local_declaration) && !$take_slot && !$reference_parameter && !($origin instanceof ast_parameter_declaration && $origin->is_readonly_pack())) {
			throw new diagnostic($source, $target->byte_start(), 'Local alias requires a previously initialized named local or a writable parameter of a free function or final instance method');
		}
		$this->take_borrowed[$origin->storage_id()] = true;
		unset($this->take_status[$origin->storage_id()]);
		$type = $origin->type_def();
		if ($declaration->type_reference !== null) {
			$written = Type_Resolution::local($declaration->type_reference, $this->symbols, $this->owner, $this->dependencies);
			if ($written === null || !$type->matches($written)) {
				throw new diagnostic($source, $declaration->type_reference->byte_start(), 'Local alias requires the exact storage type: ' . $type->name);
			}
		}
		if ($target instanceof ast_name_expression) {
			++$this->expression_visits;
			$target->settle_local($origin, unknown: true);
		}
		$declaration->settle_type($type, constant_evaluation::unavailable);
		$this->local_frames[count($this->local_frames) - 1][$name] = $origin;
	}

	/** Prepare one local, account for inline storage and publish it after its initializer succeeds. */
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
		if ($declaration->initializer instanceof ast_struct_default && $type instanceof fixed_array_type) {
			if ($declaration->initializer->arguments !== []) throw new diagnostic($source, $declaration->byte_start(), 'Fixed arrays take no constructor arguments');
			$this->expression_visits++;
			$declaration->initializer->settle_construction($type, []);
		}
		elseif ($declaration->initializer instanceof ast_struct_default) {
			if (!($type instanceof record_type_definition) && !($type instanceof record_handle_definition) && !($type instanceof type_exception) && !($type instanceof owned_callable_type)) {
				throw new diagnostic($source, $declaration->byte_start(), 'A local without an initializer requires a struct type');
			}
			if ($type instanceof shared_record_type_definition && $type->required) {
				throw new diagnostic($source, $declaration->byte_start(), 'Required class locals need an initializer in this slice');
			}
			$this->expression_visits++;
			$this->construction($declaration->initializer, $type, $name);
		}
		else {
			$this->value($declaration->initializer, $name,
				allow_callback: $type === null && $declaration->initializer instanceof ast_lambda_expression,
				destination: $declaration instanceof ast_assignment_snapshot ? $this->find_local($declaration->context_target)?->type_def() : $type);
		}
		if ($type === null) {
			$type = $declaration->initializer->type_def();
			if (!$type->supports_inferred_local() && !($type instanceof fixed_array_type)
				&& !($declaration instanceof ast_assignment_snapshot && $type === type_null::instance())) {
				throw new diagnostic($source, $declaration->initializer->byte_start(),
					'Cannot infer a local type from null; write an explicit type');
			}
		}
		$conversion = $this->prepare_local_initialization($declaration, $type);
		$declaration->settle_type($type, $type->evaluate_constant($conversion, $declaration->initializer), true, $conversion);
		$this->local_frames[count($this->local_frames) - 1][$name] = $declaration;
		if ($declaration->initializer instanceof ast_take_expression) $this->track_take_status($declaration, $declaration->initializer);
		if ($type instanceof lambda_type_definition) $this->has_lambda_local = true;
		if ($this->unknown_initializers) {
			$this->latest_writes[$declaration->storage_id()] = $declaration;
		}
	}

	/** Owned locals and capture fields initialize through the same source conversion and layout rules. */
	private function prepare_local_initialization(ast_local_declaration $declaration, type_definition $type): int /** uint16 */
	{
		if ($type instanceof fixed_array_type) {
			if ($declaration instanceof ast_closure_capture || $declaration->parent instanceof ast_for_statement || $this->enclosing_lambda !== null || $this->owner->parent->return_type_def() instanceof generator_type_definition) {
				throw new diagnostic($declaration->source(), $declaration->byte_start(), 'Declare fixed-array locals in an ordinary statement block');
			}
			$this->reserve_array_bytes($type->bytes, $declaration);
		}
		elseif ($type instanceof record_type_definition) {
			if ($declaration->type_reference === null) $this->require_inline_cleanup($type, $declaration);
			$this->reserve_array_bytes($type->array_bytes(), $declaration);
		}
		return $type->prepare_local_initializer($declaration->initializer, $declaration->type_reference !== null ? conversion_context::typed_destination : conversion_context::inference);
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

	/** A retained field or indexed place borrows through inline fields up to its nearest owner. */
	private function retain_member_place(ast_expression $place): void
	{
		while ($place instanceof ast_index_expression) $place = $place->receiver;
		while ($place instanceof ast_field_expression && $place->shares_receiver_storage()) {
			$place = $place->receiver;
		}
		$borrow = $place->member_borrow;
		if ($borrow === null || $borrow instanceof nullable_enum_type_definition || $borrow->payload_type instanceof shared_record_type_definition) return;
		$place->retain_member_borrow = true;
		if ($borrow instanceof record_result_type_definition) {
			$this->dependencies?->watch($this->symbols->entry($borrow->payload_type->declaration->symbol_key()), $this->owner, dependency_use::record_borrow_storage->value);
		}
	}

	/** A checked lookup yields an independent handle that field lowering retains through the write. */
	private function check_indexed_field_owner(ast_field_expression $field): bool
	{
		$owner = $field->receiver;
		if (!($owner instanceof ast_index_expression) || $field->source()->flavor !== flavor::php
			|| !($owner->receiver->type_def() instanceof readonly_index_lookup_type)) return false;
		if (!$owner->type_def()->mutates_referenced_value()) {
			throw new diagnostic($field->source(), $field->byte_start(), 'Field assignment requires writable local storage');
		}
		return true;
	}

	/** Check writable instance paths and qualified static members for all property writes. */
	private function writable_property(ast_name_expression|ast_field_expression $root, ?ast_field_assignment $assignment = null, bool $payload = false): ?ast_instance_field
	{
		if ($root instanceof ast_name_expression) {
			if ($root instanceof ast_called_type_name && $payload) throw new diagnostic($root->source(), $root->byte_start(), 'Indexed late-static property writes are deferred');
			if ($root instanceof ast_qualified_data && $payload) throw new diagnostic($root->source(), $root->byte_start(), 'Indexed expression-qualified static property writes are deferred');
			$this->static_destination($root);
			return null;
		}
		$this->value($root, deep_write_path: true);
		if ($root->is_dynamic_key()) {
			$place = ast_index_expression::boxed_path_root($root);
			if ($place === null) throw new diagnostic($root->source(), $root->byte_start(), 'Dynamic key write requires local or instance-field storage');
			Readonly_Access::write($place, true);
			$this->retain_member_place($place);
			$root->key_write_root = $place;
			return null;
		}
		$field = $assignment !== null ? $this->constructor_initialization?->assignment($root, $this->loop !== null) : null;
		Readonly_Access::write($root, $payload, $field);
		if ($payload && $this->check_indexed_field_owner($root)) return $field;
		$owner = $root->receiver;
		if ($assignment !== null && $root->source()->flavor === flavor::php && $root->type_def()->predicate_kind() !== ''
			&& $owner->type_def() instanceof shared_record_type_definition
			&& ($owner instanceof ast_call_expression || $this->check_indexed_field_owner($root))) {
			$assignment->retain_selected_owner = true;
			return $field;
		}
		$root = $root->receiver;
		while ($root instanceof ast_field_expression) $root = $root->receiver;
		if ($root instanceof ast_index_expression && $root->receiver->type_def() instanceof segment_type) return $field;
		if (!($root instanceof ast_this_expression)
			&& (!($root instanceof ast_name_expression) || $root->local_binding() === null)) {
			throw new diagnostic($root->source(), $root->byte_start(), 'Field assignment requires writable local storage');
		}
		return $field;
	}

	/** Resolve a local write without introducing a binding or applying a read-time narrowed type. */
	private function assignment_local(ast_node $site, string $name): ast_local_binding
	{
		$this->name_lookups++;
		$local = $this->find_local($name);
		if ($local === null) {
			if (($site->source()->flavor !== flavor::php)
				&& ($this->symbols->resolve($site->lexical_root()->constant_key($name)) instanceof ast_constant_declaration)) {
				throw new diagnostic($site->source(), $site->byte_start(), 'Cannot assign to a constant');
			}
			throw new diagnostic($site->source(), $site->byte_start(),
				'Assignment requires an existing local; global writes are deferred: ' . $name);
		}
		if (!$site->parent instanceof ast_take_expression && !($site instanceof ast_assignment_statement && !$site->calculates_value())) {
			$this->require_take_ready($site, $local);
		}
		$this->require_collection_available($site, $local);
		Readonly_Access::require_writable($site, $local);
		return $local;
	}

	/** A conditional write may be skipped, so it cannot publish a latest-value proof. */
	private function record_assignment(ast_local_binding $local, ?ast_assignment_statement $write): void
	{
		unset($this->guards[$local->storage_id()], $this->take_status[$local->storage_id()]);
		if ($write?->value instanceof ast_take_expression && !$write->calculates_value()) $this->track_take_status($local, $write->value);
		// A separately indexed write can change the slot before the next binding read.
		$this->latest_writes[$local->storage_id()] = ast_writable_element::origin($local) !== null ? null : $write;
	}

	/** Resolve established storage once; generated admission precedes publication and latest-value invalidation. */
	private function write(ast_storage_write $statement, bool $consumes_result = false): void
	{
		if ($statement instanceof ast_index_update) {
			$this->prepare_index_mutation($statement);
			$this->retain_member_place($statement->target->receiver);
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
		$local = $this->assignment_local($statement, $statement->name);
		if ($statement instanceof ast_reset_statement) {
			$statement->prepare_reset($local);
			$this->record_assignment($local, null);
			return;
		}
		$type = $local->type_def();
		if ($statement instanceof ast_update_statement) {
			$payload = $type instanceof carrier_type_definition ? carrier_write_payload($type, $statement->source()->flavor) : $type;
			if ($consumes_result && ($local instanceof ast_parameter_declaration || $payload !== type_registry::integer())) {
				throw new diagnostic($statement->source(), $statement->byte_start(), 'Update RHS requires an initialized int64 local');
			}
			$statement->require_update_type($type);
			$statement->settle_update($local);
			$this->record_assignment($local, null);
			return;
		}
		$this->value($statement->value, destination: $statement->calculates_value() ? null : $type);
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
			if ($local instanceof ast_produced_binding && $local->is_take_payload()) $this->take_ready[spl_object_id($local)] = true;
		}
		else {
			$source = $statement->source();
			if ($source->flavor === flavor::python) {
				if (!type_registry::is_numeric_scalar($type)) {
					throw new diagnostic($source, $statement->byte_start(), 'Python compound destination requires a numeric scalar');
				}
				if (!type_registry::is_numeric_scalar($statement->value->type_def())) {
					throw new diagnostic($source, $statement->value->byte_start(), 'Python compound operand requires a numeric scalar');
				}
			}
			$statement->prepare_local_compound($local);
		}
		$this->record_assignment($local, $statement);
	}

	/** Prepare ordered elements once using the destination contract; nested literals inherit their element destination. */
	private function collection(ast_collection_expression $syntax, type_definition&indexed_collection_type $type, ?string $initializing): void
	{
		if ($type instanceof fixed_array_type && count($syntax->elements) !== $type->extent) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Fixed-array initializer must match extent ' . $type->extent);
		}
		if ($syntax->elements === []) {
			$syntax->settle_collection($type);
			return;
		}
		if (!($type instanceof sequence_initializer_type || $type instanceof fixed_array_type)) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Populated literal requires a sequence-initializable destination');
		}
		$destination = $type->write_type();
		$conversions = [];
		foreach ($syntax->elements as $element) {
			$this->collection_element($element, $destination, $initializing);
			$conversions[] = $destination->prepare_conversion($element, conversion_context::typed_destination);
		}
		$syntax->settle_collection($type, $conversions);
	}

	/** A nested keyed literal with a family-specific spelling keeps that family. */
	private function collection_element(ast_expression $element, type_definition $destination, ?string $initializing): void
	{
		if ($destination instanceof boxed_type_definition && $element instanceof ast_keyed_collection_expression
			&& in_array($element->source()->flavor, [flavor::php, flavor::js], true)) {
			$this->value($element, $initializing);
		} else $this->value($element, $initializing, destination: $destination);
	}

	/** Resolve a contextual construction target before applying PHP/JS defaults. */
	private function literal(ast_collection_expression|ast_keyed_collection_expression $syntax, ?type_definition $type, ?string $initializing, ?ast_expression $prepared_key = null): void
	{
		$syntax->suspends = false;
		$syntax->record_call = null;
		$record = $type instanceof shared_record_type_definition ? $type->payload : $type;
		if ($record instanceof record_type_definition && $record->declaration->is_data) {
			$this->record_literal($syntax, $record, $type, $initializing);
			return;
		}
		$flavor = $syntax->source()->flavor;
		if ($type === null && ($flavor === flavor::php || $flavor === flavor::js)) {
			$dynamic = $syntax instanceof ast_keyed_collection_expression
				? $syntax->lexical_root()->tokens->tokens[$syntax->start]->kind === token_kind::lbrace : $flavor === flavor::js;
			$type = type_registry::resolve($dynamic ? 'dynamic' : 'mixed');
		}
		if ($syntax instanceof ast_collection_expression) {
			if ($syntax instanceof ast_tuple_expression && !($type instanceof boxed_type_definition)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Tuple requires a mixed or dynamic destination');
			}
			if (!($type instanceof indexed_collection_type)) {
				if ($type instanceof callback_type_definition && $flavor === flavor::php) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'A collection literal cannot supply a typed callback');
				$message = $type === null ? 'Collection requires an explicitly typed local initializer' : 'Destination has no published collection initialization capability';
				throw new diagnostic($syntax->source(), $syntax->byte_start(), $message);
			}
			if ($type instanceof fixed_array_type && !($syntax->parent instanceof ast_local_declaration || $syntax->parent instanceof ast_collection_expression)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Fixed-array literal requires a local initializer');
			}
			$this->collection($syntax, $type, $initializing);
			return;
		}
		if (!($type instanceof keyed_initializer_type)) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Keyed initializer requires an explicitly typed published keyed destination');
		}
		foreach ($syntax->entries as $entry) {
			if ($entry->key !== $prepared_key) $this->value($entry->key, $initializing);
			$type->prepare_key($entry->key);
			$this->collection_element($entry->value, $type->write_type(), $initializing);
			$entry->conversion = $type->write_type()->prepare_conversion($entry->value, conversion_context::typed_destination);
		}
		$syntax->settle_collection($type);
	}

	/** Bind a keyed literal against the same typed inputs used by constructor invocation. */
	private function record_literal(ast_collection_expression|ast_keyed_collection_expression $literal, record_type_definition $record, type_definition $type, ?string $initializing): void
	{
		if ($literal instanceof ast_collection_expression && $literal->elements !== []) {
			throw new diagnostic($literal->source(), $literal->byte_start(), 'Data construction requires named inputs');
		}
		$labels = [];
		$values = [];
		foreach ($literal->entries ?? [] as $entry) {
			if (!($entry->key instanceof ast_string_expression)) throw new diagnostic($entry->source(), $entry->byte_start(), 'Data construction requires literal string keys');
			$this->value($entry->key);
			$labels[] = $entry->key->bytes();
			$values[] = $entry->value;
		}
		$call = new ast_data_construction($literal->start, $literal->end, $values, new Named_Arguments($labels));
		$call->attach($literal);
		$this->dependencies?->watch($this->symbols->entry($record->declaration->symbol_key()), $this->owner);
		$this->construction($call, $type, $initializing);
		$literal->settle_record($call);
	}

	/** Statement and fallback throws use the same admission and settled root conversion. */
	private function prepare_throw(ast_throw_expression $throw, ?string $initializing = null): void
	{
		$operand = $throw->operand;
		$this->value($operand, $initializing);
		$this->require_exception($operand->type_def(), $operand);
		$throw->conversion = type_registry::exception()->prepare_conversion($operand, conversion_context::typed_destination);
	}

	/** Prepare each lazy arm with the initialization facts available on its own path. */
	private function ternary(ast_ternary_expression $value, ?string $initializing, ?type_definition $return_type = null): void
	{
		$this->condition($value->condition, $initializing);
		$otherwise = $this->take_ready;
		$status = $this->take_status;
		$this->take_paths($value->condition, $otherwise);
		$type = $return_type;
		if ($value->when_true !== null) {
			$value->true_conversion = $this->ternary_arm($value->when_true, $return_type, $type, $initializing);
		} elseif ($return_type !== null) {
			$value->true_conversion = $this->prepared_return_conversion($value->condition, $return_type);
		} else {
			$this->prepared_selection_result($value->condition, $type, 'Ternary');
		}
		$yes_ready = $this->take_ready;
		$yes_status = $this->take_status;
		$this->take_ready = $otherwise;
		$this->take_status = $status;
		$value->false_conversion = $this->ternary_arm($value->when_false, $return_type, $type, $initializing);
		$this->take_ready = array_intersect_key($yes_ready, $this->take_ready);
		$this->take_status = array_intersect_assoc($yes_status, $this->take_status);
		$value->settle_result($type);
	}

	/** Each arm of a PHP value return has the ordinary return destination. */
	private function ternary_arm(ast_expression $arm, ?type_definition $return_type, ?type_definition &$type, ?string $initializing): int /** uint16 */
	{
		if ($return_type !== null && !$arm instanceof ast_throw_expression) return $this->return_conversion($arm, $return_type);
		$this->selection_result($arm, $type, $initializing, 'Ternary');
		return value_conversion::identity;
	}

	/** Check one lazy arm without retaining a second result-type or branch-fact structure. */
	private function selection_result(ast_expression $branch, ?type_definition &$result, ?string $initializing, string $construct): void
	{
		$this->value($branch, $initializing);
		$this->prepared_selection_result($branch, $result, $construct);
	}

	/** Join an already prepared arm, including the saved operand of a PHP short ternary. */
	private function prepared_selection_result(ast_expression $branch, ?type_definition &$result, string $construct): void
	{
		if ($branch instanceof ast_throw_expression) return;
		$type = $branch->type_def();
		if (!$type->supports_inferred_local() || $type instanceof generator_type_definition) throw new diagnostic($branch->source(), $branch->byte_start(), $construct . ' arm requires a supported value result');
		if ($result !== null && !$result->matches($type)) throw new diagnostic($branch->source(), $branch->byte_start(), $construct . ' result types must be identical');
		$result ??= $type;
	}

	/** Reject uses of conditional storage on a path where it may still be empty. */
	private function require_take_ready(ast_node $site, ast_local_binding $binding): void
	{
		if ($binding instanceof ast_produced_binding && $binding->is_take_payload() && !isset($this->take_ready[spl_object_id($binding)])) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Check take success before using fresh output: ' . $binding->declaration_key());
		}
	}

	/** Resolve exact output slots after evaluating the input and establish the status relation. */
	private function prepare_take(ast_take_expression $take, ?string $initializing): void
	{
		$test = $take;
		while ($test->parent instanceof ast_not_expression) $test = $test->parent;
		$site = $test->parent;
		$ternary_test = $site instanceof ast_ternary_expression && $site->condition === $test;
		$while_test = $site instanceof ast_while_statement && $site->condition === $take;
		if (!($site instanceof ast_take_statement || $site instanceof ast_local_declaration || $site instanceof ast_assignment_statement
			|| $site instanceof ast_if_arm || $site instanceof ast_return_statement || $ternary_test || $while_test)) {
			throw new diagnostic($take->source(), $take->byte_start(), 'Use take as a statement, assignment, return or direct if/ternary condition or positive while condition');
		}
		for ($ancestor = $while_test ? $site->parent : $site; $ancestor !== null && !$ancestor instanceof ast_statement_block; $ancestor = $ancestor->parent) {
			if ($ancestor instanceof ast_for_statement || $ancestor instanceof ast_tested_loop) {
				throw new diagnostic($take->source(), $take->byte_start(), 'Use take in the loop body');
			}
		}
		if ($this->owner->parent->return_type_def() instanceof generator_type_definition) throw new diagnostic($take->source(), $take->byte_start(), 'take outputs across generator suspension are unsupported');
		$this->value($take->input, $initializing);
		$type = $take->input->type_def();
		$count = count($take->outputs);
		if (!$type instanceof carrier_type_definition || !in_array($type->family, ['result','result_or_bool','result_or_false'], true)
			|| $count > ($type->family === 'result' ? 3 : 2)) {
			throw new diagnostic($take->source(), $take->byte_start(), 'take requires a result and the output count for its family');
		}
		foreach ($take->outputs as $index => $output) {
			$expected = $index === 0 ? $type->payload_type : ($type->family === 'result' && $index === 1 ? type_registry::exception() : type_bool::instance());
			if ($output instanceof ast_produced_binding) {
				$output->settle_type($expected);
				$this->local_frames[array_key_last($this->local_frames)][$output->declaration_key()] = $output;
				$slot = $output;
			} else {
				$slot = $this->assignment_local($output, $output->name);
				if (ast_writable_element::origin($slot) !== null) throw new diagnostic($output->source(), $output->byte_start(), 'take requires whole-variable output storage');
				if ($index !== 0) $this->require_take_ready($output, $slot);
				if ($slot->type_def() !== $expected) throw new diagnostic($output->source(), $output->byte_start(), 'take output requires exact ' . $expected->name);
				$output->settle_local($slot, unknown: true);
				$this->record_assignment($slot, null);
			}
			if (in_array($slot, $take->slots, true)) throw new diagnostic($output->source(), $output->byte_start(), 'take requires distinct output slots');
			$take->slots[] = $slot;
		}
		// A nested lazy operand can be skipped while a pre-existing status is still true.
		if ($count > 1 && (!$ternary_test || $site->parent instanceof ast_statement)) {
			for ($index = 1; $index < $count; ++$index) $this->track_take_status($take->slots[$index], $take);
		}
		$take->settle_take();
	}

	/** Only private status storage can witness initialization across subsequent statements. */
	private function track_take_status(ast_local_binding $slot, ast_take_expression $take): void
	{
		if (isset($this->take_borrowed[$slot->storage_id()])) return;
		if ($slot->type_def() !== type_bool::instance() && !$slot->type_def() instanceof type_exception) return;
		if ($slot instanceof ast_parameter_declaration && $slot->mode !== passing_mode::value) return;
		if ($slot instanceof ast_closure_capture || ast_writable_element::origin($slot) !== null) return;
		$this->take_status[$slot->storage_id()] = spl_object_id($take->slots[0]);
	}

	/** Resolve each expression once; a reference retains its exact declaration binding. */
	private function value(ast_expression $syntax, ?string $initializing = null, bool $allow_callback = false, ?type_definition $destination = null,
		bool $deep_write_path = false): void
	{
		if ($syntax instanceof ast_take_expression) {
			$this->prepare_take($syntax, $initializing);
			return;
		}
		if ($syntax instanceof ast_type_marker) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type marker is only valid as an enum lookup, object_cast or json_decode_as target');
		$this->expression_visits++;
		if ($syntax instanceof ast_throw_expression) {
			$this->prepare_throw($syntax, $initializing);
			return;
		}
		if ($syntax instanceof ast_keyed_collection_expression || $syntax instanceof ast_collection_expression) {
			$this->literal($syntax, $destination, $initializing);
			return;
		}
		if ($syntax instanceof ast_update_expression) {
			$this->write($syntax->update, consumes_result: true);
			$syntax->settle_update();
			return;
		}
		if ($syntax instanceof ast_condition_assignment) {
			if (!$syntax->in_condition()) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Parenthesized assignment requires a control-statement condition');
			$local = $this->assignment_local($syntax, $syntax->target->name);
			if (!($local instanceof ast_local_declaration)) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Condition assignment requires a declared local');
			$this->value($syntax->value, $initializing, destination: $local->type_def());
			$syntax->settle_assignment($local, $local->type_def()->prepare_conversion($syntax->value, conversion_context::assignment));
			$this->record_assignment($local, null);
			return;
		}
		if ($syntax instanceof ast_ternary_expression) {
			$this->ternary($syntax, $initializing);
			return;
		}
		if ($syntax instanceof ast_match_expression) {
			$syntax->suspends = false;
			$this->value($syntax->subject, $initializing);
			$type = $syntax->subject->type_def()->dispatch_label_type();
			$conditional = ast_match_expression::is_condition_subject($syntax->subject);
			$dispatch = $type->constant_dispatch();
			if (!$conditional && $dispatch === null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Match subject requires constant-label dispatch or literal true');
			}
			$seen = [];
			$default = null;
			$result = null;
			foreach ($syntax->arms as $arm) {
				if ($arm->labels === []) {
					if ($default !== null) throw new diagnostic($arm->source(), $arm->byte_start(), 'Duplicate match default');
					$default = $arm;
					continue;
				}
				foreach ($arm->labels as $label) {
					$this->value($label, $initializing);
					if (!$conditional) $this->case_label($label, $type, $dispatch, $seen, 'Match');
					elseif ($label->type_def() !== $type) throw new diagnostic($label->source(), $label->byte_start(), 'Match label type must equal subject type');
				}
				$this->selection_result($arm->result, $result, $initializing, 'Match');
			}
			if ($default === null) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Match requires a default arm');
			// Fallback runs after conditions, even when written first. Preserve reference-call invalidation.
			$this->selection_result($default->result, $result, $initializing, 'Match');
			if ($result === null) {
				throw new diagnostic($default->source(), $default->byte_start(), 'Throwing match default requires a value arm');
			}
			$syntax->settle_result($result);
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
			if ($this->enclosing_lambda !== null) $this->borrow_receiver($syntax);
			$record = $this->receiver_owner($syntax);
			$parent = $syntax->parent;
			$is_receiver = ($parent instanceof ast_field_expression || $parent instanceof ast_call_expression
				|| $parent instanceof ast_qualified_data) && $parent->receiver === $syntax;
			if (!$is_receiver && $this->enclosing_lambda !== null) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Borrowed this can only be used as a receiver');
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
			$factory = $payload instanceof runtime_handle_type ? runtime_functions::resolve($syntax->source()->flavor->name . ':new:' . $payload->name) : null;
			if ($factory instanceof runtime_function) {
				if ($syntax->named !== null || count($syntax->arguments) < $factory->required_parameters || count($syntax->arguments) > count($factory->parameters)) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Invalid runtime constructor arguments');
				}
				foreach ($syntax->arguments as $index => $argument) {
					$type = $factory->parameters[$index];
					$this->value($argument, destination: $type);
					if ($argument->type_def() !== $type) throw new diagnostic($argument->source(), $argument->byte_start(), 'Constructor requires ' . $type->name);
				}
				$syntax->settle_construction($payload, array_fill(0, count($syntax->arguments), value_conversion::identity));
				return;
			}
			if ($payload instanceof type_exception) {
				if ($syntax->unpacked) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unpacking requires a variadic constructor');
				if ($syntax->named !== null) throw new diagnostic($syntax->source(), $syntax->byte_start(), ast_named_call::UNSUPPORTED_TARGET);
				$factory = $payload->factory;
				if (count($syntax->arguments) !== count($factory->parameters)) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Argument count mismatch for Exception constructor');
				}
				$message = $syntax->arguments[0];
				$this->value($message, $initializing);
				if ($message->type_def() !== $factory->parameters[0]) {
					throw new diagnostic($message->source(), $message->byte_start(), 'Exception constructor requires ' . $factory->parameters[0]->name);
				}
				$syntax->settle_construction($factory->return_type_def(), [value_conversion::identity]);
				return;
			}
			if ($payload instanceof shared_record_type_definition && $payload->required) $payload = $payload->payload;
			if (!($payload instanceof record_type_definition)) {
				throw new diagnostic($syntax->source(), $syntax->target->byte_start(), 'Shared construction requires a record: ' . $syntax->target->name);
			}
			$this->construction($syntax, $payload->required, $initializing);
			return;
		}
		if ($syntax instanceof ast_nullsafe_member) {
			$member = $syntax->operand;
			$this->value($member, $initializing, destination: $syntax->parent instanceof ast_discard_statement ? type_void::instance() : null);
			$guarded = $syntax->guarded_member;
			$kind = $guarded instanceof ast_call_expression ? 'method call' : 'field read';
			$owner = $guarded->receiver->type_def();
			if (!($owner instanceof nullable_enum_type_definition) && !($owner instanceof shared_record_type_definition && !$owner->required)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Nullsafe ' . $kind . ' requires an optional shared owner or nullable enum');
			}
			$payload = $member->type_def();
			for ($hop = $member; $hop !== $guarded->receiver; $hop = $hop->receiver) {
				if ($hop instanceof ast_index_expression && !($hop->receiver->type_def() instanceof readonly_index_lookup_type)) {
					throw new diagnostic($hop->source(), $hop->byte_start(), 'Nullsafe indexed read requires a typed vector or hash');
				}
				if ($hop instanceof ast_call_expression) $this->check_nullsafe_call($hop);
			}
			$result = $payload === type_void::instance() || ($payload instanceof carrier_type_definition && $payload->family === 'nullable')
				|| ($member instanceof ast_field_expression && $payload instanceof owned_callable_type)
				? $payload : Type_Resolution::nullable_value($payload);
			if ($result === null) {
				$kind = $member instanceof ast_index_expression ? 'indexed element' : ($member instanceof ast_call_expression ? 'method result' : 'field type');
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'No optional representation for ' . $kind . ' ' . $payload->name);
			}
			$syntax->settle_result($result);
			return;
		}
		if ($syntax instanceof ast_field_expression) {
			$this->value($syntax->receiver, $initializing, deep_write_path: $deep_write_path);
			if ($syntax->is_dynamic_key() && $syntax->source()->flavor !== flavor::python) {
				$syntax->settle_field($syntax->receiver->type_def());
				return;
			}
			if (in_array($syntax->source()->flavor, [flavor::php, flavor::cpp], true)) $syntax->receiver->prepare_member_borrow();
			$type = $syntax->receiver->member_type();
			$guard = $syntax->parent;
			if ($type instanceof enum_type_definition && $syntax->receiver->member_borrow instanceof nullable_enum_type_definition
				&& $guard instanceof ast_nullsafe_member && $guard->guarded_member === $syntax) {
				$guard->enum_property = match ($syntax->field) {
					'name' => dependency_use::enum_name,
					'value' => dependency_use::enum_value,
					default => throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown enum property ' . $type->name . '.' . $syntax->field),
				};
				$syntax->settle_field($this->enum_projection($type, $guard->enum_property)->return_type_def());
				return;
			}
			if ($type instanceof shared_record_type_definition) $type = $type->payload;
			if ($type instanceof runtime_field_type) {
				$field = $type->runtime_field($syntax->field);
				if ($field === null || ($type instanceof runtime_handle_type && $syntax->source()->flavor !== flavor::php)) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown field ' . $type->name . '.' . $syntax->field);
				$syntax->settle_field($field);
				return;
			}
			if ($type instanceof type_segment_range) {
				if ($syntax->field !== 'offset' && $syntax->field !== 'length') throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown segment_range field: ' . $syntax->field);
				$syntax->settle_field(type_int64::instance());
				return;
			}
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
			$field_type = $field->type_def();
			if ($field_type instanceof fixed_array_type) {
				$base = $syntax->receiver;
				while ($base instanceof ast_field_expression && $base->type_def() instanceof record_type_definition) $base = $base->receiver;
				if (!($base instanceof ast_name_expression && $base->local_binding() !== null
				&& ($base->type_def() instanceof record_type_definition || $base->type_def() instanceof shared_record_type_definition))) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Fixed-array field access requires a named record or shared owner');
				}
			}
			$syntax->settle_field($field_type instanceof class_union_type_definition ? ($field_type->receiver_view($syntax) ?? $field_type) : $field_type);
			if (!(($syntax->parent instanceof ast_field_assignment) && $syntax->parent->target === $syntax)) {
				$this->constructor_initialization?->read($syntax, $field);
			}
			return;
		}
		if ($syntax instanceof ast_index_expression) {
			$this->value($syntax->receiver, $initializing, deep_write_path: $deep_write_path);
			$type = $syntax->receiver->type_def();
			if (!($type instanceof index_read_type)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Indexed read requires a vector, hash, boxed container or readable string');
			}
			if ($syntax->source()->flavor === flavor::python && !($type instanceof python_index_read_type)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Provider does not support Python indexed reads');
			}
			$this->value($syntax->index, $initializing);
			if ($deep_write_path && $type instanceof boxed_type_definition) $type->prepare_index($syntax, deep_path: true);
			else $type->prepare_index($syntax);
			if (!$syntax->index->is_effect_free_literal()) $this->retain_member_place($syntax->receiver);
			return;
		}
		if ($syntax instanceof ast_interpolated_string) {
			$string = type_registry::string();
			foreach ($syntax->parts as $part) {
				$this->value($part, $initializing);
				if ($part instanceof ast_interpolation_index && !($part->receiver->type_def() instanceof hash_type_definition)
					&& !($part->receiver->type_def() instanceof type_mixed)) {
					throw new diagnostic($part->source(), $part->receiver->byte_start(), 'Keyed interpolation requires a hash or mixed row');
				}
				if (!($part instanceof ast_interpolation_text)) {
					$part->prepare_text($string);
				}
			}
			$syntax->settle_string($string);
			return;
		}
		if ($syntax instanceof ast_cast_expression) {
			$type = Type_Resolution::resolve($syntax->type_reference);
			$this->value($syntax->operand, $initializing, destination: $type instanceof boxed_type_definition ? $type : null);
			if ($type === null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unsupported cast target: ' . $syntax->type_reference->spelling());
			}
			$source = $syntax->operand->type_def();
			$schema = $source instanceof native_sequence_type ? $source->write_type() : $source;
			if ($type instanceof boxed_type_definition && $schema instanceof record_type_definition) {
				$seen = $path = [];
				$this->check_boxed_schema($schema, $type, $syntax, $seen, $path);
				$syntax->settle_cast($type, value_conversion::export_record);
			} else $syntax->settle_cast($type, $type->prepare_conversion($syntax->operand, conversion_context::explicit_cast));
			return;
		}
		if ($syntax instanceof ast_unary_expression) {
			$this->value($syntax->operand, $initializing);
			if ($syntax->operator === unary_operator::bit_not && $syntax->source()->flavor === flavor::python
				&& !type_registry::is_numeric_scalar($syntax->operand->type_def())) {
				throw new diagnostic($syntax->source(), $syntax->operand->byte_start(), 'Unsupported unary operand: Python bitwise operators require integer scalars');
			}
			if ($syntax->operand->type_def() instanceof carrier_type_definition) {
				$syntax->operand->type_def()->require_operator_payload($syntax->operand);
			}
			$syntax->operand->value_type()->prepare_unary($syntax);
			return;
		}
		if ($syntax instanceof ast_isset_expression) {
			$this->value($syntax->operand, $initializing);
			$type = $syntax->operand->type_def();
			$entry = $syntax->operand;
			if ($entry instanceof ast_index_expression && $type instanceof owned_callable_type) $this->retain_member_place($entry);
			if ($syntax->source()->flavor === flavor::php && $entry instanceof ast_index_expression
				&& $entry->receiver instanceof ast_name_expression && $entry->receiver->local_binding() !== null
				&& $entry->receiver->type_def() instanceof hash_type_definition
				&& ($entry->receiver->type_def()->publishes_entry_isset() || $type === type_registry::integer()
					|| $type === type_registry::string() || $type instanceof shared_record_type_definition)) {
				$syntax->settle_presence($entry);
				return;
			}
			if (!($type instanceof presence_test_type)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'This isset slice requires a carrier or owned callable');
			}
			$syntax->settle_presence();
			return;
		}
		if ($syntax instanceof ast_not_expression) {
			$this->value($syntax->operand, $initializing);
			$this->logical_operand($syntax->operand);
			$syntax->settle_not();
			return;
		}
		if ($syntax instanceof ast_comparison_chain) {
			$syntax->suspends = false;
			foreach ($syntax->links as $link) {
				$this->value($link, $initializing);
				if ($link->type_def() !== type_bool::instance()) {
					throw new diagnostic($link->source(), $link->byte_start(), 'Comparison chain requires a boolean comparison');
				}
			}
			$syntax->settle_chain();
			return;
		}
		if ($syntax instanceof ast_binary_expression) {
			if (!($syntax instanceof ast_continued_comparison)) $this->value($syntax->left, $initializing);
			// A lazy RHS is still checked; any reference-call invalidation conservatively survives the join.
			if ($syntax instanceof ast_key_comparison) {
				$this->expression_visits++;
				if (!$this->resolve_value_name($syntax->right, $initializing, false)) {
					if (!($syntax->left->receiver->type_def() instanceof boxed_type_definition)) {
						throw new diagnostic($syntax->right->source(), $syntax->right->byte_start(), 'Missing-key test requires mixed or dynamic');
					}
					$syntax->settle_key_test();
					return;
				}
			} else {
				$this->value($syntax->right, $initializing);
			}
			if ($syntax->operator === binary_operator::coalesce) {
				$left = $syntax->left->type_def();
				$right = $syntax->right instanceof ast_throw_expression ? null : $syntax->right->type_def();
				if ($left instanceof carrier_type_definition && $left->supports_coalescing()) {
					$result = $left->payload_type;
					if ($right !== null && $right !== $result && !($right === $left && $result === type_int64::instance())) {
						throw new diagnostic($syntax->right->source(), $syntax->right->byte_start(), 'Coalescing fallback requires the exact payload type or a matching nullable<int>');
					}
					$this->retain_member_place($syntax->left);
				} elseif ($left instanceof shared_record_type_definition && $left->payload->declaration->kind === record_kind::class_type) {
					if ($right !== $left) throw new diagnostic($syntax->right->source(), $syntax->right->byte_start(), 'Coalescing requires identical shared class handle types');
					$result = $left;
				} else {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Coalescing requires a published nullable carrier or a shared class handle');
				}
				$syntax->settle_binary($result);
				return;
			}
			if ($syntax->operator->logical()) {
				$this->logical_operand($syntax->left);
				$this->logical_operand($syntax->right);
				$syntax->settle_binary(type_bool::instance());
				return;
			}
			$left = $syntax->left->type_def();
			$right = $syntax->right->type_def();
			if ($syntax->operator->membership()) {
				if (!($right instanceof membership_type)) throw new diagnostic($syntax->source(), $syntax->right->byte_start(), 'Receiver does not support Python membership');
				$right->prepare_membership($syntax);
				return;
			}
			if (in_array($syntax->operator, [binary_operator::bit_and, binary_operator::bit_xor, binary_operator::bit_or], true)
				&& $syntax->source()->flavor === flavor::python
				&& !(type_registry::is_numeric_scalar($left) && type_registry::is_numeric_scalar($right))) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unsupported binary operands: Python bitwise operators require integer scalars');
			}
			// A Python owner identity test accepts any two shared views of an allocation.
			if (($syntax->operator === binary_operator::identical || $syntax->operator === binary_operator::not_identical)
				&& $syntax->source()->flavor === flavor::python && !($syntax instanceof ast_key_comparison)
				&& !($syntax->right instanceof ast_keyword_expression && $syntax->right->word === 'null')) {
				if (!($left instanceof shared_record_type_definition && $right instanceof shared_record_type_definition)) {
					throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Python identity requires two shared handles or a literal None on the right');
				}
				$syntax->settle_binary(type_bool::instance(), $left);
				return;
			}
			if ($left instanceof shared_record_type_definition || $right instanceof shared_record_type_definition) {
				$owner = $left instanceof shared_record_type_definition ? $left : $right;
				$owner->prepare_binary($syntax);
				return;
			}
			if ($syntax->operator === binary_operator::identical || $syntax->operator === binary_operator::not_identical) {
				$carrier = $left instanceof carrier_type_definition ? $left : $right;
				$literal = $carrier === $left ? $syntax->right : $syntax->left;
				if ($carrier instanceof carrier_type_definition
					&& $literal instanceof ast_keyword_expression && in_array($literal->word, ['true','false'], true)
					&& $carrier->sentinel_factory($literal->word) !== null
					&& $carrier->payload_type instanceof runtime_handle_type && $syntax->source()->flavor !== flavor::php) {
					$syntax->settle_binary(type_bool::instance(), $carrier);
					return;
				}
				$carrier = $left === type_null::instance() ? $right : ($right === type_null::instance() ? $left : null);
				if ($carrier instanceof carrier_type_definition && $carrier->accepts_empty()) {
					$syntax->settle_binary(type_bool::instance(), $carrier);
					return;
				}
				if ($syntax->source()->flavor === flavor::python && $syntax->right instanceof ast_keyword_expression && $left !== type_null::instance()) {
					throw new diagnostic($syntax->source(), $syntax->left->byte_start(), 'Python None test requires a nullable value, shared handle or None');
				}
			}
			$carrier = $left instanceof carrier_type_definition ? $left : $right;
			if ($carrier instanceof carrier_type_definition) {
				$comparison = peer_equality_binding($left, $right, $syntax->source()->flavor, $syntax->operator);
				if ($comparison !== null) {
					if ($comparison->observation === result_observation::null_payload) {
						if ($comparison->operator !== binary_operator::identical) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Use strict comparison to test for a null result payload');
						if (!$carrier->can_hold_null_payload()) throw new diagnostic($syntax->source(), $syntax->byte_start(), $carrier->name . ' cannot contain null; compare with false to test failure');
					}
					$execution = $comparison->execution;
					if ($execution !== null && ($execution->binary_result($comparison->operator, $execution) !== type_bool::instance()
						|| !$execution->binary_syntax_allowed($comparison->operator, $syntax))) {
						throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unsupported binary operands or operator');
					}
					$syntax->settle_binary(type_bool::instance(), $carrier, peer_equality: $comparison);
					return;
				}
			}
			$box = $left instanceof boxed_type_definition ? $left : $right;
			if ($box instanceof boxed_type_definition) {
				$box->prepare_binary($syntax);
				return;
			}
			if ($left instanceof carrier_type_definition) {
				$left->require_operator_payload($syntax->left);
			}
			if ($right instanceof carrier_type_definition) {
				$right->require_operator_payload($syntax->right);
			}
			$syntax->left->value_type()->prepare_binary($syntax);
			return;
		}
		if ($syntax instanceof ast_lambda_expression) {
			if ((!$allow_callback && !($destination instanceof owned_callable_type)) || $this->owner->parent->return_type_def() instanceof generator_type_definition) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Lambda requires an inferred local initializer or typed callback argument, or an owned callable destination');
			}
			$signature = $destination instanceof owned_callable_type ? $destination->signature : $destination;
			$this->lambda($syntax, $signature instanceof callback_type_definition ? $signature : null);
			return;
		}
		if ($syntax instanceof ast_callable_reference) {
			if ($destination instanceof owned_callable_type || ($allow_callback && $destination instanceof callback_type_definition)) {
				$this->callback_reference($syntax, $destination instanceof owned_callable_type ? $destination->signature : $destination);
				return;
			}
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Callable reference requires a typed callback argument');
		}
		if ($syntax instanceof ast_await_expression) {
			$this->prepare_await_context($syntax);
			$suspends = $this->owner->parent->is_async;
			if ($suspends) ++$this->suspension_count;
			$target = $this->async_producer($syntax->operand, $initializing,
				$suspends ? dependency_use::async_task : dependency_use::async_wait, $syntax);
			if ($target->return_type_def() === type_void::instance() && $destination !== type_void::instance() && !($syntax->parent instanceof ast_discard_statement)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Void call cannot be used as a value');
			}
			$syntax->settle($suspends);
			return;
		}
		if ($syntax instanceof ast_call_expression) {
			$this->call($syntax, $initializing);
			if ($syntax->type_def() === type_void::instance() && $destination !== type_void::instance()) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Void call cannot be used as a value');
			}
			return;
		}
		if (($syntax instanceof ast_integer_expression) || ($syntax instanceof ast_float_expression)
			|| ($syntax instanceof ast_keyword_expression) || ($syntax instanceof ast_string_expression)) {
			type_registry::prepare_literal($syntax);
			return;
		}
		if ($syntax instanceof ast_name_expression) {
			if ($this->resolve_value_name($syntax, $initializing, $allow_callback || $destination instanceof owned_callable_type)) return;
			$name = $syntax->name;
			throw new diagnostic($syntax->source(), $syntax->byte_start(),
				$syntax->constant_only ? 'Unknown constant: ' . $name : 'Unknown or not-yet-initialized local/global: ' . $name);
		}
		throw new \LogicException('Unhandled expression preparation');
	}

	/** Bind a case name or backing value and watch its declaring enum for edits. */
	private function enum_projection(enum_type_definition $type, dependency_use $operation): runtime_function
	{
		$this->dependencies?->watch($this->symbols->entry($type->declaration->symbol_key()), $this->owner, $type->helper_usage($operation));
		return $type->helper_function($operation);
	}

	/** Guarded calls use synchronous methods or a terminal indexed callable returning a value. */
	private function check_nullsafe_call(ast_call_expression $call): void
	{
		if ($call->qualifier === call_qualifier::stored_value) {
			$signature = $call->receiver->type_def()->signature;
			if ($signature->reference_origin !== null || $signature->result === type_void::instance()) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Nullsafe indexed invocation requires a callable returning by value');
			}
			return;
		}
		$method = $call->function_def();
		if (!($method instanceof ast_function_declaration) || !$method->has_receiver() || $method->returns_reference || $method->is_async) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Nullsafe call requires a synchronous instance method without a reference return');
		}
	}

	/** Watch each candidate symbol, including misses. Invalid bindings retain their own diagnostic. */
	private function resolve_value_name(ast_name_expression $syntax, ?string $initializing, bool $allow_callback): bool
	{
		$this->name_lookups++;
		$name = $syntax->name;
		if ($syntax->constant_only && str_starts_with($name, '__') && $syntax::class === ast_name_expression::class
			&& $syntax->source()->flavor === flavor::php && ($path = php_source_path::tryFrom(strtoupper($name))) !== null) {
			$syntax->settle_source_path($path);
			return true;
		}
		if ($syntax instanceof ast_ancestor_name || $syntax instanceof ast_called_type_name || $syntax instanceof ast_qualified_data) {
			$lookup = match (true) {
				$syntax instanceof ast_called_type_name => $this->called_record($syntax, 'Late-static access')->definition,
				$syntax instanceof ast_ancestor_name => $this->lexical_base($syntax, 'Ancestor access'),
				default => $this->static_qualifier($syntax->receiver, $initializing),
			};
			$this->bind_record_data($syntax, $lookup, $name, $lookup->name);
			return true;
		}
		if (!$syntax->constant_only && ($name === $initializing)) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(),
				'Cannot read local during its initialization: ' . $name);
		}
		$binding = $syntax->constant_only ? null : $this->find_local($name);
		if ($binding !== null) {
			$this->require_take_ready($syntax, $binding);
			if ($syntax->parent instanceof ast_closure_capture && $syntax->parent->by_reference) {
				$this->take_borrowed[$binding->storage_id()] = true;
				unset($this->take_status[$binding->storage_id()]);
			}
			$type = $binding->type_def();
			if ($type instanceof lambda_type_definition && $type->has_borrow && $binding->initializer->borrows_selected_element()) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'A closure borrowing a foreach element requires direct local invocation');
			}
			if (!$allow_callback && ($type instanceof callback_type_definition || $type instanceof lambda_type_definition)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Callable value can only be invoked or forwarded');
			}
			if ($syntax->parent instanceof ast_closure_capture) {
				$syntax->settle_local($binding, unknown: true);
				return true;
			}
			$id = $binding->storage_id();
			// A borrowed slot may observe writes through another name.
			$unknown = ($binding instanceof ast_closure_capture && $binding->by_reference)
				|| (($binding instanceof ast_parameter_declaration) && ($binding->mode !== passing_mode::value))
				|| (array_key_exists($id, $this->latest_writes) ? ($this->latest_writes[$id] === null) : $this->unknown_initializers);
			$write = $this->latest_writes[$id] ?? null;
			$type = $binding->type_def();
			$view = $type instanceof intersection_type_definition
				? $type->receiver_view($syntax, $this->symbols, $this->owner, $this->dependencies) : null;
			if ($type instanceof class_union_type_definition) {
				$guard = $this->guards[$id] ?? null;
				if ($guard instanceof ast_object_access_expression) {
					$syntax->settle_guard($guard, narrow: true);
					return true;
				}
				$view = $guard ?? $type->receiver_view($syntax);
			}
			$syntax->settle_local($binding, $write instanceof ast_assignment_statement ? $write : null, $unknown, $view);
			return true;
		}
		if ($syntax->parent instanceof ast_closure_capture) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Capture requires an initialized local: ' . $name);
		if ($syntax instanceof ast_interpolation_name) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown interpolation local or parameter: ' . $name);
		}
		$separator = strpos($name, '\\');
		if ($syntax->member_syntax && ($separator !== false)
			&& ($this->find_local(substr($name, 0, $separator)) !== null)) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Member reads are not supported in this slice');
		}
		if (Enum_Preparation::reference($syntax, $this->symbols, $this->owner, $this->dependencies)) return true;
		$scope_separator = strrpos($name, '::');
		if ($scope_separator !== false && $this->bind_static_data($syntax, substr($name, 0, $scope_separator),
			substr($name, $scope_separator + 2))) return true;
		if (!$syntax->constant_only) {
			$key = 'var:' . Source_Names::resolve($name, $this->owner->scope->namespace);
			$symbol = $this->symbols->entry($key);
			if ($this->bind_literal($syntax, $symbol)) return true;
			if ($syntax->source()->flavor === flavor::php && str_contains($name, '::')) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown static property: ' . $name);
			}
		}
		if ($syntax->constant_only || ($syntax->source()->flavor !== flavor::php)) {
			$symbol = $this->symbols->entry($syntax->lexical_root()->constant_key($name));
			if ($this->bind_literal($syntax, $symbol)) return true;
			// C++ and JS share the namespace separator with type-qualified members.
			if ($separator !== false && $syntax->source()->flavor !== flavor::php && ($last = strrpos($name, '\\')) > 0) {
				if ($this->bind_static_data($syntax, substr($name, 0, $last), substr($name, $last + 1))) return true;
			}
			$constant = $syntax->lexical_root()->type_availability->runtime_constant($syntax->lexical_root()->runtime_constant_key($name), $syntax->source()->flavor);
			if ($constant !== null) {
				$syntax->settle_runtime_constant($constant);
				return true;
			}
		}
		return false;
	}

	/**
	 * Bind exact stable storage. Only a writable reference can invalidate the caller’s value facts.
	 * @param int[] $writes Local storage IDs invalidated by the call.
	 */
	private function bind_reference(ast_expression $argument, type_definition $type, array &$writes /** list<uint32> */, bool $readonly = false,
		ast_function_declaration|ast_lambda_expression|callback_type_definition|null $target = null, string $label = ''): void
	{
		$storage = $argument instanceof ast_name_expression ? $argument->declaration() : null;
		if (!$readonly && ($storage instanceof ast_constant_declaration || $storage instanceof runtime_integer_constant)) {
			throw new diagnostic($argument->source(), $argument->byte_start(), $label . 'Constant cannot bind a mutable reference');
		}
		if (!($storage instanceof ast_local_binding)) {
			throw new diagnostic($argument->source(), $argument->byte_start(),
				($label === '' ? 'Reference argument requires stable variable storage: a named local or parameter'
				: $label . 'requires a writable ' . $type->name . ' variable (named local or parameter), received ' . $argument->type_def()->name));
		}
		if (ast_writable_element::origin($storage) !== null && (!($target instanceof ast_function_declaration)
			|| $target->parent instanceof ast_method_owner || $target->is_async || $target->returns_reference)) {
			throw new diagnostic($argument->source(), $argument->byte_start(), $label . 'Writable foreach element cannot bind a reference parameter');
		}
		if ($storage->type_def() !== $type) {
			throw new diagnostic($argument->source(), $argument->byte_start(), ($label === '' ? 'Reference argument requires exact storage type ' . $type->name : $label . 'requires writable ' . $type->name . ' storage')
				. ', received ' . $storage->type_def()->name);
		}
		if (!$readonly) {
			Readonly_Access::require_writable($argument, $storage);
			$writes[] = $storage->storage_id();
		}
	}

	/** Supply mixed context to an unannotated lambda and resolve a declared capture row once. */
	private function regex_callable(regex_callback_overloads $group, ast_expression $value): regex_callback_function
	{
		if ($value instanceof ast_lambda_expression) {
			$annotation = $value->parameters[0]->type_reference ?? null;
			$row = $annotation === null ? type_mixed::instance()
				: Type_Resolution::signature($annotation, $this->symbols, $this->owner->parent, $this->dependencies, watcher: $this->owner);
			$binding = $group->binding($row, $value);
			$this->value($value, destination: $binding->callback);
			return $binding;
		}
		if ($value instanceof ast_callable_reference) $this->callback_reference($value, null);
		else $this->value($value);
		$type = $value->type_def();
		$signature = $type instanceof owned_callable_type || $type instanceof lambda_type_definition ? $type->signature : $type;
		return $group->binding($signature instanceof callback_type_definition ? ($signature->inputs[0] ?? null) : null, $value);
	}

	/** Select the table from its declared type or the first literal entry, preserving entry preparation order. */
	private function regex_callback_binding(regex_callback_overloads $group, ast_expression $value): regex_callback_function
	{
		if (!$group->table) return $this->regex_callable($group, $value);
		if ($value instanceof ast_keyed_collection_expression) {
			$binding = null;
			foreach ($value->entries as $entry) {
				$this->value($entry->key);
				if ($binding === null) $binding = $this->regex_callable($group, $entry->value);
				else $this->value($entry->value, destination: $binding->callback);
				$table = $binding->parameters[0];
				$table->prepare_key($entry->key);
				$entry->conversion = $binding->callback->prepare_conversion($entry->value, conversion_context::typed_destination);
			}
			$value->settle_collection($binding->parameters[0]);
			return $binding;
		}
		$context = $value instanceof ast_collection_expression ? $group->binding(type_mixed::instance(), $value)->parameters[0] : null;
		$this->value($value, destination: $context);
		$table = $value->type_def();
		$callback = $table instanceof hash_type_definition ? $table->value_type : null;
		return $group->binding($callback instanceof owned_callable_type ? ($callback->signature->inputs[0] ?? null) : null, $value);
	}

	/** A callee can replace the slot only after its arguments are evaluated. @param int[] $writes */
	private function invalidate_references(ast_expression $call, array $writes /** list<uint32> */): void
	{
		foreach ($writes as $id) {
			if (isset($this->iterated_collections[$id])) throw new diagnostic($call->source(), $call->byte_start(), 'Call may mutate the collection during writable foreach');
			$read = $this->guard_reads[$id] ?? null;
			if ($read !== null && ($read < $call->start || $read >= $call->end)) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Separate guarded argument use and mutable reference access into different expressions');
			}
			unset($this->guards[$id], $this->take_status[$id]);
			$this->latest_writes[$id] = null;
		}
	}

	/** Resolve a literal from its key or list shape before choosing an ordered rewrite overload. @param array<class-string,type_definition> $rule_types */
	private function rewrite_input(array $rule_types, ast_expression $argument, bool $typed): type_definition
	{
		if ($argument instanceof ast_keyed_collection_expression) {
			$key = $argument->entries[0]->key;
			$this->value($key);
			$hash = Type_Resolution::collection('hash', type_string::instance(), $key->type_def());
			if ($hash === null || !isset($rule_types[$hash::class])) {
				throw new diagnostic($key->source(), $key->byte_start(), 'Rewrite rule keys must be int or string');
			}
			$this->literal($argument, $hash, null, $key);
		} else {
			$context = $argument instanceof ast_collection_expression ? ($rule_types[type_vector_string::class] ?? null)
				: ($typed ? type_string::instance() : null);
			$this->value($argument, destination: $context);
		}
		$type = $typed ? $argument->value_type() : $argument->type_def();
		return $rule_types[$type::class] ?? type_string::instance();
	}

	/** Validate supplied runtime arguments against their generated signature. */
	private function runtime_call(ast_call_expression $call, runtime_function|runtime_overloads $runtime, ?ast_expression $prepared = null): void
	{
		if ($call->unpacked ?? false) throw new diagnostic($call->source(), $call->byte_start(), 'Unpacking requires a variadic target');
		if ($runtime instanceof json_decoder_contract) $runtime = $this->record_decoder($call, $runtime);
		if ($runtime instanceof runtime_function && $runtime->retained_slot_types !== []) {
			if ($this->enclosing_lambda !== null || $this->owner->parent->is_async || $this->owner->parent->return_type_def() instanceof generator_type_definition) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Bound parameter frame lifetime is unproven');
			}
			$tail = array_slice($call->arguments, count($runtime->parameters) - 1);
			if ($tail === []) throw new diagnostic($call->source(), $call->byte_start(), $runtime->name() . ' requires at least one retained slot');
			$params = $runtime->parameters;
			foreach ($tail as $slot) {
				$this->value($slot);
				if (!isset($runtime->retained_slot_types[$slot->type_def()::class])) throw new diagnostic($slot->source(), $slot->byte_start(), 'Unsupported retained slot type');
				$params[] = $slot->type_def();
			}
			($this->bound_slots ??= new Bound_Slots())->bind($call, $tail);
			$runtime = new borrowed_slot_function($runtime, $params);
		}
		$slots = null;
		if ($call instanceof ast_named_call) {
			if (!($runtime instanceof runtime_function) || $runtime->parameter_names === null || $call->receiver !== null) {
				throw new diagnostic($call->source(), $call->byte_start(), ast_named_call::UNSUPPORTED_TARGET);
			}
			$call->named->bind_slots($call, $runtime);
			$slots = $call->named->slots;
		}
		$operands = $call->runtime_operands($runtime instanceof runtime_function ? $runtime : null);
		$prepared ??= $call->receiver;
		$php = $call->source()->flavor === flavor::php;
		$rule_group = $runtime instanceof rewrite_rule_overloads ? $runtime : null;
		$callback_group = $runtime instanceof regex_callback_overloads ? $runtime : null;
		if ($callback_group !== null) {
			$prepared = $operands[$runtime->argument_index] ?? throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
			$runtime = $this->regex_callback_binding($runtime, $prepared);
		}
		if ($runtime instanceof php_kind_predicate) {
			if (count($operands) !== 1) throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
			$arg = $operands[0];
			$this->value($arg, destination: $arg instanceof ast_collection_expression ? type_mixed::instance() : null);
			$type = $arg->type_def();
			$kind = $type instanceof vector_type_definition || $type instanceof hash_type_definition ? 'container' : $type->predicate_kind();
			if ($kind === '' && !($type instanceof boxed_type_definition)) {
				throw new diagnostic($arg->source(), $arg->byte_start(), $call->name . ' requires a scalar, vector, hash or box; received ' . $type->name);
			}
			$call->settle_runtime(new prepared_kind_predicate($call->name, $type, $runtime->kind, $kind === '' ? null : $kind === $runtime->kind));
			return;
		}
		if ($runtime instanceof runtime_overloads) {
			$variants = $runtime->by_arity[count($operands)] ?? null;
			if ($variants === null) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
			}
			if ($variants instanceof runtime_function) {
				$runtime = $variants;
			} else {
				$selection_index = $runtime->argument_index;
				$selected = $operands[$selection_index];
				if ($selected !== $prepared) {
					$this->value($selected, destination: $selected instanceof ast_collection_expression ? $runtime->list_context : null);
					$prepared = $selected;
				}
				$selector = $runtime->project_selector ? $selected->value_type() : $selected->type_def();
				if ($php && $selector instanceof carrier_type_definition && !isset($variants[$selector::class]) && ($payload = carrier_operand_payload($selector)) !== null) {
					$candidate = $variants[$payload::class] ?? null;
					if ($candidate?->php_value_input($selection_index) || ($candidate === null && $runtime instanceof json_encoder_overloads)) {
						$selector = $payload;
					}
				}
				$runtime = $variants[$selector::class] ?? ($runtime instanceof json_encoder_overloads
					? $this->record_encoder($runtime, $selector, $selected) : null);
				if ($runtime === null) {
					throw new diagnostic($selected->source(), $selected->byte_start(), ($selection_index === 0 ? 'Unsupported first argument type for ' : 'Unsupported argument type for ') . $call->name);
				}
			}
		}
		elseif (count($operands) < $runtime->required_parameters || count($operands) > count($runtime->parameters)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . $call->name);
		}
		$writes = [];
		$conversions = [];
		$pattern_type = null;
		$labels = $runtime->php_argument_labels !== [] && $call->source()->flavor === flavor::php ? $runtime->php_argument_labels : [];
		foreach ($operands as $index => $argument) {
			$slot = $slots[$index] ?? $index;
			if ($callback_group !== null && $index === $callback_group->argument_index + 1) {
				$this->value($argument, destination: $argument instanceof ast_collection_expression ? $callback_group->list_context : null);
				$subject = $argument->type_def();
				if ($php) $subject = carrier_operand_payload($subject) ?? $subject;
				$runtime = $callback_group->binding($runtime->callback->signature->inputs[0], $argument, $subject, $pattern_type);
				$prepared = $argument;
			}
			$type = $runtime->parameters[$slot];
			$typed = $php && $runtime->php_value_input($slot);
			$label = $labels === [] ? '' : $call->name . ' argument ' . ($slot + 1) . ' (' . $labels[$slot] . ') ';
			if ($slot === 0 && $callback_group !== null && $callback_group->pattern_inputs !== []) {
				$type = $pattern_type = $this->rewrite_input($callback_group->pattern_inputs, $argument, $typed);
			}
			elseif ($rule_group !== null && $slot < 2) {
				$type = $this->rewrite_input($rule_group->rule_types, $argument, $typed);
				if ($type !== $runtime->parameters[$slot]) {
					if ($slot === 1 && $runtime->parameters[0] === type_string::instance()) {
						throw new diagnostic($argument->source(), $argument->byte_start(), 'A replacement collection requires a pattern collection');
					}
					$parameters = $runtime->parameters;
					$parameters[$slot] = $type;
					$runtime = $runtime->with_inputs($parameters, $rule_group->rule_header);
				}
			}
			elseif ($argument !== $prepared && $argument !== $call->receiver && !($runtime instanceof borrowed_slot_function && $runtime->is_reference($index))) {
				$this->value($argument, destination: $labels === [] || $typed ? $type : null);
			}
			if ($argument === $call->receiver && $argument->member_borrow !== null && $type === $argument->member_type()) {
				if ($runtime->is_reference($slot)) Readonly_Access::write($argument, true);
			}
			elseif ($index === 0 && $runtime->receiver_method && $call->receiver !== null) {
				// Receiver preparation already checked field visibility; expression temporaries own their lifetime.
				if ($runtime->is_reference(0)) Readonly_Access::write($argument, true);
				if ($runtime->is_reference(0) && $argument instanceof ast_name_expression) {
					$this->bind_reference($argument, $type, $writes);
				}
			}
			elseif ($runtime->is_reference($slot)) {
				$this->bind_reference($argument, $type, $writes, label: $label);
			}
			elseif ($typed) {
				$conversion = $this->value_argument($argument, $type, $label === '' ? $call->name . ' ' : $label);
				if ($conversion !== value_conversion::identity) {
					if ($conversions === []) $conversions = array_fill(0, count($operands), value_conversion::identity);
					$conversions[$index] = $conversion;
				}
			}
			elseif ($type instanceof owned_callable_type) {
				$type->prepare_scalar_conversion($argument, conversion_context::typed_destination);
			}
			elseif ($argument->type_def() !== $type) {
				throw new diagnostic($argument->source(), $argument->byte_start(), ($label === '' ? $call->name . ' requires ' . $type->name
					: $label . 'requires exact ' . $type->name . ', received ' . $argument->type_def()->name));
			}
		}
		if ($writes !== []) $this->invalidate_references($call, $writes);
		$call->settle_runtime($runtime, $conversions);
		if ($call->receiver !== null && ($runtime->receiver_method || $runtime->free_receiver)
			&& ($call->suspending_operands || $call->has_runtime_conversions() || $call->has_nonliteral_arguments())) {
			$this->retain_member_place($call->receiver);
		}
	}

	/** Resolve the marker without evaluating it and watch the schema used by the native reader. */
	private function record_decoder(ast_call_expression $call, json_decoder_contract $contract): runtime_function
	{
		$marker = $call->arguments[1] ?? null;
		$python_type_name = $call->source()->flavor === flavor::python && $marker instanceof ast_name_expression
			&& !$this->resolve_value_name($marker, null, false);
		if (count($call->arguments) !== 2 || !($marker instanceof ast_type_marker || $python_type_name)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'json_decode_as requires text and a data record type marker');
		}
		$entry = $this->symbols->entry($marker->lexical_root()->type_key($marker->name));
		$this->dependencies?->watch($entry, $this->owner);
		$node = $entry->declaration();
		if (!($node instanceof ast_record_declaration)) throw new diagnostic($marker->source(), $marker->byte_start(), 'json_decode_as requires a concrete data record');
		return $this->decode_binding($node, $contract, $marker, false);
	}

	private function decode_binding(ast_record_declaration $node, json_decoder_contract $contract, ast_node $site, bool $cursor): runtime_function
	{
		$records = [];
		self::check_decode_schema($node->definition, $site, $this->symbols, $this->dependencies, $this->owner, $records, json: true);
		return new record_decode_function($node->definition, $contract, $cursor);
	}

	/** Request the shared field walk and recheck casts/calls when a contributing layout changes. */
	private function watch_encode_schema(record_type_definition $record): void
	{
		$this->dependencies?->watch($this->symbols->entry($record->declaration->symbol_key()), $this->owner, dependency_use::record_encode->value);
		for ($base = $record->base(); $base !== null; $base = $base->base()) {
			$this->dependencies?->watch($this->symbols->entry($base->declaration->symbol_key()), $this->owner);
		}
	}

	/** Validate and watch each embedded value schema once per cast. @param bool[] $seen @param string[] $path */
	private function check_boxed_schema(record_type_definition $record, boxed_type_definition $box, ast_cast_expression $site, array &$seen, array &$path): void
	{
		if (isset($seen[$record->name])) return;
		$seen[$record->name] = true;
		$this->watch_encode_schema($record);
		$data = $record->declaration->data_constructor;
		if (!$box->supports_record_export() || $record->declaration->kind !== record_kind::structure || $data === null || $record->constructor() !== null) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Boxed record export requires a field-derived data struct and structural Encoder'
				. ($path === [] ? '' : ' at ' . implode(' -> ', $path)));
		}
		foreach ($data->parameters as $field) {
			$type = $field->type_def();
			$leaf = $type instanceof native_sequence_type ? $type->write_type() : $type;
			if ($leaf instanceof record_type_definition) {
				$path[] = $record->name . '::$' . $field->short_name;
				$this->check_boxed_schema($leaf, $box, $site, $seen, $path);
				array_pop($path);
			} elseif (!$box->exports_scalar($leaf)) {
				$path[] = $record->name . '::$' . $field->short_name;
				throw new diagnostic($site->source(), $site->byte_start(), 'Cannot export ' . implode(' -> ', $path) . ' (' . $type->name . ') to ' . $box->name);
			}
		}
	}

	/** Resolve field encoders using the active module's published overloads. */
	private function record_encoder(json_encoder_overloads $encoders, type_definition $input, ast_expression $site): ?runtime_function
	{
		$record = $input instanceof shared_record_type_definition ? $input->payload : $input;
		if (!($record instanceof record_type_definition)) return null;
		if ($record->declaration->data_constructor === null || $record->constructor() !== null) return null;
		$seen = [];
		$this->check_encode_schema($record, $encoders, $site, $seen);
		$this->dependencies?->watch($this->symbols->entry($record->declaration->symbol_key()), $this->owner, dependency_use::record_json_encode->value);
		$variants = $encoders->by_arity[1];
		$published = reset($variants);
		return new record_encode_function($record, $input, $published, $encoders);
	}

	/** Check the declared fields of every reachable schema and subscribe the call to layout changes. @param bool[] $seen */
	private function check_encode_schema(record_type_definition $record, json_encoder_overloads $encoders, ast_expression $site, array &$seen): void
	{
		if (isset($seen[$record->name])) return;
		$seen[$record->name] = true;
		$this->watch_encode_schema($record);
		foreach ($record->declaration->data_constructor->parameters as $field) {
			$type = $field->type_def();
			$leaf = $type instanceof native_sequence_type ? $type->write_type() : $type;
			if (isset($encoders->by_arity[1][$leaf::class])) continue;
			$nested = $leaf instanceof shared_record_type_definition ? $leaf->payload : $leaf;
			if ($nested instanceof record_type_definition && $nested->declaration->data_constructor !== null && $nested->constructor() === null) {
				$this->check_encode_schema($nested, $encoders, $site, $seen);
				continue;
			}
			throw new diagnostic($site->source(), $site->byte_start(), 'No JSON encoder for ' . $record->name . '::$' . $field->short_name . ' (' . $type->name . ')');
		}
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

	/** Check the return operand once against the callable's declared result. */
	private function return_conversion(ast_expression $value, type_definition $result): int /** uint16 */
	{
		if ($value instanceof ast_ternary_expression && $value->source()->flavor === flavor::php && $result !== type_void::instance()) {
			++$this->expression_visits;
			$this->ternary($value, null, $result);
			return value_conversion::identity;
		}
		$this->value($value, destination: $result);
		return $this->prepared_return_conversion($value, $result);
	}

	/** Apply the return contract to a settled value without evaluating its syntax again. */
	private function prepared_return_conversion(ast_expression $value, type_definition $result): int /** uint16 */
	{
		if ($result === type_void::instance()) {
			if ($value->type_def() !== $result) throw new diagnostic($value->source(), $value->byte_start(), 'Void function cannot return a value');
			return value_conversion::identity;
		}
		if ($result instanceof class_union_type_definition) $this->watch_union_source($value);
		if ($result instanceof carrier_type_definition && $result->family === 'result') {
			$source = $value->type_def();
			if ($source instanceof type_exception) return value_conversion::forwarded_error;
			if ($source instanceof shared_record_type_definition && $source !== $result->payload_type) {
				$this->dependencies?->watch($this->symbols->entry($source->payload->declaration->symbol_key()), $this->owner);
				if ($source->payload->exception_descendant()) return value_conversion::forwarded_error;
			}
		}
		return $result->prepare_conversion($value, conversion_context::typed_destination);
	}

	/** Demand the receiver in this lexical scope, including implicit ancestor dispatch in PHP arrows. */
	private function borrow_receiver(ast_node $site): void
	{
		if ($this->enclosing_lambda !== null) {
			if (!$this->enclosing_lambda->explicit_receiver
				&& ($this->enclosing_lambda->body instanceof ast_block || $site->source()->flavor !== flavor::php)) {
				throw new diagnostic($site->source(), $site->byte_start(), 'Closure must capture this receiver');
			}
			$this->borrows_receiver = true;
			return;
		}
		$this->receiver_owner($site);
		$this->constructor_initialization?->complete($site);
	}

	/** Resolve the current instance independently of the declaring class used for lexical access. */
	private function receiver_owner(ast_node $site): ast_method_owner
	{
		if ($this->initializing_base) throw new diagnostic($site->source(), $site->byte_start(), 'Current instance is unavailable during base initialization');
		if ($this->owner->parent->is_static) throw new diagnostic($site->source(), $site->byte_start(), 'Static method has no this receiver');
		$record = $this->owner->parent->parent;
		if (!($record instanceof ast_method_owner)) throw new diagnostic($site->source(), $site->byte_start(), 'this requires an instance method');
		return $record;
	}

	/** Bind a whole caller-owned result through the same origin check for named and anonymous returns. */
	private function reference_return(?ast_expression $value, ast_parameter_declaration $origin, ast_node $site): void
	{
		if ($value instanceof ast_call_expression) {
			$this->value($value);
			$selected = $value->reference_origin();
		} else {
			$selected = $this->lexical_reference_slot($value);
		}
		if ($selected !== $origin) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Reference return must originate in its writable parameter');
		}
		if ($value instanceof ast_name_expression) {
			++$this->expression_visits;
			$value->settle_local($origin, unknown: true);
		}
	}

	/** A lambda body uses the enclosing work owner, with a fresh lexical and value environment. */
	private function lambda(ast_lambda_expression $lambda, ?callback_type_definition $expected, ?type_definition $transform_element = null): void
	{
		$function = $this->owner->parent;
		$variadic = $lambda->variadic_slot();
		if ($expected !== null && (count($lambda->parameters) !== count($expected->inputs) || $variadic !== $expected->variadic_slot)) {
			throw new diagnostic($lambda->source(), $lambda->byte_start(), 'Expected exact callback signature ' . $expected->name);
		}
		$result = $lambda->result === null
			? ($expected?->result ?? throw new diagnostic($lambda->source(), $lambda->byte_start(), 'Lambda requires an explicit result type without a callback context'))
			: Type_Resolution::signature($lambda->result, $this->symbols, $function, $this->dependencies, true, $this->owner);
		if (!Type_Resolution::callback_component($result) && !($transform_element !== null && $result instanceof record_type_definition)) {
			throw new diagnostic($lambda->result->source(), $lambda->result->byte_start(), 'Lambda requires a supported result type');
		}
		$locals = [];
		$inputs = [];
		$modes = [];
		$optional = false;
		foreach ($lambda->parameters as $index => $parameter) {
			$name = $parameter->declaration_key();
			if (isset($locals[$name])) {
				$reason = $lambda->parent instanceof ast_inline_job ? 'Duplicate inline job binding: ' : 'Duplicate lambda parameter: ';
				throw new diagnostic($parameter->source(), $parameter->byte_start(), $reason . $name);
			}
			$syntax = $parameter->type_reference;
			$type = ($syntax === null || ($index === $variadic && $syntax->first_child === null))
				? ($expected?->inputs[$index] ?? $transform_element ?? throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Lambda requires an explicit parameter type without a callback context'))
				: Type_Resolution::signature($syntax, $this->symbols, $function, $this->dependencies, watcher: $this->owner);
			if ($index === $variadic) Function_Preparation::require_pack_type($parameter, $type);
			if ($parameter->mode === passing_mode::readonly_reference && !Type_Resolution::readonly_collection_input($type)) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Read-only callable references require a borrowable collection input');
			}
			if (!Type_Resolution::callback_component($type) && !($transform_element !== null && $type instanceof record_type_definition)) {
				throw new diagnostic($parameter->source(), $parameter->byte_start(), 'Lambda requires supported value or mutable reference parameters');
			}
			$parameter->settle_type($type);
			if ($index !== $variadic && ($optional || $parameter->default_value !== null)) {
				Function_Preparation::parameter_default($parameter, $this->symbols, $this->owner, $this->dependencies);
				$optional = true;
			}
			$locals[$name] = $parameter;
			$inputs[] = $type;
			if ($parameter->mode !== passing_mode::value) $modes[$index] = $parameter->mode;
		}
		$borrowed /** array<uint32,ast_closure_capture> */ = [];
		$writes /** list<uint32> */ = [];
		foreach ($lambda->captures as $capture) {
			$name = $capture->declaration_key();
			if (isset($locals[$name])) {
				$reason = $locals[$name] instanceof ast_closure_capture ? 'Duplicate closure capture: ' : 'Capture conflicts with closure parameter: ';
				throw new diagnostic($capture->source(), $capture->byte_start(), $reason . $name);
			}
			$this->value($capture->initializer);
			$origin = $capture->initializer->local_def();
			if (!$capture->by_reference && $origin instanceof ast_closure_capture && ast_writable_element::origin($origin) !== null) {
				throw new diagnostic($capture->source(), $capture->byte_start(), 'Nested by-value capture of a borrowed foreach element is deferred');
			}
			$capture_type = $capture->initializer->type_def();
			$conversion = $capture->by_reference ? value_conversion::identity : $this->prepare_local_initialization($capture, $capture_type);
			$capture->settle_type($capture_type, constant_evaluation::unavailable, true, $conversion);
			if ($capture->by_reference) {
				// Different capture names can borrow the same slot through local aliases.
				$this->require_collection_available($capture, $origin);
				if (ast_writable_element::origin($origin) !== null && (!($lambda->parent instanceof ast_local_declaration)
					|| $lambda->parent->type_reference !== null || $lambda->parent->initializer !== $lambda)) {
					throw new diagnostic($capture->source(), $capture->byte_start(), 'A borrowed foreach element requires an inferred local closure');
				}
				$id = $origin->storage_id();
				if (!isset($borrowed[$id])) {
					$borrowed[$id] = $capture;
					if (Readonly_Access::readonly_origin($origin) === null) $writes[] = $id;
				}
				$locals[$name] = $borrowed[$id];
			} else {
				$locals[$name] = $capture;
			}
		}
		$scope = new self();
		$scope->owner = $this->owner;
		$scope->symbols = $this->symbols;
		$scope->dependencies = $this->dependencies;
		$scope->local_frames = [$locals];
		$scope->enclosing_lambda = $lambda;
		$scope->borrows_receiver = $lambda->explicit_receiver;
		$origin = $lambda->returns_reference ? Function_Preparation::reference_return_origin($lambda, $result) : null;
		$this->dependencies?->begin_lambda($this->owner, $lambda);
		$conversion = value_conversion::identity;
		if ($lambda->body instanceof ast_expression) {
			if ($origin === null) $conversion = $scope->return_conversion($lambda->body, $result);
			else $scope->reference_return($lambda->body, $lambda->parameters[$origin], $lambda->body);
		}
		if ($scope->borrows_receiver) $this->borrow_receiver($lambda);
		$type = new lambda_type_definition(new callback_type_definition($result, $inputs, $modes, $origin, $variadic), $lambda->captures !== [],
			$scope->borrows_receiver, $scope->borrows_receiver || $borrowed !== [], $optional);
		$lambda->settle_lambda($type, $conversion, $writes);
		if ($lambda->body instanceof ast_block) {
			$errors = Declaration_Diagnostics::locals($lambda->body);
			if ($errors !== []) throw new diagnostic_group($errors);
			$completion = $scope->sequence($lambda->body->statements, $lambda->source(), null, $lambda);
			if ($result !== type_void::instance() && ($completion & statement_completion::fallthrough) !== 0) {
				throw new diagnostic($lambda->source(), $lambda->lexical_root()->byte_at($lambda->body->end - 1),
					'Non-void closure requires a value return');
			}
		}
		$this->statement_visits += $scope->statement_visits;
		$this->expression_visits += $scope->expression_visits;
		$this->name_lookups += $scope->name_lookups;
	}

	/** Only a suspension examines its parent path. Eligibility uses the facts from the ordinary binder. */
	private function prepare_await_context(ast_await_expression $syntax): void
	{
		if ($this->enclosing_lambda !== null) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Await requires a function body outside a lambda');
		if (!$this->owner->parent->is_async) return;
		for ($context = $syntax->parent; $context instanceof ast_expression || $context instanceof ast_match_arm
			|| $context instanceof ast_keyed_entry; $context = $context->parent) {
			if ($context instanceof ast_comparison_chain || $context instanceof ast_match_expression || $context instanceof ast_collection_expression || $context instanceof ast_keyed_collection_expression) {
				$context->suspends = true;
			} elseif ($context instanceof ast_call_expression) {
				$context->suspending_operands = true;
			}
		}
	}

	/** Bind a consumed producer and request the native entry selected by its consumer. */
	private function async_producer(ast_call_expression $producer, ?string $initializing, dependency_use $use,
		ast_await_expression|ast_call_expression $site): ast_function_declaration
	{
		$this->call($producer, $initializing, $use);
		$target = $producer->has_direct_signature() ? $producer->function_def() : null;
		if ($target instanceof ast_function_declaration && $target->is_async) return $target;
		throw new diagnostic($site->source(), $site->byte_start(),
			($site instanceof ast_await_expression ? 'Await' : 'async_wait') . ' requires an async function');
	}

	/** Provision a whole batch, exposing the iteration index only while preparing repeated bindings. */
	private function joined_batch(ast_call_expression $call, ?string $initializing): void
	{
		if ($this->dependencies?->mt === null) throw new diagnostic($call->source(), $call->byte_start(), 'Joined segments require fresh compilation');
		Mt_Provisioning::batch($call, $this->symbols, $this->dependencies, $this->owner, function (ast_expression $arg, ?callback_type_definition $signature = null, bool $writable_segment = false) use ($initializing): void {
			$this->value($arg, $initializing, allow_callback: $signature !== null, destination: $signature);
			if ($writable_segment) $this->require_index_write_available($arg);
		}, function (ast_produced_binding $index, \Closure $bind): void {
			$this->local_frames[] = [$index->declaration_key() => $index];
			$this->forget_values();
			try {
				$bind();
			} finally {
				array_pop($this->local_frames);
				$this->forget_values();
			}
		});
	}

	/** Resolve only the published signature, then validate and convert arguments in source order. */
	private function call(ast_call_expression $call, ?string $initializing = null, ?dependency_use $async_use = null): void
	{
		if ($call instanceof ast_parallel_expression) {
			if (!type_registry::HAS_MT_SEGMENTS) throw new diagnostic($call->source(), $call->byte_start(), 'MT segments unavailable in this runtime');
			$this->joined_batch($call, $initializing);
			return;
		}
		if ($call->qualifier === call_qualifier::stored_value) {
			$this->value($call->receiver, $initializing);
			$type = $call->receiver->type_def();
			if (!($type instanceof owned_callable_type)) throw new diagnostic($call->source(), $call->byte_start(), 'Invocation requires an owned callable: ' . $call->name);
			$this->dependencies?->callable_site($this->owner, $call);
			$call->settle_callable_call(null, $type->signature, $this->bind_arguments($call, $type->signature, $initializing));
			return;
		}
		$type_symbol = null;
		$class = null;
		if ($call->source()->flavor === flavor::python && $call->receiver === null && $call->member_separator === null) {
			$type_symbol = $this->symbols->entry($call->lexical_root()->type_key($call->name));
			$this->dependencies?->watch($type_symbol, $this->owner);
			$candidate = $type_symbol->declaration();
			if ($candidate instanceof ast_record_declaration && $candidate->kind === record_kind::class_type) {
				$class = $candidate;
			}
		}
		$binding = null;
		if ($call->receiver === null && $call->member_separator === null
			&& ($call->qualifier === call_qualifier::local || ($call->qualifier === call_qualifier::ordinary && $call->source()->flavor !== flavor::php))) {
			$binding = ($this->has_lambda_local || $call->qualifier === call_qualifier::local)
				? $this->find_local($call->name) : ($this->local_frames[0][$call->name] ?? null);
		}
		$type = $binding?->type_def();
		$signature = $type instanceof lambda_type_definition || $type instanceof owned_callable_type || $type instanceof callback_view_type ? $type->signature : $type;
		if ($signature instanceof callback_type_definition && $this->find_local($call->name) === $binding) {
			if ($class !== null) $this->ambiguous_construction($call);
			$this->name_lookups++;
			$target = $type instanceof lambda_type_definition ? $binding->initializer : $signature;
			$call->settle_callable_call($binding, $signature, $this->bind_arguments($call, $target, $initializing));
			if ($target instanceof ast_lambda_expression) $this->dependencies?->closure_call($this->enclosing_lambda ?? $this->owner, $target);
			elseif (!($type instanceof callback_view_type)) $this->dependencies?->callable_site($this->owner, $call);
			if ($target instanceof ast_lambda_expression && $target->borrowed_writes !== []) {
				$this->invalidate_references($call, $target->borrowed_writes);
			}
			return;
		}
		if ($call->qualifier === call_qualifier::local) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Invocation requires a typed callable local: ' . $call->name);
		}
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
					if ($call->member_separator === token_kind::member) $receiver->prepare_member_borrow();
					$record = $receiver->member_type();
					if ($record instanceof shared_record_type_definition) $record = $record->payload;
				}
				if ($record instanceof record_type_definition || $record instanceof enum_type_definition) {
					if ($call->source()->flavor === flavor::cpp && $call->member_separator === token_kind::dot) {
						throw new diagnostic($call->source(), $call->byte_start(), 'Struct methods use -> in the C++ flavor');
					}
					$symbol = $record instanceof record_type_definition
						? $record->method_symbol($name, $this->symbols, $this->owner, $this->dependencies, $call)
						: $this->symbols->entry('method:' . $record->name . '::' . $name);
					if ($symbol !== null) $this->dependencies?->watch($symbol, $this->owner,
						$call instanceof ast_named_call ? Dependency_Watches::PARAMETER_NAMES : 0);
					$method = $symbol?->declaration();
					if ($method === null && !$static && $record instanceof record_type_definition && $record->exception_descendant()) {
						$binding = type_registry::exception()->runtime_method($name);
						if ($binding !== null) {
							if ($receiver instanceof ast_this_expression) $this->constructor_initialization?->complete($call);
							$this->runtime_call($call, $binding);
							return;
						}
					}
					if ($method === null || $method->is_constructor()) throw new diagnostic($call->source(), $call->byte_start(), 'Unknown method ' . $record->name . '::' . $name);
					$interface_dispatch = !$static && $record instanceof record_type_definition && $record->interface_dispatch($name);
					if ($static !== $method->is_static) {
						$message = $static ? 'Expression-qualified call requires a static method: ' : 'Static method requires a type-qualified call: ';
						throw new diagnostic($call->source(), $call->byte_start(), $message . $method->name());
					}
					if (!$static) {
						$this->retain_member_place($receiver);
						Readonly_Access::write($receiver, true);
						if ($receiver instanceof ast_this_expression) $this->constructor_initialization?->complete($call);
					}
					$this->source_call($call, $symbol, $method, $initializing, called: $static ? $record : null);
					$call->interface_dispatch = $interface_dispatch;
					return;
				}
				$key = $call->source()->flavor->name . ':' . $receiver->member_type()->name . ':' . $name;
				$runtime = $receiver->member_type()->runtime_method($name) ?? runtime_functions::resolve($key);
				if ($runtime === null) {
					throw new diagnostic($call->source(), $call->byte_start(), 'Member calls: unknown method ' . $name . ' on ' . $receiver->type_def()->name);
				}
				if ($runtime->receiver_method || ($runtime instanceof runtime_function && $runtime->free_receiver)) {
					$expected = in_array($call->source()->flavor, [flavor::js, flavor::python], true) ? token_kind::dot : token_kind::member;
					if ($call->member_separator !== $expected) {
						throw new diagnostic($call->source(), $call->byte_start(), 'Native receiver methods use -> in PHP/C++ and . in JS/Python');
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
		$function = $symbol->declaration();
		$uses = $call instanceof ast_named_call ? Dependency_Watches::PARAMETER_NAMES : 0;
		if ($function instanceof ast_function_declaration && $this->enclosing_lambda === null) {
			$this->dependencies?->watch_call($symbol, $this->owner, $uses);
		} else {
			$this->dependencies?->watch($symbol, $this->owner, $uses);
			if ($function instanceof ast_function_declaration) $this->dependencies?->closure_call($this->enclosing_lambda, $symbol);
		}
		if ($class !== null && $function instanceof ast_function_declaration) $this->ambiguous_construction($call);
		if (!($function instanceof ast_function_declaration)) {
			// PHP has distinct type qualification. C++ and JS first try an existing namespace function.
			if ($call->source()->flavor !== flavor::php && ($last = strrpos($call->name, '\\')) > 0) {
				$qualified = substr($call->name, 0, $last) . '::' . substr($call->name, $last + 1);
				if ($this->qualified_call($call, $qualified, $initializing, false)) return;
			}
			if ($call->unpacked ?? false) throw new diagnostic($call->source(), $call->byte_start(), 'Unpacking requires a variadic target');
			$runtime = $file->type_availability->runtime_function($file->runtime_function_key($call->name, $key), $file->source()->flavor);
			if ($runtime !== null && $class !== null && $file->type_availability->allows($runtime instanceof runtime_factory ? null : $runtime->required_module)) {
				$this->ambiguous_construction($call);
			}
			if ($runtime !== null && $class === null) {
				if (!($runtime instanceof runtime_factory) && !$file->type_availability->allows($runtime->required_module)) {
					throw new diagnostic($call->source(), $call->byte_start(),
						'Function ' . $call->name . ' requires module ' . $runtime->required_module);
				}
				if ($runtime === runtime_factory::vector_filled) {
					if ($call instanceof ast_named_call) throw new diagnostic($call->source(), $call->byte_start(), ast_named_call::UNSUPPORTED_TARGET);
					if (count($call->arguments) !== 2) throw new diagnostic($call->source(), $call->byte_start(), 'vector_filled requires a count and fill value');
					$fill = $call->arguments[1];
					$this->value($fill, $initializing);
					$vector = Type_Resolution::collection('vector', $fill->type_def(), null);
					if (!($vector instanceof vector_type_definition)) throw new diagnostic($fill->source(), $fill->byte_start(), 'Unsupported vector_filled element type');
					$this->runtime_call($call, $vector->filled_function(), $fill);
					return;
				}
				if ($runtime === runtime_factory::in_array) {
					$this->collection_membership($call);
					return;
				}
				if ($runtime instanceof runtime_factory) {
					$this->collection_transform($call, $runtime, $initializing);
					return;
				}
				$this->runtime_call($call, $runtime);
				return;
			}
			if ($call instanceof ast_named_call && $class === null) throw new diagnostic($call->source(), $call->byte_start(), ast_named_call::UNSUPPORTED_TARGET);
			$builtin = $file->imported_function_target($call->name) === null ? ltrim($call->name, '\\') : null;
			if ($builtin === 'hash_contains' || $builtin === 'hash_find' || $builtin === 'hash_get_or_null') {
				if ($class !== null) $this->ambiguous_construction($call);
				if (count($call->arguments) !== 2) throw new diagnostic($call->source(), $call->byte_start(), $builtin . ' requires a collection and key');
				$receiver = $call->arguments[0];
				$this->value($receiver, $initializing);
				$type = $receiver->type_def();
				$binding = match ($builtin) {
					'hash_contains' => $type instanceof key_presence_type ? $type->presence_function() : null,
					'hash_get_or_null' => $type instanceof shared_value_lookup_type ? $type->shared_lookup_function() : null,
					'hash_find' => $type instanceof key_lookup_type ? $type->lookup_function() : null,
				};
				if ($binding === null) throw new diagnostic($receiver->source(), $receiver->byte_start(), match ($builtin) {
					'hash_contains' => 'hash_contains requires a key-presence collection',
					'hash_find' => 'hash_find requires a collection with published optional lookup',
					'hash_get_or_null' => 'hash_get_or_null requires a hash with shared class values',
				});
				$this->runtime_call($call, $binding, $receiver);
				return;
			}
			if ($builtin === 'mt_join' && type_registry::HAS_MT_SEGMENTS) {
				if ($class !== null) $this->ambiguous_construction($call);
				$this->joined_batch($call, $initializing);
				return;
			}
			if ($builtin === 'async_wait') {
				$producer = $call->arguments[0] ?? null;
				if (count($call->arguments) !== 1 || !($producer instanceof ast_call_expression)) {
					throw new diagnostic($call->source(), $call->byte_start(), 'async_wait requires one direct async call');
				}
				$this->async_producer($producer, $initializing, dependency_use::async_wait, $call);
				$call->settle_blocking_wait($producer);
				return;
			}
			if ($builtin === 'enum_from_value' || $builtin === 'enum_try_from_value') {
				if ($class !== null) $this->ambiguous_construction($call);
				$nullable = $builtin === 'enum_try_from_value';
				if (count($call->arguments) !== 2 || !($call->arguments[0] instanceof ast_type_marker)) {
					throw new diagnostic($call->source(), $call->byte_start(),
						$builtin . ' requires an enum type marker and one ' . ($nullable ? 'string' : 'integer'));
				}
				$marker = $call->arguments[0];
				$entry = $this->symbols->entry($file->type_key($marker->name));
				$use = $nullable ? dependency_use::enum_try_from_value : dependency_use::enum_from_value;
				$this->dependencies?->watch($entry, $this->owner, $use->value);
				$enum = $entry->declaration();
				if (!($enum instanceof ast_enum_declaration)) throw new diagnostic($marker->source(), $marker->byte_start(), $builtin . ' target must be an enum');
				$string_backing = $enum->definition->backing() === type_string::instance();
				if ($nullable && !$string_backing) throw new diagnostic($marker->source(), $marker->byte_start(), $builtin . ' requires a string-backed enum');
				$argument = $call->arguments[1];
				$this->value($argument, $initializing);
				$input = $argument->type_def();
				if ($string_backing) {
					if ($input !== type_string::instance()) throw new diagnostic($argument->source(), $argument->byte_start(), $builtin . ' requires a string');
				} elseif (!defined($input::class . '::MINIMUM') || !defined($input::class . '::MAXIMUM')) {
					throw new diagnostic($argument->source(), $argument->byte_start(), $builtin . ' requires an integer');
				}
				$binding = $enum->definition->helper_function($use);
				$conversion = $this->value_argument($argument, $binding->parameters[0]);
				$call->settle_runtime($binding, [$conversion]);
				return;
			}
			if ($builtin === 'enum_value' || $builtin === 'enum_name') {
				if ($class !== null) $this->ambiguous_construction($call);
				if (count($call->arguments) !== 1) throw new diagnostic($call->source(), $call->byte_start(), $builtin . ' requires one enum argument');
				$argument = $call->arguments[0];
				$this->value($argument, $initializing);
				$type = $argument->type_def();
				if (!($type instanceof enum_type_definition)) throw new diagnostic($argument->source(), $argument->byte_start(), $builtin . ' requires an enum');
				$call->settle_runtime($this->enum_projection($type, $builtin === 'enum_name' ? dependency_use::enum_name : dependency_use::enum_value));
				return;
			}
			$operation = match ($builtin) {
				'count' => collection_cardinality::count,
				'empty' => collection_cardinality::empty,
				default => null,
			};
			if ($operation === null) {
				if ($class !== null) {
					$this->construction($call, $class->definition->required, $initializing, $type_symbol);
					return;
				}
				throw new diagnostic($call->source(), $call->byte_start(), 'Unknown function: ' . $call->name);
			}
			if ($class !== null) $this->ambiguous_construction($call);
			if (count($call->arguments) !== 1) {
				throw new diagnostic($call->source(), $call->byte_start(), $operation->name . ' requires one argument');
			}
			$receiver = $call->arguments[0];
			$this->value($receiver, $initializing);
			$type = $receiver->type_def();
			$result = $type instanceof counted_collection_type ? $type->cardinality_result($operation) : null;
			if ($result === null) {
				$message = $operation === collection_cardinality::empty
					? 'empty requires a string or collection' : 'count requires a type that supports counting';
				throw new diagnostic($receiver->source(), $receiver->byte_start(), $message);
			}
			$call->settle_cardinality($operation, $result);
			return;
		}
		$this->source_call($call, $symbol, $function, $initializing, async_use: $async_use);
	}

	private function ambiguous_construction(ast_call_expression $call): never
	{
		throw new diagnostic($call->source(), $call->byte_start(), 'Ambiguous Python call: both function and class named ' . $call->name);
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
			$symbol = $record->definition->method_symbol($name, $this->symbols, $this->owner, $this->dependencies, $call);
		}
		else {
			$symbol = $this->symbols->entry('method:' . Source_Names::resolve($qualified, $this->owner->scope->namespace));
			$this->dependencies?->watch($symbol, $this->owner);
		}
		$method = $symbol?->declaration();
		if (!($method instanceof ast_function_declaration)) {
			if ($name === 'decode' && $record instanceof ast_record_declaration && $record->is_data
				&& $call->lexical_root()->type_availability->allows('runtime-json') && type_registry::resolve('json_pull_reader') !== null) {
				$contract = runtime_functions::resolve('php:func:json_decode_as');
				if ($contract instanceof json_decoder_contract) {
					$this->runtime_call($call, $this->decode_binding($record, $contract, $call, true));
					return true;
				}
			}
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
		$symbol = $selected ?? $lookup->method_symbol($name, $this->symbols, $this->owner, $this->dependencies, $call);
		$method = $symbol?->declaration();
		if (!($method instanceof ast_function_declaration) || $method->is_constructor()) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Unknown ancestor method: ' . $name);
		}
		if ($method->is_static) {
			$this->source_call($call, $symbol, $method, $initializing, called: $lookup);
			return;
		}
		if ($this->enclosing_lambda !== null) $this->borrow_receiver($call);
		if ($this->owner->parent->is_static) throw new diagnostic($call->source(), $call->byte_start(), 'Instance method requires a receiver: ancestor call has no current instance');
		if ($this->initializing_base) throw new diagnostic($call->source(), $call->byte_start(), 'Current instance is unavailable during base initialization');
		if ($method->is_abstract) throw new diagnostic($call->source(), $call->byte_start(), 'Cannot call an abstract ancestor implementation');
		$this->constructor_initialization?->complete($call);
		$this->source_call($call, $symbol, $method, $initializing, true);
	}

	/** Standalone preparation has no subscription to retain. */
	private function access_watcher(): ?dependency_watcher
	{
		return $this->dependencies === null ? null : $this->owner;
	}

	/** Shared positional admission, conversions and reference invalidation for source calls. */
	private function source_call(ast_call_expression $call, symbol_entry $symbol, ast_function_declaration $function, ?string $initializing, bool $ancestor_dispatch = false, ?record_type_definition $called = null, ?dependency_use $async_use = null): void
	{
		if ($function->is_async && $async_use === null) throw new diagnostic($call->source(), $call->byte_start(), 'Async call requires await');
		if ($function->is_destructor()) throw new diagnostic($call->source(), $call->byte_start(), 'Explicit destructor invocation is forbidden');
		$context = $this->owner->parent->parent;
		$result = $function->return_type_def();
		if ($result instanceof record_type_definition) $this->require_inline_cleanup($result, $call);
		$receiver = null;
		if ($function->is_protected && !$function->is_static) {
			$receiver = $ancestor_dispatch && $context instanceof ast_record_declaration ? $context->definition : $call->receiver?->member_type();
			if ($receiver instanceof shared_record_type_definition) $receiver = $receiver->payload;
		}
		$function->require_access($context, $call, $receiver instanceof record_type_definition ? $receiver : null, $this->access_watcher());
		if (($called !== null || $ancestor_dispatch) && $call instanceof ast_named_call) {
			$this->dependencies?->watch($symbol, $this->owner, Dependency_Watches::PARAMETER_NAMES);
		}
		$conversions = $this->bind_arguments($call, $function, $initializing);
		if ($function->is_direct_final_method()) {
			if ($this->enclosing_lambda === null) $this->dependencies?->watch_call($symbol, $this->owner);
			else $this->dependencies?->closure_call($this->enclosing_lambda, $symbol);
		}
		$instance = null;
		if ($called !== null && $this->dependencies !== null) {
			$forwarded = $call->qualifier === call_qualifier::ordinary ? null : $this->owner->parent->instance?->called;
			$instance = $this->dependencies->static_call($this->owner, $symbol,
				$forwarded ?? $this->symbols->entry($called->declaration->symbol_key()));
			$this->dependencies->closure_call($this->enclosing_lambda ?? $this->owner, $instance);
		}
		if ($function->is_async) {
			$this->dependencies?->watch($symbol, $this->owner, $async_use->value);
		}
		$call->settle_function($symbol, $conversions, $ancestor_dispatch, $instance);
	}

	/** Inline and shared construction use the same published signature and argument checks. */
	private function construction(ast_construction_expression|ast_call_expression $site, type_definition $type, ?string $initializing,
		?symbol_entry $target = null): void
	{
		$shared = $site instanceof ast_new_expression || $site instanceof ast_call_expression || ($site instanceof ast_data_construction && $type instanceof shared_record_type_definition);
		$payload = $type instanceof shared_record_type_definition && $shared ? $type->payload : $type;
		if ($payload instanceof record_type_definition && !($site instanceof ast_base_initialization)) $payload->require_complete_value($site, $shared);
		$constructor = $payload instanceof record_type_definition ? $payload->constructor() : null;
		if ($constructor === null && $site instanceof ast_data_construction) {
			$site->settle_construction($type, $this->bind_arguments($site, $payload->declaration->data_constructor, $initializing));
			return;
		}
		if ($constructor === null && $payload instanceof record_type_definition && $payload->requires_constructor_inputs()) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Record requires named data inputs: ' . $payload->name);
		}
		if ($constructor === null && $site->arguments !== []) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Only zero-argument initialization is available for ' . $type->name);
		}
		if ($constructor !== null) {
			$this->dependencies?->watch($this->symbols->entry($constructor->symbol_key()), $this->owner,
				isset($site->named) ? Dependency_Watches::PARAMETER_NAMES : 0);
			$constructor->require_access($this->owner->parent->parent, $site, watcher: $this->access_watcher());
		}
		$conversions = $constructor === null ? [] : $this->bind_arguments($site, $constructor, $initializing);
		if ($site instanceof ast_base_initialization && $site->named?->reordered()) {
			$this->dependencies?->watch($this->symbols->entry($constructor->symbol_key()), $this->owner, dependency_use::reordered_base->value);
		}
		if ($site instanceof ast_call_expression) {
			$site->settle_construction($target, $type, $conversions);
		}
		else $site->settle_construction($type, $conversions);
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

	/** A concrete source can leave the destination union after an ancestry edit. */
	private function watch_union_source(ast_expression $value): void
	{
		$source = $value->type_def();
		if ($source instanceof shared_record_type_definition) {
			$this->dependencies?->watch($this->symbols->entry($source->payload->declaration->symbol_key()), $this->owner);
		}
	}

	/** The typed PHP membership slice compares exact scalar elements with their published equality. */
	private function collection_membership(ast_call_expression $call): void
	{
		if ($call instanceof ast_named_call) throw new diagnostic($call->source(), $call->byte_start(), ast_named_call::UNSUPPORTED_TARGET);
		$count = count($call->arguments);
		if ($count !== 2 && $count !== 3) throw new diagnostic($call->source(), $call->byte_start(), 'in_array requires a needle, a collection and an optional strict flag');
		[$needle, $source] = $call->arguments;
		$this->value($needle);
		$type = $needle->type_def();
		$this->value($source, destination: $source instanceof ast_collection_expression ? Type_Resolution::collection('vector', $type, null) : null);
		$input = $source->type_def();
		if (!($input instanceof collection_value_source)
			|| $type !== $input->iteration_value_type() || $type->predicate_kind() === ''
			|| $type->binary_result(binary_operator::equal, $type) !== type_bool::instance()) {
			throw new diagnostic($call->source(), $call->byte_start(), 'in_array requires value traversal with a matching comparable scalar needle');
		}
		$parameters = [$type, $input];
		if ($count === 3) {
			$flag = $call->arguments[2];
			$this->value($flag);
			if ($flag->type_def() !== type_bool::instance()) throw new diagnostic($flag->source(), $flag->byte_start(), 'in_array strict flag requires bool');
			$parameters[] = type_bool::instance();
		}
		$this->retain_member_place($source);
		$call->settle_runtime(new collection_membership_function('in_array', $parameters, type_bool::instance(), '::scpp::detail::any_value', 'scpp/collection_scan.hpp'));
	}

	/** Select a concrete traversal and result using the settled input and callback types. */
	private function collection_transform(ast_call_expression $call, runtime_factory $operation, ?string $initializing): void
	{
		if ($call instanceof ast_named_call) throw new diagnostic($call->source(), $call->byte_start(), ast_named_call::UNSUPPORTED_TARGET);
		$implicit_filter = $operation === runtime_factory::array_filter && count($call->arguments) === 1;
		if (!$implicit_filter && count($call->arguments) !== 2) throw new diagnostic($call->source(), $call->byte_start(), $operation->name . ' requires a collection and a callback');
		$map = $operation === runtime_factory::array_map;
		$source = $call->arguments[$map ? 1 : 0];
		$callback = $call->arguments[$map ? 0 : 1] ?? null;
		$this->value($source, $initializing);
		$input = $source->type_def();
		$filter_output = $input;
		if ($operation === runtime_factory::array_filter) {
			if (!($input instanceof subset_source)) throw new diagnostic($source->source(), $source->byte_start(), 'Unsupported collection for array_filter');
			$element = $input->iteration_value_type();
			$filter_output = $input->subset_result_type();
			if (!($filter_output instanceof keyed_result_builder)) throw new diagnostic($source->source(), $source->byte_start(), 'Unavailable keyed filter result');
		} elseif ($input instanceof vector_type_definition && !in_array($operation, [runtime_factory::keyed_map, runtime_factory::keyed_filter], true)) {
			$element = $input->element_type;
			$key = null;
		} elseif ($input instanceof hash_type_definition && !in_array($operation, [runtime_factory::sequence_map, runtime_factory::sequence_filter], true)) {
			$element = $input->value_type;
			$key = $input->key_type;
		} elseif ($input instanceof boxed_type_definition && in_array($operation, [runtime_factory::collection_map, runtime_factory::collection_filter, runtime_factory::array_map], true)) {
			$element = $input;
			$key = null;
		} else {
			throw new diagnostic($source->source(), $source->byte_start(), 'Unsupported collection for ' . $operation->name);
		}
		$filter = in_array($operation, [runtime_factory::array_filter, runtime_factory::collection_filter, runtime_factory::sequence_filter, runtime_factory::keyed_filter], true);
		if ($implicit_filter) {
			if (!$element->supports_scalar_condition()) {
				throw new diagnostic($source->source(), $source->byte_start(), 'array_filter requires a callback for this element type');
			}
			$this->retain_member_place($source);
			$call->settle_runtime(new collection_transform_function($operation, $input, null, $filter_output, true));
			return;
		}
		if ($callback instanceof ast_callable_reference) {
			$this->callback_reference($callback, null, $element);
		} elseif ($callback instanceof ast_lambda_expression && !($this->owner->parent->return_type_def() instanceof generator_type_definition)) {
			$this->lambda($callback, $filter ? new callback_type_definition(type_bool::instance(), [$element]) : null, $element);
		} else {
			$this->value($callback, $initializing, allow_callback: true);
		}
		$type = $callback->type_def();
		$signature = $type instanceof lambda_type_definition || $type instanceof owned_callable_type ? $type->signature : $type;
		if (!($signature instanceof callback_type_definition) || $signature->inputs !== [$element]
			|| $signature->input_mode(0) !== passing_mode::value || $signature->reference_origin !== null
			|| ($type instanceof lambda_type_definition && $type->has_defaults)
			|| (!$filter && $input instanceof boxed_type_definition && $signature->result !== $input)
			|| $signature->result === type_void::instance() || ($filter && $signature->result !== type_bool::instance())) {
			throw new diagnostic($callback->source(), $callback->byte_start(), 'Invalid callback signature for ' . $operation->name);
		}
		if ($type instanceof lambda_type_definition) {
			$lambda = $callback instanceof ast_name_expression ? $callback->local_binding()?->initializer : $callback;
			if ($lambda instanceof ast_lambda_expression) {
				$this->dependencies?->closure_call($this->enclosing_lambda ?? $this->owner, $lambda);
				if ($lambda->borrowed_writes !== []) $this->invalidate_references($call, $lambda->borrowed_writes);
			}
		} else {
			$this->dependencies?->callable_site($this->owner, $call);
		}
		$output = ($filter || $input instanceof boxed_type_definition) ? $filter_output : Type_Resolution::collection($key === null ? 'vector' : 'hash', $signature->result, $key);
		if ($output === null) throw new diagnostic($call->source(), $call->byte_start(), 'Unavailable collection result for ' . $operation->name);
		$this->retain_member_place($source);
		$call->settle_runtime(new collection_transform_function($operation, $input, $type, $output, $filter));
	}

	/** Watch both missing and existing targets without inspecting their bodies. */
	private function callback_reference(ast_callable_reference $site, ?callback_type_definition $signature, ?type_definition $element = null): void
	{
		$this->name_lookups++;
		$file = $site->lexical_root();
		$first = strpos($site->name, '\\');
		if ($file->source()->flavor === flavor::js && $first !== false && $first > 0
			&& $this->find_local(substr($site->name, 0, $first)) !== null) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Instance method callback references are deferred');
		}
		$explicit = strrpos($site->name, '::');
		$symbol = $explicit === false ? $this->symbols->entry($file->function_key($site->name)) : null;
		if ($symbol !== null) $this->dependencies?->watch($symbol, $this->owner);
		$target = $symbol?->declaration();
		$record = null;
		if ($target === null) {
			$separator = $explicit !== false ? $explicit : strrpos($site->name, '\\');
			if ($separator !== false && $separator > 0 && ($explicit !== false || $file->source()->flavor !== flavor::php)) {
				$type = $this->symbols->entry($file->type_key(substr($site->name, 0, $separator)));
				$record = $type->declaration();
				if ($record instanceof ast_record_declaration) {
					$name = substr($site->name, $separator + ($explicit !== false ? 2 : 1));
					$symbol = $this->symbols->entry('method:' . $record->name() . '::' . $name);
					$this->dependencies?->watch($symbol, $this->owner);
					// Exact declarations have a stable method contract even when their record is reparsed.
					if ($symbol->declaration() === null) {
						$symbol = $record->definition->method_symbol($name, $this->symbols, $this->owner, $this->dependencies);
					}
					$target = $symbol?->declaration();
				} else {
					$this->dependencies?->watch($type, $this->owner);
				}
			}
		}
		if (!($target instanceof ast_function_declaration) || $target->has_receiver()
			|| $target->is_async || $target->is_implicit_entry || $target->is_constructor() || $target->is_destructor()) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Callback requires a source free function or static method: ' . $site->name);
		}
		$target->require_access($this->owner->parent->parent, $site, null, $this->access_watcher());
		$signature ??= new callback_type_definition($target->return_type_def(), $element !== null ? [$element]
			: array_map(static fn(ast_parameter_declaration $parameter): type_definition => $parameter->type_def(), $target->parameters));
		$signature->require_target($site, $target);
		if ($record instanceof ast_record_declaration && $this->dependencies !== null) {
			$symbol = $this->dependencies->static_call($this->owner, $symbol, $type);
		}
		$site->settle_target($symbol, $signature);
		$this->dependencies?->callable_site($this->owner, $site);
	}

	/** Share arity, conversions and reference effects between functions and constructors. @return int[] */
	private function bind_arguments(ast_call_expression|ast_construction_expression $call, ast_function_declaration|ast_lambda_expression|callback_type_definition|data_constructor_signature $function,
		?string $initializing): array /** list<uint16> */
	{
		$supplied = count($call->arguments);
		$callback = $function instanceof callback_type_definition;
		$variadic = $callback ? $function->variadic_slot : ($function instanceof data_constructor_signature ? null : $function->variadic_slot());
		$unpacked = $call->unpacked ?? false;
		if ($unpacked && ($variadic === null || $supplied !== $variadic + 1)) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Unpacking requires one vector after all fixed arguments of a variadic target');
		}
		$slots = null;
		if (isset($call->named)) {
			if ($callback) throw new diagnostic($call->source(), $call->byte_start(), 'Typed callback calls require positional arguments');
			$call->named->bind_slots($call, $function, $variadic);
			$slots = $call->named->slots;
		}
		$accepted = $callback
			? ($variadic === null ? $supplied === count($function->inputs) : $supplied >= $variadic)
			: ($function instanceof data_constructor_signature || $function->accepts_argument_count($supplied));
		if (!$accepted) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Argument count mismatch for function: ' . ($function instanceof ast_function_declaration ? $function->name() : $call->name));
		}
		$conversions = [];
		$writes = [];
		foreach ($call->arguments as $index => $argument) {
			$slot = $slots[$index] ?? $index;
			if ($variadic !== null && $slot >= $variadic) $slot = $variadic;
			$parameter = $callback ? null : $function->parameters[$slot];
			$type = $parameter === null ? $function->inputs[$slot] : $parameter->type_def();
			if ($slot === $variadic && !$unpacked) $type = $type->element_type;
			if ($type instanceof callback_view_type) {
				// The first binding accounts for the closure’s writes. Forwarding carries that same view.
				$local = $argument::class === ast_name_expression::class && !$argument->constant_only && $argument->name !== $initializing
					? $this->find_local($argument->name) : null;
				if ($local instanceof ast_local_declaration && $local->type_reference === null && $local->type_def() instanceof lambda_type_definition
					&& $local->initializer instanceof ast_lambda_expression && $type->signature->matches($local->type_def()->signature)) {
					$this->dependencies?->closure_call($this->enclosing_lambda ?? $this->owner, $local->initializer);
					array_push($writes, ...$local->initializer->borrowed_writes);
				} elseif (!($local instanceof ast_parameter_declaration && $local->type_def() instanceof callback_view_type)) {
					throw new diagnostic($argument->source(), $argument->byte_start(), 'A callback view requires a named local closure with the exact void() signature or a callback view parameter');
				}
				$this->expression_visits++;
				$this->name_lookups++;
				$argument->settle_local($local, unknown: true);
				$conversions[] = value_conversion::identity;
				continue;
			}
			$this->value($argument, $initializing, allow_callback: $type instanceof callback_type_definition, destination: $type);
			if ($unpacked && $slot === $variadic && $argument->type_def() !== $type) {
				throw new diagnostic($argument->source(), $argument->byte_start(), 'Unpacked argument requires ' . $type->name);
			}
			$mode = $parameter instanceof ast_instance_field ? passing_mode::value : ($parameter?->mode ?? $function->input_mode($slot));
			if ($mode !== passing_mode::value) {
				$this->bind_reference($argument, $type, $writes, $mode === passing_mode::readonly_reference, $function);
				$conversions[] = value_conversion::identity;
			}
			else {
				$conversions[] = $this->value_argument($argument, $type);
			}
		}
		// Invocation follows every argument evaluation. Preserve earlier snapshots, invalidate future reads.
		if ($writes !== []) $this->invalidate_references($call, $writes);
		return $conversions;
	}

	/** Prepare one declared by-value argument for native and source calls, including guarded object conversions. */
	private function value_argument(ast_expression $argument, type_definition $type, string $label = ''): int /** uint16 */
	{
		if ($type instanceof record_type_definition) $this->require_inline_cleanup($type, $argument);
		if ($type instanceof class_union_type_definition) $this->watch_union_source($argument);
		if ($type instanceof intersection_type_definition && $argument instanceof ast_name_expression
			&& ($local = $argument->local_binding()) !== null
			&& isset($this->guards[$local->storage_id()])) {
			$id = $local->storage_id();
			$guard = $this->guards[$id];
			$from = $local->type_def();
			if ($from instanceof shared_record_type_definition && $from->payload->derives_from($type->base->payload)
				&& $guard->target_type()->payload->derives_from($type->capability->payload)
				&& (!$from->required || !$from->payload->derives_from($type->capability->payload))) {
				$argument->settle_guard($guard);
				$this->guard_reads[$id] ??= $argument->start;
				return value_conversion::guarded_intersection;
			}
		}
		try {
			return $type->prepare_conversion($argument, conversion_context::value_argument);
		} catch (diagnostic $error) {
			if ($label === '') throw $error;
			$message = $label . 'requires ' . $type->name . ', received ' . $argument->type_def()->name;
			try {
				$type->prepare_conversion($argument, conversion_context::explicit_cast);
				$message .= '. Use an explicit (' . $type->name . ') cast for this conversion';
			} catch (diagnostic) {
				// No explicit cast is available either.
			}
			throw new diagnostic($argument->source(), $argument->byte_start(), $message);
		}
	}

	/** Prevent C++ implicit scalar conversions from widening the supported echo/entry contracts. */
	private function require_integer(ast_expression $value, string $message): void
	{
		if ($value->type_def() !== type_registry::integer()) {
			throw new diagnostic($value->source(), $value->byte_start(), $message);
		}
	}

	/** STATIC METHODS */
	/** Watch each reachable schema once, including recursive owner fields. @param record_type_definition[] $records */
	public static function check_decode_schema(record_type_definition $record, ast_node $site, scope $symbols, ?Dependency_Watches $dependencies,
		dependency_watcher $owner, array &$records, bool $json = false): void
	{
		if (isset($records[$record->name])) return;
		$records[$record->name] = $record;
		$node = $record->declaration;
		$uses = dependency_use::record_decode->value;
		if ($json) $uses |= dependency_use::record_json_decode->value | dependency_use::record_result->value;
		$dependencies?->watch($symbols->entry($node->symbol_key()), $owner, $uses);
		if ($node->is_abstract || $node->data_constructor === null || $record->constructor() !== null) {
			throw new diagnostic($site->source(), $site->byte_start(), 'Decoding requires a concrete data record with a generated constructor');
		}
		$record->require_destruction($site, true);
		for ($base = $record->base(); $base !== null; $base = $base->base()) {
			$dependencies?->watch($symbols->entry($base->declaration->symbol_key()), $owner);
		}
		foreach ($node->data_constructor->parameters as $field) {
			$type = $field->storage_type();
			$leaf = $type instanceof native_sequence_type ? $type->write_type() : $type;
			$nested = $leaf instanceof shared_record_type_definition && $leaf->required ? $leaf->payload : $leaf;
			if ($nested instanceof record_type_definition) {
				if ($type === $leaf && !$json) {
					if (type_registry::RECORD_RESULT_HEADER === null) {
						throw new diagnostic($site->source(), $site->byte_start(), 'Embedded record decoding requires carrier storage');
					}
					$dependencies?->watch($symbols->entry($nested->declaration->symbol_key()), $owner, dependency_use::record_result->value);
				}
				self::check_decode_schema($nested, $site, $symbols, $dependencies, $owner, $records, $json);
			}
			elseif ($leaf->structural_reader() === null) {
				throw new diagnostic($site->source(), $site->byte_start(), ($json ? 'No JSON decoder for ' : 'No structural decoder for ') . $record->name . '::$' . $field->short_name . ' (' . $type->name . ')');
			}
		}
	}

	private static function borrows_mutably(ast_local_binding $binding): bool
	{
		return ($binding instanceof ast_parameter_declaration && $binding->mode === passing_mode::reference)
			|| ($binding instanceof ast_closure_capture && $binding->by_reference);
	}

	/**
	 * Keep definite constructor states only where all paths agree.
	 * @param array<string,int>|null $joined
	 * @param array<string,int> $incoming
	 * @return array<string,int>
	 */
	private static function join_initialization(?array $joined, array $incoming): array
	{
		if ($joined === null) return $incoming;
		foreach ($incoming as $name => $state) if ($joined[$name] !== $state) $joined[$name] = 2;
		return $joined;
	}

}
