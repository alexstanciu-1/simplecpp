<?php

/* Original declarations, not a flattened effective-member cache. Access is a later decision. */
namespace scpp\compiler;

final class Inherited_Lookup
{
	/** Each consulted category/name pool is observed even when no member exists. */
	private static function local(collected_member_owner $entry, string $name, preparation_lookup_kind $kind,
		preparation_context $context): array /** vector<inherited_member_candidate> */
	{
		$result /** vector<inherited_member_candidate> */ = [];
		foreach (Member_Inventory::scopes($entry) as $members) {
			foreach (self::in_scope($entry, $members, $name, $kind, $context) as $candidate) { $result[] = $candidate; }
		}
		return $result;
	}

	private static function in_scope(collected_member_owner $entry, scope $members, string $name,
		preparation_lookup_kind $kind, preparation_context $context): array /** vector<inherited_member_candidate> */
	{
		$result /** vector<inherited_member_candidate> */ = [];
		$context->worker->observe($members, $name, $kind, $context->owner);
		if ($kind === preparation_lookup_kind::property_name) {
			foreach ($members->variables_named($name) as $property) {
				if ($property instanceof collected_field) {
					$result[] = new inherited_member_candidate($entry, object_cast($property, collected_field::class));
				}
			}
		}
		elseif ($kind === preparation_lookup_kind::method_name) {
			foreach ($members->methods_named($name) as $method) {
				if (Member_Inventory::is_requirement($method)) { continue; }
				$result[] = new inherited_member_candidate($entry, $method);
			}
		}
		elseif ($kind === preparation_lookup_kind::constant) {
			foreach ($members->constants_named($name) as $constant) {
				if ($constant instanceof collected_constant) {
					$result[] = new inherited_member_candidate($entry, object_cast($constant, collected_constant::class));
				}
			}
		}
		else { throw new \LogicException('Inherited lookup requires a member category'); }
		return $result;
	}

	/** Local declarations hide base candidates; interface diamonds deduplicate only identical declarations. */
	public static function members(collected_member_owner $entry, string $name, preparation_lookup_kind $kind,
		preparation_context $context): array /** vector<inherited_member_candidate> */
	{
		$relationships = $context->worker->require_relationships($entry, $context);
		$result = self::local($entry, $name, $kind, $context);
		if (q_count($result) !== 0) { return $result; }
		$base = weakref_get($relationships->base);
		if ($base !== null) {
			$result = self::members($base, $name, $kind, $context);
			if (q_count($result) !== 0) { return $result; }
		}
		if ($kind === preparation_lookup_kind::method_name) {
			$selected /** nullable<collected_method> */ = null;
			foreach (Member_Inventory::requirements($entry) as $requirement) {
				if ($requirement->name !== $name) { continue; }
				if ($selected === null) { $selected = $requirement; }
				elseif ((Member_Preparation::visibility($selected->callable_syntax()->modifiers) !== member_visibility::public_access)
					&& (Member_Preparation::visibility($requirement->callable_syntax()->modifiers) === member_visibility::public_access)) {
					$selected = $requirement;
				}
			}
			if ($selected !== null) { return [new inherited_member_candidate($entry, $selected)]; }
		}
		// Implements contributes obligations, never a concrete method implementation.
		if (!($entry instanceof collected_interface)) { return $result; }
		$seen /** hash<bool, shared<collected_declaration>> */ = new \SplObjectStorage /** hash<bool, shared<collected_declaration>> */();
		foreach ($relationships->interfaces as $parent_interface) {
			$inherited = self::members($parent_interface, $name, $kind, $context);
			foreach ($inherited as $candidate) {
				if (!isset($seen[$candidate->member])) {
					$seen[$candidate->member] = true;
					$result[] = $candidate;
				}
			}
		}
		return $result;
	}
}
