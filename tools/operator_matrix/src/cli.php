<?php

declare(strict_types=1);

/**
 * Parse CLI arguments.
 *
 * @param list<string> $args
 * @return array<string, mixed>
 */
function om_parse_arguments(array $args): array
{
	$parsed = [
		'family' => null,
		'validate' => false,
		'stdout' => false,
	];

	for ($i = 0; $i < count($args); $i++) {
		$arg = $args[$i];
		if ($arg === '--validate') {
			$parsed['validate'] = true;
			continue;
		}
		if ($arg === '--stdout') {
			$parsed['stdout'] = true;
			continue;
		}
		if (str_starts_with($arg, '--family=')) {
			$parsed['family'] = substr($arg, strlen('--family='));
			continue;
		}
		if ($arg === '--family' && isset($args[$i + 1])) {
			$parsed['family'] = $args[++$i];
			continue;
		}
		if (in_array($arg, ['-h', '--help'], true)) {
			om_print_help();
			exit(0);
		}

		fwrite(STDERR, 'Unknown argument: ' . $arg . PHP_EOL);
		exit(1);
	}

	return $parsed;
}

function om_print_help(): void
{
	echo "Operator matrix generator\n";
	echo "\n";
	echo "Usage:\n";
	echo "  php tools/operator_matrix/generator.php [--family=<family_id>] [--validate] [--stdout]\n";
	echo "\n";
	echo "Options:\n";
	echo "  --family     Limit generation to one family\n";
	echo "  --validate   Generate and validate rows\n";
	echo "  --stdout     Also print generated rows to stdout\n";
}
