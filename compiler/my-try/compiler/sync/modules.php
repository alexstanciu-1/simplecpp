<?php

/* Role: reconcile module configuration; any change retires the complete compilation graph. */
namespace scpp\compiler;

final class Module_Synchronization
{
	/** Validate before mutation, retain identities by key, then rediscover on any change. */
	public static function run(Storage $inputs /** Storage<module_input> */): bool
	{
		$candidates = self::validate($inputs);
		if (!Compiler_Lifecycle::initialized()) {
			Compiler_Lifecycle::reset();
		}
		$modules = Model::$modules;
		$sync = new Key_Synchronization($modules->next_revision());
		$ordered /** Storage<module> */ = new Storage();
		$changed = !Model::$modules_ready;

		// Incoming order drives matching, including order-only changes and reappearances.
		foreach ($candidates as $candidate)
		{
			$previous = $modules->find($candidate->name);
			$record = $candidate;
			if ($previous === null) {
				$record->changes = \scpp\compiler\SYNC_ADDED;
				$modules->add($record);
			}
			else {
				$retained /** module */ = $previous;
				$record = $retained;
				self::update($record, $candidate);
			}
			$sync->present($record->presence, $record->name);
			if ($record->changes !== 0) {
				$changed = true;
			}
			$ordered->append($record);
		}

		// Deleted identities stay indexed but do not participate in discovery or generation.
		foreach ($modules->inventory() as $record)
		{
			if ($sync->missing($record->presence)) {
				if ($record->changes !== \scpp\compiler\SYNC_DELETED) {
					$record->changes = \scpp\compiler\SYNC_DELETED;
					$changed = true;
				}
			}
		}
		$modules->set_order($ordered);
		if ($changed) {
			self::rebuild();
		}
		return $changed;
	}

	/** Resolve complete input privately; invalid configurations preserve the published session. */
	private static function validate(Storage $inputs /** Storage<module_input> */): Storage /** Storage<module> */
	{
		$result /** Storage<module> */ = new Storage();
		$keys /** hash<bool> */ = [];
		$position = 0;
		foreach ($inputs as $input)
		{
			if (isset($keys[$input->name])) {
				throw new \LogicException('Duplicate module key: ' . $input->name);
			}
			$keys[$input->name] = true;
			$resolved = fs_require_realpath($input->declared_path);
			if (!fs_is_dir($resolved)) {
				throw new \LogicException('Module root must be a directory: ' . $input->declared_path);
			}
			$candidate = new module($input->declared_path, $resolved, $input->name);
			$candidate->position = $position;
			$position++;
			foreach ($result as $existing) {
				if (($existing->resolved_path === $resolved) || Module_Loader::contains_path($existing, $resolved) || Module_Loader::contains_path($candidate, $existing->resolved_path)) {
					throw new \LogicException('Overlapping module roots are not supported');
				}
			}
			$result->append($candidate);
		}
		return $result;
	}

	/** Configuration and ordinal position determine whether a retained identity changed. */
	private static function update(module $record, module $candidate): void
	{
		$changes = 0;
		if ($record->changes === \scpp\compiler\SYNC_DELETED) {
			$changes = \scpp\compiler\SYNC_ADDED;
		}
		elseif (($record->declared_path !== $candidate->declared_path) || ($record->resolved_path !== $candidate->resolved_path) || ($record->position !== $candidate->position)) {
			$changes = \scpp\compiler\SYNC_CHANGED;
		}
		$record->declared_path = $candidate->declared_path;
		$record->resolved_path = $candidate->resolved_path;
		$record->position = $candidate->position;
		$record->changes = $changes;
	}

	/** Failed discovery leaves no partial source graph and identical input can retry it. */
	private static function rebuild(): void
	{
		Model::$modules_ready = false;
		Model::$full_sync_pending = true;
		Compiler_Lifecycle::reset_compilation();
		try {
			foreach (Model::modules() as $module) {
				Module_Loader::discover($module);
			}
			Model::$modules_ready = true;
		}
		catch (\Throwable $error) {
			Compiler_Lifecycle::reset_compilation();
			throw $error;
		}
	}
}
