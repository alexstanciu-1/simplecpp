<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Settle namespace literal declarations and global storage initialization before any executable body reads them. */
final class Literal_Declaration_Preparation
{
	/** All literal declarations are validated, including unused declarations; unchanged settled AST facts can be reused. */
	public static function declarations(scope $symbols, Dependency_Watches $dependencies): void
	{
		$path = [];
		foreach ($symbols->declarations as $declaration)
		{
			if (!($declaration instanceof ast_literal_declaration) || $declaration->type_ready()) {
				continue;
			}
			self::prepare($declaration, $symbols, $dependencies, $path);
		}
	}

	private static function prepare(ast_literal_declaration $declaration, scope $symbols, Dependency_Watches $dependencies, array &$path /** array<string,ast_computed_integer_constant> */): void
	{
		if ($declaration instanceof ast_computed_integer_constant) {
			$key = $declaration->symbol_key();
			if (isset($path[$key])) {
				throw self::cycle($path, $key);
			}
			$path[$key] = $declaration;
			$retry = $declaration->begin_fold($symbols, $dependencies);
			try {
				self::operand($declaration->initializer, $declaration, $symbols, $dependencies, $path, $retry);
				$declaration->settle_type(type_int64::instance());
			} finally {
				unset($path[$key]);
			}
			return;
		}
		$declaration->reset_preparation();
		type_registry::prepare_literal($declaration->initializer);
		$type = $declaration->type_reference === null ? $declaration->initializer->type_def()
			: Type_Resolution::resolve($declaration->type_reference);
		if ($type === null) {
			throw new diagnostic($declaration->source(), $declaration->byte_start(),
				'Unknown or unsupported ' . ($declaration instanceof ast_global_declaration ? 'global' : 'constant')
				. ' type: ' . $declaration->type_reference?->spelling());
		}
		if (($declaration instanceof ast_global_declaration) && !$type->supports_global_storage()
			&& !(($declaration->parent instanceof ast_record_declaration) && $type->supports_static_property())) {
			throw new diagnostic($declaration->source(), $declaration->byte_start(), 'String globals are deferred');
		}
		$conversion = $type->prepare_conversion($declaration->initializer, $declaration->type_reference !== null ? conversion_context::typed_destination : conversion_context::inference);
		$declaration->settle_type($type, $conversion);
	}

	/** Report the same cycle and location regardless of which dependent was prepared first. */
	private static function cycle(array $path /** array<string,ast_computed_integer_constant> */, string $key): diagnostic
	{
		$cycle = array_keys($path);
		$cycle = array_slice($cycle, array_search($key, $cycle, true));
		$first = min($cycle);
		$offset = array_search($first, $cycle, true);
		$site = $path[$first];
		return new diagnostic($site->source(), $site->byte_start(), 'Cyclic integer constant dependency: '
			. implode(' -> ', [...array_slice($cycle, $offset), ...array_slice($cycle, 0, $offset), $first]));
	}

	private static function operand(ast_expression $expr, ast_computed_integer_constant $owner, scope $symbols,
		Dependency_Watches $dependencies, array &$path /** array<string,ast_computed_integer_constant> */, bool $retry): int /** int64 */
	{
		if ($retry) $expr->reset_preparation();
		$integer = type_int64::instance();
		if ($expr instanceof ast_integer_expression) {
			$integer->prepare_literal($expr);
			return $expr->constant();
		}
		if ($expr::class === ast_name_expression::class && !str_contains($expr->name, '::')
			&& ($expr->constant_only || $expr->source()->flavor !== flavor::php)) {
			return self::reference($expr, $owner, $symbols, $dependencies, $path);
		}
		if ($expr instanceof ast_binary_expression) {
			$left = self::operand($expr->left, $owner, $symbols, $dependencies, $path, $retry);
			$right = self::operand($expr->right, $owner, $symbols, $dependencies, $path, $retry);
			if ($integer->binary_result($expr->operator, $integer) === $integer) {
				if ($expr->operator === binary_operator::divide || $expr->operator === binary_operator::remainder) {
					if ($right === 0) throw new diagnostic($expr->source(), $expr->byte_start(), 'Division by zero in integer constant');
					if ($left === PHP_INT_MIN && $right === -1) throw new diagnostic($expr->source(), $expr->byte_start(), 'Integer constant out of range');
				}
				$value = match ($expr->operator) {
					binary_operator::add => $left + $right,
					binary_operator::subtract => $left - $right,
					binary_operator::multiply => $left * $right,
					binary_operator::divide => intdiv($left, $right),
					binary_operator::remainder => $left % $right,
					default => null,
				};
				if (is_int($value)) {
					$expr->settle_binary($integer, $integer, folded: $value);
					return $value;
				}
				if ($value !== null) throw new diagnostic($expr->source(), $expr->byte_start(), 'Integer constant out of range');
			}
		}
		if ($expr instanceof ast_unary_expression && $expr->operator !== unary_operator::bit_not) {
			$value = self::operand($expr->operand, $owner, $symbols, $dependencies, $path, $retry);
			if ($expr->operator === unary_operator::negative) {
				if ($value === PHP_INT_MIN) throw new diagnostic($expr->source(), $expr->byte_start(), 'Integer constant out of range');
				$value = -$value;
			}
			$expr->settle_unary($integer, $value);
			return $value;
		}
		throw new diagnostic($expr->source(), $expr->byte_start(), 'Unsupported integer constant expression');
	}

	private static function reference(ast_name_expression $expr, ast_computed_integer_constant $owner, scope $symbols,
		Dependency_Watches $dependencies, array &$path /** array<string,ast_computed_integer_constant> */): int /** int64 */
	{
		$file = $owner->lexical_root();
		if (!$expr->constant_only) {
			$storage = $symbols->entry('var:' . Source_Names::resolve($expr->name, $owner->namespace));
			$dependencies->watch($storage, $owner);
			if ($storage->declaration() !== null) {
				throw new diagnostic($expr->source(), $expr->byte_start(), 'Integer constant cannot read global storage');
			}
		}
		$entry = $symbols->entry($file->constant_key($expr->name));
		$dependencies->watch($entry, $owner);
		$target = $entry->declaration();
		if ($target === null && $expr->source()->flavor === flavor::php) {
			$runtime = runtime_constants::integer($file->runtime_constant_key($expr->name));
			if ($runtime !== null) {
				$expr->settle_runtime_constant($runtime);
				return $runtime->value;
			}
		}
		if (!($target instanceof ast_constant_declaration) || $target->parent instanceof ast_record_declaration) {
			throw new diagnostic($expr->source(), $expr->byte_start(), 'Unknown namespace integer constant: ' . $expr->name);
		}
		if (!$target->type_ready()) self::prepare($target, $symbols, $dependencies, $path);
		if ($target->type_def() !== type_int64::instance()) {
			throw new diagnostic($expr->source(), $expr->byte_start(), 'Integer constant operand required: ' . $expr->name);
		}
		$expr->settle_constant($entry);
		return $expr->constant();
	}
}
