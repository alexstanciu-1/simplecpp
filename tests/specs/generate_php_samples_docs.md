# Simple C++ – PHP Test Sample Generation Specification (v2)

This document is the strict specification for generating PHP test samples under `tests/php/`.

Its purpose is to make test-case generation reproducible across chats, contributors, and future tooling.

The target end-to-end pipeline is:

`PHP test code -> generate C++ -> compile C++ -> execute C++`

This specification covers:
- directory layout
- naming rules
- allowed outcome classes
- required metadata
- exact comparison rules
- generation constraints
- review and acceptance rules

This specification does **not** define the runner implementation in detail, but every generated test case must conform to this document so that a runner can process it deterministically.

---

## 1. Normative Terms

The keywords **MUST**, **MUST NOT**, **REQUIRED**, **SHOULD**, **SHOULD NOT**, and **MAY** are normative.

- **MUST / REQUIRED**: mandatory.
- **SHOULD**: expected unless there is a documented reason not to.
- **MAY**: optional.

---

## 2. Core Principles

Every generated PHP test case MUST satisfy all of the following:

1. It tests exactly one primary behavior or design rule.
2. It has a deterministic expected outcome.
3. Its expected result is machine-checkable.
4. Its filename and metadata identify its purpose clearly.
5. It is small enough to debug quickly.
6. It does not depend on unstable environment behavior unless that dependency is explicitly the subject of the test.

A sample MUST NOT be added if its result depends on timing, randomness, host-specific state, or ambiguous failure sources.

---

## 3. Canonical Directory Layout

All generated PHP samples MUST live under `tests/php/`.

Canonical layout:

```text
tests/
	php/
		<feature>/
			level_01/
				<feature>_<nnn>_<slug>.php
				<feature>_<nnn>_<slug>.php.json
			level_02/
				...
			level_03/
				...
```

Examples:

```text
tests/
	php/
		echo/
			level_01/
				echo_001_basic.php
				echo_001_basic.php.json
		nullable/
			level_02/
				nullable_004_coalesce_guard.php
				nullable_004_coalesce_guard.php.json
		integration/
			level_03/
				integration_002_loop_and_nullable.php
				integration_002_loop_and_nullable.php.json
```

Rules:
- samples MUST be grouped by feature first, then by level.
- `integration` SHOULD be used for mixed-feature scenarios.
- a flat layout such as `tests/php/level_01/...` is NOT canonical and MUST NOT be used for new test generation.

---

## 4. Test Levels

## 4.1 `level_01`

Purpose:
- smoke coverage
- readability
- one concept at a time
- fast diagnosis

Rules:
- target **2** samples per functionality/design when meaningful.
- each sample MUST isolate the feature under test.
- samples MUST be minimal.
- unrelated constructs MUST NOT be introduced just to make the sample more realistic.

Typical shape:
- one basic success case
- one nearby edge case or one basic rejection case

## 4.2 `level_02`

Purpose:
- broader coverage
- edge cases
- boundary rules
- regression coverage inside one feature bucket

Rules:
- target **up to 10** useful samples per functionality/design.
- filler tests MUST NOT be created to reach 10.
- both positive and negative cases SHOULD be included when meaningful.
- each sample MUST still have one primary purpose.

## 4.3 `level_03`

Purpose:
- mixed-feature scenarios
- integration-like validation
- realistic small programs
- regression cases involving feature interaction

Rules:
- samples MAY combine multiple supported features.
- each sample MUST still have one primary asserted purpose.
- tiny smoke samples SHOULD NOT be placed here.
- large benchmark-style programs MUST NOT be placed here.

---

## 5. Outcome Classes

Every test case MUST declare exactly one `outcome` value.

Allowed values:
- `positive`
- `negative_generate`
- `negative_compile`
- `negative_runtime`

## 5.1 `positive`

Contract:
- PHP execution succeeds, unless `php_as_oracle` is explicitly false.
- PHP -> C++ generation succeeds.
- generated C++ compilation succeeds.
- compiled C++ execution succeeds.
- final observed result matches the declared contract.

## 5.2 `negative_generate`

Contract:
- the PHP file is accepted as a test input file.
- generation MUST fail.
- the failure MUST be validated using declared generator error matching rules.

Use for:
- unsupported syntax forms intentionally rejected by the generator
- invalid type comments
- invalid wrapper combinations
- forbidden internal test constructs intended to stop at generation time

## 5.3 `negative_compile`

Contract:
- generation succeeds.
- compilation fails.
- the failure MUST be validated using declared compiler diagnostic matching rules.

Use only when compile-time rejection is the intended boundary.

## 5.4 `negative_runtime`

Contract:
- generation succeeds.
- compilation succeeds.
- execution fails or produces a declared failing runtime signature.

This class SHOULD be rare. Prefer earlier rejection whenever the design permits it.

---

## 6. Canonical File Naming Rules

Each PHP test sample MUST follow this filename format:

`<feature>_<nnn>_<slug>.php`

The sidecar metadata filename MUST be:

`<feature>_<nnn>_<slug>.php.json`

Examples:
- `echo_001_basic.php`
- `echo_002_concat_literal.php`
- `nullable_004_manual_guard.php`
- `wrappers_007_nested_value_negative.php`

Rules:
- `<feature>` MUST match the parent feature directory name.
- `<nnn>` MUST be a zero-padded decimal sequence of exactly 3 digits.
- `<slug>` MUST be lowercase and use underscores only.
- filenames MUST be stable and sortable.
- the slug SHOULD describe intent, not implementation detail.

The metadata field `id` MUST equal the basename without `.php`.

Example:
- filename: `echo_001_basic.php`
- metadata `id`: `echo_001_basic`

---

## 7. Canonical PHP Source Rules

Unless the test specifically requires otherwise, generated PHP source SHOULD follow these rules:

1. Start with `<?php`.
2. Use `declare(strict_types=1);` when the tested behavior is compatible with strict typing.
3. Avoid trailing spaces.
4. End the file with a newline.
5. Keep helper declarations inside the same file unless the test explicitly targets include/module behavior.
6. Keep the program self-contained.

The sample MUST NOT:
- read environment variables
- read or write files
- use current time
- use random values
- depend on network access
- depend on locale settings
- depend on external services

Unless that dependency is itself the explicit subject of the test, such tests are forbidden in this corpus.

---

## 8. Required Metadata Format

Every PHP sample MUST have a sidecar JSON file.

The sidecar JSON MUST be valid UTF-8 encoded JSON object.

## 8.1 Required Keys

The following keys are REQUIRED in every sidecar file:

- `id`
- `feature`
- `level`
- `outcome`
- `enabled`
- `status`
- `php_as_oracle`
- `compare`
- `expect`
- `tags`
- `notes`

## 8.2 Allowed Values and Types

### `id`
- type: string
- required
- MUST equal the PHP basename without `.php`

### `feature`
- type: string
- required
- MUST equal the parent feature directory name

### `level`
- type: string
- required
- allowed values: `level_01`, `level_02`, `level_03`

### `outcome`
- type: string
- required
- allowed values: `positive`, `negative_generate`, `negative_compile`, `negative_runtime`

### `enabled`
- type: boolean
- required
- `true` means the runner is allowed to execute the test
- `false` means the test exists but MUST be skipped by default

### `status`
- type: string
- required
- allowed values: `active`, `todo`, `known_fail`, `experimental`

Meaning:
- `active`: expected to behave according to the declared contract
- `todo`: intentionally present but incomplete; SHOULD normally be disabled
- `known_fail`: intentionally tracked failing case; SHOULD normally be disabled unless runner explicitly includes known failures
- `experimental`: unstable contract under active development; SHOULD normally be disabled

### `php_as_oracle`
- type: boolean
- required
- `true`: PHP execution result is the source of truth for result comparison
- `false`: expectations are taken from `expect`

### `compare`
- type: object
- required
- defines how comparisons are performed

### `expect`
- type: object
- required
- declares expected stage results and outputs

### `tags`
- type: array of strings
- required
- MAY be empty
- MUST contain only lowercase strings with underscores or digits

### `notes`
- type: string
- required
- short human explanation of the sample purpose

---

## 9. Canonical Metadata Schema

The sidecar object MUST conform to this schema shape:

```json
{
	"id": "echo_001_basic",
	"feature": "echo",
	"level": "level_01",
	"outcome": "positive",
	"enabled": true,
	"status": "active",
	"php_as_oracle": true,
	"compare": {
		"stdout": "exact",
		"stderr": "exact",
		"generator_error": "substring_all",
		"compile_error": "substring_all",
		"normalize_stdout_newlines": true,
		"normalize_stderr_newlines": true,
		"trim_final_stdout_newline": false,
		"trim_final_stderr_newline": false,
		"case_sensitive_errors": true
	},
	"expect": {
		"php": {
			"run": true,
			"exit_code": 0,
			"stdout": "hello\n",
			"stderr": ""
		},
		"generate": {
			"success": true,
			"error_contains": []
		},
		"compile": {
			"success": true,
			"error_contains": []
		},
		"run": {
			"success": true,
			"exit_code": 0,
			"stdout": "hello\n",
			"stderr": ""
		}
	},
	"tags": [
		"echo",
		"smoke"
	],
	"notes": "Minimal echo success case."
}
```

The schema above is the canonical model.

---

## 10. Comparison Modes

Only the following comparison mode values are allowed:

- `exact`
- `substring_all`
- `ignore`

Meaning:

### `exact`
Observed content MUST equal expected content exactly after applying the configured normalization rules.

### `substring_all`
Every expected string in the corresponding `error_contains` array MUST appear in the observed content.
Order is not required unless separately declared by future schema extension.

### `ignore`
That channel is not compared.

Rules:
- `stdout` and `stderr` compare modes in `compare` MUST be one of `exact` or `ignore`.
- `generator_error` and `compile_error` compare modes MUST be one of `substring_all` or `ignore`.

Regex matching is NOT allowed in v2.
Full diagnostic equality is NOT required in v2.

---

## 11. Normalization Rules

To make comparisons stable across environments, the following normalization rules apply before comparison when the corresponding flags are enabled.

### 11.1 Newline Normalization

If `normalize_stdout_newlines` or `normalize_stderr_newlines` is `true`:
- convert `\r\n` to `\n`
- convert isolated `\r` to `\n`

### 11.2 Final Newline Trimming

If `trim_final_stdout_newline` or `trim_final_stderr_newline` is `true`:
- remove **one** final trailing `\n` if present
- do not trim multiple trailing newlines beyond one

Default recommendation for this corpus:
- keep `trim_final_stdout_newline: false`
- keep `trim_final_stderr_newline: false`

Reason: exact output contracts should remain explicit.

### 11.3 Error Case Sensitivity

If `case_sensitive_errors` is `true`, substring matching is case-sensitive.
If `false`, both observed and expected error strings are compared using lowercased copies.

Default in this corpus: `true`.

---

## 12. Stage Contracts

The pipeline stages are:

1. `php`
2. `generate`
3. `compile`
4. `run`

The meaning of each `expect` sub-object is fixed.

## 12.1 `expect.php`

Fields:
- `run`: boolean
- `exit_code`: integer
- `stdout`: string
- `stderr`: string

Rules:
- if `php_as_oracle` is `true`, the runner SHOULD still record observed PHP results, but the declared `stdout` and `stderr` MAY be used only as documentation or as optional self-checks.
- if `php_as_oracle` is `false`, the values in `expect.php` are authoritative if `run` is `true`.
- if `run` is `false`, PHP execution MUST NOT be required for pass/fail.

## 12.2 `expect.generate`

Fields:
- `success`: boolean
- `error_contains`: array of strings

Rules:
- if `success` is `true`, `error_contains` MUST be empty.
- if `success` is `false`, `error_contains` SHOULD contain at least one stable diagnostic fragment.

## 12.3 `expect.compile`

Fields:
- `success`: boolean
- `error_contains`: array of strings

Rules:
- if `success` is `true`, `error_contains` MUST be empty.
- if `success` is `false`, `error_contains` SHOULD contain at least one stable diagnostic fragment.

## 12.4 `expect.run`

Fields:
- `success`: boolean
- `exit_code`: integer
- `stdout`: string
- `stderr`: string

Rules:
- if `success` is `false`, the test MAY still define exact expected stdout/stderr if those are stable.
- if the runtime failure is not stable enough for exact stderr, set `compare.stderr` to `ignore` and use exit code only.

---

## 13. Outcome-to-Expectation Mapping

The metadata MUST be internally consistent with the declared `outcome`.

## 13.1 `positive`

Required mapping:
- `expect.generate.success = true`
- `expect.compile.success = true`
- `expect.run.success = true`

Additionally:
- if `php_as_oracle = true`, PHP SHOULD run successfully.
- `compare.generator_error` SHOULD be `ignore` or `substring_all` with an empty array because no error is expected.
- `compare.compile_error` SHOULD be `ignore` or `substring_all` with an empty array because no error is expected.

## 13.2 `negative_generate`

Required mapping:
- `expect.generate.success = false`
- `expect.compile.success = false` is NOT used for pass/fail because compile must not be reached
- `expect.run.success = false` is NOT used for pass/fail because run must not be reached

Additionally:
- `expect.generate.error_contains` SHOULD be non-empty.
- `php_as_oracle` MUST be `false` unless there is a documented reason to run PHP anyway.

## 13.3 `negative_compile`

Required mapping:
- `expect.generate.success = true`
- `expect.compile.success = false`
- `expect.run.success = false` is NOT used for pass/fail because run must not be reached

Additionally:
- `expect.compile.error_contains` SHOULD be non-empty.

## 13.4 `negative_runtime`

Required mapping:
- `expect.generate.success = true`
- `expect.compile.success = true`
- `expect.run.success = false`

Additionally:
- the failure signature MUST be stable enough to check using declared comparison rules.

---

## 14. PHP Oracle Rules

PHP as oracle is the default for executable positive tests.

Rules:
- for `positive` tests, `php_as_oracle` SHOULD be `true` by default.
- for `negative_generate`, `php_as_oracle` SHOULD be `false`.
- for `negative_compile`, `php_as_oracle` SHOULD usually be `false`.
- for `negative_runtime`, `php_as_oracle` MAY be `true` only if that comparison is meaningful and stable.

When `php_as_oracle` is `true`:
1. execute the PHP sample
2. capture `exit_code`, `stdout`, `stderr`
3. use that observed result as the expected result for C++ runtime comparison

When `php_as_oracle` is `false`:
- `expect` is the sole source of truth.

A test MUST NOT set `php_as_oracle` to `true` if PHP behavior is environment-sensitive or intentionally not the contract.

---

## 15. Stability Constraints

Generated samples MUST be deterministic.

The following are forbidden unless explicitly controlled and documented:
- randomness
- current wall-clock time
- monotonic clock timing output
- filesystem reads/writes
- network access
- locale-sensitive formatting
- host-specific absolute paths in expected output
- dependence on installed extensions not already part of the project contract

If a feature inherently depends on unstable environment behavior, it MUST be excluded from this baseline corpus or moved to a separate, explicitly specialized test layer.

---

## 16. Sample Design Rules

## 16.1 One Primary Assertion

Every sample MUST have one primary asserted purpose.

A sample MAY include supporting constructs, but those constructs MUST NOT become competing possible explanations for failure.

## 16.2 Minimal Failure Surface

Negative tests MUST isolate the intended failure cause.

Bad:
- one file containing three unsupported constructs and relying on whichever fails first

Good:
- one file with one local, obvious unsupported construct

## 16.3 Small Reviewable Outputs

Positive tests SHOULD prefer small exact outputs.

Preferred outputs:
- `"1\n"`
- `"ok\n"`
- `"10\n20\n"`

Large output SHOULD be used only when output volume itself is part of the contract.

## 16.4 No Filler Variants

Samples MUST NOT be generated solely to satisfy a count target.

A new sample is justified only if it adds one of:
- new supported syntax shape
- new boundary value
- new edge condition
- new regression reproduction
- new feature interaction
- new negative rejection boundary

## 16.5 Level Discipline

- trivial smoke samples MUST stay in `level_01`
- feature-local edge cases SHOULD stay in `level_02`
- mixed-feature cases SHOULD go to `level_03`

---

## 17. Sample Count Targets

The following are target generation counts, not hard quotas:

- `level_01`: 2 samples per functionality/design where meaningful
- `level_02`: up to 10 useful samples per functionality/design
- `level_03`: no fixed count; only meaningful interaction samples

The generator MUST prefer coverage quality over raw count.

---

## 18. Feature Bucket Guidance

Feature buckets SHOULD reflect real design areas of the generator/runtime.

Suggested stable buckets:
- `echo`
- `scalars`
- `strings`
- `concat`
- `conditions`
- `loops`
- `switch`
- `match`
- `functions`
- `methods`
- `classes`
- `properties`
- `constants`
- `arrays`
- `vector`
- `references`
- `nullable`
- `isset_unset`
- `type_comments`
- `wrappers`
- `namespaces`
- `integration`

The exact taxonomy MAY evolve, but new buckets SHOULD be added deliberately and remain stable afterward.

---

## 18.1 Current Array Literal Rule for `vector<T>`

At the current project stage, a PHP array literal MAY produce a `vector<T>` only when the target context is explicitly typed as `vector<T>`.

This applies to explicit typed contexts such as:
- local variables with `/** vector<T> */`
- instance properties with `/** vector<T> */`
- static properties with `/** vector<T> */`
- function parameters with `/** vector<T> */`
- function return types/contexts with `/** vector<T> */` when applicable

### Accepted examples

```php
$v /** vector<int> */ = [];
$v /** vector<int> */ = [1, 2, 3];
```

### Rejected examples

```php
$a = [];
$a = [1, 2, 3];
```

### Reason

At the current stage, unless there is an explicit `/** vector<T> */` type, there is no supported way to create a `vector<T>`.

Untyped array literals MUST be rejected for now because the project reserves those forms for future PHP-like-array support.

A future design may allow:

```php
$a = [1, 2, 3];
```

to create a PHP-like-array when there is no explicit type. That behavior is NOT part of the current test generation contract and MUST NOT be assumed by generated tests.

## 19. Canonical Metadata Examples

## 19.1 Positive Example

```json
{
	"id": "echo_001_basic",
	"feature": "echo",
	"level": "level_01",
	"outcome": "positive",
	"enabled": true,
	"status": "active",
	"php_as_oracle": true,
	"compare": {
		"stdout": "exact",
		"stderr": "exact",
		"generator_error": "ignore",
		"compile_error": "ignore",
		"normalize_stdout_newlines": true,
		"normalize_stderr_newlines": true,
		"trim_final_stdout_newline": false,
		"trim_final_stderr_newline": false,
		"case_sensitive_errors": true
	},
	"expect": {
		"php": {
			"run": true,
			"exit_code": 0,
			"stdout": "hello\n",
			"stderr": ""
		},
		"generate": {
			"success": true,
			"error_contains": []
		},
		"compile": {
			"success": true,
			"error_contains": []
		},
		"run": {
			"success": true,
			"exit_code": 0,
			"stdout": "hello\n",
			"stderr": ""
		}
	},
	"tags": [
		"echo",
		"smoke"
	],
	"notes": "Minimal echo success case."
}
```

## 19.2 Generator-Rejection Example

```json
{
	"id": "wrappers_007_nested_value_negative",
	"feature": "wrappers",
	"level": "level_02",
	"outcome": "negative_generate",
	"enabled": true,
	"status": "active",
	"php_as_oracle": false,
	"compare": {
		"stdout": "ignore",
		"stderr": "ignore",
		"generator_error": "substring_all",
		"compile_error": "ignore",
		"normalize_stdout_newlines": true,
		"normalize_stderr_newlines": true,
		"trim_final_stdout_newline": false,
		"trim_final_stderr_newline": false,
		"case_sensitive_errors": true
	},
	"expect": {
		"php": {
			"run": false,
			"exit_code": 0,
			"stdout": "",
			"stderr": ""
		},
		"generate": {
			"success": false,
			"error_contains": [
				"Invalid nested wrapper type"
			]
		},
		"compile": {
			"success": false,
			"error_contains": []
		},
		"run": {
			"success": false,
			"exit_code": 0,
			"stdout": "",
			"stderr": ""
		}
	},
	"tags": [
		"type_comment",
		"wrapper",
		"negative"
	],
	"notes": "Reject nested wrappers such as value<value<Box>>."
}
```

## 19.3 Compile-Failure Example

```json
{
	"id": "types_003_compile_boundary_negative",
	"feature": "type_comments",
	"level": "level_02",
	"outcome": "negative_compile",
	"enabled": true,
	"status": "active",
	"php_as_oracle": false,
	"compare": {
		"stdout": "ignore",
		"stderr": "ignore",
		"generator_error": "ignore",
		"compile_error": "substring_all",
		"normalize_stdout_newlines": true,
		"normalize_stderr_newlines": true,
		"trim_final_stdout_newline": false,
		"trim_final_stderr_newline": false,
		"case_sensitive_errors": true
	},
	"expect": {
		"php": {
			"run": false,
			"exit_code": 0,
			"stdout": "",
			"stderr": ""
		},
		"generate": {
			"success": true,
			"error_contains": []
		},
		"compile": {
			"success": false,
			"error_contains": [
				"no viable conversion"
			]
		},
		"run": {
			"success": false,
			"exit_code": 0,
			"stdout": "",
			"stderr": ""
		}
	},
	"tags": [
		"compile_negative",
		"boundary"
	],
	"notes": "Generation is allowed, but C++ compilation must reject the emitted type boundary."
}
```

---

## 20. Generation Procedure

When creating new samples for a feature, use this order:

1. identify the feature bucket
2. identify the single primary rule to test
3. choose the correct level
4. choose the outcome class
5. write the minimal PHP sample
6. define the exact metadata contract
7. verify determinism
8. verify naming and placement
9. run review checklist
10. only then add the sample to the corpus

For positive features, the preferred progression is:
- minimal success case
- nearby supported edge case
- boundary/permutation cases
- mixed-feature interaction case

For negative features, the preferred progression is:
- common user mistake
- strict forbidden boundary
- regression case for a previously mishandled rejection

---

## 21. Acceptance Checklist

A generated sample MUST NOT be accepted until every answer below is yes.

1. Does the sample have exactly one primary purpose?
2. Is the feature bucket correct?
3. Is the level correct?
4. Is the outcome class correct?
5. Is the filename canonical?
6. Does the metadata `id` match the filename?
7. Is the expected result deterministic?
8. Is the failure source isolated?
9. Are comparison rules explicit?
10. Is PHP oracle usage correct?
11. Are all required metadata keys present?
12. Would a failure be easy to diagnose from a stage-by-stage report?

If any answer is no, the sample MUST be revised before acceptance.

---

## 22. Non-Goals

This specification does not require:
- exhaustive combinatorial enumeration
- random fuzz generation as part of the baseline corpus
- benchmark-scale programs in normal feature levels
- automatic inclusion of every unsupported PHP feature
- regex-based comparison rules

Those can be added later as separate layers, but they are outside this v2 baseline.

---

## 23. Final Rule

For future chats and future contributors, this file is intended to be sufficient on its own to generate PHP test samples consistently.

When there is a conflict between convenience and determinism, determinism wins.


---

## 20. Normative Source Hierarchy for Test Generation

PHP test samples MUST be generated from project sources in the following precedence order:

### 20.1 Tier A — Normative sources
These define intended behavior and therefore create primary test obligations.

1. `runtime/specs/spec.md`
2. `php_generator/specs/rules.md`
3. `php_generator/specs/rules_catalog.md`

### 20.2 Tier B — Support and configuration sources
These refine support boundaries but MUST NOT invent semantics unless explicitly stated.

4. `runtime/specs/config.json`

### 20.3 Tier C — Implementation and history sources
These do not define intent first, but they MUST be used for regression coverage and mismatch detection.

5. runtime implementation code
6. generator implementation code
7. previous bug reports, failing samples, debug reproductions, and known-failure notes

### 20.4 Conflict rule
If sources disagree, the following decision order MUST be used:

1. explicit normative spec text
2. explicit support/config declaration
3. established regression requirement
4. current implementation behavior
5. inference

A disagreement MUST NOT be silently resolved. It MUST produce one of:
- `spec_gap`
- `known_fail`
- `regression`

---

## 21. Test Obligation Extraction Procedure

Before concrete PHP files are written, the test generator MUST build a normalized obligation inventory.

### 21.1 Definition
A **test obligation** is a machine-reviewable statement that some behavior, rejection, boundary, or regression MUST be represented by at least one test sample.

### 21.2 Required extraction passes
The generator MUST perform these passes in order:

1. read normative sources
2. read support/config sources
3. extract candidate obligations
4. normalize and deduplicate obligations
5. classify obligations
6. map obligations to levels and outcome classes
7. generate concrete PHP samples and metadata
8. review for missing negatives, integrations, and regressions

### 21.3 Extraction requirements by source

#### `runtime/specs/spec.md`
Extract obligations for:
- runtime types
- conversions and casts
- ownership / cleanup semantics
- nullability rules
- helper behavior
- container behavior
- observable output behavior

Typical obligation outputs:
- `runtime_behavior`
- `positive_behavior`
- `negative_runtime`
- `integration_behavior`

#### `php_generator/specs/rules.md`
Extract obligations for:
- lowering rules
- emitted shapes
- supported PHP constructs
- unsupported constructs
- rejection rules
- typing rules
- wrapper rules
- generation-stage constraints

Typical obligation outputs:
- `positive_behavior`
- `negative_rejection`
- `compile_constraint`

#### `php_generator/specs/rules_catalog.md`
Treat as the feature inventory and rule index.

Extract obligations for:
- every distinct rule
- every explicitly documented allowed example
- every explicitly documented forbidden example
- every semantically distinct branch or edge case

Minimum rule:
- each distinct rule in the catalog SHOULD map to at least one `level_01` or `level_02` obligation.

#### `runtime/specs/config.json`
Use to:
- confirm support status
- refine enabled/disabled scope
- refine feature names or option names
- mark candidate obligations as unsupported or gated

This file MUST NOT be used alone to invent runtime or generator semantics unless the semantics are explicit.

---

## 22. Canonical Test Obligation Schema

The obligation inventory MAY be stored in any implementation format, but each obligation MUST carry at least the following fields conceptually.

Required conceptual fields:
- `obligation_id`
- `feature_area`
- `feature_name`
- `origin_type`
- `source_files`
- `source_sections`
- `obligation_type`
- `status`
- `primary_assertion`
- `expected_outcome`
- `level_hint`
- `reason_for_existence`

### 22.1 Allowed `origin_type` values
- `runtime_spec`
- `generator_spec`
- `cross_spec`
- `regression`
- `spec_gap`

### 22.2 Allowed `obligation_type` values
- `positive_behavior`
- `negative_rejection`
- `compile_constraint`
- `runtime_behavior`
- `integration_behavior`
- `regression`

### 22.3 Allowed `status` values
- `required`
- `optional`
- `known_gap`
- `known_fail`

### 22.4 Deduplication rule
Two candidate obligations MUST be merged if all of the following are true:
- they assert the same primary behavior or rejection boundary
- they target the same feature area
- they produce the same expected stage outcome
- no meaningful provenance would be lost by merging

When merged, provenance from all contributing sources MUST be preserved.

---

## 23. Mapping Obligations to Concrete Samples

The following rules MUST be used when converting obligations into PHP tests.

### 23.1 Minimum mapping per distinct feature/design target
For each distinct required feature/design target, generate at minimum:
- one `level_01` positive sample, if the feature is supported
- one second nearby sample that is either:
  - a boundary positive, or
  - a closely related negative

### 23.2 When `level_02` is required
An obligation set SHOULD expand into `level_02` samples when at least one is true:
- multiple syntax shapes exist
- multiple type variants exist
- multiple documented branches exist
- meaningful error paths exist
- wrapper, nullability, or container variants exist
- the feature has regression history

### 23.3 When `level_03` is required
An obligation set SHOULD expand into `level_03` samples when at least one is true:
- behavior depends on both generator lowering and runtime semantics
- the feature historically fails in combination with another feature
- the intended behavior is inherently an interaction behavior
- realistic mixed-feature coverage is needed to validate the design

### 23.4 Unsupported behavior
If a spec or config indicates behavior is not yet supported, the generator MUST choose one of:
- create a disabled `todo` sample
- create a disabled `known_fail` sample
- defer generation only if the spec explicitly says the area is out of scope

Unsupported obligations MUST NOT disappear silently.

---

## 24. Provenance Requirements for Every Test Case

Every concrete PHP test case MUST be traceable back to its source obligations.

The sidecar JSON in v2 does not yet require provenance fields, but generation tooling and future specs SHOULD preserve them.

Recommended provenance fields for future-compatible metadata:
- `origin_type`
- `source_files`
- `source_sections`
- `feature_area`
- `feature_name`
- `reason_for_existence`

### 24.1 Minimum provenance rule
A test is not valid for generation review unless a human can answer all of the following:
- which source required this test?
- what behavior or rejection does it assert?
- why is its level appropriate?
- why does its expected outcome class make sense?

---

## 25. Regression Test Generation Policy

Regression tests are mandatory additions to the corpus and are distinct from pure spec-coverage tests.

Regression obligations MUST be created from:
- fixed bugs
- user-provided failing samples
- crash reproductions
- miscompilations
- previously observed spec/code mismatches

Rules:
- regression tests MUST be marked with `origin_type = regression`
- regression tests MUST include a short reason or bug reference in notes or provenance
- regression tests MUST NOT be removed simply because broader coverage already exists
- if the bug is not fixed yet, the test SHOULD exist as disabled `known_fail` or `todo`

---

## 26. Review Checklist Before Accepting Newly Generated Samples

Before adding newly generated test cases, the review MUST verify:
- the sample came from a documented obligation
- the sample has one primary assertion
- the outcome class is correct
- the level is appropriate
- the metadata is internally consistent
- expected outputs or diagnostics are stable
- no filler variants were added only to hit a count target
- provenance is preserved or recorded externally
- unsupported behaviors were not silently omitted
- known regressions were represented where required

---

## 27. Recommended Companion Files in `tests/specs/`

To reduce drift across future chats, the following additional files are recommended:

- `tests/specs/test_obligation_schema.md`
- `tests/specs/test_generation_workflow.md`
- `tests/specs/test_source_hierarchy.md`

If these files exist, they SHOULD be kept consistent with this document and MAY restate parts of it in a more focused form for tooling or audit work.


---

## 7. Feature-Specific Rules Locked for Current Test Generation

This section records explicit project decisions that MUST be used when generating PHP tests, even if implementation details remain permissive in some places.

### 7.1 Arrays / `vector<T>`

#### 7.1.1 Canonical rule
A PHP array literal creates `vector<T>` only in an explicit typed context using `/** vector<T> */`.

Accepted examples:

```php
$v /** vector<int> */ = [];
$v /** vector<int> */ = [1, 2, 3];
```

The same rule applies, where supported by the language surface, to explicit typed contexts such as:
- typed local variables
- typed instance properties
- typed static properties
- typed parameter/default contexts
- typed return contexts

Rejected examples:

```php
$a = [];
$a = [1, 2, 3];
```

These untyped forms are rejected for now because there is currently no PHP-like-array feature. They are reserved for future PHP-like-array support.

#### 7.1.2 Positional array literal
`[1, 2, 3]` is supported only when all of the following hold:
- elements are positional only
- no explicit keys are used
- the target is an explicit `vector<T>` typed context

#### 7.1.3 Mixed element types
The generator does not need to reject mixed element types early. In explicit `vector<T>` contexts, generation may proceed and C++ compilation is allowed to enforce the type constraint.

Example:

```php
$v /** vector<int> */ = [1, "x"];
```

This is a `negative_compile` test, not a `negative_generate` test.

#### 7.1.4 Nested arrays
Nested arrays are allowed at generation time when written in an explicit nested vector type context.

Example:

```php
$v /** vector<vector<int>> */ = [[1, 2], [3, 4]];
```

Structural/type mismatches are allowed to fail at C++ compile stage.

#### 7.1.5 Append `[]=`
From the test/spec perspective, append is a supported positive behavior only for variables known to be `vector<T>`.

Example:

```php
$v /** vector<int> */ = [];
$v[] = 1;
```

The current generator implementation may be permissive and accept `[]=` on non-vector targets before failing later. That implementation detail does not expand the supported behavior set for generated tests.

#### 7.1.6 Index access
For `vector<T>`, index access supports:
- read
- write

Example:

```php
$v /** vector<int> */ = [1, 2, 3];
echo $v[0];
$v[1] = 5;
```

No associative/keyed-array behavior is implied by this rule.

### 7.2 Foreach

#### 7.2.1 Source type
`foreach` is supported only when the source is `vector<T>`.

#### 7.2.2 Supported forms
Supported:

```php
foreach ($v as $item) {}
foreach ($v as &$item) {}
```

Not supported:

```php
foreach ($v as $k => $item) {}
foreach ($v as $k => &$item) {}
```

The unsupported key/value forms MUST generate negative tests.

#### 7.2.3 By-reference semantics
For `foreach ($v as &$item)`, `$item` refers to the current vector element and mutations inside the loop are expected to affect the original vector.

#### 7.2.4 Scope after loop
Foreach loop variables use an inner loop context following the generated C++ scoping model, not PHP variable leakage semantics.

Example:

```php
$x = 10;
foreach ($v as $x) {}
echo $x; // 10
```

This is a supported positive case. The outer `$x` remains unchanged.

### 7.3 String interpolation

#### 7.3.1 Spec target
String interpolation is considered an intended supported feature set, but it is not fully implemented yet. Tests for it MUST currently be classified as `known_gap` / `todo` until the implementation lands.

#### 7.3.2 Intended surface
The intended supported surface is broad and currently includes, at spec level:
- simple variables: `"$a"`, `"{$a}"`, `"${a}"`
- property access
- index access
- static access
- function and method calls
- general braced expressions
- nested combinations

#### 7.3.3 Conversion semantics
Interpolated expressions MUST use the same standard cast-to-string behavior used by the `.` concatenation operator. Tests and future lowering rules MUST assume explicit project string casting, not ad-hoc C++ implicit conversion.


---

## 21. Locked Phase-1 Tree Basis

The current phase-1 corpus is organized around language areas first, then level.

Locked phase-1 top-level buckets:
- `types`
- `variables`
- `constants`
- `operators`
- `casts`
- `control_flow`
- `functions`
- `classes`
- `namespaces`
- `use`
- `references`
- `output`
- `integration`
- `known_gap`

Within `types`, the current phase-1 sub-buckets are:
- `int`
- `float`
- `bool`
- `string`
- `nullable`
- `vector`

Level generation for the current phase-1 pass is locked as follows:
- generate actual files for `level_01`
- keep `level_02` and `level_03` as planned follow-up work
- keep implementation-missing but spec-supported items under `known_gap`

Current `level_01` generation scope:
- `types/int`
- `types/float`
- `types/bool`
- `types/string`
- `types/nullable`
- `types/vector`
- `variables`
- `constants`
- `operators`
- `casts`
- `control_flow`
- `functions`
- `classes`
- `namespaces`
- `use`
- `references`
- `output`
- `known_gap/interpolation`

Notes:
- not every type must be paired with every operator
- each type bucket should cover init, assign, relevant casts, and relevant operators only
- control-flow coverage should include one straightforward readable sample per supported construct in `level_01`
- class, namespace, and `use` samples should stay minimal and self-contained
