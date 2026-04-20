# Coalesce Layer 2 — Implementation Audit

Status: audit only
Scope: `??` / `php::coalesce_eval(...)`
Primary implementation file: `runtime/include/lang/php/support/php_value.hpp`
Reference docs: `specs/conditional_expression_matrix.md`, `specs/operator_matrix/profile_semantics_v1.md`

## Goal

Check the current implementation against the locked Layer 1 coalesce model:
- approved wrapper families for `??`: `nullable<T>`, `result<T>`, `result_or_false<T>`
- approved wrappers auto-unpack to the selected usable value domain
- wrapper carriers are not preserved in the `??` result
- `result_or_bool<T>` is runtime-rejected in v1
- `mixed_t(null)` is a valid selected mixed result domain
- diagnostics should be framed as the **selected branch** lacking a usable value domain, not only the syntactic RHS
- some invalid rows are runtime-only in v1 because the generator is intentionally type-blind

## Summary

The runtime helper is already much closer to the locked coalesce model than the current failing tests suggest.

### Known conforming behavior

1. **Wrapper-state selection is implemented**
	- `detail::coalesce_has_usable_value(...)` uses `has_value()` for:
		- `nullable<T>`
		- `result_or_false<T>`
		- `result<T>`
	- `mixed_t` is considered usable only when it is not PHP-null
	- plain non-wrapper values are always usable

	Relevant implementation:
	- `runtime/include/lang/php/support/php_value.hpp:1567-1579`

2. **Approved wrappers auto-unpack instead of preserving carriers**
	- `detail::normalize_coalesce_branch(...)` throws when a selected approved wrapper still has no usable value
	- otherwise it unwraps approved wrapper payloads into the result domain
	- when the resolved result type is `mixed_t`, approved wrappers normalize to `mixed_t(payload)`

	Relevant implementation:
	- `runtime/include/lang/php/support/php_value.hpp:1582-1610`

3. **`result_or_bool<T>` is runtime-rejected in `??`**
	- `php::coalesce_eval(...)` explicitly throws a deterministic runtime error if either side is `result_or_bool<T>`

	Relevant implementation:
	- `runtime/include/lang/php/support/php_value.hpp:1618-1634`

4. **`mixed_t(null)` is treated as a valid selected mixed result domain**
	- `mixed_t(null)` triggers fallback when it is the branch currently being tested for usability
	- but if fallback selects a `mixed_t(null)` branch, normalization succeeds and the final result can be `mixed_t(null)`
	- this matches the locked Layer 1 wording and explains rows such as:
		- `result_or_false<float_t>.false_sentinel ?? mixed.null -> mixed.null`

## Implementation findings

### Finding 1 — The helper logic is coherent

The current helper effectively implements this rule:
- evaluate left once
- if left has a usable selected value, normalize and return it
- otherwise evaluate/select right
- if the selected branch is an approved wrapper with no usable value, runtime-reject
- otherwise normalize and return the selected branch

This rule is coherent and matches the updated docs better than several existing matrix classifications do.

### Finding 2 — The compile-time result matrix is intentionally more open than the semantic data sometimes assumes

`detail::coalesce_result<...>` does not model a completely closed semantic matrix.
It allows broad result-type formation for several families, especially when `mixed_t` participates.

Examples:
- `mixed_t ?? Right -> mixed_t`
- `Left ?? mixed_t -> mixed_t`
- approved wrapper + matching payload type -> payload type
- matching approved wrappers -> payload type

Relevant implementation:
- `runtime/include/lang/php/support/php_value.hpp:1312-1404`

Practical consequence:
- many rows that the semantic data tries to classify as compile-time rejections cannot realistically be rejected that early in the current architecture
- the actual rejection point is often the runtime helper, not the type-level result matrix

### Finding 3 — The selected-branch rejection is centralized for approved wrappers only

`detail::normalize_coalesce_branch(...)` throws:
- `scpp::php::coalesce_eval(): selected branch has no usable value domain`

but only when the selected branch is one of the approved wrapper families and `has_value()` is false.

Relevant implementation:
- `runtime/include/lang/php/support/php_value.hpp:1586-1589`

This is good because:
- the diagnostic is selected-branch based, not syntactic-RHS based
- both-sentinel wrapper cases reject deterministically

This is also important because:
- `mixed_t(null)` is *not* treated as “no domain” at normalization time
- that is why some current `coalesce_runtime_reject` rows are misclassified in the semantics data

### Finding 4 — `result_or_bool<T>` still has a stale type-matrix specialization

The file still contains:
- `coalesce_result<result_or_bool<T>, Right> -> result_or_bool<T>`

Relevant implementation:
- `runtime/include/lang/php/support/php_value.hpp:1401-1404`

Under the current v1 rule, this specialization is unreachable for valid runtime execution because `php::coalesce_eval(...)` throws before using it.

Assessment:
- **Known:** this specialization is inconsistent with the locked coalesce policy
- **Known:** it is not the primary source of the current observed test failures
- **Probable:** it is a stale leftover from an older carrier-preserving or broader-support model

Recommendation:
- remove or clearly comment this specialization in the implementation cleanup pass
- do **not** treat it as an urgent semantic bug by itself

### Finding 5 — The implementation supports the “selected mixed null” distinction correctly

This is the most important behavioral finding for the current failures.

The implementation distinguishes:
- **wrapper unusable state**
	- `nullable.empty`
	- `result.failure`
	- `result_or_false.sentinel.false`
- **valid selected mixed value that happens to be null**
	- `mixed_t(null)`

This distinction is implemented correctly in the runtime helper.

That means rows of this shape should succeed:
- approved-wrapper unusable state on the left
- `mixed.null` selected on the right

And rows of this shape should reject:
- approved-wrapper unusable state on the left
- approved-wrapper unusable state selected on the right

## Mismatches between implementation and current matrix/test expectations

### Mismatch A — Some rows marked `coalesce_compile_reject` are implementation-incompatible

Because the generator is type-blind and the result matrix is not fully closed, many profile-specific unusable-domain cases cannot be rejected at generation time.

These rows should be reviewed and likely moved to either:
- success rows, or
- runtime-reject rows

but not compile-reject.

### Mismatch B — Some rows marked `coalesce_runtime_reject` are actually successful selected-mixed-null cases

Example family:
- `result_or_false<T>.false_sentinel ?? mixed.null`

Observed implementation behavior:
- success
- result `NULL`

Assessment:
- **Known:** current runtime behavior is internally consistent
- **Known:** classifying these as `selected branch has no usable value domain` is wrong

### Mismatch C — The testing layer is stricter or simply different from the implementation in this subfamily

Current failures are often not telling us “the runtime is wrong”.
They are telling us:
- the semantic data chose the wrong class, or
- the emitter/harness expects the wrong outcome, or
- both

## Recommendations

### Recommendation 1 — Do not redesign runtime first

The runtime helper already matches the locked Layer 1 rule well enough that a large redesign would likely create more churn than value.

### Recommendation 2 — Normalize semantics data around the implementation rule already present

Use this as the working coalesce rule for the next pass:
- runtime reject only when the **selected branch** still has no usable value domain after branch selection
- `mixed_t(null)` is a valid selected mixed result domain, not a no-domain state

### Recommendation 3 — Clean up one stale implementation artifact later

In the implementation cleanup pass, remove or document:
- `coalesce_result<result_or_bool<T>, Right>`

Reason:
- it encodes an older support model and is no longer authoritative

### Recommendation 4 — Fix semantics data before touching test harness logic broadly

Most current coalesce failures are more likely to be caused by bad row classification than by the helper itself.

Order:
1. semantics data cleanup
2. emitter expectation cleanup
3. rerun coalesce buckets
4. only then patch runtime if a genuine behavioral mismatch remains

## Verdict

### Known
- The current coalesce runtime helper is broadly consistent with the updated Layer 1 docs/specs.
- The helper already implements the crucial distinction between wrapper-unusable state and selected `mixed_t(null)`.
- `result_or_bool<T>` is correctly rejected at runtime in `??`.

### Probable
- The main source of current coalesce failures is the semantic/test classification layer, not the runtime helper.
- The remaining implementation cleanup needed for coalesce is small and localized.

### Not yet justified
- A broad runtime redesign for `??`
- Treating `mixed_t(null)` as “no usable value domain” during selected-branch normalization

## Next layer

After this Layer 2 audit, the next pass should target Layer 3:
- `specs/operator_matrix/data/semantics.json`
- `tools/operator_matrix/src/test_emitter.php`
- generated expectation classes for coalesce success vs runtime-reject rows
