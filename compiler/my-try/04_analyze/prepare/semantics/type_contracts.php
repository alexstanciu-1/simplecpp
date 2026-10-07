<?php

/* Post-layout contracts: signatures never depend back on these obligations. */
namespace scpp\compiler;

final class Type_Contract_Preparation
{
	public static function prepare(collected_record $record, preparation_context $context): prepared_type_contract
	{
		$context->worker->require_declaration($context->owner, $record);
		$relations = $context->worker->require_relationships($record, $context);
		$facts = new prepared_type_contract();
		$facts->abstract_type = $relations->abstract_type;
		$base = weakref_get($relations->base);
		$pending /** vector<collected_method> */ = [];
		if ($base !== null) {
			$base_contract = $context->worker->require_type_contract($base, $context);
			$facts->polymorphic = $base_contract->polymorphic;
			$pending = $base_contract->obligations;
		}
		$methods /** Storage<method_node> */ = $record->syntax()->methods;
		$decisions /** Keyed_Storage<prepared_method_contract> */ = $facts->methods;
		foreach ($methods as $syntax) {
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
				$parent_contract = $context->worker->require_type_contract($parent->declaring_type(), $context);
				$parent_methods /** Keyed_Storage<prepared_method_contract> */ = $parent_contract->methods;
				$inherited = $parent_methods[$parent->name];
				if ($inherited->final_method) { throw new \RuntimeException('Cannot override a final method'); }
				if (!$inherited->virtual_method) { throw new \RuntimeException('Nonvirtual inherited member redeclaration is deferred'); }
				$parent_signature = $parent->callable_syntax()->require_preparation();
				if ($signature->static_member || !self::compatible($parent_signature->signature, $signature->signature)) {
					throw new \RuntimeException('Override requires an exact compatible instance signature');
				}
				if (($signature->visibility === member_visibility::private_access)
					|| (($parent_signature->visibility === member_visibility::public_access) && ($signature->visibility !== member_visibility::public_access))) {
					throw new \RuntimeException('Override cannot narrow visibility');
				}
				$decision->overridden = $parent;
				$decision->virtual_method = true;
			}
			elseif ($signature->override_assertion) { throw new \RuntimeException('Override assertion requires an inherited virtual method'); }
			if ($decision->virtual_method) { $facts->polymorphic = true; }
			$decisions->add($syntax->name, $decision);
		}
		foreach ($pending as $obligation) {
			if (!isset($decisions[$obligation->name])) { $facts->obligations[] = $obligation; }
		}
		foreach ($decisions as $decision) {
			if ($decision->abstract_method) { $facts->obligations[] = weakref_get($decision->method); }
		}
		if (!$facts->abstract_type && (q_count($facts->obligations) !== 0)) {
			throw new \RuntimeException('Concrete type has unimplemented abstract methods');
		}
		return $facts;
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
