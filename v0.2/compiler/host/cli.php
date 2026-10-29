<?php

declare(strict_types=1);
namespace scpp\compiler;

final class Compiler_Cli
{
	/** Inspect complete token generations; failures produce stderr and no partial stdout. */
	public static function run(array $arguments): int
	{
		if (($arguments === [$arguments[0]]) || ($arguments === [$arguments[0], '--help'])) {
			fwrite(STDOUT, "Usage: php main.php tokens <cpp|php|js> <file>\nOutputs JSON token spans; parsing and compilation are not implemented yet.\n");
			return 0;
		}
		if ((count($arguments) !== 4) || ($arguments[1] !== 'tokens')) {
			fwrite(STDERR, "Usage: php main.php tokens <cpp|php|js> <file>\n");
			return 2;
		}
		$flavor = flavor::tryFrom($arguments[2]);
		if ($flavor === null) {
			fwrite(STDERR, "Flavor must be cpp, php or js\n");
			return 2;
		}
		try {
			$list = (new Source_Frontend())->scan_file($arguments[3], $flavor);
			$rows = [];
			foreach ($list->tokens as $index => $token) {
				// Hex is lossless even when quoted source contains non-UTF-8 bytes.
				$rows[] = ['kind' => $token->kind->label(), 'offset' => $token->offset, 'length' => $token->length,
					'text_hex' => bin2hex($list->text_at($index))];
			}
			fwrite(STDOUT, json_encode(['flavor' => $flavor->value, 'tokens' => $rows], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
			return 0;
		}
		catch (\RuntimeException $error) {
			fwrite(STDERR, $error->getMessage() . "\n");
			return 1;
		}
	}
}
