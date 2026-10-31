# No-argument void functions

Doc Status: supporting

The [entry/function contract](../../../specs/compiler_entry_v0_2.md) owns semantics.
This slice implements explicit no-parameter `void` definitions, statement calls,
empty bodies and bare returns in all flavors, including cross-file calls.
PHP remains sequential; incremental reuse is not implemented.

```text
PHP/JS: function ping(): void { return; }
C++:    void ping() { return; }
Call:   ping();
Entry:  ping(); return 8;
```

Definitions can appear after the entry's return because they are not executable
statements. A void body may fall through. It may use the existing integer local
syntax, but cannot read entry locals or another body's locals. Parameters,
arguments, overloads, methods, inferred/non-void return types and value-position
calls remain outside this slice. Explicit function definitions do not execute
merely because their source is included.

## Ownership and flow

- `03_parse/structures/functions.php`: stable header identity, private body syntax
  and declaration-owned return type facts plus attached body facts. Source syntax stays readonly;
  source-owned completion slots change only in their owning phase.
- `03_parse/parser.php`: builds one keyed `ast_file_node` declaration collection
  and notifies discovery using those exact function nodes. Implicit private `main`
  is first when top-level statements exist; no empty entry is invented.
- `03_parse/collect/`: body-local declarations and reference occurrences, retained
  by each function body, including implicit `main`.
- `compiler/symbols.php`: coordinator-owned pending candidates. Discovery reads
  initialized header identity only, before the body is complete. Publication checks
  candidates against the completed source inventory; failed parses cannot publish.
- `04_analyze/prepare/functions.php`: cross-file duplicate/type validation and settled return types
  on the function declarations first, followed by source-owned body preparation. Calls link directly to declarations and consume only
  settled signature fields, so forward, self-recursive and mutually recursive calls work.
- `04_analyze/prepare/prepare.php`: one shared statement-preparation path for entry
  and void bodies, with separate local environments and explicit return rules.
- `05_backend/cpp/`: completed body handles supply local definitions and needed
  prototypes. Foreign bodies are never copied into the caller's translation unit.

Discovery uses direct PHP calls, not serialized transport. Each compilation gets a
new publication object; re-preparing an in-memory collection clears the owning
nodes' previous type/body/call facts before declaration work. Saved handles reference
those same declarations; they are not immutable snapshots across reset. Incremental
invalidation and concurrent reader retirement are not implemented. PHP object identity used to validate notification
membership is invocation-local and is not a durable incremental symbol ID.

Backend names are `scpp_function_` plus the hexadecimal bytes of the source function
name. This is injective, avoids C++ reserved/user-entry collisions and stays stable
under source-root relocation or inventory reordering. Prototypes are gathered from
the source's own definitions/calls and sorted. There is no shared lowering registry.
Integer locals use `[[maybe_unused]]`, allowing valid unused locals in void bodies
under warning-as-error native builds.

## Donor concepts mined

- [Callable AST](../../../compiler/my-try/03_parse/structures/structures_declarations.php):
  signature syntax and executable body have separate ownership; attached facts
  describe the corresponding semantic part.
- [Prepared callable/call records](../../../compiler/my-try/04_analyze/prepare/data/structures.php):
  signatures complete before bodies; calls retain selected target/signature facts.
- [Preparation model](../../../compiler/my-try/04_analyze/prepare/README.md):
  body workers consume completed contracts without re-entering declaration preparation.

These ideas are retained without the donor's incremental scheduler, subscription
graphs, materialization rounds, shared-publication locks or converter machinery.

## Verification

The retained mixed-flavor example can be emitted with:

```sh
php v0.2/compiler/main.php program v0.2/compiler/examples/functions main.php.scpp v0.2/build/clang/native-layout/facts.json v0.2/build/functions-example php main.php.scpp cpp support.cpp.scpp js support.js.scpp
```

Build the exact translation units in its manifest as shown in the multifile guide.
Focused checks:

```sh
php v0.2/compiler/tests/functions.php
python3 -B v0.2/compiler/tests/functions_native.py
```

Checks cover discovery before body completion, rejection of partial publication,
exact identity binding, attached facts, independent locals, forward/self/mutual
recursion, signature-only lookup, duplicate/unknown functions, invalid returns,
unsupported forms and declaration-only secondary sources. Native tests compile and
run all flavors with exit 8, including mixed-flavor cross-file calls. Reordering and
relocation preserve output; failures preserve previous bundles and create no new
publication. Lowering leaves retained semantic facts unchanged. A body-only source change changes only its generated unit, which is an
output-boundary proof, not evidence of incremental compilation.

The next incremental slice can use these explicit signature/body boundaries to
decide when caller preparation can be reused. No caching or invalidation policy is
introduced here.
