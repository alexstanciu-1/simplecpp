# Concrete indexing, append and removal
Doc Status: planning

Date: 2026-10-19. Candidate: `f34ea284` plus this commit's runtime/backend changes.

The approved [runtime interoperability contract](../../../../runtime/specs/concrete_type_interoperability.md)
lets concrete integer keys use existing hash/equality, width/sign and append behavior.
`CPP_Concrete_Index` publishes demanded non-template overloads of runtime indexing/path
helpers. Existing read, write and keyed-path emitters retain evaluation and resolution
ownership. Existing fragment requests and type bindings supply all physical types;
there is no new AST, semantic registry or engine container-family branch.

## Proof

```sh
python3 compiler/my-try/tests/prelower_substitution.py --indexing --results /tmp/prelower-index-verified-2
python3 compiler/my-try/tests/prelower_expressions.py --results /tmp/prelower-index-legacy-expressions
```

Both passed: 143.97s and 156.56s respectively. The shared runner used CLI fallback
because this sandbox disallows PHP-FPM socket binding. Builds use no PCH and `-g0`.
The native compiler itself was not rebuilt.

The compiler emits source indexed reads/writes, direct/nested append, integer-key
hash writes, and direct/nested `unset`. The inferred `$read` variable requires no
annotation or changed inference when representations change. Tests assert:

- Exact key/RHS trace `1,4,2,1,1,1,4,1,1,3` across the write/append/removal scenarios.
- Nested writes use the root after RHS replacement; the copied source remains unchanged.
- Nested removal skips the second key for a missing prefix and re-resolves the prefix
  after a later key expression replaces the root; it preserves the replacement's entry.
- Internal probe/descent references point to actual stored elements and measure zero
  allocation/copy/move activity. No escaping source element-reference feature is added.
- Hash append chooses key 8 after key 7, chooses 0 with only negative keys present,
  and throws on maximum-key overflow. The edge checks are outside allocation counting.
- Three throwing-payload-copy points preserve the expected prior key/RHS side effects,
  do not execute the later map mutation, and leave payload lifetimes balanced.

Each direct/concrete pair has exactly identical counters within its configuration:

| Scenario | Allocations | Bytes | Frees | Constructed | Copied | Moved | Destroyed |
|---|---:|---:|---:|---:|---:|---:|---:|
| Indexing, O0/O2 | 61 | 3723 | 61 | 5 | 25 | 10 | 35 |
| Indexing, ASan/UBSan | 61 | 3723 | 61 | 5 | 25 | 11 | 36 |
| Indexed references | 0 | 0 | 0 | 0 | 0 | 0 | 0 |
| Injected copy failure 0 | 9 | 1000 | 9 | 3 | 8 | 1 | 10 |
| Injected copy failure 1 | 9 | 1000 | 9 | 3 | 10 | 1 | 11 |
| Injected copy failure 2 | 10 | 1512 | 10 | 3 | 11 | 1 | 12 |

The sanitizer build disables optional copy elision. It adds the same source-return
move/destruction to both variants; parity is checked pairwise, not by requiring
identical counts across different compiler settings. There are zero extra measured
boundary allocations, copies or moves. Existing member/record/reference/copy-failure
scenarios remain active and retain their previous direct/concrete parity.

No-op fragment replay, reversed demand order, member-demand removal/restoration,
method-body edits and lowering-option switching pass. Warm builds compile/link nothing.
A body edit compiles one application object and reuses five objects and three layouts;
provider alignment edits rebuild affected layouts; restoration reuses all objects and
layouts without linking. See [summary.json](summary.json).

The old ABI expression proof also passes, including nested mutation and its original
counters; see [legacy-summary.json](legacy-summary.json). Three existing native hash
regressions pass: find/at/remove/append, strict key domains, and fixed-width key domains.
They link the runtime core. The portable-PHP checker passes all seven touched backend
PHP files. Commands/results are in [runtime-checks.json](runtime-checks.json).

Scope remains bounded: this does not establish all ownership-wrapper element/key
interfaces, mixed/dynamic composition, compound indexed updates, probes or initializer
coverage, whole-accounting coverage, a native compiler rebuild, or a new compile-speed
ratio. Standard runtime-error stack allocations are not compared by the payload-copy
failure tests. Earlier comparison paths remain available.
