<?php

/* Original declarations, not a flattened effective-member cache. Access is a later decision. */
namespace scpp\compiler;

final class Inherited_Lookup
{
	/** Each consulted category/name pool is observed even when no member exists. */
	private static function local(collected_member_owner $entry, string $name, preparation_lookup_kind $kind,
		preparation_context $context): array /** vector<inherited_member_candidate> */
	{
		$members = $entry->syntax()->member_scope();
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
		if ($base !== null) { return self::members($base, $name, $kind, $context); }
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
