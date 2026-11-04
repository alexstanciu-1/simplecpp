# Initializer-based local inference

Doc Status: supporting

The [source contract](../../../specs/compiler_scalars_v0_2.md#initializer-based-local-inference)
owns the rules. The supported declaration forms are:

```text
PHP: $value = 8;
C++: auto value = 8;
JS:  let value = 8;
```

The initializer is prepared once and its canonical type becomes the local's fixed
type. No host-PHP inference or C++ `auto` is used in emitted storage. Literal integers
infer int64, floats float64, booleans bool, strings string; a local/global reference
preserves its resolved type. In particular a byte reference infers byte, but the
integer literal 8 does not. Inferred initialization requests implicit conversion;
written types retain their existing explicit-intent conversion behavior.

Null cannot infer a local type, whether written as a literal or read from null-typed
storage. Explicit `null` locals still work. Subsequent assignments do not widen the
type. Declaration visibility begins after successful initialization, so self reads
and forward local reads reject. Known/unknown value facts continue through the
existing local/assignment paths; no extra prepared record is introduced.

## Collection and structure changes

`Body_Collection` now has `local_names`, a transient non-owning name-to-first-local
index, and `has_local()`. Its existing `Scope_Collection` still owns registration,
publication and duplicate linking. The additional index never owns duplicate links.
It is populated only after a statement has been parsed; it does not scan ahead.

The statement grammar receives the current body collector explicitly. For PHP, an
untyped bare write is parsed directly into `ast_local_declaration(type_name: null)`
when the name has not yet been declared in this body. Otherwise it becomes the
existing assignment node. This avoids temporary assignment nodes, child reparenting
or an extra normalization traversal. Explicit declarations still register normally
and duplicate diagnostics remain unchanged. C++/JS declaration syntax already
identifies inferred locals independently of this query.

Implicit main and each function have separate collectors; interleaved function
parsing cannot replace the main collector. Global declarations do not populate the
body index. A first PHP bare write therefore declares a local even when a global
has the same name. It does not modify global storage, and its initializer cannot
read that new local before initialization. No global symbol publication is needed
to classify it.

Preparation resolves or infers the type and uses existing generated type/conversion
handlers. AST `type_name` remains null for inferred declarations; only prepared
`type_def` changes. No new AST class, retained inference descriptor, metadata matrix,
watcher graph or lowering path is added.

## Deferred nullable inference

**Debt:** a future version may infer a type from subsequent assignments and make
an initially-null local nullable. That requires a separate decision about multiple
writes, control-flow paths, incompatible types, loops and incremental invalidation.
For now `$value = null; $value = 8;` rejects at the initializer. Do not infer from
later writes or silently introduce nullable storage. This slice does not change
existing explicit-global literal inference or implement nullable types.

## Verification

`tests/inference.php` checks all flavors, literal/reference types, byte inference,
null rejection, fixed-type assignments, duplicate/self/forward diagnostics, source
AST shape, pure lowering, reset/repreparation, function/main isolation and PHP local
over global classification. A global int-to-float edit re-prepares the observing
inferred local without reparsing its unchanged source. `tests/parser.php` also
verifies first-write versus subsequent-write shape.

`tests/inference_native.py` builds all flavors and verifies function-body inference,
source-order assignments, string copies/NUL bytes, exact output and exit status.
On 2026-11-04 it used Clang `-O2 -g0`, libc++, Ninja `-j8`, mold, no PCH/LTO,
fresh instrumented app objects and a reused runtime archive:

| Flavor | Native wall ms | Frontend ms | LLVM backend ms | Optimizer ms | Codegen ms | Link command ms |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| cpp | 192.007 | 23.035 | 21.586 | 8.540 | 9.688 | 75 |
| php | 121.560 | 11.840 | 8.615 | 2.964 | 4.268 | 58 |
| js | 165.403 | 16.911 | 10.042 | 3.304 | 5.232 | 83 |

Single correctness builds, not repeated-run performance estimates. Trace stages
are nested and must not be summed. PHP fixture generation/assertions took 64.017 ms.
Raw commands, traces and results are in untracked `v0.2/build/inference-proof-2026-11-04/`.
