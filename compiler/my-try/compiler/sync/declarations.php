<?php

/* Role: compare successive declaration inventories without resolving names. */
namespace scpp\compiler;

final class Declaration_Changes
{
	/** Compare significant token spellings within AST-selected declaration/body boundaries. */
	private static function spelling(token_list $tokens, int $start, int $end): string
	{
		$rows /** Storage<token> */ = $tokens->tokens;
		$result = '';
		for ($index = $start; $index < $end; $index++) {
			$text = $rows[$index]->text();
			$result .= string_byte_len($text) . ':' . $text;
		}
		return $result;
	}

	/** Function headers and bodies are independent; other declarations compare their full syntax. */
	private static function declaration_text(collected_name $entry, bool $body): string
	{
		$node = $entry->node;
		$start = (int) $node->token_index;
		$end = (int) $node->end_token_index;
		if ($node->kind() === node_kind::function_declaration)
		{
			$function = Syntax_Nodes::function_data($node);
			if ($body) {
				$start = (int) $function->body->token_index;
			}
			else {
				$end = (int) $function->body->token_index;
			}
		}
		elseif ($body) {
			return '';
		}
		return self::spelling($entry->token_snapshot(), $start, $end);
	}

	/** Names identify candidate groups; enclosing syntax distinguishes members and function locals. */
	private static function declaration_key(collected_name $entry): string
	{
		$key = Node_Kind_Name::text($entry->node->kind()) . ':' . $entry->name;
		$parent = $entry->node->parent();
		while ($parent !== null)
		{
			$node /** ast_node */ = $parent;
			if ($node->kind() === node_kind::function_declaration) {
				$key = 'function:' . $node->payload()->occurrence()->name . '/' . $key;
			}
			elseif ($node->kind() === node_kind::struct_declaration) {
				$key = 'struct:' . $node->payload()->occurrence()->name . '/' . $key;
			}
			$parent = $node->parent();
		}
		return $key;
	}

	/** Compute each candidate's structural key and spellings once for this comparison. */
	private static function candidates(collected_file $collection): Key_Storage_List /** Key_Storage_List<declaration_comparison> */
	{
		$result /** Key_Storage_List<declaration_comparison> */ = new Key_Storage_List();
		$entries /** Storage<collected_name> */ = $collection->entries;
		foreach ($collection->defined_elements as $index)
		{
			$entry = $entries[$index];
			$record = new declaration_comparison();
			$record->entry = $entry;
			$record->key = self::declaration_key($entry);
			$record->declaration = self::declaration_text($entry, false);
			$record->body = self::declaration_text($entry, true);
			$result->add($record->key, $record);
		}
		return $result;
	}

	/** Match equal duplicates first, then unambiguous remaining keys; retain unmatched old rows deleted. */
	public static function compare(parsed_file $previous, parsed_file $candidate): void
	{
		$old_records /** Key_Storage_List<declaration_comparison> */ = self::candidates($previous->collection);
		$new_records /** Key_Storage_List<declaration_comparison> */ = self::candidates($candidate->collection);
		for ($pass = 0; $pass < 2; $pass++)
		{
			foreach ($new_records->items() as $record)
			{
				if ($record->paired) {
					continue;
				}
				$matches /** vector<declaration_comparison> */ = [];
				foreach ($old_records->named($record->key) as $old)
				{
					if (($old->paired) || ($old->entry->changes === \scpp\compiler\SYNC_DELETED)) {
						continue;
					}
					if ($pass === 0) {
						if (($old->declaration !== $record->declaration) || ($old->body !== $record->body)) {
							continue;
						}
					}
					$matches[] = $old;
					if ($pass === 0) {
						break;
					}
				}
				if (q_count($matches) !== 1) {
					continue;
				}
				if ($pass === 1)
				{
					$remaining = 0;
					foreach ($new_records->named($record->key) as $other) {
						if (!$other->paired) {
							$remaining++;
						}
					}
					if ($remaining !== 1) {
						continue;
					}
				}
				$old = $matches[0];
				$record->entry->changes = 0;
				if ($old->declaration !== $record->declaration) {
					$record->entry->changes = $record->entry->changes + \scpp\compiler\SYNC_CHANGED;
				}
				if ($old->body !== $record->body) {
					$record->entry->changes = $record->entry->changes + \scpp\compiler\SYNC_BODY_CHANGED;
				}
				$record->paired = true;
				$old->paired = true;
			}
		}
		$new_entries /** Storage<collected_name> */ = $candidate->collection->entries;
		foreach ($old_records->items() as $old)
		{
			if ($old->paired) {
				continue;
			}
			$old->entry->changes = \scpp\compiler\SYNC_DELETED;
			$position = $new_entries->append($old->entry);
			$candidate->collection->defined_elements[] = $position;
		}
	}
}
