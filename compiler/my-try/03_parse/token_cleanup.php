<?php

/* Deferred normalization of live syntax after output; never part of generation. */
namespace scpp\compiler;

final class Token_Cleanup implements node_maintenance_worker_i
{
	private token_list $tokens;

	public function __construct(token_list $tokens)
	{
		$this->tokens = $tokens;
	}

	/** Incomplete/deleted inventories retain provenance until their normal recovery/deletion work. */
	public static function file(parsed_file $parsed): void
	{
		$tokens = $parsed->tokens;
		if ((!$parsed->complete) || (($tokens->first_token === 0) && ($tokens->content_offset === 0))) {
			return;
		}
		$entries /** Storage<collected_name> */ = $parsed->collection->entries;
		foreach ($entries as $entry) {
			if ($entry->change_status === change_state::deleted) {
				return;
			}
		}
		$worker = new Token_Cleanup($tokens);
		$parsed->root->maintain($worker);
		foreach ($entries as $entry) {
			$entry->token_index = $worker->token_index($entry->token_index);
		}
		$old /** Storage<token> */ = $tokens->tokens;
		$current /** Storage<token> */ = new Storage();
		for ($index = $tokens->first_token; $index < $tokens->end_token; $index++) {
			$row = $old[$index];
			$current->append(new token((int)$row->offset - $tokens->content_offset, (int)$row->length, $row->text()));
		}
		$tokens->content = string_byte_slice($tokens->content, $tokens->content_offset, string_byte_len($tokens->content) - $tokens->content_offset);
		$tokens->tokens = $current;
		$tokens->end_token -= $tokens->first_token;
		$tokens->first_token = 0;
		$tokens->content_offset = 0;
		$tokens->retained_ranges = new Storage /** Storage<retained_token_range> */();
	}

	/** Reused syntax maps to its corresponding current interval; new syntax only loses the prefix. */
	private function delta(int $first): int
	{
		$ranges /** Storage<retained_token_range> */ = $this->tokens->retained_ranges;
		foreach ($ranges as $range) {
			if (($first >= $range->first) && ($first < $range->end)) {
				return $range->current_first - $range->first - $this->tokens->first_token;
			}
		}
		return -$this->tokens->first_token;
	}

	public function token_index(int $index): int
	{
		return $index + $this->delta($index);
	}

	public function enter(ast_node $node): void
	{
		$delta = $this->delta($node->start_token());
		$node->set_span($node->start_token() + $delta, $node->end_token() + $delta);
	}

	public function edge(ast_node $parent, ast_node $child): void
	{
		$child->maintain($this);
	}

}
