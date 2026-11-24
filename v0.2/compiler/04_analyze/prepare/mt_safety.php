<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Closed admission rules for isolated inline slots and operations reachable from a segment worker. */
final class Mt_Rules
{
	public static function isolated(type_definition $type): bool
	{
		if (type_registry::is_numeric_scalar($type) || $type === type_bool::instance()) return true;
		if (!($type instanceof record_type_definition) || $type->declaration->kind !== record_kind::structure
			|| $type->base() !== null || $type->declaration->methods !== []) return false;
		foreach ($type->declaration->fields as $field) if (!self::isolated($field->type_def())) return false;
		return true;
	}

	/** Recognize local accesses and return the next direct callee for independent inspection. */
	public static function inspect(ast_node $node, bool $known): ?ast_function_declaration
	{
		if (!$known) self::reject($node, 'unsupported operation ' . $node::class);
		if ($node instanceof ast_name_expression && $node->local_binding() === null)
			self::reject($node, 'worker cannot access global or static storage');
		if ($node instanceof ast_local_declaration && !type_registry::is_numeric_scalar($node->type_def()) && $node->type_def() !== type_bool::instance())
			self::reject($node, 'worker locals must be scalar; a segment cannot escape');
		if ($node instanceof ast_call_expression) {
			if ($node->cardinality !== null && $node->arguments[0]->type_def() instanceof segment_type) return null;
			if (!$node->has_direct_signature() || $node->receiver !== null || $node->member_separator !== null || $node->runtime_binding() !== null)
				self::reject($node, 'worker requires a direct checked helper');
			$function = $node->implementation();
			if (!($function instanceof ast_function_declaration) || $function->is_async || $function->returns_reference
				|| $function->parent instanceof ast_method_owner || count($function->parameters) !== count($node->arguments)
				|| !($function->return_type_def() === type_void::instance() || type_registry::is_numeric_scalar($function->return_type_def())))
				self::reject($node, 'unsupported worker helper contract');
			foreach ($function->parameters as $parameter) {
				if ($parameter->mode !== passing_mode::value || !($parameter->type_def() instanceof segment_type || type_registry::is_numeric_scalar($parameter->type_def())))
					self::reject($node, 'worker helper requires scalar or segment parameters');
			}
			return $function;
		}
		return null;
	}

	public static function reject(ast_node $site, string $message): never
	{
		throw new diagnostic($site->source(), $site->byte_start(), 'MT safety: ' . $message);
	}
}

/** Check each reachable body once, including mutually recursive direct helpers. */
final class Mt_Safety
{
	/** Drain launch roots only after all bodies have settled. */
	public static function check(mt_worker_roots $roots): void
	{
		$pending = $roots->workers;
		$roots->workers = [];
		$seen = [];
		while ($pending !== []) {
			$function = array_pop($pending);
			$id = spl_object_id($function);
			if (isset($seen[$id])) continue;
			$seen[$id] = true;
			array_push($pending, ...self::body($function->body()));
		}
	}

	/** Independent body check. Reads settled AST facts and returns only direct callees. @return ast_function_declaration[] */
	private static function body(ast_function_body $body): array /** list<ast_function_declaration> */
	{
		$callees = [];
		$nodes = [$body];
		while ($nodes !== []) {
			$node = array_pop($nodes);
			$children = Mt_Traversal::children($node);
			$callee = Mt_Rules::inspect($node, $children !== null);
			if ($callee !== null) $callees[] = $callee;
			array_push($nodes, ...array_reverse($children));
		}
		return $callees;
	}
}
