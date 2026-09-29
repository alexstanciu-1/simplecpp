<?php

/* Append complete tokenizations only; read/lexical failures never publish partial storage. */
namespace scpp\compiler;

final class Token_Buffer
{
	/** Keep old indexes and source bytes valid while parsing only the appended input. */
	public static function append(token_list $incoming, token_list $previous): void
	{
		if (($incoming === $previous) || ($incoming->first_token !== 0) || ($incoming->content_offset !== 0)) {
			return;
		}
		$offset = string_byte_len($previous->content);
		if (($offset === 0) && ($previous->end_token === 0)) {
			return;
		}
		$first = q_count($previous->tokens);
		$rows /** Storage<token> */ = $incoming->tokens;
		if (($offset + string_byte_len($incoming->content) > 4294967295) || ($first + q_count($rows) > 4294967295)) {
			throw new \RuntimeException('Retained token buffer exceeds uint32 capacity');
		}
		$storage /** Storage<token> */ = $previous->tokens;
		foreach ($rows as $row) {
			$row->offset += $offset;
			$storage->append($row);
		}
		$incoming->content = $previous->content . $incoming->content;
		$incoming->content_offset = $offset;
		$incoming->first_token = $first;
		$incoming->end_token = q_count($storage);
		$incoming->tokens = $storage;
	}
}
