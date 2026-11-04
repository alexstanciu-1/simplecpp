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
		if (!($declaration instanceof ast_function_declaration) && !($declaration instanceof ast_global_declaration)) {
			throw new \LogicException('Only functions and explicit globals are published by the current file grammar');
		}
		if (!($declaration instanceof ast_function_declaration) || (!$declaration->is_private)) {
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
			$contracts = self::declaration_contracts($scope);
			foreach (array_unique(array_merge(array_keys($old_contracts), array_keys($contracts))) as $key)
			{
				$old = $old_contracts[$key] ?? null;
				$current = $contracts[$key] ?? null;
				$entry = $scope->entry($key);
				if (($old !== $current) || (array_key_exists($key, $old_contracts) !== array_key_exists($key,
							$contracts))
					|| (array_key_exists($key, $contracts) && ($current === null) && ($entry->status !== change_status::none))) {
					$dependencies->changed($entry);
				}
			}
			$dependencies->dispatch();
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
	 * Ephemeral signature snapshot across chain publication; no AST copies or retained candidate
	 * lists.
	 *
	 * Return: null means ambiguous, absent key means missing.
	 *
	 * @return (string|null)[]
	 */
	private static function declaration_contracts(scope $scope): array /** array<string,?string> */
	{
		$contracts = [];
		foreach ($scope->declarations as $key => $head) {
			if ($head->duplicate_element() !== null) {
				$contracts[$key] = null;
			}
			elseif ($head instanceof ast_global_declaration) {
				$contracts[$key] = 'global:' . ($head->type_name ?? type_registry::literal_type($head->initializer)->name);
			}
			else {
				/** @var ast_function_declaration $head */
				$contracts[$key] = $head->return_type_name;
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
					$watchers[] = $function->body();
				}
			}
		}
		return $watchers;
	}
}
