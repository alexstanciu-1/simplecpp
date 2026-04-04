# Runtime Config Validation Tools

This directory contains CLI tools for validating `runtime/specs/config.json` and auditing runtime drift against it.

## Tools

### `bin/validate_config.php`

Validates the machine-readable runtime contract.

Checks currently include:
- required config sections
- explicit `mixed_t` boundary-bridge booleans
- duplicate casts
- duplicate assignments
- duplicate operator signatures
- forbidden/allowed operation overlap
- derived compound-assignment policy consistency
- condition policy sanity
- generator/runtime boundary overlap for generator-owned operations

Usage:

```bash
php tools/runtime_config/bin/validate_config.php
php tools/runtime_config/bin/validate_config.php --json
php tools/runtime_config/bin/validate_config.php runtime/specs/config.json
```

Exit codes:
- `0` = config passed validation
- `1` = config ambiguity or inconsistency detected
- `2` = fatal tool/runtime error

### `bin/audit_runtime_vs_config.php`

Audits runtime code drift against `runtime/specs/config.json`.

Usage:

```bash
php tools/runtime_config/bin/audit_runtime_vs_config.php
php tools/runtime_config/bin/audit_runtime_vs_config.php --json
php tools/runtime_config/bin/audit_runtime_vs_config.php --strict
```

Exit codes:
- default mode: always `0` unless the tool itself fails
- `--strict`: returns `1` when drift is found
- `2` = fatal tool/runtime error

## Intended flow

1. update `runtime/specs/config.json`
2. run `validate_config.php`
3. review required runtime changes with `audit_runtime_vs_config.php`
4. implement runtime changes only after config is accepted
