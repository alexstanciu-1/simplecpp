<?php
namespace scpp\compiler;

/** Shared private-source support for composition and callable instantiation. */
final class Source_Fragments
{
	/** Mutable token rows must not alias the authored source's cleanup/rebasing buffer. */
	public static function snapshot(token_list $source): token_list
	{
		$result = new token_list();
		$result->file = $source->file;
		$result->content = $source->content;
		$result->first_token = $source->first_token;
		$result->end_token = $source->end_token;
		$result->content_offset = $source->content_offset;
		$rows /** Storage<token> */ = $source->tokens;
		$copies /** Storage<token> */ = $result->tokens;
		foreach ($rows as $row) {
			$copies->append(new token((int)$row->offset, (int)$row->length, $row->text()));
		}
		return $result;
	}

	/** Coordinator ordering: notify/unlink analysis, clear facts, then dispose collection membership. */
	public static function retire(Storage $parses /** Storage<parsed_file> */, scope $language): void
	{
		if ($parses->is_empty()) {
			return;
		}
		$sources /** Storage<collected_file> */ = new Storage();
		foreach ($parses as $parsed) {
			$parsed->collection->deleted = true;
			$sources->append($parsed->collection);
		}
		(new Preparation_Worker($language))->retire_deleted_sources($sources);
		foreach ($parses as $parsed) {
			Preparation_Cleanup::tree($parsed->root);
		}
		Collection_Retirement::remove_deleted_sources($sources);
		foreach ($sources as $source) {
			$source->preparation_changes = new \SplObjectStorage /** hash<bool, preparation_owner> */();
		}
	}
}
