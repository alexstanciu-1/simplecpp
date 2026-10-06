# 09. Value types and enums
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires canonical types and declarations. The imported cards primarily cover enums.
A bounded value-struct slice already exists: named fields, default initialization,
value copies, field access and value/reference function boundaries. Its scope and
proofs are recorded with [ordinary functions](04_functions.md#current-ordinary-function-slice-2026-09-27)
and [current native status](../portability/conversion_review.md). This does not mark
the enum, ownership or imported annotation cards below complete.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [TYPE-VAR-006](#type-var-006) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-006A](#type-var-006a) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-007](#type-var-007) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [ENUM-001](#enum-001) | bounded pass | `enum Suit { case Hearts; case Spades; }` | proved | proved | deferred | [Execution checkpoint](#enum-execution--2026-10-06) |
| [ENUM-002](#enum-002) | bounded pass | `enum HttpStatus: int { case Ok = 200; case NotFound = 404; }` | proved | proved | deferred | Includes exact fixed-width backing; [execution checkpoint](#enum-execution--2026-10-06) |
| [ENUM-003](#enum-003) | pending-discussion | `Color::tryFrom('green')?->name ?? 'null'` | unverified | unverified | deferred | — |
| [ENUM-004](#enum-004) | pending-discussion | `enum Status implements HasLabel { case Open; public function label(): string { return 'open'; } }` | unverified | unverified | deferred | — |

## Enum model foundation — 2026-10-06

Agreed grouping: reconcile value-storage/construction cards against existing CH07
support and CH10 prerequisites; implement core unit/integer/fixed-backed enums
together; keep extended helpers, string backing, methods/interfaces separate.

The first checkpoint (`57c34018`) was deliberately model-only:

- One `enum_node` owns optional backing syntax and ordered `enum_case_node` entries;
  each case owns an optional value expression. No redundant flavor flag or namespace
  constant publication. Inspection and maintenance share the same owning edges.
- Final `enum_type_definition` extends the existing nominal abstract with the
  `enumeration` kind. It remains nominal, value-stored and distinct from integers.
  Registry normalization removes redundant `value<Enum>`, without granting operations.
- `collected_type_definition` groups source structs and enums. The existing weak
  source-type association, publication, reuse and retirement paths accept that role.
  Record-only queries/consumers remain explicitly narrowed; no second registry.
- Backing resolution, exact case values, storage choice and source capabilities belong
  to subsequent preparation. No mutable derived facts or C++ spelling live in the registry.

The subsequent vertical slice, now implemented below: authored declarations/case references, typed boundaries and C++
`enum class` artifacts. Unit storage is smallest fitting unsigned; `int` backing uses
the smallest fitting integer, signed when negatives require it; explicit fixed backing
is exact width under [the normative contract](../../../../specs/compact_layout_types.md#3-enum-semantics).
Legacy `enumStorageType`, `emitEnumClass` and `validateEnumCases` were reviewed:
preserve literal-only backing, nonempty enums, unique names/values and range checks.
Do not inherit host-integer precision assumptions. Default initialization and helper
admission are not decided by this model checkpoint.

Proof: [enum_model.php](../../tests/enum_model.php) assembles syntax directly and checks
identity, storage normalization, distinct same-name declarations, source association,
both publication paths, reuse, retirement, ordered independent traversal and the
remaining source/C++ gates. Existing struct/namespace/incremental regressions remain
separate proofs. No enum parser, preparation facts, execution or native compiler build
is claimed. The portable-PHP skill guided typed collections, explicit narrowing and
weak association ownership.

Focused PHP checkpoint: **11 suites passed** (`enum_model`, `type_catalog`,
`parse_collection`, `incremental_cpp`, `incremental_preparation`, `capabilities`,
`ast`, `token_cleanup`, `namespaces`, `source_constants`, `s2s`) through the shared
12-slot runner, with automatic CLI fallback after sandbox FPM startup rejection.
Command evidence: `/tmp/my-try-enum-model-final-20261006-1/commands.json`.
No portability conversion or native compiler rebuild was run.

Validation note: the older [model.php](../../tests/model.php) fails its hardcoded
built-in-count assertion before reaching source checks. Reproduced identically on
unchanged `d12ab9db`; this is pre-existing test-maintenance debt, not an enum regression.
Repair/reconcile that legacy suite before using it as a current whole-model proof.

## Enum execution — 2026-10-06

Source declarations and qualified/imported `Type::Case` references now use the normal
source-type lookup, declaration scheduler and partitioned type-header artifacts.
`prepared_enum` owns exact decimal case values, optional source backing and selected
integer storage. Published snapshots are retained on effective-equal edits; case
references share selected facts without syntax backlinks or a second case registry.

The bounded pass supports explicit local initialization, same-enum copying and
value/reference function boundaries. Unit ordinals use compact unsigned storage;
`int` backing compacts with signedness chosen from values; fixed integer backing
preserves exact width. Signed minimum and full `uint64` maximum are represented
without host numeric conversion. Preparation rejects empty/duplicate cases,
duplicate values, missing/forbidden initializers, noninteger backing, range failures
and wrong-enum/integer boundaries.

Current gates: decimal literals with one optional sign only (not arbitrary constant
expressions); enum locals require an explicit initializer; enum-valued fields await
field-initialization support. These gates do not establish a language-wide enum
default policy. Equality, switch/match, helpers, string backing and methods/interfaces
are not enabled by this slice. No implicit integer conversion, arithmetic or truthiness.

Proof: `python3 compiler/my-try/tests/enums.py --results FRESH` passed **12 PHP suites
and 6 generated C++ programs**. Coverage includes compact/exact native storage,
cross-file imports, references, generation purity, no-op preservation, dependency
invalidation, failed-parse/preparation recovery, deletion/reintroduction and token cleanup.
Evidence: `/tmp/my-try-enums-final-20261006-1/commands.json` and its generated-program
results. CLI fallback was used after sandbox FPM startup rejection. This is not a
native compiler rebuild or a portability-conversion proof.

Shared exact C++ integer spelling also passed the scalar-constant regression runner:
**10 PHP suites and 10 generated programs**, including signed-minimum constants and
labels (`/tmp/my-try-enum-scalar-regression-20261006-1`).

Next discussion: same-enum equality and switch/match integration; settle defaults
and helper scope separately. CH09 remains open, with the combined native compiler
checkpoint still deferred by user request.

## TYPE-VAR-006

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:355](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** value<Point> */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
value_p<Point> p = value<Point>(static_cast<int_t>(1), static_cast<int_t>(2));
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit inline-storage local annotation present

**Normalized pattern:** `typed inline-storage object local variable`

**General rule:** The local PHPDoc form `value<T>` is an explicit opt-in to inline storage and lowers to `value_p<T>`. When the initializer is `new T(...)`, generation uses `value<T>(...)` rather than `create<T>(...)`. The resulting local remains object-like at the usage surface, so property and method access continue to lower through `->`.

**Diagnostics:** Error if the generator falls back to `shared_p<T>` or `create<T>(...)` for this explicit local form, or if later member access is emitted as direct `.` access on the wrapper instead of object-like access through `->`.

**Notes:** This rule applies only to typed local variables in the current prototype. Legacy `value T` comments may still be accepted for compatibility, but `value<T>` is canonical.


## TYPE-VAR-006A

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:356](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** value<?Point> */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
nullable<Point> p{Point(static_cast<int_t>(1), static_cast<int_t>(2))};
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit nullable inline-storage local annotation present

**Normalized pattern:** `typed nullable inline object local variable`

**General rule:** The local PHPDoc form `value<?T>` is an explicit opt-in to nullable inline object storage. When the initializer is `new T(...)`, generation lowers to `nullable<T>{T(...)}` rather than `value_p<T>` or `mixed_t`. The resulting local remains object-like at the usage surface through nullable `->` forwarding.

**Diagnostics:** Error if the generator silently degrades this explicit local form to `mixed_t`, `shared_p<T>`, or any non-nullable storage, or if later member access does not preserve object-like `->` behavior.

**Notes:** `shared_p<T> x = null` is not a valid test for this rule because it exercises handle-null behavior rather than `nullable<T>`.


## TYPE-VAR-007

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:358](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** value */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
value_p<Point> p = value<Point>(static_cast<int_t>(1), static_cast<int_t>(2));
```

**Category:** Type system

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** the typed local initializer is exactly `new ClassName(...)` and the class target is statically known

**Normalized pattern:** `bare local wrapper shortcut`

**General rule:** Bare local wrapper comments `value`, `shared`, and `unique` are strict shortcuts only for direct constructor assignment. The generator must immediately normalize them to `value<ClassName>`, `shared<ClassName>`, or `unique<ClassName>` before later lowering steps. After normalization, later wrapper-aware lowering must still validate that an explicit wrapper inner type matches the class named by any direct `new ClassName(...)` initializer.

**Diagnostics:** Emit an error when the wrapper is bare but the assignment is not a normal direct local assignment from `new ClassName(...)`, or when the class target is not statically known.

**Notes:** This shortcut is intentionally narrow and does not authorize general inference from factories, conditionals, variables, or dynamic class names.


## ENUM-001

**v0.2 decision / target C++:** Shared nominal enum model; prepared unit cases
lower to compact `enum class`. [Execution checkpoint](#enum-execution--2026-10-06).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:375](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
enum Suit { case Hearts; case Spades; }
```

**Existing C++ lowering / result**

generated native C++ scoped enum with compact underlying storage

**Category:** Enum

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** enum body contains only simple cases

**Normalized pattern:** unit enum

**General rule:** PHP unit enums lower to `enum class <Name> : <smallest unsigned storage>` with one enumerator per case. Storage stays at 1 byte while the case set fits, then widens as needed.

**Diagnostics:** Error on unsupported enum members or unsupported case expressions.

**Notes:** Current stage intentionally targets only simple enum declarations and case references.


## ENUM-002

**v0.2 decision / target C++:** Same model as unit enums; preparation distinguishes
compact `int` backing from exact fixed-width backing through resolved type identity.
[Execution checkpoint](#enum-execution--2026-10-06).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:376](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
enum HttpStatus: int { case Ok = 200; case NotFound = 404; }
```

**Existing C++ lowering / result**

generated native C++ scoped enum with explicit integral enumerator values

**Category:** Enum

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** backing type is `int` and every case value is a literal integer

**Normalized pattern:** int-backed enum

**General rule:** Int-backed enums lower to `enum class <Name> : <smallest fitting integer storage>` and preserve the declared integer case values.

**Diagnostics:** Error if the backing type is not `int` or if a case value is not a literal integer.

**Notes:** Signed storage is selected only when negative case values require it.


## ENUM-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:377](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
Color::tryFrom('green')?->name ?? 'null'
```

**Existing C++ lowering / result**

helper-based enum API

**Category:** Enum

**Rule kind:** generation

**Source support status:** unsupported

**Preconditions:** n/a

**Normalized pattern:** enum helper API

**General rule:** Helper synthesis such as pseudo-properties, `cases()`, `from()`, and `tryFrom()` is not part of the current simple-enum stage.

**Diagnostics:** Error / unsupported lowering if used.

**Notes:** Revisit after the base native-enum path is stable.


## ENUM-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:378](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
enum Status implements HasLabel { case Open; public function label(): string { return 'open'; } }
```

**Existing C++ lowering / result**

enum methods / interfaces

**Category:** Enum

**Rule kind:** generation

**Source support status:** unsupported

**Preconditions:** n/a

**Normalized pattern:** enriched enum bodies

**General rule:** Methods, interfaces, and other class-like enum features are out of scope for the current simple-enum stage.

**Diagnostics:** Error / unsupported lowering if used.

**Notes:** Revisit after simple enums are validated end-to-end.
