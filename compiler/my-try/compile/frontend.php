<?php

/* Role: perform one file's frontend operation without publishing global roots. */
namespace scpp\compiler;

final class Source_Frontend
{
	/** Finish each file's scan/parse chain privately; retain queue failure reporting. */
	public static function run(source_work $work, Source_Work_Queue $queue, frontend_operation $operation): source_work
	{
		$queue->start($work);
		try
		{
			if ($operation === frontend_operation::synchronize) {
				if ($work->source->changes === \scpp\compiler\SYNC_DELETED) {
					return $work;
				}
			}
			if ($operation !== frontend_operation::parse) {
				$work->tokens = (new Tokenizer($work->source))->tokenize();
			}
			if ($operation !== frontend_operation::scan) {
				$tokens = object_cast($work->tokens, token_list::class);
				$work->result = (new Parser($tokens))->parse();
			}
		}
		catch (\Throwable $error) {
			$queue->fail($work);
			throw $error;
		}
		return $work;
	}
}
