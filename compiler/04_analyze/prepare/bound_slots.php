<?php

declare(strict_types=1);
namespace scpp\compiler;

/** A prepared statement can retain caller slots only while its original local is confined. */
final class Bound_Slots
{
	/** Require a fresh confined receiver and storage live throughout its use. */
	public static function prove(ast_call_expression $call, ast_expression $operand): void
	{
		$receiver = $call->receiver;
		$stmt = $receiver instanceof ast_name_expression ? $receiver->local_binding() : null;
		$slot = $operand instanceof ast_name_expression ? $operand->local_binding() : null;
		if (!($stmt instanceof ast_local_declaration) || $stmt::class !== ast_local_declaration::class
			|| !($stmt->initializer instanceof ast_call_expression)
			|| $stmt->initializer->runtime_binding()?->symbol !== '::scpp::mysqli_api::prepare'
			|| !($stmt->parent instanceof ast_statement_block) || $slot === null
			|| !in_array($operand->type_def()->name, ['int64','float64','string','mixed'], true)) {
			throw new diagnostic($operand->source(), $operand->byte_start(), 'bind_param requires a fresh local statement and a stable typed slot');
		}
		$scope = $stmt->parent;
		if (!($slot instanceof ast_parameter_declaration)) {
			$owner = $scope;
			while ($owner !== null && $owner !== $slot->parent) $owner = $owner->parent ?? null;
			if ($slot::class !== ast_local_declaration::class || $owner === null) {
				throw new diagnostic($operand->source(), $operand->byte_start(), 'Bound parameter may expire before its statement');
			}
		}
		// Scan the whole lexical lifetime, including later, as-yet unprepared uses.
		// Every occurrence must be a terminal scalar/property read or ordinary
		// receiver invocation. Copy, reassignment, return, capture and first-class
		// callable creation would give another owner access after the slot expires.
		$tokens = $stmt->lexical_root()->tokens;
		$name = '$' . $receiver->name;
		for ($i = $stmt->end; $i < $scope->end; ++$i) {
			$token = $tokens->tokens[$i];
			if (($token->kind === token_kind::keyword || $token->kind === token_kind::identifier) && in_array($tokens->text_at($i), ['fn','function','async'], true)) {
				throw new diagnostic($call->source(), $call->byte_start(), 'Bound statement lifetime across a closure is unproven');
			}
			if ($token->kind !== token_kind::variable || $tokens->text_at($i) !== $name) continue;
			if (($tokens->tokens[$i + 1]->kind ?? null) !== token_kind::member
				|| ($tokens->tokens[$i + 4]->kind ?? null) === token_kind::ellipsis) {
				throw new diagnostic($call->source(), $tokens->tokens[$i]->offset, 'Bound statement must stay in its declaring scope without aliases');
			}
		}
	}
}
