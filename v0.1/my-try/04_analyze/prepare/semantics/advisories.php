<?php
namespace scpp\compiler;

/** Successful shared analysis publishes a complete replacement advisory snapshot. */
final class Preparation_Advisories
{
	public static function collect(Storage $sources /** Storage<collected_file> */): Storage /** Storage<compiler_diagnostic> */
	{
		$unique /** Keyed_Storage<compiler_diagnostic> */ = new Keyed_Storage();
		foreach (Callable_Inventory::source_collections($sources) as $source) {
			if ($source->deleted) {
				continue;
			}
			$entries /** Storage<collected_name> */ = $source->entries;
			foreach ($source->defined_elements as $position) {
				$entry = $entries[$position];
				if ($entry->change_status === change_state::deleted) {
					continue;
				}
				if (!($entry instanceof collected_source_callable)) {
					continue;
				}
				$callable = object_cast($entry, collected_source_callable::class);
				if ($callable->callable_syntax()->signature_parts()->returns_reference) {
					self::append($unique, $callable->token_snapshot(), $callable->syntax()->start_token());
				}
			}
			$anonymous /** Storage<anonymous_callable_identity> */ = $source->anonymous_callables;
			foreach ($anonymous as $identity) {
				if ($identity->deleted || ($identity->signature_work === null)) {
					continue;
				}
				if ($identity->signature()->reference_return !== null) {
					self::append($unique, $source->token_snapshot(), $identity->syntax()->start_token());
				}
			}
		}
		$result /** Storage<compiler_diagnostic> */ = new Storage();
		foreach ($unique as $diagnostic) {
			$result->append($diagnostic);
		}
		return $result;
	}

	private static function append(Keyed_Storage $unique /** Keyed_Storage<compiler_diagnostic> */,
		token_list $tokens, int $index): void
	{
		$location = Source_Locations::at($tokens, $index);
		$diagnostic = new compiler_diagnostic('REF001', 'warning', $location->path, $location->line, $location->column,
			'Return-by-reference is supported only within the bounded reference contract; prefer a value return.');
		$key = $diagnostic->path . ':' . $diagnostic->line . ':' . $diagnostic->column . ':' . $diagnostic->code;
		if (isset($unique[$key])) {
			return;
		}
		$unique->add($key, $diagnostic);
	}
}
