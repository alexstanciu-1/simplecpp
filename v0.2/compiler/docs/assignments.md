# Existing-local assignment

Doc Status: supporting

The [source contract](../../../specs/compiler_scalars_v0_2.md#local-assignment-extension)
defines plain `=` for existing locals in all flavors. No new grammar or type matrix
was needed. Preparation uses each destination's generated
`prepare_conversion(value, conversion_context::assignment)`; lowering consumes that decision through the
existing generated conversion methods. Current metadata admits identity only for
implicit conversions. Typed declaration initialization still requests explicit intent.

```text
$value int = 3;
echo $value; // 3
$value = 8;
echo $value; // 8
return $value;
```

## Structure and ownership

`ast_assignment_statement` now owns two prepared facts: a non-owning local declaration
link and the approved conversion enum. Checked accessors expose readiness; reset
clears both. Its operand already owns value facts. The assignment derives its scalar
result from the prepared operand/conversion instead of storing another payload copy.

`Preparation::$latest_writes` is a transient non-owning index from local ID to its
latest completed assignment. Null means unknown after a reference call; absence means
the declaration initializer still applies.
The RHS is prepared first, then the write is published to this private index.
`ast_name_expression::settle_local()` snapshots the scalar value at this occurrence
from that write or the initializer. Its declaration binding remains unchanged.
Later writes therefore cannot alter earlier reads, and no recursive chain of reads
is needed to obtain the current scalar value. String bytes are never copied into
scalar facts. Local declaration facts continue to describe initialization only.

The index is reset for each body. Mutable reference calls invalidate only passed local
storage after argument evaluation. A later assignment restores facts for ordinary
locals/value parameters. Reference parameters always read as unknown, since they may
alias one another or globals. This retains earlier snapshots without an alias graph.
Braced conditionals clear value knowledge at alternatives and joins. After a barrier,
new declarations and assignments establish fresh facts; outer initializers cannot
be reused accidentally. Loops apply the same conservative barriers around repeating regions and on exit;
more precise value merging remains deferred.

Lowering emits the approved write and any conversion headers without preparing data.
Identity self-writes to the same local emit `(void)local_n;`, a no-op for currently
supported built-in types; this avoids Clang's self-assignment warnings under `-Werror`.
Review this rule before adding observable user-defined assignment behavior.
String copy assignment and destruction remain owned by the existing public runtime.
CLI/panel preparation inspection now includes assignment destination IDs and conversions.

Initializer-based local inference is now supported; see [inference](inference.md).
Direct global writes, compound assignments, member/index writes and assignment expressions remain deferred. There is no new watcher graph or cross-file ownership.

## Verification

`tests/assignments.php` covers all current types and flavors, self-assignment,
implicit-conversion rejection, unchanged initializer/earlier-read facts, updated
range checks, unknown-to-known and known-to-unknown transitions, reset/repreparation,
inspection and lowering purity. A function-body edit reparses only the changed
source and retains the unchanged caller AST while refreshing assignment facts.

`tests/assignments_native.py` reuses the string fixture build harness to verify exact
output and exit status for all flavors, including owning string-copy reassignment,
self-writes and embedded NUL. It does not inject the string-only native mutation probe.

On 2026-11-04: Clang `-O2 -g0`, libc++, Ninja `-j8`, mold, no PCH/LTO, fresh
instrumented app objects and reused runtime archive:

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link command ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 160.096 | 16.784 | 27.969 | 9.611 | 13.052 | 67 |
| php | 124.703 | 10.230 | 7.866 | 2.525 | 3.782 | 64 |
| js | 115.044 | 11.051 | 7.077 | 2.231 | 3.447 | 55 |

Single correctness builds, not repeated-run performance estimates. Nested trace
stages must not be summed. PHP fixture generation and assertions took 59.849 ms
in this native proof. Raw commands, traces and results remain in the untracked
`v0.2/build/assignments-proof-2026-11-04/` directory.
