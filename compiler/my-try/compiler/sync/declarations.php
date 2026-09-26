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
		if ($node->kind === node_kind::function_declaration)
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
		return self::spelling($entry->file->source, $start, $end);
	}

	/** Names identify candidate groups; enclosing syntax distinguishes members and function locals. */
	private static function declaration_key(collected_name $entry): string
	{
		$key = Node_Kind_Name::text($entry->node->kind) . ':' . $entry->name;
		$parent = $entry->node->parent();
		$tokens /** Storage<token> */ = $entry->file->source->tokens;
		while ($parent !== null)
		{
			$node = object_cast($parent, ast_node::class);
			if ($node->kind === node_kind::function_declaration) {
				$index = Syntax_Nodes::function_data($node)->name_token_index;
				$key = 'function:' . $tokens[$index]->text() . '/' . $key;
			}
			elseif ($node->kind === node_kind::struct_declaration) {
				$index = Syntax_Nodes::struct_data($node)->name_token_index;
				$key = 'struct:' . $tokens[$index]->text() . '/' . $key;
			}
			$parent = $node->parent();
		}
		return $key;
	}

	/** Match equal duplicates first, then unambiguous remaining keys; retain unmatched old rows deleted. */
	public static function compare(parsed_file $previous, parsed_file $candidate): void
	{
		$old_entries /** Storage<collected_name> */ = $previous->collection->entries;
		$new_entries /** Storage<collected_name> */ = $candidate->collection->entries;
		$matched /** hash<bool, int> */ = [];
		$paired /** hash<bool, int> */ = [];
		for ($pass = 0; $pass < 2; $pass++)
		{
			foreach ($candidate->collection->defined_elements as $new_index)
			{
				if (isset($paired[$new_index])) {
					continue;
				}
				$entry = $new_entries[$new_index];
				$key = self::declaration_key($entry);
				$found = -1;
				$count = 0;
				foreach ($previous->collection->defined_elements as $old_index)
				{
					$old = $old_entries[$old_index];
					if (isset($matched[$old_index]) || ($old->changes === \scpp\compiler\SYNC_DELETED)) {
						continue;
					}
					if (self::declaration_key($old) !== $key) {
						continue;
					}
					if ($pass === 0) {
						if ((self::declaration_text($old, false) !== self::declaration_text($entry, false)) || (self::declaration_text($old, true) !== self::declaration_text($entry, true))) {
							continue;
						}
					}
					$found = $old_index;
					$count++;
					if ($pass === 0) {
						break;
					}
				}
				if ($count !== 1) {
					continue;
				}
				if ($pass === 1)
				{
					$remaining = 0;
					foreach ($candidate->collection->defined_elements as $other_index) {
						if (!isset($paired[$other_index])) {
							if (self::declaration_key($new_entries[$other_index]) === $key) {
								$remaining++;
							}
						}
					}
					if ($remaining !== 1) {
						continue;
					}
				}
				$old = $old_entries[$found];
				$entry->changes = 0;
				if (self::declaration_text($old, false) !== self::declaration_text($entry, false)) {
					$entry->changes = $entry->changes + \scpp\compiler\SYNC_CHANGED;
				}
				if (self::declaration_text($old, true) !== self::declaration_text($entry, true)) {
					$entry->changes = $entry->changes + \scpp\compiler\SYNC_BODY_CHANGED;
				}
				$paired[$new_index] = true;
				$matched[$found] = true;
			}
		}
		foreach ($previous->collection->defined_elements as $old_index)
		{
			if (isset($matched[$old_index])) {
				continue;
			}
			$old = $old_entries[$old_index];
			$old->changes = \scpp\compiler\SYNC_DELETED;
			$position = $new_entries->append($old);
			$candidate->collection->defined_elements[] = $position;
		}
	}
}
