<?php

/* Role: perform one file's frontend operation without publishing global roots. */
namespace scpp\compiler;

final class Source_Frontend
{
	/** Dispatch one selected phase; parsing retains identities and scanning publishes new tokens. */
	public static function run(source_work $work, Source_Work_Queue $queue, frontend_operation $operation): source_work
	{
		$queue->start($work);
		if ($operation === frontend_operation::parse) {
			return self::parse($work, $queue);
		}
		try {
			$work->tokens = (new Tokenizer($work->source))->tokenize(Source_Registry::full_path($work->record->owning_module(), $work->record->path));
		}
		catch (\Throwable $error) {
			$queue->fail($work);
			throw $error;
		}
		return $work;
	}

	/** Catch file errors as data so other files can complete before the coordinator reports failure. */
	private static function parse(source_work $work, Source_Work_Queue $queue): source_work
	{
		$tokens = object_cast($work->tokens, token_list::class);
		$parser = new Parser($tokens, null, $work->previous, Model::$global_scope);
		try {
			$work->result = $parser->parse();
		}
		catch (\Throwable $error) {
			$work->result = $parser->result();
			$work->error = $work->record->path . ': ' . $error->getMessage();
			$queue->fail($work);
		}
		return $work;
	}
}
