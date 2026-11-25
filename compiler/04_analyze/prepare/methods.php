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
		self::ancestor_contracts($record, $symbols, $dependencies);
		// Inherited contracts still relay ancestry changes. A root static method observes its written signature and component types.
		if (!$method->is_static || $record->base() !== null) $method->watch_signature($dependencies);
		$dependencies?->watch($symbols->entry($method->parent->symbol_key()), $method);
		$marked = $method->has_virtual_dispatch() || ($method->is_final && !$method->is_static);
		if ($marked && ($method->is_constructor() || $method->is_static || $method->is_private
			|| $method->short_name === '__destruct')) {
			self::fail($method, 'Virtual modifiers require a public or protected instance method');
		}
		if (($method->is_abstract && (!$method->parent->is_abstract || $method->is_final))
			|| ($method->is_abstract && $method->is_virtual && $method->source()->flavor !== flavor::cpp)) self::fail($method, 'Abstract method requires an abstract class or struct and cannot be final or explicitly virtual');
		if ($method->is_virtual && ($method->is_override || $method->is_final)) self::fail($method, 'Conflicting virtual method modifiers');
		$requirements = $record->interface_requirements($method->short_name);
		$exact_slot = false;
		$method->adapted_base = null;
		foreach ($requirements as $requirement) {
			$dependencies?->watch($symbols->entry($requirement->symbol_key()), $method);
		}
		if ($requirements !== []) {
			if (!$method->is_override && $record->declaration->kind !== record_kind::interface_type) self::fail($method, 'Interface implementation requires override');
			foreach ($requirements as $requirement) {
				if (self::compatible($method, $requirement, $symbols, $dependencies, $method)) $exact_slot = true;
			}
		}
		if (!$method->is_constructor() && !$method->is_destructor()) {
			$base = $record->base()?->method_symbol($method->short_name, $symbols, $method, $dependencies)?->declaration();
			if ($base !== null) {
				self::contract($base, $symbols, $dependencies);
				if ($base->is_static !== $method->is_static) self::fail($method, 'Static and instance methods cannot replace one another');
				if ($method->is_static) {
					if ($base->is_final) self::fail($method, 'Cannot redeclare final static method');
				}
				else {
					if ($base->is_final) self::fail($method, 'Cannot override final method');
					if (!$base->has_virtual_dispatch() && !$record->base()->interface_dispatch($method->short_name)) self::fail($method, 'Nonvirtual method hiding is forbidden');
					if (!$method->is_override) self::fail($method, 'Inherited virtual method requires override');
				}
				if (self::compatible($method, $base, $symbols, $dependencies, $method)) $exact_slot = true;
				else $method->adapted_base = $base;
			} elseif ($method->is_override && $requirements === []) self::fail($method, 'Override has no inherited virtual target');
		}
		$method->introduces_slot = $method->is_override && !$exact_slot;
		$method->method_contract_ready = true;
	}

	/** Validate parents before consumers so an invalid inherited signature has one diagnostic in every generation. */
	private static function ancestor_contracts(record_type_definition $record, scope $symbols, ?Dependency_Watches $dependencies): void
	{
		if ($record->base() !== null) self::record_contract($record->base(), $symbols, $dependencies);
		foreach ($record->interfaces() as $interface) self::record_contract($interface, $symbols, $dependencies);
	}

	/** Scan nearest declarations once per invalidated record; no retained inherited-method inventory. */
	public static function record_contract(record_type_definition $record, scope $symbols, ?Dependency_Watches $dependencies): void
	{
		if ($record->method_contract_ready) return;
		self::ancestor_contracts($record, $symbols, $dependencies);
		foreach ($record->declaration->methods as $method) self::contract($method, $symbols, $dependencies);
		$slots = [];
		foreach ($record->declaration->trait_hooks as $name => $hooks) {
			$implementation = $record->method_symbol($name, $symbols, $record, $dependencies)?->declaration();
			$selected = $hooks[0];
			foreach ($hooks as $hook) {
				if ($hook->return_type_ready()) $hook->reset_preparation();
				Function_Preparation::signature($hook, $symbols, $record, $dependencies);
				if ($selected->is_protected && !$hook->is_protected) $selected = $hook;
			}
			$synthesize = $implementation === null && $record->declaration->is_abstract;
			if ($synthesize) {
				// Prefer the first signature at the required visibility that satisfies every obligation.
				foreach ($hooks as $candidate) {
					if ($candidate->is_protected !== $selected->is_protected) continue;
					foreach ($hooks as $hook) {
						if ($candidate !== $hook && self::signature_match($candidate, $hook, $symbols, $dependencies, $record) === null) continue 2;
					}
					$selected = $candidate;
					break;
				}
				$implementation = $selected;
			}
			if ($implementation === null || ($implementation->is_abstract && !$record->declaration->is_abstract) || $implementation->is_static) {
				self::fail($selected, 'Trait hook requires a concrete instance method');
			}
			foreach ($hooks as $hook) self::compatible($implementation, $hook, $symbols, $dependencies, $record, 'Trait hook');
			if ($synthesize) {
				// The record owns signature dependencies; descendants use the normal abstract override contract.
				$selected->method_contract_ready = true;
				$slots[$name] = $selected;
			}
		}
		$forwards = [];
		$adaptations = [];
		$requirements = [];
		foreach ($record->interface_requirements() as $requirement) {
			$name = $requirement->short_name;
			$dependencies?->watch($symbols->entry($requirement->symbol_key()), $record);
			foreach ($requirements[$name] ?? [] as $other) {
				if (self::signature_match($requirement, $other, $symbols, $dependencies, $record) === null) {
					self::compatible($other, $requirement, $symbols, $dependencies, $record);
				}
			}
			$requirements[$name][] = $requirement;
			$implementation = $record->method_symbol($name, $symbols, $record, $dependencies)?->declaration();
			// An abstract implementation may defer its body, but not its callable contract.
			if ($implementation !== null && $implementation->parent->kind !== record_kind::interface_type) {
				self::contract($implementation, $symbols, $dependencies);
				$exact = self::compatible($implementation, $requirement, $symbols, $dependencies, $record);
				if (!$exact && ($implementation->parent === $record->declaration || !$record->base()?->derives_from($requirement->parent->definition))) {
					$adaptations[] = [$requirement, $implementation];
				}
				if ($implementation->is_private || $implementation->is_protected || $implementation->is_static) self::fail($implementation, 'Interface implementation must be public and nonstatic');
			}
			if ($implementation === null || $implementation->parent->kind === record_kind::interface_type || $implementation->is_abstract) {
				if (!$record->declaration->is_abstract) throw new diagnostic($record->declaration->source(), $record->declaration->byte_start(), 'Concrete class must implement interface method: ' . $name);
				continue;
			}
			if ($implementation->parent !== $record->declaration) {
				// Reuse an already inherited interface slot without a redundant bridge.
				if ($record->base()?->derives_from($requirement->parent->definition)) continue;
				if ($exact || (!$implementation->has_virtual_dispatch() && !$record->base()?->interface_dispatch($name))) {
					$forwards[$name] = $implementation;
				}
			}
		}
		$record->interface_forwards = $forwards;
		$record->interface_adaptations = $adaptations;
		if (!$record->declaration->is_abstract) {
			$seen = [];
			for ($type = $record; $type !== null; $type = $type->base()) {
				foreach ($type->effective_methods() as $name => $method) {
					if (isset($seen[$name])) continue;
					$seen[$name] = true;
					if ($method->is_abstract) throw new diagnostic($record->declaration->source(), $record->declaration->byte_start(),
						'Concrete ' . ($record->declaration->kind === record_kind::structure ? 'struct' : 'class') . ' must implement abstract method: ' . $name);
				}
			}
		}
		if ($slots !== []) $record->settle_hook_slots($slots);
		$record->method_contract_ready = true;
	}

	/** Validate a directional contract and report whether its native slot is exact. */
	private static function compatible(ast_function_declaration $method, ast_function_declaration $base, scope $symbols,
		?Dependency_Watches $dependencies, dependency_watcher $watcher, ?string $contract = null): bool
	{
		$contract ??= $method->is_static ? 'Static redeclaration' : 'Override';
		if (($method->is_private && !$base->is_private) || ($method->is_protected && !$base->is_private && !$base->is_protected)) self::fail($method, $contract . ' cannot narrow visibility');
		return self::signature_match($method, $base, $symbols, $dependencies, $watcher)
			?? self::fail($method, $contract . ' signature must match exactly' . ($method->is_static ? '' : ' except for compatible shared values'));
	}

	/** True for identical slots, false for an admitted adaptation, null for incompatible contracts. */
	private static function signature_match(ast_function_declaration $method, ast_function_declaration $base, scope $symbols,
		?Dependency_Watches $dependencies, dependency_watcher $watcher): ?bool
	{
		if ($method->returns_reference !== $base->returns_reference || count($method->parameters) !== count($base->parameters)
			|| $method->variadic_slot() !== $base->variadic_slot()) return null;
		$exact = $method->return_type_def()->matches($base->return_type_def());
		if (!$exact && ($method->is_static || !self::widens($method->return_type_def(), $base->return_type_def(), $symbols, $dependencies, $watcher))) return null;
		foreach ($method->parameters as $i => $parameter) {
			$original = $base->parameters[$i];
			if ($parameter->is_readonly_pack() !== $original->is_readonly_pack() || $parameter->mode !== $original->mode || ($parameter->default_value === null) !== ($original->default_value === null)) return null;
			$provided = $parameter->type_def();
			$required = $original->type_def();
			if ($provided->matches($required)) continue;
			if ($method->is_static || $parameter->mode !== passing_mode::value
				|| !self::widens($original->type_def(), $parameter->type_def(), $symbols, $dependencies, $watcher)) return null;
			$exact = false;
		}
		return $exact;
	}

	/** Only a distinct shared-value pair needs an ancestry dependency. Presence is invariant. */
	private static function widens(type_definition $from, type_definition $to, scope $symbols,
		?Dependency_Watches $dependencies, dependency_watcher $watcher): bool
	{
		if (!($from instanceof shared_record_type_definition) || !($to instanceof shared_record_type_definition)
			|| $from->required !== $to->required) return false;
		$dependencies?->watch($symbols->entry($from->payload->declaration->symbol_key()), $watcher);
		return $from->payload->derives_from($to->payload);
	}

	private static function fail(ast_function_declaration $method, string $message): never
	{
		throw new diagnostic($method->source(), $method->lexical_root()->byte_at($method->modifier_start ?? $method->start), $message . ': ' . $method->name());
	}
}
