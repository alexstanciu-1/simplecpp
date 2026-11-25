# Runtime Error Handling (v1)
Doc Status: normative
## Overview

The Prism++ runtime supports two low-level error output modes:

1. Human-readable (default)
2. Structured JSON (opt-in via environment variable)

This allows:
- clear developer-facing runtime messages
- stable machine-readable diagnostics for tests
- higher-level CLI tooling to recover source-mapped diagnostics from structured runtime payloads

---

## Environment Controls

### JSON output
SCPP_ERROR_FORMAT=json

### Debug trace
SCPP_DEBUG_TRACE=1

When JSON output is enabled, `SCPP_DEBUG_TRACE=1` adds debug-only trace frames to the structured runtime payload. Higher-level CLI commands may remap those frames back to source locations and present a source-first view.

---

## Output Formats

### Default runtime stream
operator ?? selected a branch with no usable value

---

### JSON (basic)

{
	"error": {
		"message": "operator ?? selected a branch with no usable value"
	}
}

---

### JSON (structured)

{
	"error": {
		"message": "operator ?? selected a branch with no usable value",
		"code": "coalesce_selected_branch_has_no_usable_value_domain",
		"component": "php::coalesce_eval",
		"operator": "??"
	}
}

---

### JSON (debug)

{
	"error": {
		"message": "operator ?? selected a branch with no usable value",
		"code": "coalesce_selected_branch_has_no_usable_value_domain",
		"component": "php::coalesce_eval",
		"operator": "??",
		"details": {
			"selected_branch": "rhs"
		},
		"trace": [
			"scpp::php::coalesce_eval at /path/to/.prism/generated/main.cpp:42",
			"scpp::__scpp_main() at /path/to/.prism/generated/main.cpp:88",
			"main at /path/to/runtime/bootstrap.cpp:12"
		]
	}
}

---

## Stability Contract

Stable:
- code
- operator
- component

Not stable:
- message
- details
- trace

The `trace` field is intentionally debug-only and may contain generated C++ locations, runtime-internal frames, or host-specific symbolization details.

---

## Rules

- JSON is opt-in only
- Tests must rely on `code`, not message
- Trace is debug-only and not stable
- Not all errors must be structured (incremental rollout allowed)

## CLI presentation note

The public `scpp run` / `scpp error` experience may present a source-first remapped view built from the structured runtime payload. Current CLI behavior prefers:

- source file + line when generated locations can be remapped
- a tiny source snippet around the failing source line
- source-mapped trace frames only
- deeper generated/runtime trace detail through `scpp full-error`

This higher-level CLI presentation is downstream of the runtime JSON contract above.

---

## Coalesce Error Codes

- coalesce_selected_branch_has_no_usable_value_domain
- coalesce_reject_result_or_bool
