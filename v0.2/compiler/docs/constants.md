# Typed literal constants

Doc Status: supporting

The [namespace contract](../../../specs/namespaces_v0_2.md#php-first-typed-literal-constants--2026-11-04)
defines the bounded slice. cpp/php use `const int LIMIT = 8;`; js uses
`const LIMIT: int = 8;`. Constants may belong to a file/namespace or struct, have required types,
and accept literal initializers only. Current generated int64/bool/byte/null/float64/
string definitions own admission, range checks, conversions and C++ representation.

## Owners and structure

- `ast_literal_declaration` extracts the common name/type/initializer ownership and
  preparation readiness previously held by `ast_global_declaration`. Globals retain
  their semantics; the new `ast_constant_declaration` supplies `const:` identity and
  derives scalar facts through the approved initializer conversion. String bytes
  remain on the original literal AST; there is no second retained constant-value object.
- `Literal_Declaration_Preparation` replaces `Global_Preparation`, settling both
  kinds in one declaration pass before body work. Every declaration is checked,
  including unused constants; already-prepared unchanged declarations are skipped.
  String globals remain deferred. This worker does not evaluate arbitrary source
  expressions or construct an initializer dependency graph.
- Existing `ast_name_expression` stores a PHP constant-only syntax flag. Its prepared
  binding can select a constant through a stable symbol entry, alongside existing
  local/global bindings. Reads keep ordinary occurrence-specific scalar facts;
  no extra expression node, watcher type or prepared hierarchy is introduced.
- File syntax inventories and singular indexes include constants. Publication and
  duplicate chains use the existing declaring-scope owner. The file's new constant
  import index references original import occurrences. Plain imports participate
  independently in function and constant lookup; no alias symbols are published.

PHP bare names read constants, dollar-prefixed names read storage. cpp/js value
lookup checks visible locals, then exact global storage, then constants. Namespace
and root paths retain exact resolution; imports affect unqualified names only.
Mutable reference binding and assignment to constants reject during preparation.
Parameter defaults, initializer references/operations, inferred/local constants
and text conversions remain deferred.

## Public struct constants

PHP `public const int LIMIT = 2;`, C++ `static constexpr int LIMIT = 2;` and JS
`const LIMIT: int = 2;` inside `Point` publish the same identity, `const:Point::LIMIT`.
The struct indexes constants separately from fields and methods. The file inventories
the same declaration for ordinary publication, duplicate diagnostics and literal preparation.
A qualified read uses the existing literal lowering and adds no instance storage.
PHP distinguishes `Point::LIMIT` from a namespace path in the syntax. C++ and JS
share separators with namespace paths and retain existing namespace value lookup.
JS dotted reads rooted in a visible local or parameter use instance field lookup first.

## Incremental and lowering

The publication contract for a constant consists of written type and literal spelling.
Unlike mutable globals, constant value changes invalidate readers. The existing
body watcher tracks the target's stable entry; removal leaves its tombstone and
reintroduction reuses it. Unchanged reader AST/tokens survive. cpp/js constant reads
also observe a missing global slot so introducing higher-priority storage cannot
leave a stale resolution. Import validation continues checking all occurrences on
each preparation pass, including unused imports and removed targets.

This is linear declaration work plus direct lookup at each read, with no callee/body
scan, constant-expression interpreter, fixed point or new preparation traversal.
Lowering reads the declaration's already-approved literal/conversion and requests
its public representation/conversion headers. It creates no runtime constant storage
or cross-unit linker definition. Existing string-value copying remains intact.

## Mining and verification

Reviewed `compiler/my-try/04_analyze/prepare/semantics/constants.php`, its source
constant tests and the namespace specification. Retained distinct constant identity,
checking unused declarations, exact namespace/import lookup and immutable values.
The donor's inferred constants, recursive initializer evaluator, prepared value
objects, member constants and dependency graphs are not imported. The explicit
type/literal-only boundary is documented as a PHP-first specification amendment.

`tests/constants.php` covers all flavors, forward and qualified reads, generated
conversions/ranges, signed limits, embedded string bytes, null/byte values, mutable
reference rejection, scope/shadowing and global coexistence, aliases/groups/plain
imports, duplicate diagnostics and invalid unused declarations. It checks AST
ownership, inspection, reset/reprepare and emission purity. Incremental tests cover
value changes, type failure/recovery, removal/reintroduction with stable handles,
unrelated changes and no-op reuse while retaining reader AST/tokens. Native proofs
include separate translation units and a mixed-flavor project. Incremental native
emission remains separate from these frontend/preparation checks.


## Recorded proof — 2026-11-04

All 48 focused compiler CTest checks passed. Native verification passed 13 constant
programs (all three flavors, namespace imports, globals/shadowing and a mixed-flavor
project), plus three existing string regression programs. The compiler style check
passed. Raw fixtures, generated C++, traces, results and the regression log are kept
locally under `v0.2/build/constants-proof-2026-11-04/` (ignored build output).

The main constant fixture timings below are single instrumented fresh builds, not
benchmark medians: O2/g0, libc++, Ninja8, mold, no PCH/LTO, prebuilt runtime.
Frontend/LLVM values are Clang trace durations; the linker column is command time.
They do not add to native wall time. PHP fixture generation/preparation and checks
for the full 13-program collection took 72.71 ms; this is not an isolated preparation
stage measurement.

| Flavor | Native wall ms | Clang frontend ms | LLVM backend ms | Link command ms |
| --- | ---: | ---: | ---: | ---: |
| cpp | 185.91 | 19.27 | 16.49 | 75 |
| php | 127.17 | 14.00 | 7.99 | 59 |
| js | 119.47 | 11.53 | 7.64 | 57 |
