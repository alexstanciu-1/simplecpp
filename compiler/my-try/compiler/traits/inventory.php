<?php
namespace scpp\compiler;

/** Transient views of local members, not a second retained owner or inherited-member cache. */
final class Member_Inventory
{
	/** Include private occurrences for graph consumers without publishing synthetic files. */
	public static function source_collections(Storage $sources /** Storage<collected_file> */): Storage /** Storage<collected_file> */
	{
		$result /** Storage<collected_file> */ = new Storage();
		$seen /** hash<bool, shared<collected_file>> */ = new \SplObjectStorage /** hash<bool, shared<collected_file>> */();
		foreach ($sources as $source) {
			if (!isset($seen[$source])) { $seen[$source] = true; $result->append($source); }
			if ($source->deleted) { continue; }
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($source->defined_elements as $index) {
				$entry = $entries[$index];
				if ($entry->change_status === change_state::deleted) { continue; }
				if ($entry instanceof collected_record) {
					$instances /** Storage<trait_instance> */ = object_cast($entry, collected_record::class)->trait_instances;
					foreach ($instances as $instance) {
						$collection = $instance->collection;
						if (!isset($seen[$collection])) { $seen[$collection] = true; $result->append($collection); }
					}
				}
			}
		}
		return $result;
	}

	public static function methods(collected_member_owner $owner): Storage /** Storage<method_node> */
	{
		$result /** Storage<method_node> */ = new Storage();
		$authored /** Storage<method_node> */ = $owner->syntax()->methods;
		foreach ($authored as $method) { $result->append($method); }
		foreach (self::contributions($owner) as $member) {
			if ($member->working instanceof collected_method) {
				$result->append(object_cast($member->working->syntax(), method_node::class));
			}
		}
		return $result;
	}

	public static function properties(collected_member_owner $owner): Storage /** Storage<property_node> */
	{
		$result /** Storage<property_node> */ = new Storage();
		$authored /** Storage<property_node> */ = $owner->syntax()->properties;
		foreach ($authored as $property) { $result->append($property); }
		foreach (self::contributions($owner) as $member) {
			if ($member->working instanceof collected_field) {
				$result->append(object_cast($member->working->syntax(), property_node::class));
			}
		}
		return $result;
	}

	public static function constants(collected_member_owner $owner): Storage /** Storage<member_constant_node> */
	{
		$result /** Storage<member_constant_node> */ = new Storage();
		$authored /** Storage<member_constant_node> */ = $owner->syntax()->constants;
		foreach ($authored as $constant) { $result->append($constant); }
		foreach (self::contributions($owner) as $member) {
			if ($member->working instanceof collected_constant) {
				$result->append(object_cast($member->working->syntax(), member_constant_node::class));
			}
		}
		return $result;
	}

	public static function contributions(collected_member_owner $owner): Storage /** Storage<trait_member_instance> */
	{
		$result /** Storage<trait_member_instance> */ = new Storage();
		if (!($owner instanceof collected_record)) { return $result; }
		$instances /** Storage<trait_instance> */ = object_cast($owner, collected_record::class)->trait_instances;
		foreach ($instances as $instance) {
			$members /** Storage<trait_member_instance> */ = $instance->members;
			foreach ($members as $member) { $result->append($member); }
		}
		return $result;
	}

	public static function scopes(collected_member_owner $owner): Storage /** Storage<scope> */
	{
		$result /** Storage<scope> */ = new Storage();
		$result->append($owner->syntax()->member_scope());
		if ($owner instanceof collected_record) {
			$instances /** Storage<trait_instance> */ = object_cast($owner, collected_record::class)->trait_instances;
			foreach ($instances as $instance) { $result->append($instance->parsed->root_scope()); }
		}
		return $result;
	}
}
