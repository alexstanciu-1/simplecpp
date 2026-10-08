<?php

/* Post-layout contracts: signatures never depend back on these obligations. */
namespace scpp\compiler;

final class Type_Contract_Preparation
{
	public static function prepare(collected_member_owner $record, preparation_context $context): prepared_type_contract
	{
		$is_interface = $record instanceof collected_interface;
		if (!$is_interface) { $context->worker->require_declaration($context->owner, $record); }
		else { self::interface_shape($record->syntax()); }
		$relations = $context->worker->require_relationships($record, $context);
		$facts = new prepared_type_contract();
		$facts->abstract_type = $is_interface || $relations->abstract_type;
		$facts->polymorphic = $is_interface || (q_count($relations->interfaces) !== 0);
		$base = weakref_get($relations->base);
		$base_contract /** nullable<prepared_type_contract> */ = null;
		$pending /** vector<collected_method> */ = [];
		if ($base !== null) {
			$base_contract = $context->worker->require_type_contract($base, $context);
			$facts->polymorphic = $facts->polymorphic || $base_contract->polymorphic;
			$pending = $base_contract->obligations;
			$facts->interface_requirements = $base_contract->interface_requirements;
		}
		foreach ($relations->interfaces as $interface) {
			$interface_contract = $context->worker->require_type_contract($interface, $context);
			foreach ($interface_contract->interface_requirements as $requirement) {
				self::add_requirement($facts, $requirement, $context);
			}
		}
		// Direct signature dependencies matter even when the original requirement identities stay unchanged.
		foreach ($facts->interface_requirements as $requirement) { Call_Preparation::require_signature($requirement, $context); }
		$methods /** Storage<method_node> */ = Member_Inventory::methods($record);
		$decisions /** Keyed_Storage<prepared_method_contract> */ = $facts->methods;
		foreach ($methods as $syntax) {
			if (isset($decisions[$syntax->name])) { throw new \RuntimeException('Duplicate method: ' . $syntax->name); }
			$method = object_cast($syntax->occurrence(), collected_method::class);
			Call_Preparation::require_signature($method, $context);
			$signature = $syntax->require_preparation();
			$decision = new prepared_method_contract();
			$decision->method = $method;
			$decision->abstract_method = $signature->abstract_method;
			$decision->final_method = $signature->final_method;
			$decision->virtual_method = $signature->abstract_method;
			if ($signature->abstract_method && !$facts->abstract_type) {
				throw new \RuntimeException('Abstract method requires an abstract declaring type');
			}
			$matches /** vector<inherited_member_candidate> */ = [];
			if (($base !== null) && ($syntax->name !== '__construct') && ($syntax->name !== '__destruct')) {
				$matches = Inherited_Lookup::members($base, $syntax->name, preparation_lookup_kind::method_name, $context);
			}
			if (q_count($matches) !== 0) {
				if (q_count($matches) !== 1) { throw new \RuntimeException('Ambiguous inherited method contract'); }
				$parent = object_cast($matches[0]->member, collected_method::class);
				Call_Preparation::require_signature($parent, $context);
				$inherited = self::effective_method($base, $parent->name, $context);
				if ($inherited->final_method) { throw new \RuntimeException('Cannot override a final method'); }
				$parent_signature = $parent->callable_syntax()->require_preparation();
				$static_redeclaration = $signature->static_member && $parent_signature->static_member;
				if (!$static_redeclaration && !$inherited->virtual_method) {
					throw new \RuntimeException('Nonvirtual inherited member redeclaration is deferred');
				}
				if (($signature->static_member !== $parent_signature->static_member)
					|| !self::compatible($parent_signature->signature, $signature->signature)) {
					if ($static_redeclaration) {
						throw new \RuntimeException('Static redeclaration requires an exact compatible signature');
					}
					throw new \RuntimeException('Override requires an exact compatible instance signature');
				}
				if ((($signature->visibility === member_visibility::private_access) && ($parent_signature->visibility !== member_visibility::private_access))
					|| (($parent_signature->visibility === member_visibility::public_access) && ($signature->visibility !== member_visibility::public_access))) {
					throw new \RuntimeException('Override cannot narrow visibility');
				}
				if (!$static_redeclaration) {
					$decision->overridden = $parent;
					$decision->virtual_method = true;
				}
			}
			if ($is_interface) { self::add_requirement($facts, $method, $context); }
			if ($decision->virtual_method) { $facts->polymorphic = true; }
			$decisions->add($syntax->name, $decision);
		}
		$forwarders /** Keyed_Storage<prepared_method_contract> */ = $facts->forwarders;
		if (!$is_interface) {
			foreach ($facts->interface_requirements as $requirement) {
				$matches = Inherited_Lookup::members($record, $requirement->name, preparation_lookup_kind::method_name, $context);
				if (q_count($matches) === 0) { $facts->obligations[] = $requirement; continue; }
				if (q_count($matches) !== 1) { throw new \RuntimeException('Ambiguous interface implementation'); }
				$implementation = object_cast($matches[0]->member, collected_method::class);
				Call_Preparation::require_signature($implementation, $context);
				$signature = $implementation->callable_syntax()->require_preparation();
				if ($signature->static_member || ($signature->visibility !== member_visibility::public_access)
					|| !self::compatible($requirement->signature(), $signature->signature)) {
					throw new \RuntimeException('Interface implementation requires an exact compatible public instance signature');
				}
				if (isset($decisions[$requirement->name])) { $decisions[$requirement->name]->virtual_method = true; }
				elseif (!$signature->abstract_method) {
					$needs_forwarder = true;
					if ($base !== null) {
						foreach ($base_contract->interface_requirements as $previous) {
							if ($previous === $requirement) { $needs_forwarder = false; }
						}
					}
					if ($needs_forwarder && !isset($forwarders[$requirement->name])) {
						$inherited = self::effective_method($base, $implementation->name, $context);
						if ($inherited->virtual_method && $inherited->final_method) {
							throw new \RuntimeException('New interface forwarding through an inherited final virtual method is deferred');
						}
						$bridge = new prepared_method_contract();
						$bridge->method = $implementation;
						$bridge->forwarding_owner = $base;
						$bridge->virtual_method = true;
						$bridge->final_method = $signature->final_method;
						$forwarders->add($implementation->name, $bridge);
					}
				}
			}
		}
		foreach ($pending as $obligation) {
			if (!isset($decisions[$obligation->name])) { $facts->obligations[] = $obligation; }
		}
		foreach ($decisions as $decision) {
			if ($decision->abstract_method) { $facts->obligations[] = weakref_get($decision->method); }
			$method = weakref_get($decision->method);
			if ($method->callable_syntax()->require_preparation()->override_assertion) {
				$has_target = weakref_get($decision->overridden) !== null;
				foreach ($facts->interface_requirements as $requirement) {
					if (($requirement !== $method) && ($requirement->name === $method->name)) { $has_target = true; }
				}
				if (!$has_target) { throw new \RuntimeException('Override assertion requires an inherited virtual method'); }
			}
		}
		if (!$facts->abstract_type && (q_count($facts->obligations) !== 0)) {
			throw new \RuntimeException('Concrete type has unimplemented abstract methods');
		}
		return $facts;
	}

	private static function interface_shape(type_declaration_node $syntax): void
	{
		$properties /** Storage<property_node> */ = $syntax->properties;
		$constants /** Storage<member_constant_node> */ = $syntax->constants;
		$traits /** Storage<trait_use_node> */ = $syntax->use_traits;
		if (!$properties->is_empty() || !$constants->is_empty() || !$traits->is_empty()
			|| (q_count($syntax->modifiers) !== 0)) {
			throw new \RuntimeException('Interface slice supports public method contracts only');
		}
	}

	/** Requirements observe original identities, deduplicating diamonds without copying signatures. */
	private static function add_requirement(prepared_type_contract $facts, collected_method $method, preparation_context $context): void
	{
		Call_Preparation::require_signature($method, $context);
		foreach ($facts->interface_requirements as $previous) {
			if ($previous === $method) { return; }
			if ($previous->name === $method->name) {
				Call_Preparation::require_signature($previous, $context);
				if (!self::compatible($previous->signature(), $method->signature())) {
					throw new \RuntimeException('Incompatible same-named interface contracts');
				}
			}
		}
		$facts->interface_requirements[] = $method;
	}

	/** An inherited bridge is virtual in its owning descendant, without changing the original method. */
	private static function effective_method(collected_member_owner $owner, string $name, preparation_context $context): prepared_method_contract
	{
		$contracts = $context->worker->require_type_contract($owner, $context);
		$methods /** Keyed_Storage<prepared_method_contract> */ = $contracts->methods;
		$bridges /** Keyed_Storage<prepared_method_contract> */ = $contracts->forwarders;
		if (isset($methods[$name])) { return $methods[$name]; }
		if (isset($bridges[$name])) { return $bridges[$name]; }
		$base = weakref_get($context->worker->require_relationships($owner, $context)->base);
		if ($base === null) { throw new \LogicException('Missing inherited method contract'); }
		return self::effective_method($base, $name, $context);
	}

	private static function compatible(callable_signature $base, callable_signature $child): bool
	{
		if (!$base->return_type->matches($child->return_type)) { return false; }
		if (($base->reference_return === null) !== ($child->reference_return === null)) { return false; }
		if ($base->reference_return !== null) {
			if ($base->reference_return->parameter_index() !== $child->reference_return->parameter_index()) { return false; }
		}
		$before /** Storage<callable_parameter> */ = $base->parameters;
		$after /** Storage<callable_parameter> */ = $child->parameters;
		if ($before->count() !== $after->count()) { return false; }
		foreach ($before as $index => $parameter) {
			$current = $after[$index];
			if (!$parameter->type->matches($current->type) || ($parameter->mode !== $current->mode)
				|| ($parameter->access !== $current->access)
				|| (($parameter->element_type === null) !== ($current->element_type === null))
				|| (($parameter->default_value === null) !== ($current->default_value === null))) { return false; }
		}
		return true;
	}

	public static function same(?prepared_type_contract $old, prepared_type_contract $current): bool
	{
		if ($old === null) { return false; }
		if (($old->abstract_type !== $current->abstract_type) || ($old->polymorphic !== $current->polymorphic)) { return false; }
		if (q_count($old->interface_requirements) !== q_count($current->interface_requirements)) { return false; }
		foreach ($old->interface_requirements as $index => $method) {
			if ($method !== $current->interface_requirements[$index]) { return false; }
		}
		$old_bridges /** Keyed_Storage<prepared_method_contract> */ = $old->forwarders;
		$new_bridges /** Keyed_Storage<prepared_method_contract> */ = $current->forwarders;
		if ($old_bridges->count() !== $new_bridges->count()) { return false; }
		foreach ($new_bridges as $name => $bridge) {
			if (!isset($old_bridges[$name])) { return false; }
			$previous = $old_bridges[$name];
			if ((weakref_get($previous->method) !== weakref_get($bridge->method))
				|| (weakref_get($previous->forwarding_owner) !== weakref_get($bridge->forwarding_owner))
				|| ($previous->final_method !== $bridge->final_method)) { return false; }
		}
		$before /** Keyed_Storage<prepared_method_contract> */ = $old->methods;
		$after /** Keyed_Storage<prepared_method_contract> */ = $current->methods;
		if (($before->count() !== $after->count()) || (q_count($old->obligations) !== q_count($current->obligations))) { return false; }
		foreach ($after as $name => $method) {
			if (!isset($before[$name])) { return false; }
			$previous = $before[$name];
			if ((weakref_get($previous->method) !== weakref_get($method->method))
				|| (weakref_get($previous->overridden) !== weakref_get($method->overridden))
				|| ($previous->virtual_method !== $method->virtual_method)
				|| ($previous->abstract_method !== $method->abstract_method)
				|| ($previous->final_method !== $method->final_method)) { return false; }
		}
		foreach ($old->obligations as $index => $method) { if ($method !== $current->obligations[$index]) { return false; } }
		return true;
	}
}
