# 01. Literals and local variables
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Start here: source spelling, literal values, typed declarations, initialization, reads and reassignment.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [LIT-INT-001](#lit-int-001) | agreed | `$a = 10;` | proved | proved | deferred | [S2S integer slice](../s2s_integer_slice.md); PHP preparation + Clang execution; [current native evidence](../portability/conversion_review.md) |
| [LIT-BOOL-001](#lit-bool-001) | agreed | `$a = true;` | proved | proved | deferred | [Boolean slice](../s2s_integer_slice.md#boolean-literal-extension); [PHP + emitted-C++ cases](../../tests/s2s.php) |
| [LIT-BOOL-002](#lit-bool-002) | agreed | `$a = false;` | proved | proved | deferred | [Boolean slice](../s2s_integer_slice.md#boolean-literal-extension); [PHP + emitted-C++ cases](../../tests/s2s.php) |
| [LIT-FLOAT-001](#lit-float-001) | agreed | `$a = 10.5;` | proved | proved | deferred | [Scalar proof](../../tests/s2s.php), [float decision](#lit-float-001) |
| [LIT-STR-001](#lit-str-001) | agreed | `$a = 'x';` | proved | in-progress | deferred | [PHP frontend and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [LIT-STR-002](#lit-str-002) | agreed | `$a = "x";` | proved | in-progress | deferred | [PHP frontend and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [TYPE-VAR-001](#type-var-001) | agreed | `$x string = "test";` | proved | in-progress | deferred | [PHP preparation and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [VAR-ASSIGN-001](#var-assign-001) | agreed | `$a = $b;` | proved | in-progress | deferred | [PHP preparation and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [VAR-REASSIGN-001](#var-reassign-001) | agreed | `$a = 1; $a = 2;` | proved | in-progress | deferred | [PHP preparation and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [LIT-STR-003](#lit-str-003) | agreed | `$a = "";` | proved | in-progress | deferred | [Generalized string preparation and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [LIT-CONST-001](#lit-const-001) | agreed | `$a = PHP_INT_MAX;` | proved | in-progress | deferred | [PHP preparation and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [VAR-CHAIN-001](#var-chain-001) | agreed | `$a = $b = 1;` | proved | in-progress | deferred | [PHP preparation and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [VAR-CHAIN-002](#var-chain-002) | agreed | `$a = 1; $b = $a;` | proved | in-progress | deferred | [Exact emitted-C++ and variable-copy proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [VAR-CHAIN-003](#var-chain-003) | agreed | `$a = 1; $b = $a; $c = $b;` | proved | in-progress | deferred | [Exact prepared-identity and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [VAR-CHAIN-004](#var-chain-004) | agreed | `$a = 1; $b = $a + 1;` | proved | in-progress | deferred | [Canonical integer-addition preparation and emitted-C++ proof](../../tests/s2s.php); native C++ execution remains explicit-request validation |
| [VAR-ORDER-001](#var-order-001) | agreed | `$a = $b; $b = 1;` | proved | not-applicable | deferred | [Exact source-order diagnostic and no-publication proof](../../tests/s2s.php); invalid standalone input has no C++ lowering |
| [VAR-REASSIGN-002](#var-reassign-002) | pending-discussion | `$a = 1; $a = $a + 1;` | unverified | unverified | deferred | — |
| [VAR-REASSIGN-003](#var-reassign-003) | pending-discussion | `$a = 1; $a = $a + $a;` | unverified | unverified | deferred | — |
| [IDENT-VAR-001](#ident-var-001) | pending-discussion | `function f(int $int) { $while = $int; }` | unverified | unverified | deferred | — |
| [NOTE-011](#note-011) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-021](#note-021) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-033](#note-033) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-034](#note-034) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-035](#note-035) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |

### Deferred legacy syntax

These syntax-specific entries are retained for traceability, not scheduled before strict-mode features.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [TYPE-VAR-005](#type-var-005) | deferred-legacy | `/** string */ $x = "test";` | unverified | unverified | deferred | Legacy annotation syntax only; outside strict-first work |
## LIT-INT-001

**v0.2 decision / target C++:** The first assignment establishes a local with the
canonical Simple C++ `int` type inferred from its integer initializer. Preparation
retains the existing binding occurrence as declaration identity without mutating
the AST. The first one-file entry emits:

```cpp
auto local_a = static_cast<scpp::int_t<>>(10LL);
```

The generated name uses the saved declaration name and a role prefix, independent
of token position. The current runtime type is signed 64-bit; the native carrier suffix preserves the supported integer range.
Explicit `int` initialization, copies and reassignment have supporting proofs in
[tests/s2s.php](../../tests/s2s.php). This does not mark other catalog cards complete.
Scope encapsulation and the LANGUAGE+RUNTIME parent are described in the
[slice notes](../s2s_integer_slice.md). JSON import, other literal forms,
composite types, multi-file generation, validation and semantic invalidation are
outside this slice.

**Deferred illustration, not a prerequisite:** Explicit `int_t` versus `auto`,
with or without a literal cast, may be compared later if profiling justifies it.
A one-off Clang comparison is saved in [timing evidence](../../../../specs/planning/results/int_literal_clang_2026_09_25/README.md). It establishes no project-build gain; further spelling optimization is deferred.
Prioritize modular output and reduced recompilation under the
[catalog guidance](README.md#design-c-for-efficient-native-compilation).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:30](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 10;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(10);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** literal is integer; first assignment in scope

**Normalized pattern:** `<var> = <int-literal>`

**General rule:** Integer literals must be converted using `static_cast<int_t>(value)` and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if raw integer literal is emitted without cast.


## LIT-BOOL-001

**v0.2 decision / target C++:** Boolean literals follow the integer pipeline.
The frontend normalizes `true` into a boolean_literal_node. Preparation
attaches a prepared_boolean_literal with canonical language `bool` identity and
its boolean value. The shared binding path infers the local type and establishes
its declaration on first assignment. Inside the program entry:

```cpp
auto local_a = static_cast<scpp::bool_t>(true);
```

Include only `scpp/bool_t.hpp` when this is the only literal type needed. True and
false use one implementation; both catalog IDs remain for provenance. Explicit
`$a bool = true;`, copies and same-type reassignment use the existing scope/binding
flow. Cross-type conversions and boolean operators are outside this slice.
See [the shared slice and proof](../s2s_integer_slice.md#boolean-literal-extension).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:32](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = true;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(true);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** literal is boolean; first assignment in scope

**Normalized pattern:** `<var> = <bool-literal>`

**General rule:** Boolean literals must be converted using `static_cast<bool_t>(value)` and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if raw boolean literal is emitted without cast.

**Notes:** Covers `true` and `false`; duplicate bool rows should be collapsed.


## LIT-BOOL-002

**v0.2 decision / target C++:** Boolean literals follow the integer pipeline.
The frontend normalizes `false` into a boolean_literal_node. Preparation
attaches a prepared_boolean_literal with canonical language `bool` identity and
its boolean value. The shared binding path infers the local type and establishes
its declaration on first assignment. Inside the program entry:

```cpp
auto local_a = static_cast<scpp::bool_t>(false);
```

Include only `scpp/bool_t.hpp` when this is the only literal type needed. True and
false use one implementation; both catalog IDs remain for provenance. Explicit
`$a bool = false;`, copies and same-type reassignment use the existing scope/binding
flow. Cross-type conversions and boolean operators are outside this slice.
See [the shared slice and proof](../s2s_integer_slice.md#boolean-literal-extension).

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:33](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = false;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<bool_t>(false);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** covered-by-LIT-BOOL-001

**Preconditions:** same as LIT-BOOL-001

**Normalized pattern:** `<var> = <bool-literal>`

**General rule:** Same generalized rule as LIT-BOOL-001.

**Notes:** Redundant seed row kept only for traceability.


## LIT-FLOAT-001

**v0.2 decision / target C++:** Agreed decimal floating literals use canonical
`float` (signed 64-bit, runtime `scpp::float_t` backed by `double`; current target
IEEE binary64 with 53 significand bits). Emit `auto local_a =
static_cast<scpp::float_t>(10.5);` with the narrow `scpp/float_t.hpp` header.

Accept decimal point and exponent forms: `10.5`, `.5`, `10.`, `1e3`, `1E+3`,
`1.25e-3`, `.5e2`, `10.e-1`. The tokenizer owns numeric grammar and exact spans;
parser assigns the float specialization; preparation retains decimal text and the
canonical type. These spellings are already C++ compatible, including leading
zeros in floating forms, so emission preserves them without host numeric conversion.
This preserves the decimal input to target rounding, not exact decimal arithmetic.

Reuse scalar bindings for inference, explicit `float`, copies and reassignment.
Unary signs, separators, arithmetic, cross-type conversions and numeric range
validation remain outside this slice. Exponent signs are part of the literal.
Overflow/underflow diagnostics remain a target-toolchain concern in this pass.

**Legacy review:** `Generator::renderExpr()` receives an already parsed PHP float
and concatenates it into output. The precision probe `1.2345678901234567` emitted
`1.2345678901235`; this loss is deliberately avoided. Existing float initialization
and reassignment fixtures (`tests/php/types/float/level_01`) and runtime scalar
proofs were reviewed; they cover ordinary values, not text preservation or exponent
edge cases. The current proof adds all accepted forms, high precision, maximum
finite double, minimum normal and subnormal, malformed exponent rejection, canonical
identity and preparation cleanup.

**Verification (2026-09-26):** `/tmp/scpp-float-s2s-02/summary.json` records
72 PHP files linted, passing style/behavior suites, 37 generated C++ programs
compiled/executed, and the existing 19 LLVM / 28 call regressions. All 51 portable
compiler sources converted in `/tmp/scpp-float-conversion/`. Native compilation of
`my-try` itself was not run, per the opt-in rule.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:31](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 10.5;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<float_t>(10.5);
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** literal is floating-point; first assignment in scope

**Normalized pattern:** `<var> = <float-literal>`

**General rule:** Float literals must be converted using `static_cast<float_t>(value)` and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if raw float literal is emitted without cast.


## LIT-STR-001

**v0.2 decision / target C++:** A single-quoted literal is one canonical binary-safe
`string` value. The tokenizer retains the whole quoted token and rejects an
unterminated token. Preparation decodes source bytes without host-PHP evaluation:
only `\\` and `\'` collapse to one byte, while other backslash pairs remain literal.
The prepared fact owns decoded bytes and the canonical language string identity;
source quote spelling and C++ escaping are not semantic facts.

The first assignment infers that type and emits:

```cpp
auto local_a = scpp::string_t("x");
```

The C++ backend owns byte escaping. Nonzero bytes use a valid C++ literal; embedded
NUL uses a length-aware `std::string` construction so `string_t` does not truncate.
Only `scpp/string_t.hpp` is requested for the ordinary example. Program-entry return
validation remains an explicit numeric/bool ABI boundary, so `return 'x';` is
rejected before generation.

`LIT-STR-002` double quotes, interpolation, concatenation, comparison, indexing,
string fields and broader typed-string behavior remain outside this slice.
`LIT-STR-003` is not marked complete merely because the shared decoder can represent
empty bytes. LLVM remains deferred.

**Verification (2026-09-30):** focused PHP tokenizer, AST, specialization-dispatch
and S2S suites prove exact spans, unterminated rejection, canonical type identity,
single-quote decoding, binary-safe emitted spelling, cleanup/source purity and the
entry-return rejection. Generated C++ was inspected but not compiled or executed;
native compiler validation remains pending explicit request, so C++ S2S stays
`in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:35](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 'x';
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("x");
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** valid PHP string literal; first assignment in scope

**Normalized pattern:** `<var> = <string-literal>`

**General rule:** PHP string literals must first be normalized into a valid C++ string literal, then materialized as `string_t("...")`, and assigned using `auto` on first assignment in scope.

**Diagnostics:** Error if string is emitted without `string_t(...)`; error if normalization is missing.

**Notes:** Generator must implement PHP string normalization/conversion.


## LIT-STR-002

**v0.2 decision / target C++:** A non-interpolated double-quoted literal maps to
the same canonical binary-safe `string` value and C++ representation as
`LIT-STR-001`:

```cpp
auto local_a = scpp::string_t("x");
```

The tokenizer's quote-neutral scanner retains the complete source token. Preparation
owns quote semantics and supplies the emitter with the canonical string type plus
fully decoded bytes. It decodes `\\`, `\"`, `\$`, `\n`, `\r`, `\t`, `\v`, `\f`,
`\e`, one-to-three-digit octal escapes and one-to-two-digit `\x` escapes. Unknown
escape pairs retain their backslash. Interpolation is rejected rather than partially
lowered, and `\u{...}` is rejected with guidance to use literal UTF-8 bytes.

The prerequisite gate adds no new emitter fact for this spelling: preparation
already resolves the literal's value and type, while the existing backend only
chooses binary-safe C++ spelling. The legacy generator was reviewed: it receives
host-PHP-decoded values and applies JSON/C++ escaping, including a length-aware NUL
path. This slice deliberately decodes source bytes itself so host PHP cannot change
their meaning. Interpolation, Unicode escape syntax, concatenation, string operations,
typed string fields and `LIT-STR-003` remain non-goals. LLVM remains deferred.

**Verification (2026-09-30):** focused PHP tokenizer and S2S proofs cover exact
double-quoted spans, unterminated rejection, canonical AST/type identity, byte escape
decoding, interpolation and Unicode-escape rejection, output spelling, cleanup and
source purity. Generated C++ is inspected but not compiled or executed; native
compiler validation remains pending explicit request, so C++ S2S stays `in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:36](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "x";
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("x");
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** covered-by-LIT-STR-001

**Preconditions:** same as LIT-STR-001

**Normalized pattern:** `<var> = <string-literal>`

**General rule:** Same generalized rule as LIT-STR-001.

**Notes:** Covers double-quoted string without interpolation.


## TYPE-VAR-001

**Strict-mode PHP input example:** `$x string = "test";`

This is the working source spelling. Imported examples remain reference material.

**v0.2 decision / target C++:** An explicit strict local type is authoritative.
Preparation resolves `string` to the canonical language type, records declaration
identity and checks the initializer before output is permitted. The first example
emits:

```cpp
scpp::string_t local_x = scpp::string_t("test");
```

Explicit declarations spell their prepared canonical C++ type; inferred first
assignments continue to use `auto`. Later assignments use the established storage
type and declaration identity. `$x string = 1;` is rejected during preparation
rather than being deferred to the C++ compiler. This deliberately strengthens the
legacy type-blind behavior, whose inline annotation selected output spelling but
left general compatibility to native compilation.

The prerequisite gate is satisfied locally: preparation already owns type
resolution, initializer compatibility, declaration identity and source-order
single evaluation. C++ lowering only chooses explicit-type versus inferred-`auto`
spelling and applies the prepared destination conversion. The shared spelling rule
also applies to existing explicit integer, float and boolean declarations; focused
regressions cover those paths. Conversions beyond the existing integer family,
string operations, nullable/dynamic types and other `TYPE-VAR` rows remain non-goals.
LLVM remains deferred.

**Verification (2026-09-30):** focused PHP S2S proofs cover prepared canonical type
and declaration identity, explicit string output, compatible reassignment, mismatch
rejection before publication, inferred-`auto` preservation, explicit scalar
regressions, cleanup and source purity. Generated C++ is inspected but not compiled
or executed; native compiler validation remains pending explicit request, so C++ S2S
stays `in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:322](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** string */ = "test";
```

**Existing C++ lowering / result**

```cpp
string_t x("test");
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit inline typed-slot local annotation present

**Normalized pattern:** `typed local variable`

**General rule:** Explicit inline typed-slot local annotations are authoritative for local variables when present.

**Diagnostics:** Error only if the explicit type form itself is unsupported.

**Notes:** Type compatibility is left to the C++ compiler unless a generation rule requires local rejection.


## VAR-ASSIGN-001

**v0.2 decision / target C++:** Given an established value local, first assignment
to a fresh local creates a distinct binding with the source's prepared canonical
type. The focused complete example is:

```php
$b int = 11;
$a = $b;
$b = 19;
return $a;
```

The relevant C++ copy is direct:

```cpp
auto local_a = local_b;
```

Preparation resolves the right-hand reference before publishing the new target.
The source reference retains the exact source declaration and type; the assignment
fact retains a different target declaration, classifies it as a declaration and
uses the same canonical type as its expression result. Consequently there is no
backend type inference or copy-versus-reference guess. A later source mutation
continues to address the source declaration, while a read of `$a` addresses the
copy. `$a = $b; $b = 1;` and `$a = $a;` both fail during preparation: statements
are not reordered and an initializing local cannot see itself.

**Legacy edge-case review:** The old S2S also tracked declared locals in source
order, emitted a direct copy and rejected undeclared reads. Its repeated integer
fixtures confirm the basic shape. Its broader tests expose constraints that still
apply but do not belong to this scalar slice: ordinary assignment is distinct from
`=&`; value structs copy, object handles share identity, and PHP arrays historically
needed copy-on-write handling. Objects, containers, dynamic values and reference
bindings must therefore use their own prepared representation contracts rather than
being inferred from this row.

The prerequisite gate is satisfied without a production change. Preparation already
supplies source and target identity, source and result type, declaration-versus-
reassignment outcome, source order and one prepared RHS. C++ lowering only emits
the direct copy. Chained assignments, composed expressions, cross-type conversion,
block/control-flow scope and `VAR-REASSIGN-001` remain non-goals. LLVM remains
deferred.

**Verification (2026-09-30):** focused PHP S2S proofs cover distinct source/target
identity, shared canonical type, declaration classification, source mutation versus
copy reads, exact direct-copy output, source-before-use and self-initialization
diagnostics, cleanup and source purity. Existing scalar and value-struct copy cases
remain regression evidence. Generated C++ is inspected but not compiled or executed;
native compiler validation remains pending explicit request, so C++ S2S stays
`in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:40](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b;
```

**Existing C++ lowering / result**

```cpp
auto a = b;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source variable already declared in scope

**Normalized pattern:** `<var> = <var>`

**General rule:** PHP variables are mapped to native C++ variables by removing the `$` prefix. Variable assignment generates direct C++ assignment.

**Diagnostics:** Error if source variable is used before declaration.


## VAR-REASSIGN-001

**v0.2 decision / target C++:** The first untyped assignment establishes one local
binding and its canonical value type. A later ordinary assignment in the same scope
reuses that declaration and must remain assignable to its established type:

```php
$a = 1;
$a = 2;
return $a;
```

```cpp
auto local_a = static_cast<scpp::int_t<>>(1LL);
local_a = static_cast<scpp::int_t<>>(2LL);
```

Preparation classifies the first write as a declaration and the second as an
assignment. Both retain the exact same declaration identity and canonical `int`
type; the assignment expression also retains that result type. The C++ emitter
therefore omits `auto` on the second write and applies the destination's normal
value boundary only when the prepared source and destination types differ. Exact
types retain the already canonical RHS without a redundant wrapper conversion; a
write between distinct compatible integer types still converts. An established
target is visible while its reassignment RHS is prepared, so
`$a = 1; $a = $a;` is valid even though the fresh self-initialization `$a = $a;`
remains invalid.

**Legacy edge-case review:** The old S2S tracked declarations and stored local types
per active block, omitted a declaration prefix on later writes, and diagnosed local
type morphing. It also distinguished an ordinary reassignment from `=&` rebinding
and rejected a second explicit local declaration. Those constraints still apply.
The new semantic preparation enforces incompatible writes rather than depending on
the old generator/STAN split, and now rejects `$a int = 1; $a int = 2;` before C++
emission. Explicit typed syntax declares storage; the later untyped `$a = 2;` form
is the reassignment.

The prerequisite gate is satisfied by `prepared_binding`: it supplies the stable
target declaration, canonical destination type and declaration-versus-assignment
outcome, while expression preparation supplies the RHS/result type. Composed RHS
expressions (`VAR-REASSIGN-002`), compound operators, assignment chains, child-block
scope merging, reference rebinding and dynamic/container type changes remain
non-goals. LLVM remains deferred.

**Verification (2026-09-30):** focused PHP S2S proofs cover stable declaration
identity and type, assignment classification and result type, exact direct C++
reassignment without redeclaration, retained conversion between distinct compatible
integer types, a self-read from established storage, incompatible-type and duplicate-
declaration diagnostics, cleanup and source purity. Generated C++ is inspected but
not compiled or executed; native compiler validation remains pending explicit
request, so C++ S2S stays `in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:41](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a = 2;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a = static_cast<int_t>(2);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** same variable in same scope; second assignment is reassignment

**Normalized pattern:** `<var-decl>; <var-reassign>`

**General rule:** On first assignment in a scope, declare with `auto`. On later assignment to the same variable in the same scope, omit `auto`. Literals still follow normal literal conversion rules.

**Diagnostics:** Error if reassignment violates scope rules.


## LIT-STR-003

**v0.2 decision / target C++:** The empty literal is already covered by the
generalized string-literal path agreed for `LIT-STR-001` and `LIT-STR-002`:

```php
$a = "";
```

```cpp
auto local_a = scpp::string_t("");
```

Preparation decodes both `""` and `''` to the same present zero-byte value with
canonical `string` type. Empty does not mean `null`, `false` or absent. The ordinary
string constructor is sufficient; the length-aware `std::string(literal, length)`
path remains reserved for values containing embedded NUL bytes.

**Legacy edge-case review:** The old S2S rendered the already decoded empty PHP
string through its ordinary string-literal path, while separately preserving NUL-
containing values with an explicit length. Existing legacy fixtures also use empty
strings as valid initialized output buffers. Those distinctions still apply. String
truthiness, `empty(...)`, comparisons, concatenation and interpolation are separate
catalog concepts and are not inferred from this literal row.

No production change is required: tokenization and parsing already retain either
quote form, preparation owns exact byte decoding and canonical type, and C++ lowering
owns target escaping and construction. Focused proofs cover both quote forms, exact
zero length/value, canonical type and emitted `scpp::string_t("")`; the existing
embedded-NUL proof guards the distinct length-aware path. Cleanup and source purity
remain covered. LLVM stays deferred. Generated C++ is inspected but not compiled or
executed; native compiler validation remains pending explicit request, so C++ S2S
stays `in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:37](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = "";
```

**Existing C++ lowering / result**

```cpp
auto a = string_t("");
```

**Category:** Literal

**Rule kind:** generation

**Source support status:** covered-by-LIT-STR-001

**Preconditions:** same as LIT-STR-001

**Normalized pattern:** `<var> = <string-literal>`

**General rule:** Same generalized rule as LIT-STR-001.

**Notes:** Empty string stays under the same generalized string-literal rule.


## LIT-CONST-001

**v0.2 decision / target C++:** A bare identifier in expression position is a
constant reference unless call punctuation follows it. Constants have their own
non-assignable AST specialization, collection role, exact case-sensitive scope
namespace, immutable language definition and prepared reference facts. The fixed
language definition of `PHP_INT_MAX` has canonical `int` type and retains its exact
decimal value independently of the host PHP runtime. It emits:

```cpp
auto local_a = static_cast<scpp::int_t<>>(9223372036854775807LL);
```

The backend lowers the semantic value through the same integer representation path
as literals, so it adds only `scpp/int_t.hpp`; it does not emit a runtime symbol or
include `core/string_support.hpp`. `PHP_INT_MAX()` remains a call, `$PHP_INT_MAX`
remains a variable, wrong-case and unknown names fail during preparation, and
constants cannot be assignment targets. User constants, namespace qualification,
class/magic constants and general constant folding remain outside this slice.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:38](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = PHP_INT_MAX;
```

**Existing C++ lowering / result**

```cpp
auto a = PHP_INT_MAX;
```

**Category:** Constant

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** generator startup snapshot from `get_defined_constants()` contains the resolved constant name; first assignment in scope

**Normalized pattern:** `<var> = <predefined-const>`

**General rule:** Predefined/runtime constants discovered from `get_defined_constants()` at generator startup must lower to unqualified helper/constant names inside generated source because the source namespace block already uses `using namespace ::scpp;``. Generator-emitted runtime/helper references inside generated expression/type code must not use rooted `::scpp` / `::scpp::php` qualifiers. User-defined constants do not use this rule.

**Diagnostics:** Emit an error only if resolution/classification fails; do not guess that an unknown constant is predefined.

**Notes:** Classification depends on the PHP runtime/version executing the generator, so the target/test PHP version must stay aligned.


## VAR-CHAIN-001

**v0.2 decision / target C++:** Plain-variable assignment chains parse as
right-associative nested `assignment_expression_node` objects. Each target keeps its
own write occurrence and prepared binding. Preparation evaluates and publishes the
inner write before classifying the outer inferred target, so declaration versus
reassignment is decided independently at every depth. The first example emits:

```cpp
auto local_b = static_cast<scpp::int_t<>>(1LL);
auto local_a = local_b;
```

Statement generation flattens arbitrary-depth plain-variable chains from the
innermost write outward. Each outer step reads the preceding target's stored value,
preserving conversions and evaluating the original RHS once. Existing inner or
outer locals become assignments normally. For `$a = $a = 1;`, the inner write is
the single declaration and the outer write is a reassignment to that identity.
Member/index targets, typed-declaration initializers, reference/compound chains and
assignment expressions embedded in calls, returns or other expressions remain
outside this slice. Sequential-assignment catalog rows are not completed by this
recursive chain implementation.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:42](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b = 1;
```

**Existing C++ lowering / result**

```cpp
auto b = static_cast<int_t>(1); auto a = b;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** chain introduces `b` by first assignment in scope

**Normalized pattern:** `<var> = (<var> = <expr>)`

**General rule:** Chained assignments must be decomposed into sequential statements. The inner assignment is evaluated first and follows normal declaration rules. The outer assignment assigns the resulting value.

**Diagnostics:** Error if generator emits chained assignment directly without normalization.


## VAR-CHAIN-002

**v0.2 decision / target C++:** This row is sequential variable copying, not a
nested assignment chain. It reuses the established source-order variable-reference
and first-write binding path without new AST or backend cases. The exact input emits:

```cpp
auto local_a = static_cast<scpp::int_t<>>(1LL);
auto local_b = local_a;
```

Preparation resolves `$a` to its earlier declaration, gives `$b` a distinct
declaration identity with the same canonical type, and rejects reads before an
established declaration. The outer copy is direct: it does not repeat literal
normalization or insert a conversion when both identities have the same type.
The broader copy proof mutates `$a` afterward and verifies that `$b` retains its
copied value. Longer sequential copy series remain independently tracked by
`VAR-CHAIN-003`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:43](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $b = $a;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); auto b = a;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** `a` declared before second statement; `b` first assignment in scope

**Normalized pattern:** `<var> = <expr>; <var> = <var>;`

**General rule:** Sequential assignments must respect declaration rules per statement. Each first assignment in scope introduces a new variable using `auto`.

**Diagnostics:** Error if source variable is used before declaration, literal not normalized, or `auto` omitted on first assignment.


## VAR-CHAIN-003

**v0.2 decision / target C++:** This row is a longer use of the sequential-copy
path established by `VAR-CHAIN-002`; it is not a nested assignment expression and
requires no new syntax or semantic facts. The exact input emits in source order:

```cpp
auto local_a = static_cast<scpp::int_t<>>(1LL);
auto local_b = local_a;
auto local_c = local_b;
```

Preparation creates three distinct declaration identities with one canonical
integer type. The second initializer resolves exactly to `$a`, and the third
initializer resolves exactly to `$b`; lowering therefore copies each immediately
preceding stored value without repeating literal normalization or bypassing an
intermediate binding. Existing declaration-versus-assignment facts and direct-copy
emission own the behavior. Arbitrary composed expressions and reference/handle copy
semantics remain outside this row. LLVM remains deferred.

**Legacy edge-case review:** The old S2S tracked local declarations per active
scope and treated each first write separately. Its catalog also required every
source to precede its use. The applicable constraint is retained: a later write
does not make an earlier read valid, and sequential copies are not reordered.

**Verification (2026-09-30):** the focused PHP S2S proof checks the exact three
declarations, distinct identities, immediate-source resolution, shared canonical
type, direct copy spelling, cleanup and source purity. Generated C++ is inspected
but not compiled or executed; native compiler validation remains pending explicit
request, so C++ S2S stays `in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:44](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $b = $a; $c = $b;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); auto b = a; auto c = b;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** each source variable declared before use

**Normalized pattern:** `<var> = <expr>; <var> = <var>; <var> = <var>;`

**General rule:** Sequential assignments are emitted in order. Each first assignment in scope declares the target with `auto`. Variable references use the normalized C++ identifier form.

**Diagnostics:** Error if a source variable is used before declaration or if a literal is not normalized.


## VAR-CHAIN-004

**v0.2 decision / target C++:** The initial arithmetic slice accepts binary `+`
when both operands have the canonical Simple C++ `int` identity. The exact input
emits:

```cpp
auto local_a = static_cast<scpp::int_t<>>(1LL);
auto local_b = (local_a + static_cast<scpp::int_t<>>(1LL));
```

The existing `binary_expression_node` owns its left operand, operator token site and
right operand. Preparation visits operands in source order, resolves `$a` to its
established declaration, requires both operand types to be the canonical integer,
requires order-independent literal/reference/constant/addition operand shapes, and
attaches a backend-neutral `addition` operation plus the canonical result type. The
result is not addressable. C++ emission reads that prepared operation, renders both
already-prepared operands recursively, parenthesizes the operation and requests the
runtime generated-operator header. It does not inspect the operator token or infer
operand/result types.

**Legacy edge-case review:** The old S2S recursively normalized literal operands and
parenthesized binary arithmetic, but its type-blind lowering could leave invalid
operand combinations to C++. The recursive normalization and grouping still apply.
v0.2 instead rejects undeclared operands, booleans and noncanonical narrow integers
during preparation because promotion/result rules for those cases have not been
agreed.

Float/mixed arithmetic, strings, integer promotion across fixed-width aliases,
operators other than `+`, unary operators, explicit source parentheses and compound
assignment remain outside this slice. Calls and other potentially effectful operands
also wait for prepared evaluation/effect ordering rather than relying on C++ operand
order. The reusable binary path may host those later, but this row does not mark
their catalog entries complete. LLVM remains deferred.

**Verification (2026-09-30):** focused PHP S2S proofs cover the exact AST shape,
prepared operation, operand and result types, source declaration identity,
non-addressability, normalized C++ spelling, runtime operator include, cleanup and
source purity. Focused rejection proofs cover an undeclared operand, a boolean
operand, an unresolved narrow-integer promotion and an effectful call operand.
Generated C++ is inspected but not compiled or executed; native compiler validation
remains pending explicit request, so C++ S2S stays `in-progress`.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:45](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $b = $a + 1;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); auto b = a + static_cast<int_t>(1);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source variables declared before use; expression type-compatible

**Normalized pattern:** `<var> = <expr>; <var> = <expr>;`

**General rule:** When assigning from an expression, the expression must be fully normalized, and the target variable is declared with `auto` if it is the first assignment in scope.

**Diagnostics:** Error if variable used before declaration or literal not normalized.


## VAR-ORDER-001

**v0.2 decision / target C++:** The exact standalone input is rejected. Its first
statement reads `$b` before any accessible declaration exists; the later assignment
cannot retroactively establish that declaration. Preparation reports:

```text
S2S needs an established local declaration for b
```

No C++ is emitted. The imported C++ result applies only when its stated precondition
is supplied by an earlier declaration outside the shown two statements; ordinary
copy and reassignment rules then already own the valid form. Preparation's
source-order scope lookup is the semantic owner, and no hoisting, lookahead fact or
backend recovery is introduced. Branch scope and cross-file/global lookup remain
outside this row. LLVM remains deferred.

**Legacy edge-case review:** The old catalog made prior declaration an explicit
precondition and prohibited statement reordering. Its declaration tracking also
distinguished first writes from already-declared locals. v0.2 makes the failure
deterministic in semantic preparation rather than allowing invalid C++ to expose
the missing precondition.

**Verification (2026-09-30):** the exact PHP S2S rejection proof checks the named
diagnostic, unchanged source syntax, and absence of prepared-file or C++ publication.
Because rejection is the agreed result, C++ S2S is `not-applicable` for this exact
input.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:46](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b; $b = 1;
```

**Existing C++ lowering / result**

```cpp
auto a = b; b = static_cast<int_t>(1);
```

**Category:** Variable

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** `b` must already be declared before the first statement

**Normalized pattern:** `<var> = <var>; <var> = <expr>;`

**General rule:** A variable may be used only if it is already declared in an accessible scope at that point. The generator must not reorder statements.

**Diagnostics:** Error if `b` is not already declared before first use.

**Notes:** No hoisting / no reordering.


## VAR-REASSIGN-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:47](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a = $a + 1;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a = a + static_cast<int_t>(1);
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable already declared; expression type-compatible

**Normalized pattern:** `<var> = <expr>; <var> = <expr>;`

**General rule:** Reassignment must not use `auto`. The right-hand side must be fully normalized according to expression rules.

**Diagnostics:** Error if `auto` is used on reassignment, variable used before declaration, or literals not normalized.


## VAR-REASSIGN-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:48](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 1; $a = $a + $a;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(1); a = a + a;
```

**Category:** Variable

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** variable already declared; operands type-compatible

**Normalized pattern:** `<var> = <expr>; <var> = <expr>;`

**General rule:** Reassignment expressions involving only variables do not require additional normalization beyond initial literal normalization.

**Diagnostics:** Error if variable used before declaration or initial literal not normalized.


## IDENT-VAR-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:243](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int $int) { $while = $int; }
```

**Existing C++ lowering / result**

```cpp
void f(int_t int__) { auto while__ = int__; }
```

**Category:** Naming

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** generated C++ identifier must not collide with a reserved C++ keyword and must avoid local collisions in the same function-like scope

**Normalized pattern:** PHP variable identifier lowering

**General rule:** PHP variable names are preserved unless the raw name is a reserved C++ keyword. Reserved keyword names lower deterministically to `<name>__`, and if that candidate is already used in the same function-like scope the generator must continue with `<name>__1`, `<name>__2`, and so on until a free identifier is found.

**Diagnostics:** Apply the same remapped identifier consistently in declarations, headers, source definitions, and all local uses.

**Notes:** Applies to locals, parameters, and synthesized names that participate in the function-like variable map.


## TYPE-VAR-005

**Scope:** Legacy annotation syntax; deferred. This is not a strict-mode implementation prerequisite.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:354](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
/** string */ $x = "test";
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Type system

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** explicit local variable typing is requested, but the type comment is not placed immediately after the variable token

**Normalized pattern:** non-immediate local type comment

**General rule:** Explicit local variable typing is supported only in the strict immediate-after-variable form such as `$x /** string */ = "test";`. Detached, leading, or post-initializer type comments are rejected.

**Diagnostics:** Emit an error when the local type comment is not attached in the supported immediate form.

**Notes:** This keeps token-based local type extraction simple and deterministic.


## NOTE-011

**Source:** [generators/php/specs/rules.md:153](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 3. Literal Normalization
>
> All literals must be normalized.
>
> ### Required forms
> - integer â†’ `static_cast<int_t>(v)`
> - float â†’ `static_cast<float_t>(v)`
> - bool â†’ `static_cast<bool_t>(v)`
> - string â†’ `string_t("...")`
>
> Applies to:
> - assignments
> - expressions
> - returns
> - function arguments
> - default values
>
> ---

## NOTE-021

**Source:** [generators/php/specs/rules.md:308](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 11A. Variable naming normalization
>
> - PHP variable names are preserved unless the raw name is a reserved C++ keyword
> - reserved keyword names lower to `<name>__`
> - if that candidate already exists in the same function-like scope, the generator must try `<name>__1`, `<name>__2`, and so on until a free identifier is found
> - the chosen remapped identifier must be used consistently in declarations, headers, source definitions, helpers, and all uses within that function-like scope
>
> ---

## NOTE-033

**Source:** [generators/php/specs/rules.md:629](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 4. Scope model
>
> A scope is:
> - a function body
> - a namespace body
> - the global namespace body
>
> This rule is used for first-assignment / `auto` decisions.

## NOTE-034

**Source:** [generators/php/specs/rules.md:638](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 5. Variable model
>
> - PHP variables map to native C++ identifiers by removing the `$` prefix.
> - Example: `$a` -> `a`
> - First assignment in the current scope -> declare with `auto`
> - Reassignment in the same scope -> no `auto`
>
> Examples:
> ```cpp
> auto a = static_cast<int_t>(1);
> a = static_cast<int_t>(2);
> ```

## NOTE-035

**Source:** [generators/php/specs/rules.md:651](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 6. Global literal normalization rule
>
> **All literals must always be converted to runtime-compatible C++ forms. No exceptions.**
>
> This applies:
> - in assignments
> - in expressions
> - in returns
> - in function arguments
> - in conditions
> - condition lowering must use `static_cast<bool>(...)` for expressions already known to produce `bool_t`
> - condition lowering must use `php::condition_truthy(...)` plus `static_cast<bool>(...)` for non-`bool_t` expressions that are allowed to enter control flow; `mixed_t` is only valid when its runtime payload is bool/int/float
> - in branch bodies
> - in loop bodies
>
> ### 6.1 Primitive literal normalization
> - `int` -> `static_cast<int_t>(...)`
> - `float` -> `static_cast<float_t>(...)`
> - `bool` -> `static_cast<bool_t>(...)`
>
> Examples:
> ```cpp
> auto a = static_cast<int_t>(10);
> auto a = static_cast<float_t>(10.5);
> auto a = static_cast<bool_t>(true);
> ```
>
> ### 6.2 String literal normalization
> PHP string literals must first be normalized into valid C++ string literals, then materialized as `string_t("...")`.
>
> Examples:
> ```cpp
> auto a = string_t("x");
> auto a = string_t("");
> ```
>
> ### 6.3 String restriction
> Never emit:
> ```cpp
> static_cast<string_t>(...)
> ```
>
> Always emit:
> ```cpp
> string_t(...)
> ```
>
> ### 6.4 Constant normalization
> The generator snapshots `get_defined_constants()` once at startup. Inside generated source namespace blocks, predefined/runtime constants lower to unqualified names because the source already uses `using namespace ::scpp;``. Generator-emitted runtime/helper references inside generated expression/type code MUST NOT use rooted `::scpp` or `::scpp::php` qualifiers; the only allowed rooted occurrences are the generated using-directives themselves and explicit import-lowering forms such as `use` declarations. User-defined constants stay in the generated user namespace model.
>
> Examples:
> ```cpp
> auto a = PHP_INT_MAX;                // inside generated `.cpp` namespace blocks with `using namespace ::scpp;`
> auto c = LIMIT;                      // user-defined constant in the current generated namespace
> auto d = A::B::LIMIT;                // user-defined constant in another generated namespace
> ```
