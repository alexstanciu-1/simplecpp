<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Body-local proof for native receivers that keep references to caller slots. */
final class Bound_Slots
{
	/** PROPERTIES */
	/** @var ast_local_declaration[] Fresh owners and admitted extractions, indexed by local storage identity. */
	private array $owners /** array<uint32,ast_local_declaration> */ = [];
	/** @var bool[] Source occurrences already proven through their resolved local identity. */
	private array $observations /** array<uint32,bool> */ = [];

	/** METHODS */
	private function track(ast_local_declaration $local): void
	{
		$this->owners[$local->storage_id()] = $local;
	}

	/** Validate all slots together and remember the owner for one end-of-body inspection. @param ast_expression[] $slots */
	public function bind(ast_call_expression $call, array $slots /** list<ast_expression> */): void
	{
		$receiver = $call->receiver;
		$local = $receiver instanceof ast_name_expression ? $receiver->local_binding() : null;
		$origin = $local instanceof ast_local_declaration ? self::extraction($local) ?? $local : null;
		if ($origin === null || $origin::class !== ast_local_declaration::class || !($origin->parent instanceof ast_statement_block)
			|| !($origin->initializer instanceof ast_call_expression) || !$origin->initializer->runtime_binding()?->fresh_owner) {
			throw new diagnostic($call->source(), $call->byte_start(), 'Retaining call requires a fresh local owner');
		}
		foreach ($slots as $value) {
			$slot = $value instanceof ast_name_expression ? $value->local_binding() : null;
			if ($slot instanceof ast_parameter_declaration) continue;
			if ($slot === null || $slot::class !== ast_local_declaration::class) {
				throw new diagnostic($value->source(), $value->byte_start(), 'Retaining call requires a stable typed slot');
			}
			$scope = $origin->parent;
			while ($scope !== null && $scope !== $slot->parent) $scope = $scope->parent;
			if ($scope === null) throw new diagnostic($value->source(), $value->byte_start(), 'Bound parameter may expire before its statement');
		}
		$this->track($origin);
	}

	/** Consult the existing occurrence indexes, then check unindexed uses in a single source-order sweep. */
	public function verify(ast_function_body $body): void
	{
		$this->inspect_block($body);
		$events = [];
		foreach ($this->owners as $local) {
			$name = '$' . $local->name();
			$events[$local->end][$name] = ($events[$local->end][$name] ?? 0) + 1;
			$end = $local->parent->end;
			$events[$end][$name] = ($events[$end][$name] ?? 0) - 1;
		}
		// Writes, callable creation and some header bindings have no name-expression occurrence.
		// Unknown uses of a live owner must fail closed, including ones preceding the first bind.
		$active = [];
		$tokens = $body->lexical_root()->tokens;
		for ($i = $body->start; $i < $body->end; ++$i) {
			foreach ($events[$i] ?? [] as $name => $delta) {
				$active[$name] = ($active[$name] ?? 0) + $delta;
				if ($active[$name] === 0) unset($active[$name]);
			}
			if ($active === []) continue;
			$token = $tokens->tokens[$i];
			if (($token->kind === token_kind::variable && isset($active[$tokens->text_at($i)]) && !isset($this->observations[$i]))
				|| (($token->kind === token_kind::keyword || $token->kind === token_kind::identifier)
					&& in_array($tokens->text_at($i), ['fn','function','async'], true))) {
				throw new diagnostic($body->source(), $token->offset, 'Bound owner must stay confined without aliases of unproven lifetime');
			}
		}
	}

	/** Each block already indexes its expression reads, including those nested inside calls and conditions. */
	private function inspect_block(ast_statement_block $block): void
	{
		foreach ($block->references as $read) {
			$binding = $read->local_binding();
			if ($binding === null || ($this->owners[$binding->storage_id()] ?? null) !== $binding) continue;
			$use = $read->parent;
			if ($use instanceof ast_local_declaration && self::extraction($use) === $binding) {
				$this->track($use);
			} elseif (!self::observes($use, $read)) {
				throw new diagnostic($read->source(), $read->byte_start(), 'Bound owner must stay confined without aliases of unproven lifetime');
			}
			$this->observations[$read->start] = true;
		}
		foreach ($block->statements as $statement) {
			if ($statement instanceof ast_block_statement || $statement instanceof ast_repeating_statement) $this->inspect_block($statement->body);
			if ($statement instanceof ast_while_else_statement) $this->inspect_block($statement->otherwise);
			if ($statement instanceof ast_branch_statement) foreach ($statement->arms as $arm) $this->inspect_block($arm->body);
			if ($statement instanceof ast_try_statement) {
				$this->inspect_block($statement->body);
				foreach ($statement->handlers as $handler) $this->inspect_block($handler->body);
				if ($statement->finalizer !== null) $this->inspect_block($statement->finalizer);
			}
		}
	}

	/** STATIC METHODS */
	/** Recognize a by-value initialization that extracts this carrier's exact payload. */
	private static function extraction(ast_local_declaration $local): ?ast_local_declaration
	{
		$value = $local->initializer;
		$source = $value instanceof ast_name_expression ? $value->local_binding() : null;
		if ($local::class !== ast_local_declaration::class || $local->type_reference === null
			|| !($local->parent instanceof ast_statement_block) || !($source instanceof ast_local_declaration)) return null;
		$type = $source->type_def();
		return $type instanceof carrier_type_definition && $type->payload_type === $local->type_def() ? $source : null;
	}

	/** A prepared observation consumes the state or reads an independent runtime value. */
	private static function observes(ast_node $use, ast_name_expression $read): bool
	{
		if ($use instanceof ast_call_expression) return $use->receiver === $read && $use->runtime_binding()?->retains_receiver === false;
		if ($use instanceof ast_field_expression && $use->receiver === $read && $read->member_type() instanceof runtime_field_type) {
			$type = $use->type_def();
			return $type === type_string::instance() || $type === type_bool::instance() || type_registry::is_numeric_scalar($type);
		}
		if ($use instanceof ast_binary_expression) return $use->operator->logical() || $use->peer_equality?->observation !== null;
		if ($use instanceof ast_not_expression || $use instanceof ast_isset_expression) return true;
		return ($use instanceof ast_if_arm || $use instanceof ast_tested_loop || $use instanceof ast_ternary_expression) && $use->condition === $read;
	}
}
