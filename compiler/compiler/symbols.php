<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Invocation-local coordinator: discoveries are pending until every frontend result succeeds. */
final class Symbol_Publication implements declaration_sink
{
	/** PROPERTIES */
	/** Native owning; pending compilation collection. */
	private readonly Scope_Collection $collection;
	private bool $published = false;

	/** CONSTRUCTOR */
	public function __construct()
	{
		$this->collection = new Scope_Collection(new scope());
	}

	/** METHODS */
	/** Read only the initialized header; discovery must never inspect the still-unfinished body. */
	public function discovered(ast_declaration_interface $declaration): void
	{
		if ($this->published) {
			throw new \LogicException('Cannot notify a completed symbol publication');
		}
		if (!($declaration instanceof ast_function_declaration) && !($declaration instanceof ast_literal_declaration) && !($declaration instanceof ast_type_declaration)) {
			throw new \LogicException('Unsupported file declaration in symbol publication');
		}
		if (!($declaration instanceof ast_function_declaration) || (!$declaration->is_implicit_entry)) {
			$this->collection->import($declaration);
		}
	}

	/**
	 * The coordinator supplies the complete successful inventory; failed parses cannot publish candidates.
	 *
	 * @param ast_file_node[] $files
	 */
	public function publish(array $files /** array<string,ast_file_node> */, ?scope $previous = null, ?Dependency_Watches $dependencies = null): scope
	{
		if ($this->published) {
			throw new \LogicException('Symbols already published');
		}
		$complete = [];
		foreach ($files as $file) {
			foreach ($file->published_declarations() as $declaration) {
				$complete[spl_object_id($declaration)] = true;
			}
		}
		foreach ($this->collection->inventory() as $candidate) {
			$id = spl_object_id($candidate);
			if (!isset($complete[$id])) {
				throw new \LogicException('Discovery is not part of a completed source result');
			}
			unset($complete[$id]);
		}
		if ($complete !== []) {
			throw new \LogicException('Completed source has unreported declarations');
		}
		$this->published = true;
		$this->collection->order(static fn(ast_node $a, ast_node $b): int /** int32 */ =>
			strcmp($a->source()->path, $b->source()->path) ?: ($a->start <=> $b->start));
		// Capture the dependency role before publication rewrites links on reused AST declarations.
		$old_functions = [];
		$old_contracts = ($dependencies !== null) && ($previous !== null) ? self::declaration_contracts($previous, old_functions: $old_functions) : [];
		$scope = $this->collection->publish($previous);
		ksort($scope->declarations, \SORT_STRING);
		foreach ($files as $file) {
			$file->scope->parent = $scope;
		}
		if ($dependencies !== null)
		{
			$dependencies->retain(self::watchers($files));
			self::notify($scope, $dependencies, $old_contracts, self::declaration_contracts($scope), $old_functions);
		}
		return $scope;
	}

	/** STATIC METHODS */
	/**
	 * In-memory analysis clients publish their already complete collections through the same owner.
	 *
	 * @param ast_file_node[] $files
	 */
	public static function completed(array $files /** array<string,ast_file_node> */, ?scope $previous = null, ?Dependency_Watches $dependencies = null): scope
	{
		$publication = new self();
		foreach ($files as $file) {
			foreach ($file->published_declarations() as $declaration) {
				$publication->discovered($declaration);
			}
		}
		return $publication->publish($files, $previous, $dependencies);
	}

	/**
	 * Publish completed added/changed roots and withdraw replaced/removed roots; unchanged units are not inputs.
	 * @param ast_file_node[] $files
	 * @param ast_file_node[] $retired
	 */
	public static function changes(array $files /** array<string,ast_file_node> */, array $retired /** list<ast_file_node> */,
		scope $scope, Dependency_Watches $dependencies): scope
	{
		$collection = new Scope_Collection($scope);
		$keys = [];
		$removed = [];
		foreach ($retired as $file) {
			foreach ($file->published_declarations() as $node) {
				$removed[spl_object_id($node)] = true;
				$keys[$node->symbol_key()] = true;
			}
		}
		foreach ($files as $file) {
			foreach ($file->published_declarations() as $node) {
				$collection->import($node);
				$keys[$node->symbol_key()] = true;
			}
		}
		$old_functions = [];
		$old = self::declaration_contracts($scope, $keys, $old_functions);
		$collection->publish_changes($removed, $keys, static fn(ast_node $a, ast_node $b): int /** int32 */ =>
			strcmp($a->source()->path, $b->source()->path) ?: ($a->start <=> $b->start));
		foreach ($files as $file) {
			$file->scope->parent = $scope;
		}
		foreach ($retired as $file) {
			$same_bindings = null;
			foreach ($file->declaration_nodes as $node) {
				if ($node instanceof ast_function_declaration) {
					$replacement = $files[$node->dependency_source_key()] ?? null;
					$same_bindings ??= $replacement !== null && $replacement->source()->path === $file->source()->path
						&& $replacement->same_import_bindings($file);
					$next = $same_bindings ? ($scope->declarations[$node->symbol_key()] ?? null) : null;
					$node->retire_signature($next instanceof ast_function_declaration && $next->root() === $replacement
						&& $next->duplicate_element() === null ? $next : null);
					if ($node->optional_body() !== null) $dependencies->retire($node->body());
				} elseif ($node instanceof ast_computed_integer_constant) {
					$dependencies->forget($node);
				} elseif ($node instanceof ast_record_declaration) {
					$dependencies->forget($node->definition);
				}
			}
		}
		self::notify($scope, $dependencies, $old, self::declaration_contracts($scope, $keys), $old_functions);
		return $scope;
	}

	/**
	 * Compare only supplied contracts and end the existing notification batch.
	 * @param (string|null)[] $old
	 * @param ast_function_declaration[] $old_functions
	 * @param (string|null)[] $current
	 */
	private static function notify(scope $scope, Dependency_Watches $dependencies,
		array $old /** array<string,?string> */, array $current /** array<string,?string> */, array $old_functions): void
	{
		foreach (array_unique(array_merge(array_keys($old), array_keys($current))) as $key) {
			$entry = $scope->entry($key);
			if ((($old[$key] ?? null) !== ($current[$key] ?? null))
				|| (array_key_exists($key, $old) !== array_key_exists($key, $current))
				|| (array_key_exists($key, $current) && ($current[$key] === null) && ($entry->status !== change_status::none))) {
				$dependencies->changed($entry);
			} elseif (isset($old_functions[$key]) && ($previous = $old_functions[$key]) !== ($next = $entry->declaration())
				&& $next instanceof ast_function_declaration) {
				// Equivalent positional contracts already establish matching arity and types.
				foreach ($next->parameters as $index => $parameter) {
					if ($parameter->name() !== $previous->parameters[$index]->name()) {
						$dependencies->changed($entry, Dependency_Watches::PARAMETER_NAMES);
						break;
					}
				}
			}
		}
		$dependencies->dispatch();
	}

	/**
	 * Ephemeral signature snapshot across chain publication; no AST copies or retained candidate
	 * lists.
	 *
	 * Return: null means ambiguous, absent key means missing.
	 *
	 * @param bool[]|null $keys Null requests the complete inventory for full publication.
	 * @return (string|null)[]
	 * @param ast_function_declaration[]|null $old_functions
	 */
	private static function declaration_contracts(scope $scope, ?array $keys /** array<string,bool>|null */ = null, ?array &$old_functions = null): array /** array<string,?string> */
	{
		$contracts = [];
		foreach ($keys ?? $scope->declarations as $key => $_) {
			$head = $scope->declarations[$key] ?? null;
			if ($head === null) {
				continue;
			}
			if ($head->duplicate_element() !== null) {
				$contracts[$key] = null;
			}
			elseif ($head instanceof ast_type_declaration) {
				// Prepared locals link to the canonical type on this AST. Refresh them on any declaration replacement.
				$contracts[$key] = 'type:' . spl_object_id($head);
			}
			elseif ($head instanceof ast_computed_integer_constant) {
				// Identical initializer text can resolve differently after an import edit.
				$contracts[$key] = 'computed:' . spl_object_id($head);
			}
			elseif ($head instanceof ast_constant_declaration) {
				$contracts[$key] = json_encode([$head->visibility->value, $head->type_reference?->spelling(), $head->initializer->source_text()], \JSON_THROW_ON_ERROR);
			}
			elseif ($head instanceof ast_global_declaration) {
				$contracts[$key] = $head->visibility->name . ' global:' . ($head->type_reference?->spelling() ?? type_registry::literal_type($head->initializer)->name);
			}
			else {
				/** @var ast_function_declaration $head */
				if ($old_functions !== null && $head->parameters !== []) $old_functions[$key] = $head;
				// A generator return type retains its producer declaration.
				$producer = $head->return_type_reference?->name === Iterator_Contract::NAME ? spl_object_id($head) : null;
				$contracts[$key] = json_encode([$producer, $head->is_async, $head->is_private, $head->is_protected, $head->is_static, $head->is_virtual, $head->is_override, $head->is_final, $head->is_abstract, $head->returns_reference, self::signature_type($head->return_type_reference), array_map(
					static fn(ast_parameter_declaration $parameter): array => [self::signature_type($parameter->type_reference), $parameter->mode->value,
						$parameter->default_value?->source_text()], $head->parameters)], \JSON_THROW_ON_ERROR);
			}
		}
		return $contracts;
	}

	/** Written type and the lexical bindings that can change its meaning without a signature edit. */
	private static function signature_type(?ast_type_reference $type): ?string
	{
		if ($type === null) return null;
		if ($type instanceof ast_variadic_type_reference) return self::signature_type($type->first_child) . ($type->read_only ? '&...' : '...');
		if ($type instanceof ast_intersection_reference) return self::signature_type($type->left) . '&' . self::signature_type($type->right);
		if ($type->first_child === null) {
			$alias = $type->lexical_root()->imported_type_target($type->name);
			return $alias === null ? $type->name : $type->name . '=' . $alias;
		}
		$signature = $type->name . '<';
		$separator = '';
		for ($part = $type->first_child; $part !== null; $part = $part->next_sibling) {
			$signature .= $separator . self::signature_type($part) . ($part instanceof ast_callable_reference_component ? '&' : '');
			$separator = ',';
		}
		return $signature . '>';
	}

	/**
	 * Supply current invalidation targets; the watch coordinator does not inspect source or body syntax.
	 * @param ast_file_node[] $files
	 * @return dependency_watcher[]
	 */
	private static function watchers(array $files /** array<string,ast_file_node> */): array /** list<dependency_watcher> */
	{
		$watchers = [];
		foreach ($files as $file) {
			foreach ($file->declaration_nodes as $function) {
				if ($function instanceof ast_function_declaration) {
					$watchers[] = $function;
					if ($function->optional_body() !== null) $watchers[] = $function->body();
				} elseif ($function instanceof ast_computed_integer_constant) {
					$watchers[] = $function;
				} elseif ($function instanceof ast_record_declaration) {
					$watchers[] = $function->definition;
				}
			}
		}
		return $watchers;
	}
}
