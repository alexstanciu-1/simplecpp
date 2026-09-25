<?php

/*
 * Role: collect occurrences and publish declaration pools.
 * Call map: Parser -> Symbol_Collector::record -> Symbol_Collector::finish.
 */
namespace scpp\compiler;

final class Symbol_Collector
{
	private collected_file $file;
	private bool $finished = false;

	public function __construct(token_list $source)
	{
		$this->file = new collected_file();
		$this->file->source = $source;
	}

	/** Append one occurrence during parsing; never look up, merge or reject a name. */
	public function record(ast_node $node, int $token_index, collected_name_kind $kind, scope $scope): int
	{
		if ($this->finished) {
			throw new \LogicException('Collection is already finished');
		}
		$entry = new collected_name();
		$entry->file = $this->file;
		$tokens /** Storage<token> */ = $this->file->source->tokens;
		$text = $tokens[$token_index]->text();
		$entry->name = string_byte_starts_with($text, '$') ? string_byte_slice($text, 1, string_byte_len($text) - 1) : $text;
		$entry->kind = $kind;
		$entry->scope = $scope;
		$entry->node = $node;
		$entry->token_index = $token_index;
		$entries /** Storage<collected_name> */ = $this->file->entries;
		$entry->local_index = $entries->append($entry);
		if (($kind === collected_name_kind::struct_declaration) || ($kind === collected_name_kind::variable_declaration) || ($kind === collected_name_kind::function_declaration)) {
			$this->file->defined_elements[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::field_reference) {
			$this->file->field_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::variable_reference) {
			$this->file->variable_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::type_reference) {
			$this->file->type_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::function_reference) {
			$this->file->function_references[] = $entry->local_index;
		}
		elseif ($kind === collected_name_kind::binding) {
			$this->file->pending_bindings[] = $entry->local_index;
		}

		return $entry->local_index;
	}

	/** Publish declaration references only after the whole file has parsed successfully. */
	public function finish(ast_node $root): collected_file
	{
		if ($this->finished) {
			throw new \LogicException('Collection is already finished');
		}
		$this->file->root = $root;
		$entries /** Storage<collected_name> */ = $this->file->entries;
		foreach ($this->file->defined_elements as $index)
		{
			$entry = $entries[$index];
			if ($entry->kind === collected_name_kind::function_declaration) {
				$entry->scope->functions[$entry->name][] = $entry;
			}
			elseif ($entry->kind === collected_name_kind::struct_declaration) {
				$entry->scope->types[$entry->name][] = $entry;
			}
			else {
				$entry->scope->variables[$entry->name][] = $entry;
			}
		}
		$this->finished = true;
		return $this->file;
	}
}
