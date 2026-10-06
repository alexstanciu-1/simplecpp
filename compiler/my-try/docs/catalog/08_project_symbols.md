# 08. Files, namespaces and symbol resolution
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires declarations and calls. Begin with cross-file functions; object-dependent cases wait for the later chapters.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Implementation roadmap

The [multi-file output and incremental-build roadmap](../planning/incremental_cpp_artifacts.md)
records the agreed direction, ownership boundaries, staged work and runtime-PCH
requirements. Start with stable identities, explicit entry selection and cross-file
functions; preserve narrow declarations and separate implementation units. Namespace
and import groups follow on the same output architecture. The output-consumer
migration, entry selection and cross-file partitioning are implemented. Shared
preparation owns entry admission; the backend emits narrow callable/type headers,
per-source implementation groups and one entry. See the roadmap's dated evidence.
Bare-record declaration-versus-complete-type dependencies are implemented; runtime
template payloads remain conservatively complete. Content-aware disk publication is
implemented with ownership checks, locking and failed-revision recovery; see its
[protocol](../architecture/output_publication.md). Native object/link caching
and persistent runtime-only PCH reuse have a bounded implementation; see
[native build ownership and limits](../architecture/native_cpp_build.md). The bounded
broader invalidation matrix and build-phase timings are verified. Validation-overhead
optimization is deferred to [Chapter 15](15_blocking_debt.md#ch15-native-cache-perf-01--native-cache-validation-overhead).
Namespace/import semantics are agreed in the [v0.2 contract](../../../../specs/namespaces_v0_2.md).
The [identity/body ownership migration](../planning/namespace_identity_migration.md)
has completed its bounded implementation: canonical identity/backend consumers and explicit
executable-body work links are implemented. Source-body inventory, preparation,
entry selection, retirement and C++ consumers share the new model. Implicit global,
semicolon/braced named bodies and retained reconciliation are implemented with
unqualified current-namespace lookup. Qualified references now share exact
rooted/current-relative lookup and retained miss observations for supported calls,
named types and registered constants. Body-local plain/function/constant imports,
aliases and homogeneous groups are implemented. Plain imports expose each supported
symbol kind independently, without namespace aliases or re-export. Scalar source
constant declarations are implemented through the same lookup and declaration-work
model. New class/static-member capabilities and qualified runtime aliases remain
outside this bounded pass.
Imported cards below are not implementation evidence; their obsolete import restrictions
do not override the agreed contract or newer generator rules.

## Remaining-card reconciliation — 2026-10-06

Review against the existing namespace/import fixtures and the agreed v0.2 contract,
updated after the scalar-constant slice below. Historical examples
and emitted C++ below remain unchanged. The progress table and this reconciliation
supersede their old “Pending discussion” placeholders only within the stated bounds.

- **Covered foundation:** semicolon/braced/global bodies, reopening, current/rooted/
  relative lookup, body-local imports, entry-body locals and separate artifacts.
  `tests/namespaces.php`, `tests/imports.php` and their `namespaces.py` execution
  harness provide the bounded evidence. An adapted fixture does not establish every
  operation in an imported example (for example, its exact `echo` or class call).
- **Source constants implemented:** `NS-SYMBOL-PATH-001` and `NOTE-025` now have
  scalar declarations, initializer preparation, cycle checks and incremental edits.
  Broader constant expressions remain explicit debt, not implicit runtime evaluation.
- **Chapter 10 prerequisites:** all `CLASS-NS-*` and `CLASS-STATIC-NS-*` cards,
  plus the class portions of `NS-EXEC-003`, `NS-MULTI-BLOCK-001` and `RESOLVE-001`.
  Reuse namespace identities/imports; develop class and struct together as recorded
  in [Chapter 10](10_objects.md). Existing struct proofs do not complete class cards.
- **Contract differences, not missing legacy behavior:** `NS-EXEC-004` conflicts
  with the one-executable-body rule. `CLASS-NS-004` must use `X` or rooted `\A\B\X`
  to name that namespace's X; relative `A\B\X` starts under the current namespace.
  Source include/require cards and `NOTE-019` do not define project composition:
  see the [project contract](../../../../specs/project_build_v1.md#project-dependencies).
  `tests/source_constants.php` verifies non-admission of all four inclusion forms
  and the exact semicolon multiple-execution case. Diagnostic wording may still be
  improved; old textual `.php` → `.hpp` lowering is not adopted.
- **Deferred extension requiring discussion:** nested namespace declarations
  (`NS-EXEC-005/006`), distinct from already supported multi-segment namespace paths.
  Persistent namespace-variable storage is not enabled; `NS-VAR-001` describes
  ordinary locals in the selected executable body, not shared namespace state.
- **Split prose:** `NOTE-012` combines existing local-declaration rules and function
  duplicate rejection with deferred method rules. `NOTE-025` combines delivered
  namespace/import resolution with bounded source constants and deferred members.
  `NOTE-031`, `NOTE-051` and `NS-GLOBAL-EXPLICIT-001` describe old backend spelling;
  current artifact identities and `entry.cpp` own emission, not literal reproduction
  of `scpp::main`. `NOTE-051`'s interpolation discussion belongs to expression work,
  not a namespace completion requirement.

Keep native-cache validation overhead and cleanup/IDE-observability performance
with their existing Chapter 15 owners. The latest whole-native-compiler checkpoint
predates Chapter 08; generated-program execution is not a native compiler rebuild.
The bounded positive-pass conclusion below does not complete those deferred extensions.

## Scalar source constants — 2026-10-06

Agreed slice: `const NAME = initializer;` in global/named source bodies; inferred
bool/int/float/string; literal/reference initializers and numeric signs; forward and
cross-file references; early duplicate/missing/cycle/unsupported-initializer errors.
Integer constants also feed switch/match labels. Class constants remain Chapter 10.
The [language contract](../../../../specs/namespaces_v0_2.md#source-constants--bounded-v02-slice)
records limits, including unchanged parameter-default admission.

`constant_target_i` unifies registered/source lookup while `constant_value` carries
settled immutable values. Source AST and collected identities use the existing
declaration scheduler and cleanup protocol. Preparation owns evaluation and dependency
tracking; parsing only retains syntax/identity. Backend scalar emission is shared
with ordinary literals and substitutes prepared values without global storage,
constant headers or runtime initialization order. Numeric text never passes through
host PHP arithmetic; string values preserve decoded bytes.

Proof: `tests/source_constants.py` checks PHP behavior and generated C++ programs,
including exact integer bounds, string bytes, floating signs, body-local imports,
transitive value/type edits, equal-value no-op propagation, missing-name addition,
namespace move, deletion, cycle/parse recovery, token cleanup and syntax purity.
Evidence: `/tmp/my-try-constants-final-20261006-1/summary.json` on `2fdeaf44` plus
this slice. Compiler portability conversion and native compiler build are not claimed.

## Positive-pass conclusion — 2026-10-06

Chapter 08's bounded frontend + C++ S2S pass is concluded: multi-file artifacts,
entry selection, publication/native-cache baseline, canonical namespace bodies,
qualified lookup, local imports and scalar constants. Unsupported/deferred rows
remain explicit and do not claim their imported class examples passed.

Remaining debt and prerequisite owners:

- **CH08-CONST-02:** broader constant expressions and initializer families, typed/
  grouped/local declarations, and constant-valued parameter defaults. Discuss before
  expanding the evaluator; do not silently delegate admission to C++.
- **CH08-NESTED-01:** nested namespace declaration syntax (`NS-EXEC-005/006`),
  including its execution implications, remains disabled pending discussion.
- **CH08-DIAG-01:** improve dedicated diagnostics for disabled include/require and
  nested forms; current early rejection is tested, exact messages are not promised.
- Class/static-member cards and deferred persistent namespace storage retain their
  later feature/discussion gates; class and struct work proceeds together in CH10.
- Cache-validation and cleanup/IDE performance retain their Chapter 15 debt owners.

User milestone: **no native compiler build for Chapter 08**. Proceed to Chapter 09
discussion/implementation and aim to finish its bounded pass before requesting the
combined native checkpoint. Existing generated-program execution does not certify
the compiler's native portability. No new LLVM semantics are enabled.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| CH08-CONST-01 | implemented-bounded | `const LIMIT = 7; const COPY = LIMIT;` | verified | verified | explicitly rejected | `tests/source_constants.py`: scalar values, imports, labels, incremental recovery and non-admission boundaries |
| CH08-IMPORTS-01 | implemented-bounded | Plain/function/constant imports, aliases and homogeneous groups | verified | verified | explicitly rejected | `tests/imports.php`, `tests/namespaces.py`: kind preservation, body/file isolation, validation of unused imports, retargeting and recovery |
| CH08-NS-LOOKUP-01 | implemented-bounded | Rooted/current-relative calls and qualified named types | verified | verified | deferred | `tests/namespaces.py`, `tests/qualified_lookup.php`: authored paths, kinds, misses, additions, moves, ambiguity and subscription cleanup |
| CH08-PROGRAM-01 | implemented | Explicit entry + cross-file functions | verified | verified | deferred | `tests/project_cpp.py`: admission, retained edits/recovery, relocation, separate units with/without PCH |
| CH08-DEPS-01 | implemented-bounded | Record references, values, fields and inferred copies across files | unchanged | verified | deferred | `tests/project_cpp.py`: standalone headers, layout-edit isolation, clang transitive dependencies; template payloads conservatively complete |
| CH08-PUBLISH-01 | implemented-bounded | CLI `--output` / optional coordinator publisher | unchanged | verified | deferred | `tests/output_publication.py`: preservation, ownership, recovery, locking and direct published-unit execution; native adapter/facade proved, whole native compiler not rebuilt |
| CH08-NATIVE-01 | implemented-bounded | Native objects, runtime PCH and exact-member linking | unchanged | verified | deferred | `tests/native_cache.py`: real Clang cold/warm/edit, failure recovery, runtime invalidation and cache repair; core runtime only |
| CH08-REBUILD-01 | verified-bounded | Body/default/signature/layout edits, insert/delete, relocation and compiler identity | unchanged | verified | deferred | `tests/native_invalidation.py`: ten executed revisions, exact native rebuild sets, phase timings; optimization deferred to Chapter 15 |
| CH08-NS-IDENTITY-01 | implemented-model | Canonical namespaces and qualified definition identities | model verified | verified | deferred | `tests/namespace_identity.py`: supplied namespace scopes, same-short-name type/function collision proof; authored syntax proof below |
| CH08-NS-BODIES-01 | implemented | Separate namespace-body occurrences and one selected executable body | verified-bounded | verified | global single-body only | `tests/namespace_bodies.py`: independent bodies, entry admission, stale-output rejection and retirement |
| CH08-NS-PARSE-01 | implemented-bounded | `namespace A;` / `namespace A { ... }` / `namespace { ... }` | verified | verified | rejected for named/multiple bodies | `tests/namespaces.py`: reopening, same-namespace cross-file lookup, distinct qualified definitions, no-op/edit/move/recovery |
| [CLASS-STATIC-NS-003](#class-static-ns-003) | deferred-prerequisite | `$x = new X(); return $x::make();` | unverified | unverified | deferred | CH10 class/struct and method work; namespace foundation is available |
| [CLASS-STATIC-NS-002](#class-static-ns-002) | deferred-prerequisite | `namespace A; function f() { return \A\X::make(); }` | unverified | unverified | deferred | CH10 class/struct and method work; namespace foundation is available |
| [NS-EXEC-006](#ns-exec-006) | deferred-discussion | `namespace A { compute(1); namespace B { compute(2); } compute(3); }` | unverified | unverified | deferred | CH08-NESTED-01; early rejection tested; multi-segment paths remain supported |
| [NS-EXEC-005](#ns-exec-005) | deferred-discussion | `namespace A { compute(1); namespace B { class X {} } compute(3); }` | unverified | unverified | deferred | CH08-NESTED-01; early rejection tested; multi-segment paths remain supported |
| [NS-DECL-001](#ns-decl-001) | implemented-bounded | `namespace App;` | verified | verified | deferred | CH08-NS-PARSE-01; semicolon fixture |
| [NS-DECL-002](#ns-decl-002) | implemented-bounded | `namespace App { function f() {} }` | verified | verified | deferred | CH08-NS-PARSE-01; braced fixture with typed function |
| [NS-USE-001](#ns-use-001) | implemented-bounded | `namespace App; use function Lib\f;` | verified | verified | deferred | CH08-IMPORTS-01; registered targets covered here; source declarations covered by CH08-CONST-01 |
| [NS-USE-002](#ns-use-002) | implemented-bounded | `namespace App; use const Lib\X;` | verified | verified | deferred | CH08-IMPORTS-01; registered targets covered here; source declarations covered by CH08-CONST-01 |
| [NS-USE-003](#ns-use-003) | implemented-bounded | `namespace App; use Lib\X;` | verified | verified | deferred | CH08-IMPORTS-01; registered targets covered here; source declarations covered by CH08-CONST-01 |
| [NS-USE-004](#ns-use-004) | implemented-bounded | `namespace App; use function Lib\f as g;` | verified | verified | deferred | CH08-IMPORTS-01; registered targets covered here; source declarations covered by CH08-CONST-01 |
| [NS-USE-005](#ns-use-005) | implemented-bounded | `namespace App; use function Lib\{f, g};` | verified | verified | deferred | CH08-IMPORTS-01; registered targets covered here; source declarations covered by CH08-CONST-01 |
| [NS-QUALIFIED-001](#ns-qualified-001) | implemented-bounded | `namespace App; return \Lib\value();` | verified | verified | deferred | `tests/namespaces.py`: rooted call and type paths |
| [NS-QUALIFIED-002](#ns-qualified-002) | implemented-bounded | `namespace App; return Sub\value();` | verified | verified | deferred | `tests/namespaces.py`: selects App\Sub, not global Sub |
| [NS-QUALIFIED-003](#ns-qualified-003) | implemented-bounded | `namespace App; function f() { g(); }` | verified | verified | deferred | Current-namespace calls; namespaces.php cross_file/reopened fixtures |
| [NS-SYMBOL-PATH-001](#ns-symbol-path-001) | partial | `namespace A\B; const X = 1; class MyClass {} function my_function() {}` | verified-bounded | verified-bounded | deferred | Function/type/scalar-constant paths covered; classes await CH10 |
| [NS-VAR-001](#ns-var-001) | implemented-bounded | `namespace A\B; $x = 1; echo $x;` | verified | verified | deferred | Entry-body locals, not persistent namespace state; namespaces.php cross_file fixture (exact echo card not rerun) |
| [NS-EXEC-001](#ns-exec-001) | implemented-bounded | `namespace A { compute(1); }` | verified-bounded | verified-bounded | deferred | Selected executable body; CH08-NS-PARSE-01; exact legacy fixture not rerun |
| [NS-EXEC-002](#ns-exec-002) | implemented-bounded | `namespace A { compute(1); return 1; }` | verified-bounded | verified-bounded | deferred | Selected body and return; namespaces.php braced fixture |
| [NS-EXEC-003](#ns-exec-003) | deferred-prerequisite | `namespace A { compute(1); class X {} compute(2); }` | partial | partial | deferred | Executable body/declaration separation covered; class declaration awaits CH10 |
| [NS-EXEC-004](#ns-exec-004) | contract-rejected | `namespace A; compute(1); namespace B; compute(2);` | verified-bounded | not emitted | deferred | One executable body per entry file; braced and semicolon rejection tested |
| [NS-MULTI-BLOCK-001](#ns-multi-block-001) | deferred-prerequisite | `namespace A { class X {} } namespace B { class Y {} } namespace { \A\X::run(); }` | partial | partial | deferred | Multiple declaration-only bodies covered; classes/static methods await CH10 |
| [RESOLVE-001](#resolve-001) | partial | `new A(); A\B\f();` | verified-bounded | verified-bounded | deferred | Qualified calls and existing struct construction covered; class construction awaits CH10 |
| [CLASS-NS-001](#class-ns-001) | deferred-prerequisite | `namespace A; class X {}` | unverified | unverified | deferred | CH10 class/struct and method work; namespace foundation is available |
| [CLASS-NS-002](#class-ns-002) | deferred-prerequisite | `namespace A; function f() { return new X(); }` | unverified | unverified | deferred | CH10 class/struct and method work; namespace foundation is available |
| [CLASS-NS-003](#class-ns-003) | deferred-prerequisite | `namespace A; function f() { return new \A\X(); }` | unverified | unverified | deferred | CH10 class/struct and method work; namespace foundation is available |
| [CLASS-NS-004](#class-ns-004) | deferred-prerequisite | `namespace A\B; function f() { return new A\B\X(); }` | unverified | unverified | deferred | CH10; old ancestor-anchored expectation superseded by current-relative lookup |
| [CLASS-STATIC-NS-001](#class-static-ns-001) | deferred-prerequisite | `namespace A; function f() { return X::make(); }` | unverified | unverified | deferred | CH10 class/struct and method work; namespace foundation is available |
| [INC-REQUIRE-001](#inc-require-001) | contract-rejected | `require 'x.php';` | verified rejection | not emitted | deferred | source_constants.php; project discovery owns composition; dedicated diagnostics remain debt |
| [INC-INCLUDE-001](#inc-include-001) | contract-rejected | `include 'x.php';` | verified rejection | not emitted | deferred | source_constants.php; project discovery owns composition; dedicated diagnostics remain debt |
| [INC-ONCE-001](#inc-once-001) | contract-rejected | `require_once "lib/util.php";` | verified rejection | not emitted | deferred | source_constants.php also covers include_once; no legacy textual include path |
| [NS-GLOBAL-EXPLICIT-001](#ns-global-explicit-001) | implemented-bounded | — (example pending) | verified | verified | deferred | CH08-PROGRAM-01 and global_block fixture; entry.cpp owns native entry, not legacy exact spelling |
| [NOTE-012](#note-012) | split-review | — (example pending) | partial | partial | deferred | See remaining-card reconciliation; imported prose spans multiple owners |
| [NOTE-019](#note-019) | contract-reconciled | — (historical prologue rule) | verified rejection | not emitted | deferred | Include/require forms reject; project discovery replaces legacy textual composition |
| [NOTE-025](#note-025) | split-review | — (example pending) | partial | partial | deferred | See remaining-card reconciliation; imported prose spans multiple owners |
| [NOTE-031](#note-031) | split-review | — (example pending) | partial | partial | deferred | See remaining-card reconciliation; imported prose spans multiple owners |
| [NOTE-051](#note-051) | split-review | — (example pending) | partial | partial | deferred | See remaining-card reconciliation; imported prose spans multiple owners |
## CLASS-STATIC-NS-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:26](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x = new X(); return $x::make();
```

**Existing C++ lowering / result**

```cpp
auto x = create<X>(); return ::scpp::class_t<decltype(x)>::make();
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** the source uses PHP static access through an instance expression

**Normalized pattern:** `instance-based static access`

**General rule:** Static class access through an instance lowers syntactically using `::scpp::class_t<decltype(<expr>)>::member`. The generator must not attempt to validate whether the resulting C++ type supports that static member.

**Diagnostics:** Let invalid generated C++ fail at C++ compile time; the generator must not reject solely on this basis.

**Notes:** Example: `$x::make()` â†’ `::scpp::class_t<decltype(x)>::make()`.


## CLASS-STATIC-NS-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:27](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A; function f() { return \A\X::make(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { class X { public: static auto make() {} }; auto f() { return ::scpp::A::X::make(); } }
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** fully-qualified PHP static class access

**Normalized pattern:** `fully-qualified static access`

**General rule:** Fully-qualified PHP static class access lowers to rooted `::scpp::...::X::make()`.

**Diagnostics:** Error if `new` is introduced for static member access or if rooted qualification is omitted.

**Notes:** Example: `\B\Y::make()` â†’ `::scpp::B::Y::make()`.


## NS-EXEC-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:28](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A { compute(1); namespace B { compute(2); } compute(3); }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Namespace

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** parent and nested namespaces both contribute executable flow

**Normalized pattern:** `nested parent/child execution conflict`

**General rule:** Executable code in a parent namespace and executable code in a nested namespace create different execution flows and are not allowed together.

**Diagnostics:** Error if executable flow exists in both the parent namespace and any nested namespace in the same namespace tree.

**Notes:** Declarations-only nested namespaces remain allowed; see NS-EXEC-005.


## NS-EXEC-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:29](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A { compute(1); namespace B { class X {} } compute(3); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { int main() { compute(static_cast<int_t>(1)); compute(static_cast<int_t>(3)); return 0; } namespace B { class X {}; } } int main() { return ::scpp::A::main(); }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** nested namespace contributes declarations only

**Normalized pattern:** `nested declaration-only namespace inside executable parent`

**General rule:** A nested namespace may appear inside a parent namespace execution region when the nested namespace contributes declarations only. Parent executable statements remain consolidated into the parent synthetic namespace `main()`.

**Diagnostics:** Error if the nested namespace also contributes executable flow.

**Notes:** This does not create a second execution flow.


## NS-DECL-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:167](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App;
```

**Existing C++ lowering / result**

```cpp
namespace scpp::App { ... }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** namespace name is valid

**Normalized pattern:** `namespace <name>;`

**General rule:** Semicolon namespace form lowers to a C++ namespace under the `scpp::` root.

**Diagnostics:** Error if the emitted namespace omits the `scpp::` wrapper.

**Notes:** Namespace-only rule; member typing and body rules are handled separately.


## NS-DECL-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:168](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App { function f() {} }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::App { ... }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** namespace name is valid

**Normalized pattern:** `namespace <name> { <members> }`

**General rule:** Braced namespace form lowers equivalently to the semicolon namespace form.

**Diagnostics:** Error if braced and semicolon forms lower differently or omit the `scpp::` wrapper.

**Notes:** Compact nested namespace syntax may be used in emitted C++.


## NS-USE-001

**v0.2 decision / target C++:** Implemented through body-local binding lookup and
shared preparation, not generated C++ using declarations or forwarding aliases.
See CH08-IMPORTS-01 and the v0.2 namespace spec; imported historical limits below
do not override this decision.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:169](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; use function Lib\f;
```

**Existing C++ lowering / result**

```cpp
namespace scpp::App { using ::scpp::Lib::f; }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** import form is explicit `use function` with no alias and no grouping

**Normalized pattern:** `namespace + function import`

**General rule:** `use function` follows C++ `using` semantics. The generator emits a namespace-local `using ::scpp::...;` declaration and does not implement PHP import fallback rules.

**Diagnostics:** Error if the generator emits `using namespace`, aliases the import, or rewrites this as PHP-style fallback lookup.

**Notes:** This is a Prism++ rule, not PHP import compatibility.


## NS-USE-002

**v0.2 decision / target C++:** Implemented through body-local binding lookup and
shared preparation, not generated C++ using declarations or forwarding aliases.
See CH08-IMPORTS-01 and the v0.2 namespace spec; imported historical limits below
do not override this decision.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:170](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; use const Lib\X;
```

**Existing C++ lowering / result**

```cpp
namespace scpp::App { using ::scpp::Lib::X; }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** import form is explicit `use const` with no alias and no grouping

**Normalized pattern:** `namespace + const import`

**General rule:** `use const` follows C++ `using` semantics. The generator emits a namespace-local `using ::scpp::...;` declaration and does not implement PHP import fallback rules.

**Diagnostics:** Error if the generator emits `using namespace`, aliases the import, or rewrites this as PHP-style fallback lookup.

**Notes:** This is a Prism++ rule, not PHP import compatibility.


## NS-USE-003

**v0.2 decision / target C++:** Implemented through body-local binding lookup and
shared preparation, not generated C++ using declarations or forwarding aliases.
See CH08-IMPORTS-01 and the v0.2 namespace spec; imported historical limits below
do not override this decision.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:171](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; use Lib\X;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Namespace

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** plain `use` import is encountered

**Normalized pattern:** `namespace + plain use import`

**General rule:** Plain `use` imports are rejected in the current subset because they do not map cleanly to the supported C++ `using` lowering.

**Diagnostics:** Emit an explicit generator error.

**Notes:** Requires an explicit later rule if class/type imports are introduced.


## NS-USE-004

**v0.2 decision / target C++:** Implemented through body-local binding lookup and
shared preparation, not generated C++ using declarations or forwarding aliases.
See CH08-IMPORTS-01 and the v0.2 namespace spec; imported historical limits below
do not override this decision.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:172](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; use function Lib\f as g;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Namespace

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** alias is present

**Normalized pattern:** `namespace + aliased use import`

**General rule:** Aliased imports are rejected in the current subset.

**Diagnostics:** Emit an explicit generator error.

**Notes:** No wrapper/forwarding alias is generated.


## NS-USE-005

**v0.2 decision / target C++:** Implemented through body-local binding lookup and
shared preparation, not generated C++ using declarations or forwarding aliases.
See CH08-IMPORTS-01 and the v0.2 namespace spec; imported historical limits below
do not override this decision.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:173](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; use function Lib\{f, g};
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Namespace

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** grouped import is present

**Normalized pattern:** `namespace + grouped use import`

**General rule:** Grouped imports are rejected in the current subset.

**Diagnostics:** Emit an explicit generator error.

**Notes:** No grouped lowering is implemented.


## NS-QUALIFIED-001

**v0.2 decision / target C++:** Implemented for supported calls and named type
references through exact rooted lookup. The C++ backend consumes canonical identities,
not source-path spelling. See CH08-NS-LOOKUP-01 and `tests/namespaces.py`; imports,
new class/static-member features and qualified runtime aliases remain separate.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:174](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; function f() { \Lib\g(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::App { void f() { ::scpp::Lib::g(); } }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** PHP name is fully-qualified

**Normalized pattern:** `fully-qualified namespace reference`

**General rule:** Fully-qualified PHP names beginning with `\` lower to rooted C++ names beginning with `::scpp::`.

**Diagnostics:** Error if the leading `::` or the `scpp::` root is omitted.

**Notes:** The same rooted lowering applies across supported namespace-like members.


## NS-QUALIFIED-002

**v0.2 decision / target C++:** Implemented for supported calls and named type
references. Non-root paths start below the current source namespace, without ancestor
or global-tail fallback. See CH08-NS-LOOKUP-01 and `tests/namespaces.py`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:175](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; function f() { A\B\g(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::App { void f() { A::B::g(); } }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** PHP name is qualified but not rooted

**Normalized pattern:** `qualified namespace reference`

**General rule:** Qualified PHP names that do not begin with `\` lower to relative C++ qualified names using `::` separators, without auto-prepending `scpp::`.

**Diagnostics:** Error if a non-rooted qualified PHP name is rewritten as rooted or if PHP backslashes are preserved.

**Notes:** Applies equally inside nested namespaces.


## NS-QUALIFIED-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:176](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace App; function f() { g(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::App { void f() { g(); } }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** PHP name is unqualified

**Normalized pattern:** `unqualified namespace reference`

**General rule:** Unqualified PHP names remain unqualified in emitted C++.

**Diagnostics:** Error if an unqualified name is rewritten into a qualified path without an explicit rule.

**Notes:** Same-namespace calls such as `f();` stay unqualified.


## NS-SYMBOL-PATH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:177](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A\B; const X = 1; class MyClass {} function my_function() {}
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A::B { const auto X = 1; class MyClass {}; void my_function() {} }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported-with-precondition

**Preconditions:** each emitted member kind must satisfy its own generation rules

**Normalized pattern:** `uniform qualified symbol access across namespace-like members`

**General rule:** Qualified symbol access is uniform across namespace-like members. Namespaces, classes, functions, constants, and namespace-scope variables use the same path resolution syntax while preserving their own symbol kind and usage rules.

**Diagnostics:** Error if path lowering conflates symbol kinds.

**Notes:** This is a path rule, not a claim that all symbol kinds behave identically.


## NS-VAR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:178](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A\B; $x = 1; echo $x;
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A::B { int __scpp_main() { auto x = static_cast<int_t>(1); std::cout << x; return 0; } } int main() { return ::scpp::A::B::__scpp_main(); }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** assignment belongs to namespace executable bootstrap flow

**Normalized pattern:** `namespace-scope executable bootstrap variable`

**General rule:** Namespace-scope assignments that are part of executable bootstrap code are allowed. They lower as locals inside the synthetic namespace execution function rather than as mutable namespace state.

**Diagnostics:** Error if the generator emits the variable directly at namespace scope instead of inside the synthetic namespace execution function.

**Notes:** This preserves executable namespace semantics without creating mutable namespace storage.


## NS-EXEC-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:179](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A { compute(1); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { int main() { compute(static_cast<int_t>(1)); return 0; } } int main() { return ::scpp::A::main(); }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** namespace body contains executable statements

**Normalized pattern:** `single synthetic namespace main`

**General rule:** Executable statements must not be emitted directly at namespace scope. Executable statements inside the same namespace body are consolidated into a single synthetic namespace `main()`. If execution reaches the end without an explicit return, the generator must append `return 0;`. The generated global `int main()` must return the result of the selected synthetic namespace `main()` call.

**Diagnostics:** Error if executable statements are emitted directly at namespace scope.

**Notes:** Literals inside the synthetic function still follow normal normalization rules.


## NS-EXEC-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:180](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A { compute(1); return 1; }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { int main() { compute(static_cast<int_t>(1)); return static_cast<int_t>(1); } } int main() { return ::scpp::A::main(); }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** namespace body contains executable statements ending in `return <expr>;`

**Normalized pattern:** `namespace executable block with terminal return`

**General rule:** If a namespace execution block contains `return <expr>;`, that return remains inside the synthetic namespace `main()`. The generated global `int main()` must return the result of that synthetic namespace `main()` call.

**Diagnostics:** Error if the namespace return is moved out of the synthetic namespace `main()` or ignored by the global entry point.

**Notes:** Fallthrough still appends `return 0;` when needed.


## NS-EXEC-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:181](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A { compute(1); class X {} compute(2); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { int main() { compute(static_cast<int_t>(1)); compute(static_cast<int_t>(2)); return 0; } class X {}; } int main() { return ::scpp::A::main(); }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** executable statements are separated by declarations within the same namespace body

**Normalized pattern:** `declarations do not split namespace execution`

**General rule:** Executable statements inside the same namespace body are consolidated into a single synthetic namespace `main()`, even when declarations appear between them. Declarations remain at namespace scope and do not split execution into separate synthetic functions. Source order of executable statements must be preserved.

**Diagnostics:** Error if the generator creates multiple synthetic namespace execution functions for the same namespace body solely because declarations appear between executable statements.

**Notes:** Applies when all executable statements belong to one generated code block for that namespace.


## NS-EXEC-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:182](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A; compute(1); namespace B; compute(2);
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Namespace

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** executable statements would require generated code blocks across different namespaces

**Normalized pattern:** `cross-namespace split execution is forbidden`

**General rule:** Executable statement consolidation applies only within a single namespace body. Executable statements that would require generated code blocks across different namespaces are forbidden.

**Diagnostics:** Error if the generator attempts to merge execution from different namespaces or emits separate namespace execution `main()` bodies for this pattern.

**Notes:** This pattern creates multiple execution flows in different namespaces.


## NS-MULTI-BLOCK-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:183](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A { class X {} } namespace B { class Y {} } namespace { \A\X::run(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { class X {}; } namespace scpp::B { class Y {}; } namespace scpp { int __scpp_main() { ::scpp::A::X::run(); return 0; } } int main() { return ::scpp::__scpp_main(); }
```

**Category:** Namespace

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** blocks are braced and global block lowers without an empty namespace segment

**Normalized pattern:** `multiple braced namespace blocks in one file`

**General rule:** Multiple braced namespace blocks in one file are supported. Each block lowers independently under the `scpp::...` root. A braced global namespace block `namespace { ... }` lowers to `namespace scpp { ... }`, and rooted calls must not introduce an empty namespace segment.

**Diagnostics:** Error if the generator emits `namespace scpp:: { ... }`, produces `::scpp::::...`, or merges declarations from distinct namespace blocks.

**Notes:** Executable-statement consolidation still applies within one namespace body at a time; cross-namespace execution merging remains forbidden.


## RESOLVE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:271](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
new A(); A\B\f();
```

**Existing C++ lowering / result**

syntactic lowering only

**Category:** Resolution

**Rule kind:** general rule

**Source support status:** supported

**Preconditions:** explicit lowering rule exists for the referenced form

**Normalized pattern:** `do not resolve symbols unless explicitly required`

**General rule:** Except for explicitly defined cases, the generator must not attempt semantic symbol resolution. Name lowering remains syntactic unless a rule states otherwise.

**Diagnostics:** Error if the generator invents semantic rewrites not covered by explicit rules.

**Notes:** Keeps the compiler simple and predictable.


## CLASS-NS-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:272](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A; class X {}
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { class X {}; }
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** namespace declaration is supported

**Normalized pattern:** `class declaration inside namespace`

**General rule:** Class declarations are emitted inside `namespace scpp::...`. Nested PHP namespaces may use compact C++ nested namespace syntax when emitted.

**Diagnostics:** Error if the class is emitted outside the translated namespace or without the trailing semicolon.

**Notes:** Applies equally to `namespace A;` and `namespace A\B;`.


## CLASS-NS-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:273](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A; function f() { return new X(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { class X {}; auto f() { return create<X>(); } }
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** same-namespace class name is used unqualified

**Normalized pattern:** `same-namespace construction`

**General rule:** Same-namespace class construction remains unqualified and lowers from `new X()` to `create<X>()`.

**Diagnostics:** Error if the generator adds unnecessary namespace qualification for same-namespace construction.

**Notes:** This follows the â€œkeep symbol lowering simpleâ€ rule.


## CLASS-NS-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:274](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A; function f() { return new \A\X(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { class X {}; auto f() { return create<::scpp::A::X>(); } }
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** PHP class name is fully-qualified

**Normalized pattern:** `fully-qualified class construction`

**General rule:** Fully-qualified PHP class construction lowers to rooted C++ construction using `create<::scpp::...>()`.

**Diagnostics:** Error if fully-qualified PHP class names are lowered without the rooted `::scpp::` prefix.

**Notes:** Example: `new \B\Y()` â†’ `create<::scpp::B::Y>()`.


## CLASS-NS-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:275](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A\B; function f() { return new A\B\X(); }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Class

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** current namespace is already `A\B`; input uses a qualified self-reference form

**Normalized pattern:** `qualified self-reference construction`

**General rule:** Qualified self-reference class construction such as `A\B\X` from inside `namespace A\B;` is rejected in this project model. Use `X` for same-namespace construction or `\A\B\X` for fully-qualified construction.

**Diagnostics:** Emit an error for qualified self-reference class construction in the same namespace.

**Notes:** This preserves the projectâ€™s simple syntactic lowering policy.


## CLASS-STATIC-NS-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:276](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
namespace A; function f() { return X::make(); }
```

**Existing C++ lowering / result**

```cpp
namespace scpp::A { class X { public: static auto make() {} }; auto f() { return X::make(); } }
```

**Category:** Class

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** same-namespace static class access

**Normalized pattern:** `same-namespace static access`

**General rule:** Same-namespace static class member access remains unqualified as `X::make()`.

**Diagnostics:** Error if the generator rewrites same-namespace static access into an unnecessary fully-qualified path.

**Notes:** Class body details remain governed by separate class rules.


## INC-REQUIRE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:305](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
require 'x.php';
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Include

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** `require` is not part of the current subset

**Normalized pattern:** `require <string>`

**General rule:** `require` is not supported in Prism++ v1.

**Diagnostics:** Emit a generator error.

**Notes:** `require_once` is the only currently supported include-like form.


## INC-INCLUDE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:306](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
include 'x.php';
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Include

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** `include` is not part of the current subset

**Normalized pattern:** `include <string>`

**General rule:** `include` and `include_once` are not supported in Prism++ v1.

**Diagnostics:** Emit a generator error.

**Notes:** Keep include semantics narrow and explicit.


## INC-ONCE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:307](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
require_once "lib/util.php";
```

**Existing C++ lowering / result**

`#include "lib/util.hpp"` in the generated header

**Category:** Include

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** statement appears in the file prologue and the argument is a literal static string

**Normalized pattern:** `require_once <literal-string>`

**General rule:** `require_once` is treated as a static compile-time include in the file prologue. The generator lowers only the literal-string form, rewrites a trailing `.php` suffix to `.hpp`, and emits the include in the generated header.

**Diagnostics:** Emit a strong generator error for `__DIR__`, concatenation, variables, function calls, non-literal expressions, or any non-prologue placement.

**Notes:** The generator does not read the target file, does not check existence, and does not model PHP runtime include execution.


## NS-GLOBAL-EXPLICIT-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:368](../../../../generators/php/specs/rules_catalog.md)

**Imported statement:** Global PHP execution must emit scpp::main + forwarding main

PHP example and C++ solution were not supplied; define them during discussion.

## NOTE-012

**Source:** [generators/php/specs/rules.md:172](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 4. Scope and Declaration
>
> ### Scope kinds
> - global
> - namespace
> - function
>
> ### Rules
> - first assignment in scope declares with `auto`
> - reassignment in the same scope must not redeclare with `auto`
> - use-before-declare is an error
>
> ### Overloading
> - function and method overloading are forbidden by Prism++ design
> - the generator must reject same-name overload sets rather than attempting overload-based lowering
>
> ---

## NOTE-019

**Source:** [generators/php/specs/rules.md:271](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 10A. File Prologue `require_once` Subset
>
> - `require_once` is supported only as a static compile-time include in the file prologue
> - only the exact literal-string form is supported: `require_once "path/file.php";`
> - `require_once` is rejected after any non-prologue construct
> - before `require_once`, only comments and `declare(...);` are allowed
> - `namespace`, `use`, constants, classes, functions, and executable statements close the prologue
> - `require_once` is not allowed inside namespaces, functions, methods, classes, or executable statement blocks
> - dynamic include expressions are rejected, including `__DIR__` concatenation and any computed path form
> - the generator does not check file existence and does not read or transpile the required file
> - lowering is purely textual at generation time: `.php` suffixes map to `.hpp` and the generated header emits `#include "..."`
>
> ---

## NOTE-025

**Source:** [generators/php/specs/rules.md:386](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 14. Namespaces
>
> ### 14.1 Declaration Emission
> - PHP namespaces are emitted under `scpp::...`
> - semicolon and braced namespace forms are structurally equivalent
> - compact nested namespace syntax such as `namespace scpp::A::B {}` is valid and preferred
>
> ### 14.2 Qualified Name Lowering
> - fully-qualified PHP names `\A\B\x` lower to `::scpp::A::B::x`
> - qualified PHP names `A\B\x` lower to `A::B::x`
> - unqualified PHP names `x` remain `x`
>
> ### 14.3 Uniform Symbol Path Rule
> Qualified symbol access is uniform across namespace-like members.
>
> Namespaces, classes, functions, constants, and namespace-scope variables use the same path resolution syntax, while preserving their own symbol kind and usage rules.
>
> ### 14.4 Symbol Resolution Simplicity
> Except for explicitly defined cases, the generator must not attempt semantic symbol resolution.
>
> Namespace and class name lowering remains syntactic unless a rule states otherwise.
>
> ### 14.5 Namespace Imports
> `use` lowers through explicit namespace-local C++ declarations. The generator keeps the model structural and does not perform semantic symbol resolution beyond the import kind already present in the PHP AST.
>
> Core rules:
> - every imported path is treated as absolute when emitted from `use`
> - `using namespace` must not be emitted for PHP `use`
> - emitted import declarations are namespace-local and are placed inside the generated `namespace scpp::... {}` block
> - conflicts are delegated to PHP/C++ compile-time behavior; the generator does not try to pre-resolve them
>
> Supported now:
> - `use A\B\C;` lowers to `using ::scpp::A::B::C;`
> - `use A\B\C as D;` lowers to `using D = ::scpp::A::B::C;`
> - `use function A\B\f;` lowers to `using ::scpp::A::B::f;`
> - `use function A\B\f as g;` lowers to `inline constexpr auto g = ::scpp::A::B::f;`
> - `use const A\B\X;` lowers to `using ::scpp::A::B::X;`
> - `use const A\B\X as Y;` lowers to `inline constexpr auto& Y = ::scpp::A::B::X;`
> - grouped imports are supported by expanding them to one emitted declaration per imported element:
>   - `use A\B\{C, D};`
>   - `use A\B\{C as D};`
>   - `use function A\B\{f, g as h};`
>   - `use const A\B\{X, Y as Z};`
>
> Notes:
> - plain `use` is treated as a symbol import, not as a namespace-alias feature
> - fully-qualified PHP names in normal code still lower via the rooted `::scpp::...` form
> - non-root qualified names in normal code remain syntactic, for example `A\B\C` -> `A::B::C`
> - no PHP fallback import/name-resolution behavior is implemented
>
> Known semantic edge:
> - `use const A\B\X;` can still differ from PHP when the current namespace already defines `X`; PHP may prefer the imported constant while C++ `using` produces a conflict
>
> ### 14.6 Namespace-Scope Constants and Variables
> - namespace-scope constants are allowed
> - emitted constant declarations use initializer-based type deduction (`const auto`) instead of requiring an explicit mapped scalar type
> - namespace-scope executable bootstrap statements are allowed and are lowered into the synthetic namespace execution function
> - namespace-scope static variables are forbidden

## NOTE-031

**Source:** [generators/php/specs/rules.md:568](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 2. Emission namespace
>
> All generated C++ code must be emitted inside:
>
> ```cpp
> namespace scpp {
> 	// generated code
> }
> ```

## NOTE-051

**Source:** [generators/php/specs/rules.md:1177](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## (Added) Global Execution Clarification
>
> For global PHP executable code:
>
> namespace scpp {
> 	int main() { ... }
> }
>
> int main() {
> 	return scpp::main();
> }
>
> Interpolation AST finding:
> - interpolated strings are represented as `AST_ENCAPS_LIST`, not as binary concat chains
> - generator lowering should join each part in order and cast interpolated non-string values to `string_t` explicitly
> - when an interpolated fragment is an expression subtree inside `{...}`, the node must be lowered by the ordinary expression renderer and then wrapped in `cast<string_t>(...)`; interpolation must not introduce a separate expression-lowering path
> - precedence is delegated to the AST / normal expression renderer; interpolation only performs string normalization around the rendered expression
> - `samples/know_how/` remains the exporter-behavior reference folder for these checks
