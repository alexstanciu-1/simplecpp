# Active scalar completion audit

Doc Status: supporting
Date: 2026-11-06

The bounded goal is complete for the active null, bool, int64/int, uint8/byte,
float64/float and string types. This is not completion of the entire language catalog.
The admission checklist and user decisions remain in
[the goal plan](../../specs/planning/scalar_completion_goal.md).

## Behavior and proof

| Requirement | Implementation / evidence |
| --- | --- |
| String ordering and string-only concatenation | Generated binary plan; all-flavor byte/NUL/high-byte oracles, reference effects, compounds, and provider-driven incremental recovery in tests/string_equality.php |
| Ordinary null equality | Same-type metadata admission; heterogeneous rejection, both-operand effects and signature-error recovery in the same proof |
| Strict identity | Canonical RHS identity, same-type value comparison; all scalar pairs, aliases, NaN/infinity/signed zero and changing provider types in the same proof |
| Numeric/string spaceship | Integer-domain and floating promotion; byte pairs, rounding above 2^53, NaN errors and complete byte ordering; provider type changes compared with fresh compilation |
| Eager xor | Generated condition admission/lowering, both operands evaluated; tests/logical.php checks all truthiness types, effects/failures and retained-session recovery |
| Canonical int64 power | Metadata binding and checked runtime algorithm; tests/power.php plus independent wider-integer oracle, precedence, signed boundaries, compounds and recovery |
| Preserve prior numeric groups | Broad compiler regressions plus dedicated bitwise/family/conversion/operator proofs |
| Efficient preparation | Existing AST facts, one operand traversal, direct generated admission; no metadata lookup, conversion search or new preparation pass |
| Incremental/native ownership | No changes to symbol/watch/queue/session ownership or join gates; existing bodies own facts and lowering remains read-only |
| Deferred scope remains excluded | Generated registry remains the six active identities; tests reject unsupported power families, null truthiness, heterogeneous null equality and text conversion |

The final combined comparison proof has 45 native fixtures in
build/scalar-final-comparisons-proof-2026-11-06. Xor has 18 in
build/logical-xor-proof-2026-11-06-final; power has 12 in
build/power-proof-2026-11-06. All compare outputs/exit codes; retained-session
tests compare recovered native artifacts with fresh compilation. The earlier
bitwise proof has 24 native fixtures and remains covered by final compiler checks.

## Structure changes

- Existing binary/unary enums gain operation identities; no new AST classes or fact fields.
- Parser precedence handles right-associative power and low-precedence xor;
  assignment_value() requires grouped xor RHSs as agreed.
- Generated binary_syntax_allowed() owns flavor-specific concatenation admission.
- supports_compound($operator) makes compound permissions operator-specific.
- lower_cpp_binary() receives the prepared RHS type for canonical strict identity.
- New metadata groups feed the shared binary/promotion generator. Generated header
  lists union every required public binding per operator.
- The minimal arithmetic API adds checked shift helpers, float64_compare and
  int64_power. Runtime generation and consumers were rebuilt together.

## Final validation

The broad non-native compiler run covered 76 checks. Four stale rejection tests
were corrected for previously admitted byte/string behavior and passed on rerun.
The frontend-service test initially failed because the sandbox disallows Unix
sockets; it passed outside that restriction. The other 71 checks passed initially.

Nine native regression suites passed after the final lowering/header changes,
including reference parameters, logical operators, unary/arithmetic/compound
operations and incremental executable builds. The final expanded comparison proof
also passed after adding a dependency-bearing string-spaceship occurrence.
Style, generator cleanup, metadata generation, runtime freshness and public-boundary
checks passed. Logs are retained under build/scalar-goal-validation and the
individual proof directories.

Snapshot capture retained 19 accepted/537 rejected scalar cases, 130/247 runtime
cases and 17/37 CLI cases. Review found only the intended arithmetic-header additions
and metadata input hashes; other scalar artifact multisets and runtime artifacts
were unchanged. Updated snapshots are present in the worktree, not committed.

## Performance and remaining debt

The [five-run benchmark report](../../samples/capability-benchmarks/benchmark-2026-11-06-scalars.md)
and adjacent JSON retain PHP stages, native wall time, Clang frontend/LLVM/link work,
incremental checks, versions and hashes. Samples are unchanged frozen workloads,
not exhaustive new-feature coverage. All 30 measured/traced executables passed.

Average PHP/native wall milliseconds: small 5.322/142.728, medium 64.733/325.730,
large 915.204/1925.458. Large preparation averaged 269.765 ms, about 29% of measured
PHP work. These are observations, not a controlled speedup claim.

Deferred: optional widths, char, text conversions, nullable/carrier types,
user-defined overloads, collections, expression-valued updates/assignments, new
writable locations and the broader execution-order redesign. Power uses the
user-approved standard-exception code strings; structured error fields remain
[explicit debt](power.md#structured-runtime-error-debt).

No commit or push was made for this goal.
