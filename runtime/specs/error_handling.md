# Runtime Error Handling (v1)
Doc Status: normative
## Overview

The Prism++ runtime supports two error output modes:

1. Human-readable (default)
2. Structured JSON (opt-in via environment variable)

This allows:
- clear developer-facing messages
- stable machine-readable diagnostics for tests

---

## Environment Controls

### JSON output
SCPP_ERROR_FORMAT=json

### Debug trace
SCPP_DEBUG_TRACE=1

---

## Output Formats

### Default
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
			"scpp::php::coalesce_eval",
			"scpp::__scpp_main",
			"main"
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

---

## Rules

- JSON is opt-in only
- Tests must rely on `code`, not message
- Trace is debug-only and not stable
- Not all errors must be structured (incremental rollout allowed)

---

## Coalesce Error Codes

- coalesce_selected_branch_has_no_usable_value_domain
- coalesce_reject_result_or_bool
