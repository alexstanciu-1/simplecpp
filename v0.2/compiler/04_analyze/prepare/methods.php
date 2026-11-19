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
		if ($record->base() !== null) self::record_contract($record->base(), $symbols, $dependencies);
		$method->watch_signature($dependencies);
		$dependencies?->watch($symbols->entry($method->parent->symbol_key()), $method);
		$marked = $method->has_virtual_dispatch() || ($method->is_final && !$method->is_static);
		if ($marked && ($method->is_constructor() || $method->is_static || $method->is_private
			|| $method->short_name === '__destruct' || $method->parent->kind === record_kind::structure)) {
			self::fail($method, 'Virtual modifiers require a public class instance method or a protected class instance method');
		}
		if (($method->is_abstract && (!$method->parent->is_abstract || $method->is_final))
			|| ($method->is_abstract && $method->is_virtual && $method->source()->flavor !== flavor::cpp)) self::fail($method, 'Abstract method requires an abstract class and cannot be final or explicitly virtual');
		if ($method->is_virtual && ($method->is_override || $method->is_final)) self::fail($method, 'Conflicting virtual method modifiers');
		$requirements = $record->interface_requirements($method->short_name);
		foreach ($requirements as $requirement) {
			$dependencies?->watch($symbols->entry($requirement->symbol_key()), $method);
		}
		if ($requirements !== []) {
			if (!$method->is_override) self::fail($method, 'Interface implementation requires override');
			foreach ($requirements as $requirement) self::compatible($method, $requirement);
		}
		if (!$method->is_constructor()) {
			$base = $record->base()?->method_symbol($method->short_name, $symbols, $method, $dependencies)?->declaration();
			if ($base !== null) {
				self::contract($base, $symbols, $dependencies);
				if ($base->is_static !== $method->is_static) self::fail($method, 'Static and instance methods cannot replace one another');
				if ($method->is_static) {
					if ($base->is_final) self::fail($method, 'Cannot redeclare final static method');
				}
				else {
					if (!$base->has_virtual_dispatch() && !$record->base()->interface_dispatch($method->short_name)) self::fail($method, 'Nonvirtual method hiding is forbidden');
					if (!$method->is_override) self::fail($method, 'Inherited virtual method requires override');
					if ($base->is_final) self::fail($method, 'Cannot override final method');
				}
				self::compatible($method, $base);
			} elseif ($method->is_override && $requirements === []) self::fail($method, 'Override has no inherited virtual target');
		}
		if ($method->is_final && !$method->is_static && !$method->is_override) self::fail($method, 'Final requires an overriding implementation in this slice');
		$method->method_contract_ready = true;
	}

	/** Scan nearest declarations once per invalidated record; no retained inherited-method inventory. */
	public static function record_contract(record_type_definition $record, scope $symbols, ?Dependency_Watches $dependencies): void
	{
		if ($record->method_contract_ready) return;
		if ($record->declaration->is_abstract && $record->declaration->kind === record_kind::structure) {
			throw new diagnostic($record->declaration->source(), $record->declaration->byte_start(), 'Abstract structs are deferred');
		}
		if ($record->base() !== null) self::record_contract($record->base(), $symbols, $dependencies);
		foreach ($record->declaration->methods as $method) self::contract($method, $symbols, $dependencies);
		$forwards = [];
		$requirements = [];
		foreach ($record->interface_requirements() as $requirement) {
			$name = $requirement->short_name;
			$dependencies?->watch($symbols->entry($requirement->symbol_key()), $record);
			if (isset($requirements[$name])) self::compatible($requirement, $requirements[$name]);
			$requirements[$name] = $requirement;
			$implementation = $record->method_symbol($name, $symbols, $record, $dependencies)?->declaration();
			// An abstract implementation may defer its body, but not its callable contract.
			if ($implementation !== null && $implementation->parent->kind !== record_kind::interface_type) {
				self::contract($implementation, $symbols, $dependencies);
				self::compatible($implementation, $requirement);
				if ($implementation->is_private || $implementation->is_protected || $implementation->is_static) self::fail($implementation, 'Interface implementation must be public and nonstatic');
			}
			if ($implementation === null || $implementation->parent->kind === record_kind::interface_type || $implementation->is_abstract) {
				if (!$record->declaration->is_abstract) throw new diagnostic($record->declaration->source(), $record->declaration->byte_start(), 'Concrete class must implement interface method: ' . $name);
				continue;
			}
			if ($implementation->parent !== $record->declaration) {
				// Reuse an already inherited interface slot without a redundant bridge.
				if ($record->base()?->derives_from($requirement->parent->definition)) continue;
				if ($implementation->is_final) self::fail($implementation, 'Inherited final interface implementation is deferred');
				$forwards[$name] = $implementation;
			}
		}
		$record->interface_forwards = $forwards;
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
		$record->method_contract_ready = true;
	}

	/** Share the exact callable contract check across static redeclarations, overrides and interface obligations. */
	private static function compatible(ast_function_declaration $method, ast_function_declaration $base): void
	{
		$contract = $method->is_static ? 'Static redeclaration' : 'Override';
		if (($method->is_private && !$base->is_private) || ($method->is_protected && !$base->is_private && !$base->is_protected)) self::fail($method, $contract . ' cannot narrow visibility');
		if ($method->return_type_def() !== $base->return_type_def() || count($method->parameters) !== count($base->parameters)) self::fail($method, $contract . ' signature must match exactly');
		foreach ($method->parameters as $i => $parameter) {
			$original = $base->parameters[$i];
			if ($parameter->type_def() !== $original->type_def() || $parameter->mode !== $original->mode
				|| ($parameter->default_value === null) !== ($original->default_value === null)) self::fail($method, $contract . ' signature must match exactly');
		}
	}

	private static function fail(ast_function_declaration $method, string $message): never
	{
		throw new diagnostic($method->source(), $method->root()->byte_at($method->modifier_start ?? $method->start), $message . ': ' . $method->name());
	}
}
