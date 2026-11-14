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
		$old_contracts = ($dependencies !== null) && ($previous !== null) ? self::declaration_contracts($previous) : [];
		$scope = $this->collection->publish($previous);
		ksort($scope->declarations, \SORT_STRING);
		foreach ($files as $file) {
			$file->scope->parent = $scope;
		}
		if ($dependencies !== null)
		{
			$dependencies->retain(self::watchers($files));
			self::notify($scope, $dependencies, $old_contracts, self::declaration_contracts($scope));
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
		$old = self::declaration_contracts($scope, $keys);
		$collection->publish_changes($removed, $keys, static fn(ast_node $a, ast_node $b): int /** int32 */ =>
			strcmp($a->source()->path, $b->source()->path) ?: ($a->start <=> $b->start));
		foreach ($files as $file) {
			$file->scope->parent = $scope;
		}
		foreach ($retired as $file) {
			foreach ($file->declaration_nodes as $node) {
				if ($node instanceof ast_function_declaration) {
					$node->retire_signature();
					$dependencies->retire($node->body());
				} elseif ($node instanceof ast_record_declaration) {
					$dependencies->forget($node->definition);
				}
			}
		}
		self::notify($scope, $dependencies, $old, self::declaration_contracts($scope, $keys));
		return $scope;
	}

	/**
	 * Compare only supplied contracts and end the existing notification batch.
	 * @param (string|null)[] $old
	 * @param (string|null)[] $current
	 */
	private static function notify(scope $scope, Dependency_Watches $dependencies,
		array $old /** array<string,?string> */, array $current /** array<string,?string> */): void
	{
		foreach (array_unique(array_merge(array_keys($old), array_keys($current))) as $key) {
			$entry = $scope->entry($key);
			if ((($old[$key] ?? null) !== ($current[$key] ?? null))
				|| (array_key_exists($key, $old) !== array_key_exists($key, $current))
				|| (array_key_exists($key, $current) && ($current[$key] === null) && ($entry->status !== change_status::none))) {
				$dependencies->changed($entry);
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
	 */
	private static function declaration_contracts(scope $scope, ?array $keys /** array<string,bool>|null */ = null): array /** array<string,?string> */
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
			elseif ($head instanceof ast_constant_declaration) {
				$contracts[$key] = json_encode([$head->is_private, $head->type_reference?->spelling(), $head->initializer->source_text()], \JSON_THROW_ON_ERROR);
			}
			elseif ($head instanceof ast_global_declaration) {
				$contracts[$key] = ($head->is_private ? 'private global:' : 'global:') . ($head->type_reference?->spelling() ?? type_registry::literal_type($head->initializer)->name);
			}
			else {
				/** @var ast_function_declaration $head */
				$contracts[$key] = json_encode([$head->is_private, $head->is_static, $head->return_type_reference?->spelling(), array_map(
					static fn(ast_parameter_declaration $parameter): array => [$parameter->type_reference->spelling(), $parameter->mode->value,
						$parameter->default_value?->source_text()], $head->parameters)], \JSON_THROW_ON_ERROR);
			}
		}
		return $contracts;
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
					$watchers[] = $function->body();
				} elseif ($function instanceof ast_record_declaration) {
					$watchers[] = $function->definition;
				}
			}
		}
		return $watchers;
	}
}
