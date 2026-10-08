<?php
namespace scpp\compiler;

/** Read-only callable graph view; private implementations are not source/member declarations. */
final class Callable_Inventory
{
	/** Static source bodies are checked only through concrete called-type implementations. */
	public static function deferred_body(collected_source_callable $definition): bool
	{
		if (!($definition instanceof collected_method)) { return false; }
		$method = object_cast($definition, collected_method::class);
		if (weakref_get($method->instance_context) !== null) { return false; }
		foreach ($method->callable_syntax()->modifiers as $modifier) {
			if ($modifier === declaration_modifier::static_member) { return true; }
		}
		return false;
	}
	/** Read an interned implementation without parsing, scheduling or changing membership. */
	public static function find(collected_method $origin, collected_record $called): ?callable_instance
	{
		$instances /** Storage<callable_instance> */ = $origin->called_instances;
		foreach ($instances as $instance) {
			if ($instance->called_type() === $called) {
				return $instance;
			}
		}
		return null;
	}
	/** Include live private implementations once, preserving original collection identities. */
	public static function source_collections(Storage $sources /** Storage<collected_file> */): Storage /** Storage<collected_file> */
	{
		$result = Member_Inventory::source_collections($sources);
		$seen /** hash<bool, shared<collected_file>> */ = new \SplObjectStorage /** hash<bool, shared<collected_file>> */();
		foreach ($result as $source) {
			$seen[$source] = true;
		}
		// Snapshot the origin inventory: working methods never own another instance family.
		$origins /** Storage<collected_file> */ = new Storage();
		foreach ($result as $source) {
			$origins->append($source);
		}
		foreach ($origins as $source)
		{
			if ($source->deleted) {
				continue;
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($source->defined_elements as $position)
			{
				$entry = $entries[$position];
				if ($entry->change_status === change_state::deleted) {
					continue;
				}
				if ($entry instanceof collected_method)
				{
					$instances /** Storage<callable_instance> */ = object_cast($entry, collected_method::class)->called_instances;
					foreach ($instances as $instance)
					{
						$private_source = $instance->parsed->collection;
						if (!$private_source->deleted && !isset($seen[$private_source])) {
							$seen[$private_source] = true;
							$result->append($private_source);
						}
					}
				}
			}
		}
		return $result;
	}
}
