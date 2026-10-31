<?php

declare(strict_types=1);
namespace scpp\compiler;

/**
 * Dispatches compiler commands and presents results or diagnostics at the host command-line boundary.
 */
final class Compiler_Cli
{
	/** Inspect complete token or syntax generations; failures produce stderr and no partial stdout. */
	public static function run(array $arguments /** list<string> */): int /** int32; process exit status 0..2 */
	{
		if ((count($arguments) <= 1) || (($arguments[1] ?? null) === '--help')) {
			fwrite(STDOUT, self::usage());
			return 0;
		}
		$command = $arguments[1];
		if ($command === 'program')
		{
			try {
				return Program_Command::run($arguments);
			}
			catch (\RuntimeException $error) {
				fwrite(STDERR, $error->getMessage() . "\n");
				return 1;
			}
		}
		$required = match ($command) {
			'tokens', 'parse', 'collect' => 4,
			'prepare' => 5,
			'cpp' => 6,
			default => 0,
		};
		if (count($arguments) !== $required) {
			fwrite(STDERR, self::usage());
			return 2;
		}
		$flavor = flavor::tryFrom($arguments[2]);
		if ($flavor === null) {
			fwrite(STDERR, "Flavor must be cpp, php or js\n");
			return 2;
		}
		try {
			if (in_array($command, ['collect', 'prepare', 'cpp'], true)) {
				return self::compile($arguments, $flavor);
			}
			if ($arguments[1] === 'parse') {
				$file = (new Source_Frontend())->parse_file($arguments[3], $flavor);
				fwrite(STDOUT, json_encode(Syntax_Inspection::file($file), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
				return 0;
			}
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
	private static function usage(): string
	{
		return "Usage: php main.php <tokens|parse|collect> <cpp|php|js> <file>\n"
			. "       php main.php prepare <flavor> <file> <native-facts.json>\n"
			. "       php main.php cpp <flavor> <file> <native-facts.json> <output.cpp>\n"
			. "       php main.php program <root> <entry-relative> <facts.json> <output-dir> <flavor> <source-relative> [...]\n";
	}

	/** Keep stage inspection separate from the final, complete C++ file publication. */
	private static function compile(array $arguments /** list<string> */, flavor $flavor): int /** int32; exit status */
	{
		$source = File_Loader::load($arguments[3], $flavor);
		$collection = (new Source_Frontend())->collect($source);
		if ($arguments[1] === 'collect') {
			$data = Compilation_Inspection::collection($collection);
		}
		else
		{
			$target = Native_Target::load($arguments[4]);
			$program = (new Preparation())->prepare($collection, $target);
			if ($arguments[1] === 'prepare') {
				$data = Compilation_Inspection::program($program);
			}
			else
			{
				$output = $arguments[5];
				if ((realpath($output) === realpath($arguments[3]))
					|| (realpath($output) === realpath($arguments[4]))) {
					throw new \RuntimeException('Output must not replace an input file');
				}
				$text = (new Cpp_Emitter())->emit($program);
				Cpp_Publication::write($output, $text);
				$data = ['output' => $output];
			}
		}
		fwrite(STDOUT, json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
		return 0;
	}

}
