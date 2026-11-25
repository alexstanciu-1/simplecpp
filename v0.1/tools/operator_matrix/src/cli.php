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
		'emit_negative_generate' => 'all',
		'enable_negative_generate' => 'none',
		'enable_negative_generate_diagnostic' => [],
		'disable_negative_generate_diagnostic' => [],
		'negative_generate_disabled_status' => 'experimental',
		'strict_negative_generate_enable' => false,
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
		if (str_starts_with($arg, '--emit-negative-generate=')) {
			$parsed['emit_negative_generate'] = om_parse_enum_option(substr($arg, strlen('--emit-negative-generate=')), ['none', 'all'], '--emit-negative-generate');
			continue;
		}
		if ($arg === '--emit-negative-generate' && isset($args[$i + 1])) {
			$parsed['emit_negative_generate'] = om_parse_enum_option($args[++$i], ['none', 'all'], '--emit-negative-generate');
			continue;
		}
		if (str_starts_with($arg, '--enable-negative-generate=')) {
			$parsed['enable_negative_generate'] = om_parse_enum_option(substr($arg, strlen('--enable-negative-generate=')), ['none', 'all'], '--enable-negative-generate');
			continue;
		}
		if ($arg === '--enable-negative-generate' && isset($args[$i + 1])) {
			$parsed['enable_negative_generate'] = om_parse_enum_option($args[++$i], ['none', 'all'], '--enable-negative-generate');
			continue;
		}
		if (str_starts_with($arg, '--enable-negative-generate-diagnostic=')) {
			$parsed['enable_negative_generate_diagnostic'] = om_parse_csv_option(substr($arg, strlen('--enable-negative-generate-diagnostic=')));
			continue;
		}
		if ($arg === '--enable-negative-generate-diagnostic' && isset($args[$i + 1])) {
			$parsed['enable_negative_generate_diagnostic'] = om_parse_csv_option($args[++$i]);
			continue;
		}
		if (str_starts_with($arg, '--disable-negative-generate-diagnostic=')) {
			$parsed['disable_negative_generate_diagnostic'] = om_parse_csv_option(substr($arg, strlen('--disable-negative-generate-diagnostic=')));
			continue;
		}
		if ($arg === '--disable-negative-generate-diagnostic' && isset($args[$i + 1])) {
			$parsed['disable_negative_generate_diagnostic'] = om_parse_csv_option($args[++$i]);
			continue;
		}
		if (str_starts_with($arg, '--negative-generate-disabled-status=')) {
			$parsed['negative_generate_disabled_status'] = om_parse_enum_option(substr($arg, strlen('--negative-generate-disabled-status=')), ['experimental', 'known_fail'], '--negative-generate-disabled-status');
			continue;
		}
		if ($arg === '--negative-generate-disabled-status' && isset($args[$i + 1])) {
			$parsed['negative_generate_disabled_status'] = om_parse_enum_option($args[++$i], ['experimental', 'known_fail'], '--negative-generate-disabled-status');
			continue;
		}
		if ($arg === '--strict-negative-generate-enable') {
			$parsed['strict_negative_generate_enable'] = true;
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
	echo "  --family                              Limit generation to one family\n";
	echo "  --validate                            Generate and validate rows\n";
	echo "  --stdout                              Also print generated rows to stdout\n";
	echo "  --emit-negative-generate=none|all     Emit negative_generate tests (default: all)\n";
	echo "  --enable-negative-generate=none|all   Enable all emitted negative_generate tests or keep them disabled (default: none)\n";
	echo "  --enable-negative-generate-diagnostic=LIST\n";
	echo "                                        Enable only the listed negative_generate diagnostic classes\n";
	echo "  --disable-negative-generate-diagnostic=LIST\n";
	echo "                                        Force-disable the listed negative_generate diagnostic classes\n";
	echo "  --negative-generate-disabled-status=experimental|known_fail\n";
	echo "                                        Status to assign to disabled negative_generate tests (default: experimental)\n";
	echo "  --strict-negative-generate-enable     Fail generation if an allowlisted negative_generate diagnostic emits zero tests\n";
}

/**
 * @param list<string> $allowed
 */
function om_parse_enum_option(string $value, array $allowed, string $optionName): string
{
	if (!in_array($value, $allowed, true)) {
		fwrite(STDERR, 'Invalid value for ' . $optionName . ': ' . $value . '. Allowed: ' . implode(', ', $allowed) . PHP_EOL);
		exit(1);
	}

	return $value;
}

/**
 * @return list<string>
 */
function om_parse_csv_option(string $value): array
{
	if ($value === '') {
		return [];
	}

	$parts = array_values(array_filter(array_map(
		static fn (string $part): string => trim($part),
		explode(',', $value)
	), static fn (string $part): bool => $part !== ''));
	$parts = array_values(array_unique($parts));
	sort($parts);
	return $parts;
}
