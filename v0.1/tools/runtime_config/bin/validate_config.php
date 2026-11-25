#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Validates runtime/specs/config.json for internal consistency and
 * resolution-path ambiguity according to the current runtime-config model.
 */

final class ValidationIssue
{
	public function __construct(
		public string $severity,
		public string $ruleId,
		public string $message,
		public ?string $path = null,
		public ?array $context = null,
	) {
	}

	public function toArray(): array
	{
		return [
			'severity' => $this->severity,
			'rule_id' => $this->ruleId,
			'message' => $this->message,
			'path' => $this->path,
			'context' => $this->context,
		];
	}
}

final class ConfigValidator
{
	private array $issues = [];
	private array $warnings = [];
	private array $config;

	public function __construct(private readonly string $configPath)
	{
		$json = file_get_contents($configPath);
		if ($json === false) {
			throw new RuntimeException('Could not read config file: ' . $configPath);
		}

		$data = json_decode($json, true);
		if (!is_array($data)) {
			throw new RuntimeException('Could not decode JSON config: ' . $configPath);
		}

		$this->config = $data;
	}

	public function validate(): int
	{
		$this->validateRequiredSections();
		$this->validateMixedBoundaryBridges();
		$this->validateDuplicateCasts();
		$this->validateDuplicateAssignments();
		$this->validateOverloadFamilies();
		$this->validateForbiddenOverlaps();
		$this->validateCompoundAssignmentPolicy();
		$this->validateConditionPolicy();
		$this->validateGeneratorRuntimeBoundary();

		return count($this->issues) === 0 ? 0 : 1;
	}

	public function printHumanReadable(): void
	{
		fwrite(STDOUT, "Config validation report\n");
		fwrite(STDOUT, "Config: {$this->configPath}\n\n");

		if ($this->issues === [] && $this->warnings === []) {
			fwrite(STDOUT, "PASS: no errors or warnings detected.\n");
			return;
		}

		foreach ($this->issues as $issue) {
			$this->printIssue($issue);
		}

		foreach ($this->warnings as $warning) {
			$this->printIssue($warning);
		}

		fwrite(STDOUT, "\nSummary: " . count($this->issues) . " error(s), " . count($this->warnings) . " warning(s).\n");
	}

	public function printJson(): void
	{
		$output = [
			'config_path' => $this->configPath,
			'errors' => array_map(static fn(ValidationIssue $issue): array => $issue->toArray(), $this->issues),
			'warnings' => array_map(static fn(ValidationIssue $issue): array => $issue->toArray(), $this->warnings),
			'passed' => count($this->issues) === 0,
		];

		fwrite(STDOUT, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
	}

	private function printIssue(ValidationIssue $issue): void
	{
		fwrite(STDOUT, strtoupper($issue->severity) . " [{$issue->ruleId}] {$issue->message}\n");
		if ($issue->path !== null) {
			fwrite(STDOUT, "  path: {$issue->path}\n");
		}
		if ($issue->context !== null) {
			fwrite(STDOUT, "  context: " . json_encode($issue->context, JSON_UNESCAPED_SLASHES) . "\n");
		}
	}

	private function validateRequiredSections(): void
	{
		$required = [
			'runtime',
			'types',
			'casts',
			'assignments',
			'overload_families',
			'validation_requirements',
			'compound_assignment_policy',
		];

		foreach ($required as $key) {
			if (!array_key_exists($key, $this->config)) {
				$this->error('vr_000', 'Missing required top-level config section.', '$.' . $key);
			}
		}
	}

	private function validateMixedBoundaryBridges(): void
	{
		$dispatch = $this->config['types']['mixed_t']['dispatch_contract'] ?? null;
		if (!is_array($dispatch)) {
			$this->error('vr_004', 'types.mixed_t.dispatch_contract is missing or invalid.', '$.types.mixed_t.dispatch_contract');
			return;
		}

		$bridges = $dispatch['boundary_bridges'] ?? null;
		if (!is_array($bridges)) {
			$this->error('vr_004', 'Explicit mixed_t boundary bridge booleans are required.', '$.types.mixed_t.dispatch_contract.boundary_bridges');
			return;
		}

		$requiredBooleans = [
			'initialization',
			'assignment',
			'by_value_argument',
			'typed_return',
			'operator_overload_resolution',
			'overload_disambiguation',
			'by_reference_parameter_adaptation',
			'const_reference_parameter_adaptation',
		];

		foreach ($requiredBooleans as $name) {
			if (!array_key_exists($name, $bridges) || !is_bool($bridges[$name])) {
				$this->error('vr_004', 'Boundary bridge flag must exist and be boolean.', '$.types.mixed_t.dispatch_contract.boundary_bridges.' . $name);
			}
		}

		if (($bridges['operator_overload_resolution'] ?? null) === true) {
			$this->error('vr_001', 'mixed_t implicit extraction must not be enabled for operator overload resolution.', '$.types.mixed_t.dispatch_contract.boundary_bridges.operator_overload_resolution');
		}

		if (($bridges['overload_disambiguation'] ?? null) === true) {
			$this->error('vr_001', 'mixed_t implicit extraction must not be enabled for overload disambiguation.', '$.types.mixed_t.dispatch_contract.boundary_bridges.overload_disambiguation');
		}
	}

	private function validateDuplicateCasts(): void
	{
		$seen = [];
		foreach (($this->config['casts'] ?? []) as $index => $cast) {
			$key = implode('|', [
				$cast['from'] ?? '',
				$cast['to'] ?? '',
				$cast['kind'] ?? '',
				$cast['form'] ?? '',
				$cast['cast_name'] ?? '',
				$cast['helper_name'] ?? '',
			]);

			if (isset($seen[$key])) {
				$this->error(
					'vr_006',
					'Duplicate cast path detected.',
					'$.casts[' . $index . ']',
					['duplicate_of' => '$.casts[' . $seen[$key] . ']']
				);
				continue;
			}

			$seen[$key] = $index;
		}
	}

	private function validateDuplicateAssignments(): void
	{
		$seen = [];
		foreach (($this->config['assignments'] ?? []) as $index => $assignment) {
			$key = implode('|', [
				$assignment['target'] ?? '',
				$assignment['source'] ?? '',
				$assignment['kind'] ?? '',
				$assignment['when'] ?? '',
			]);

			if (isset($seen[$key])) {
				$this->error(
					'vr_002',
					'Duplicate assignment path detected.',
					'$.assignments[' . $index . ']',
					['duplicate_of' => '$.assignments[' . $seen[$key] . ']']
				);
				continue;
			}

			$seen[$key] = $index;
		}
	}

	private function validateOverloadFamilies(): void
	{
		$seen = [];
		foreach (($this->config['overload_families'] ?? []) as $familyIndex => $family) {
			$familyName = $family['name'] ?? ('family_' . $familyIndex);
			foreach (($family['operators'] ?? []) as $opIndex => $operator) {
				$key = implode('|', [
					$operator['symbol'] ?? '',
					(string) ($operator['arity'] ?? ''),
					implode(',', $operator['operands'] ?? []),
				]);

				if (isset($seen[$key])) {
					$this->error(
						'vr_001',
						'Operator signature is defined more than once and may create resolution ambiguity.',
						'$.overload_families[' . $familyIndex . '].operators[' . $opIndex . ']',
						['duplicate_of' => $seen[$key], 'family' => $familyName]
					);
					continue;
				}

				$seen[$key] = '$.overload_families[' . $familyIndex . '].operators[' . $opIndex . ']';
			}
		}
	}

	private function validateForbiddenOverlaps(): void
	{
		$allowed = [];
		foreach (($this->config['overload_families'] ?? []) as $family) {
			foreach (($family['operators'] ?? []) as $operator) {
				if (($operator['arity'] ?? null) !== 2) {
					continue;
				}
				$allowed[] = [
					$operator['operands'][0] ?? '',
					$operator['symbol'] ?? '',
					$operator['operands'][1] ?? '',
				];
			}
		}

		foreach (($this->config['forbidden_operation_groups'] ?? []) as $groupIndex => $group) {
			foreach (($group['patterns'] ?? []) as $patternIndex => $pattern) {
				foreach ($allowed as $allowedPattern) {
					if ($pattern === $allowedPattern) {
						$this->error(
							'vr_001',
							'Forbidden operation pattern overlaps with an allowed operator signature.',
							'$.forbidden_operation_groups[' . $groupIndex . '].patterns[' . $patternIndex . ']',
							['pattern' => $pattern]
						);
					}
				}
			}
		}
	}

	private function validateCompoundAssignmentPolicy(): void
	{
		$policy = $this->config['compound_assignment_policy'] ?? null;
		if (!is_array($policy)) {
			$this->error('vr_003', 'compound_assignment_policy is missing.', '$.compound_assignment_policy');
			return;
		}

		if (($policy['model'] ?? null) !== 'derived_from_binary_op_then_assign_back') {
			$this->error('vr_003', 'compound_assignment_policy.model must be derived_from_binary_op_then_assign_back.', '$.compound_assignment_policy.model');
		}

		$symbols = $policy['supported_symbols'] ?? [];
		if (!is_array($symbols) || $symbols === []) {
			$this->error('vr_003', 'compound_assignment_policy.supported_symbols must be a non-empty array.', '$.compound_assignment_policy.supported_symbols');
			return;
		}

		if (count($symbols) !== count(array_unique($symbols))) {
			$this->error('vr_003', 'compound_assignment_policy.supported_symbols contains duplicates.', '$.compound_assignment_policy.supported_symbols');
		}

		$compoundSymbols = array_flip($symbols);
		foreach (($this->config['overload_families'] ?? []) as $familyIndex => $family) {
			foreach (($family['operators'] ?? []) as $opIndex => $operator) {
				if (isset($compoundSymbols[$operator['symbol'] ?? ''])) {
					$this->error(
						'vr_003',
						'Explicit compound assignment operator must not appear in overload_families when derivation policy is active.',
						'$.overload_families[' . $familyIndex . '].operators[' . $opIndex . ']',
						['symbol' => $operator['symbol']]
					);
				}
			}
		}
	}

	private function validateConditionPolicy(): void
	{
		$condition = $this->config['coercions']['condition'] ?? null;
		if (!is_array($condition)) {
			$this->error('vr_007', 'coercions.condition is missing.', '$.coercions.condition');
			return;
		}

		$allowedInputs = $condition['allowed_inputs'] ?? [];
		if (!is_array($allowedInputs)) {
			$this->error('vr_007', 'coercions.condition.allowed_inputs must be an array.', '$.coercions.condition.allowed_inputs');
			return;
		}

		if (count($allowedInputs) !== count(array_unique($allowedInputs))) {
			$this->error('vr_007', 'coercions.condition.allowed_inputs contains duplicates.', '$.coercions.condition.allowed_inputs');
		}

		foreach ($allowedInputs as $index => $typeName) {
			if (!isset($this->config['types'][$typeName])) {
				$this->error('vr_007', 'coercions.condition.allowed_inputs references unknown type.', '$.coercions.condition.allowed_inputs[' . $index . ']', ['type' => $typeName]);
			}
		}
	}

	private function validateGeneratorRuntimeBoundary(): void
	{
		$dispatch = $this->config['types']['mixed_t']['dispatch_contract'] ?? [];
		$generatorOwned = $dispatch['generator_owned_operations']['concat'] ?? [];
		$operatorSurface = $dispatch['operator_surface'] ?? [];
		$surfaceValues = [];
		foreach ($operatorSurface as $operators) {
			if (is_array($operators)) {
				$surfaceValues = array_merge($surfaceValues, $operators);
			}
		}

		foreach ($generatorOwned as $symbol) {
			if (in_array($symbol, $surfaceValues, true)) {
				$this->error('vr_011', 'Generator-owned operation overlaps runtime mixed_t operator surface.', '$.types.mixed_t.dispatch_contract.generator_owned_operations.concat', ['symbol' => $symbol]);
			}
		}
	}

	private function error(string $ruleId, string $message, ?string $path = null, ?array $context = null): void
	{
		$this->issues[] = new ValidationIssue('error', $ruleId, $message, $path, $context);
	}

	private function warn(string $ruleId, string $message, ?string $path = null, ?array $context = null): void
	{
		$this->warnings[] = new ValidationIssue('warning', $ruleId, $message, $path, $context);
	}
}

function usage(): void
{
	fwrite(STDERR, "Usage: php tools/runtime_config/bin/validate_config.php [--json] [path/to/config.json]\n");
}

$args = $argv;
array_shift($args);
$jsonOutput = false;
$configPath = getcwd() . '/runtime/specs/config.json';

foreach ($args as $arg) {
	if ($arg === '--json') {
		$jsonOutput = true;
		continue;
	}
	if ($arg === '--help' || $arg === '-h') {
		usage();
		exit(0);
	}
	$configPath = $arg;
}

try {
	$validator = new ConfigValidator($configPath);
	$exitCode = $validator->validate();
	if ($jsonOutput) {
		$validator->printJson();
	} else {
		$validator->printHumanReadable();
	}
	exit($exitCode);
} catch (Throwable $e) {
	fwrite(STDERR, 'FATAL: ' . $e->getMessage() . PHP_EOL);
	exit(2);
}
