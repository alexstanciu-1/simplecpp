<?php
namespace scpp\compiler;

/** Cross-stage coordinator: analysis selects, parser materializes, lifecycle retires. */
final class Trait_Composition
{
	private static function records(Storage $sources /** Storage<collected_file> */): Storage /** Storage<collected_record> */
	{
		$result /** Storage<collected_record> */ = new Storage();
		foreach ($sources as $source) {
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($entries as $entry) {
				if ($entry instanceof collected_record) { $result->append(object_cast($entry, collected_record::class)); }
			}
		}
		return $result;
	}

	public static function current(trait_instance $instance): bool
	{
		$consumer = $instance->consumer();
		$origin = $instance->origin;
		return !$consumer->collection->deleted && !$origin->collection->deleted
			&& ($consumer->change_status !== change_state::deleted) && ($origin->change_status !== change_state::deleted)
			&& ($instance->parsed->root_scope()->parent_scope() === weakref_get($origin->scope))
			&& ($instance->origin_input === $origin->collection->source_file()->content)
			&& ($instance->consumer_input === $consumer->collection->source_file()->content);
	}

	/** Run before disposal of source scopes, including removed declarations. */
	public static function retire_stale(Storage $sources /** Storage<collected_file> */, scope $language): void
	{
		foreach (self::records($sources) as $record) {
			$instances /** Storage<trait_instance> */ = $record->trait_instances;
			foreach ($instances as $instance) {
				if (!self::current($instance)) { self::retire($record, $language); break; }
			}
		}
	}

	public static function retire(collected_record $record, scope $language): void
	{
		$instances /** Storage<trait_instance> */ = $record->trait_instances;
		if ($instances->is_empty()) { return; }
		$sources /** Storage<collected_file> */ = new Storage();
		foreach ($instances as $instance) {
			$instance->collection->deleted = true;
			$sources->append($instance->collection);
		}
		$worker = new Preparation_Worker($language);
		$worker->retire_deleted_sources($sources);
		$worker->invalidate_members($record);
		foreach ($instances as $instance) { Preparation_Cleanup::tree($instance->parsed->root); }
		Collection_Retirement::remove_deleted_sources($sources);
		// Backend fragments retain their deleted owner/source until ordinary eviction;
		// this private, unpublished source must not retain a source/work self-cycle.
		foreach ($sources as $source) {
			$source->preparation_changes = new \SplObjectStorage /** hash<bool, shared<preparation_owner>> */();
		}
		$record->trait_instances = new Storage /** Storage<trait_instance> */();
	}

	/** Re-resolve targets every preparation; namespace/import membership is never cached. */
	public static function synchronize(Storage $sources /** Storage<collected_file> */, scope $language): void
	{
		foreach (self::records($sources) as $record) {
			if ($record->collection->deleted || ($record->change_status === change_state::deleted)) { continue; }
			$targets = Trait_Preparation::targets($record);
			$previous /** Storage<trait_instance> */ = $record->trait_instances;
			$same = q_count($targets) === q_count($previous);
			if ($same) {
				foreach ($targets as $index => $target) {
					if (($previous[$index]->origin !== $target) || !self::current($previous[$index])) { $same = false; break; }
				}
			}
			if ($same) { continue; }
			self::retire($record, $language);
			$next /** Storage<trait_instance> */ = new Storage();
			foreach ($targets as $target) { $next->append(Trait_Materialization::build($target, $record)); }
			$record->trait_instances = $next;
			(new Preparation_Worker($language))->invalidate_members($record);
		}
	}

	/** Reset includes tombstones, unlike live graph queries. */
	public static function private_parses(Storage $sources /** Storage<collected_file> */): Storage /** Storage<parsed_file> */
	{
		$result /** Storage<parsed_file> */ = new Storage();
		foreach (self::records($sources) as $record) {
			$instances /** Storage<trait_instance> */ = $record->trait_instances;
			foreach ($instances as $instance) { $result->append($instance->parsed); }
		}
		return $result;
	}
}
