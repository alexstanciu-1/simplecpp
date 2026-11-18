<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Validate inherited callable contracts after all signatures settle; retain watches, not copied base declarations. */
final class Method_Preparation
{
	/** Settle only invalidated method contracts, consuming completed signatures and observing inherited lookup. */
	public static function contract(ast_function_declaration $method, scope $symbols, ?Dependency_Watches $dependencies): void
	{
		if ($method->method_contract_ready || !($method->parent instanceof ast_record_declaration)) return;
		$record = $method->parent->definition;
		$method->watch_signature($dependencies);
		$dependencies?->watch($symbols->entry($method->parent->symbol_key()), $method);
		$marked = $method->has_virtual_dispatch() || $method->is_final;
		if ($marked && ($method->is_constructor() || $method->is_static || $method->is_private || $method->is_protected
			|| $method->short_name === '__destruct' || $method->parent->kind !== record_kind::class_type)) {
			self::fail($method, 'Virtual modifiers require a public class instance method');
		}
		if (($method->is_abstract && (!$method->parent->is_abstract || $method->is_final))
			|| ($method->is_abstract && $method->is_virtual && $method->source()->flavor !== flavor::cpp)) self::fail($method, 'Abstract method requires an abstract class and cannot be final or explicitly virtual');
		if ($method->is_virtual && ($method->is_override || $method->is_final)) self::fail($method, 'Conflicting virtual method modifiers');
		if (!$method->is_constructor()) {
			$base = $record->base()?->method_symbol($method->short_name, $symbols, $method, $dependencies)?->declaration();
			if ($base !== null) {
				self::contract($base, $symbols, $dependencies);
				if (!$base->has_virtual_dispatch() || $method->is_static) self::fail($method, 'Nonvirtual method hiding is forbidden');
				if (!$method->is_override) self::fail($method, 'Inherited virtual method requires override');
				if ($base->is_final) self::fail($method, 'Cannot override final method');
				if ($method->return_type_def() !== $base->return_type_def() || count($method->parameters) !== count($base->parameters)) {
					self::fail($method, 'Override signature must match exactly');
				}
				foreach ($method->parameters as $i => $parameter) {
					$original = $base->parameters[$i];
					if ($parameter->type_def() !== $original->type_def() || $parameter->mode !== $original->mode
						|| ($parameter->default_value === null) !== ($original->default_value === null)) {
						self::fail($method, 'Override signature must match exactly');
					}
				}
			} elseif ($method->is_override) self::fail($method, 'Override has no inherited virtual target');
		}
		if ($method->is_final && !$method->is_override) self::fail($method, 'Final requires an overriding implementation in this slice');
		$method->method_contract_ready = true;
	}

	/** Scan nearest declarations once per invalidated record; no retained inherited-method inventory. */
	public static function record_contract(record_type_definition $record): void
	{
		if ($record->abstract_contract_ready) return;
		if ($record->declaration->is_abstract && $record->declaration->kind !== record_kind::class_type) {
			throw new diagnostic($record->declaration->source(), $record->declaration->byte_start(), 'Abstract structs are deferred');
		}
		if (!$record->declaration->is_abstract) {
			$seen = [];
			for ($type = $record; $type !== null; $type = $type->base()) {
				foreach ($type->declaration->methods as $name => $method) {
					if (isset($seen[$name])) continue;
					$seen[$name] = true;
					if ($method->is_abstract) throw new diagnostic($record->declaration->source(), $record->declaration->byte_start(),
						'Concrete class must implement abstract method: ' . $name);
				}
			}
		}
		$record->abstract_contract_ready = true;
	}

	private static function fail(ast_function_declaration $method, string $message): never
	{
		throw new diagnostic($method->source(), $method->root()->byte_at($method->modifier_start ?? $method->start), $message . ': ' . $method->name());
	}
}
