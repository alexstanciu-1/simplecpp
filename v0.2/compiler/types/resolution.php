<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Resolve written types once at declaration/cast boundaries; generated registration owns available families and exports. */
final class Type_Resolution
{
	/** Runtime types resolve directly. Application types register a body watch even when lookup fails. */
	public static function local(ast_type_reference $syntax, scope $symbols, ast_function_body $body, ?Dependency_Watches $dependencies): ?type_definition
	{
		return self::storage($syntax, $symbols, $body->scope->namespace, $body, $dependencies);
	}

	/** Fixed extents currently have inline local and field storage only. */
	public static function storage(ast_type_reference $syntax, scope $symbols, ?string $namespace, dependency_watcher $watcher,
		?Dependency_Watches $dependencies): ?type_definition
	{
		if ($syntax->name === 'function' && $syntax->first_child !== null) {
			return new owned_callable_type(self::callback_signature($syntax, $symbols, $namespace, $watcher, $dependencies));
		}
		$bound = $syntax->first_child?->next_sibling;
		if ($bound instanceof ast_extent_reference) {
			$element = self::storage($syntax->first_child, $symbols, $namespace, $watcher, $dependencies);
			if ($element !== type_uint8::instance() && !($element instanceof fixed_array_type)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Inline fixed-array storage requires byte elements');
			}
			if ($bound instanceof ast_extent_literal) {
				$extent = $bound->extent;
			} else {
				$entry = $symbols->entry($bound->lexical_root()->constant_key($bound->name));
				$dependencies?->watch($entry, $watcher);
				$constant = $entry->declaration();
				if (!($constant instanceof ast_constant_declaration) || $constant->type_def() !== type_int64::instance()) {
					throw new diagnostic($bound->source(), $bound->byte_start(), 'Fixed-array extent requires a namespace integer constant: ' . $bound->name);
				}
				$extent = $constant->constant();
				if ($extent < 0) {
					throw new diagnostic($bound->source(), $bound->byte_start(), 'Fixed-array extent must be non-negative');
				}
			}
			return fixed_array_type::resolve($element, $bound, $extent);
		}
		return self::named($syntax, $symbols, $namespace, $watcher, $dependencies);
	}

	/** Resolve runtime signature types directly; callables watch the nominal records named in their signatures. */
	public static function signature(ast_type_reference $syntax, scope $symbols, ast_function_declaration $function,
		?Dependency_Watches $dependencies, bool $allow_void = false, ?dependency_watcher $watcher = null): ?type_definition
	{
		$watcher ??= $function;
		if ($syntax->name === 'function' && $syntax->first_child !== null) {
			$parameter = $syntax->parent;
			$is_parameter = $parameter instanceof ast_parameter_declaration && $parameter->parent === $function;
			if ($is_parameter && ($parameter->mode !== passing_mode::value || $parameter->default_value !== null)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Owned callable parameters must be required by-value parameters');
			}
			if ($is_parameter || ($syntax === $function->return_type_reference && !$function->returns_reference)) {
				$function->watch_signature($dependencies);
				return new owned_callable_type(self::callback_signature($syntax, $symbols, $function->namespace, $watcher, $dependencies));
			}
		}
		if ($syntax instanceof ast_compound_type_reference) {
			if ($syntax->name === '|') {
				if ($watcher === $function) $function->watch_signature($dependencies);
				return self::class_union($syntax, $symbols, $function->namespace, $watcher, $dependencies);
			}
			$left = self::signature($syntax->first_child, $symbols, $function, $dependencies, watcher: $watcher);
			$right = self::signature($syntax->first_child->next_sibling, $symbols, $function, $dependencies, watcher: $watcher);

			if ($left instanceof shared_record_type_definition && $right instanceof shared_record_type_definition && $left->required && $right->required) {
				if ($left->payload->declaration->kind === record_kind::interface_type) [$left, $right] = [$right, $left];
				if ($left->payload->declaration->kind === record_kind::class_type && $right->payload->declaration->kind === record_kind::interface_type) {
					return $left->payload->intersection($right);
				}
			}
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Intersection requires one required class and one interface');
		}
		if ($allow_void && $syntax->name === Iterator_Contract::NAME) {
			if ($function->parent instanceof ast_method_owner) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Yield producers must be free functions');
			}
			$first = $syntax->first_child;
			$second = $first?->next_sibling;
			if ($first === null || $second?->next_sibling !== null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Expected Iterator<Value> or Iterator<Value,Key>');
			}
			$element = self::signature($first, $symbols, $function, $dependencies);
			$key = $second === null ? null : self::signature($second, $symbols, $function, $dependencies);
			if ($element === null || ($second !== null && $key === null)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unknown yield output type');
			}
			if ($element instanceof record_type_definition || $key instanceof record_type_definition) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Inline struct yield outputs are deferred with inline struct reference parameters');
			}
			return new generator_type_definition($function, $element, $key);
		}
		if (in_array($syntax->name, ['shared', 'weak'], true) && $syntax->first_child !== null) {
			if ($watcher === $function) $function->watch_signature($dependencies);
			return self::handle($syntax, $symbols, $function->namespace, $watcher, $dependencies);
		}
		if ($syntax->name === 'nullable' && $watcher === $function) $function->watch_signature($dependencies);
		$optional = self::optional_type($syntax, $symbols, $function->namespace, $watcher, $dependencies);
		if ($optional !== null) return $optional;
		if ($syntax->first_child !== null && $watcher === $function) $function->watch_signature($dependencies);
		$type = self::resolve($syntax, $allow_void, $symbols, $function->namespace, $watcher, $dependencies);
		if ($type !== null) return $type;
		if ($watcher === $function) $function->watch_signature($dependencies);
		$type = self::application($syntax, $symbols, $watcher, $dependencies);
		if ($type instanceof record_type_definition) $type->require_complete_value($syntax);
		return $type;
	}

	/** Resolve union arms through stable nominal entries after all class ancestry has settled. */
	public static function class_union(ast_compound_type_reference $syntax, scope $symbols, ?string $namespace,
		dependency_watcher $watcher, ?Dependency_Watches $dependencies): class_union_type_definition
	{
		$first = $syntax->first_child;
		$left = self::named($first, $symbols, $namespace, $watcher, $dependencies);
		$right = self::named($first->next_sibling, $symbols, $namespace, $watcher, $dependencies);
		if ($left instanceof shared_record_type_definition && $right instanceof shared_record_type_definition
			&& $left->required && $right->required && $left !== $right
			&& $left->payload->declaration->kind === record_kind::class_type && $right->payload->declaration->kind === record_kind::class_type) {
			$union = $left->payload->class_union($right->payload);
			if ($union !== null) return $union;
		}
		throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Union requires two distinct required classes with a common class base');
	}

	/** Settle one signature at the parameter boundary with the same component lookup as a direct function. */
	public static function callback(ast_parameter_declaration $parameter, scope $symbols, ast_function_declaration $function,
		dependency_watcher $watcher, ?Dependency_Watches $dependencies): callback_type_definition
	{
		$syntax = $parameter->type_reference;
		if ($parameter->mode !== passing_mode::value || $parameter->default_value !== null || $function->has_receiver()) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Callback parameters must be required by-value parameters of free functions or static methods');
		}
		if ($watcher === $function) $function->watch_signature($dependencies);
		return self::callback_signature($syntax, $symbols, $function->namespace, $watcher, $dependencies);
	}

	/** Resolve the ordered components shared by function-pointer callbacks and owned callable locals. */
	private static function callback_signature(ast_type_reference $syntax, scope $symbols, ?string $namespace,
		dependency_watcher $watcher, ?Dependency_Watches $dependencies): callback_type_definition
	{
		$types = [];
		$modes = [];
		for ($part = $syntax->first_child; $part !== null; $part = $part->next_sibling) {
			$type = self::named($part, $symbols, $namespace, $watcher, $dependencies, $types === []);
			if (!self::callback_component($type)) {
				throw new diagnostic($part->source(), $part->byte_start(), 'Unsupported callback signature component: ' . $part->spelling());
			}
			if ($types !== [] && $part instanceof ast_callable_reference_component) $modes[count($types) - 1] = passing_mode::reference;
			$types[] = $type;
		}
		$result = array_shift($types);
		$origin = null;
		if ($syntax->first_child instanceof ast_callable_reference_component) {
			$origin = array_key_first($modes);
			if (count($modes) !== 1 || $types[$origin] !== $result) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Reference return requires exactly one writable parameter of the return type');
			}
		}
		return new callback_type_definition($result, $types, $modes, $origin);
	}

	/** Inline values and intersection projection are outside the fixed by-value callback ABI. */
	public static function callback_component(?type_definition $type): bool
	{
		return $type !== null && !($type instanceof generator_type_definition || $type instanceof record_type_definition
			|| $type instanceof intersection_type_definition || $type instanceof callback_type_definition);
	}

	/** Explicit handles name one nominal record payload; classes already have shared storage. */
	private static function handle(ast_type_reference $syntax, scope $symbols, ?string $namespace,
		dependency_watcher $watcher, ?Dependency_Watches $dependencies): record_handle_definition
	{
		$child = $syntax->first_child;
		if ($child->next_sibling === null) {
			$payload = self::named($child, $symbols, $namespace, $watcher, $dependencies);
			if ($payload instanceof shared_record_type_definition && $payload->payload->declaration->kind !== record_kind::structure) {
				if ($syntax->name === 'shared') throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Classes already use shared storage');
				if (!$payload->required) throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Weak target requires a bare record type');
				$payload = $payload->payload;
			}
			if ($payload instanceof record_type_definition) return $syntax->name === 'weak' ? $payload->weak : $payload->shared;
		}
		throw new diagnostic($syntax->source(), $syntax->byte_start(), ucfirst($syntax->name) . ' type requires one struct payload: ' . $syntax->spelling());
	}

	/** Share canonical lookup and missing-symbol watches between local and inline field declarations. */
	public static function named(ast_type_reference $syntax, scope $symbols, ?string $namespace, dependency_watcher $watcher,
		?Dependency_Watches $dependencies, bool $allow_void = false): ?type_definition
	{
		if (in_array($syntax->name, ['shared', 'weak'], true) && $syntax->first_child !== null) {
			return self::handle($syntax, $symbols, $namespace, $watcher, $dependencies);
		}
		$optional = self::optional_type($syntax, $symbols, $namespace, $watcher, $dependencies);
		if ($optional !== null) return $optional;
		$type = self::resolve($syntax, $allow_void, $symbols, $namespace, $watcher, $dependencies);
		if ($type !== null) return $type;
		return self::application($syntax, $symbols, $watcher, $dependencies);
	}

	/** Resolve the nullable element once, then select its nominal owner or published runtime carrier. */
	private static function optional_type(ast_type_reference $syntax, scope $symbols, ?string $namespace,
		dependency_watcher $watcher, ?Dependency_Watches $dependencies): ?type_definition
	{
		if ($syntax->name !== 'nullable' || $syntax->first_child === null || $syntax->first_child->next_sibling !== null) return null;
		$child = $syntax->first_child;
		$target = self::named($child, $symbols, $namespace, $watcher, $dependencies)
			?? throw new diagnostic($child->source(), $child->byte_start(), 'Unknown or unsupported type: ' . $child->spelling());
		if ($target instanceof shared_record_type_definition && $target->required) return $target->payload->shared;
		if ($target instanceof record_handle_definition || $target instanceof record_type_definition) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Nullable record spelling requires a bare class type');
		}
		if ($target instanceof enum_type_definition) return $target->nullable;
		return self::specialize($syntax, $target);
	}

	/** Subscribe to the stable nominal key, including a missing declaration that may be added later. */
	private static function application(ast_type_reference $syntax, scope $symbols, dependency_watcher $watcher,
		?Dependency_Watches $dependencies): ?type_definition
	{
		$entry = $symbols->entry($syntax->lexical_root()->type_key($syntax->name));
		$dependencies?->watch($entry, $watcher);
		$record = $entry->declaration();
		return $record instanceof ast_type_declaration ? $record->type_definition() : self::core_class($syntax->root(), $syntax->name);
	}

	/** Core-class fallback follows imports and honors a source declaration found by the caller. */
	public static function core_class(ast_file_node $file, string $name): ?type_exception
	{
		$type = type_registry::exception();
		if ($type === null) return null;
		$key = $file->type_key($name);
		if ($key === 'class:' . $type->name) return $type;
		return $name === $type->name && $file->imported_type_target($name) === null ? $type : null;
	}

	/** Preserve simple-name lookup; validate arity and resolve each argument once before specialization lookup. */
	public static function resolve(ast_type_reference $syntax, bool $allow_void = false, ?scope $symbols = null,
		?string $namespace = null, ?dependency_watcher $watcher = null, ?Dependency_Watches $dependencies = null): ?type_definition
	{
		$arity = type_registry::generic_arity($syntax->name);
		$child = $syntax->first_child;
		if ($arity === null) {
			if ($child?->next_sibling instanceof ast_extent_reference) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Fixed arrays require inline local or instance-field storage');
			}
			if ($child !== null) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type does not accept arguments: ' . $syntax->name);
			}
			$type = ($allow_void && $syntax->name === 'void') ? type_void::instance() : type_registry::resolve($syntax->name);
			$module = type_registry::required_module($syntax->name);
			if ($type !== null && $module !== null && !$syntax->root()->type_availability->allows($module)) {
				throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Type ' . $syntax->name . ' requires module ' . $module);
			}
			return $type;
		}
		// Current families have one or two arguments; walk the chain once without an intermediate collection.
		$second = $child?->next_sibling;
		if (($child === null) || (($arity === 1) && ($second !== null))
			|| (($arity === 2) && (($second === null) || ($second->next_sibling !== null)))) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(),
				'Type requires exactly ' . ($arity === 1 ? 'one argument: ' : 'two arguments: ') . $syntax->name);
		}
		$element = $child->name === 'function' && $child->first_child !== null && $symbols !== null && $watcher !== null
			&& ($syntax->name === 'vector' || $syntax->name === 'hash')
			? callable_signature_index::element(self::callback_signature($child, $symbols, $namespace, $watcher, $dependencies))
			: self::argument($child, $symbols, $namespace, $watcher, $dependencies);
		$key = $second === null ? null : self::argument($second, $symbols, $namespace, $watcher, $dependencies);
		$published = type_registry::specialize($syntax->name, $element, $key);
		if ($published !== null) return $published;
		if ($key !== null) {
			$type = $key->keyed_collection_type($syntax->name, $element);
		} else {
			$owner = $element instanceof shared_record_type_definition && $element->required ? $element->payload : $element;
			$type = $owner->collection_type($syntax->name, $key);
		}
		if ($type !== null) return $type;
		return self::specialize($syntax, $element, $key);
	}

	/** Select a published specialization from already resolved arguments. */
	private static function specialize(ast_type_reference $syntax, type_definition $element, ?type_definition $key = null): type_definition
	{
		$type = type_registry::specialize($syntax->name, $element, $key);
		if ($type === null) {
			throw new diagnostic($syntax->source(), $syntax->byte_start(), 'Unavailable runtime specialization: ' . $syntax->spelling());
		}
		return $type;
	}
	/** Resolve each written argument once and retain its own diagnostic location. */
	private static function argument(ast_type_reference $syntax, ?scope $symbols, ?string $namespace,
		?dependency_watcher $watcher, ?Dependency_Watches $dependencies): type_definition
	{
		$type = ($symbols !== null && $watcher !== null
			? self::named($syntax, $symbols, $namespace, $watcher, $dependencies) : self::resolve($syntax)) ?? throw new diagnostic($syntax->source(), $syntax->byte_start(),
			'Unknown or unsupported type: ' . $syntax->spelling());
		if ($type instanceof record_type_definition) $type->require_complete_value($syntax, true);
		return $type;
	}

}
