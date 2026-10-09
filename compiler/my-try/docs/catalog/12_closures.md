# 12. Closures and callable values
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires functions, scope resolution and lifetime. Begin with no captures, then value/reference capture.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Implementation prerequisites

Follow the [catalog gates](README.md#prerequisite-gates). Start with a capture-free
slice of `CLOSURE-001` and invocation (`CLOSURE-CALL-001`); split the control-flow and
argument-count combinations before implementation. Establish the callable signature
and relevant scope/type boundaries before adding captures. A STAN-labelled imported
case does not authorize work on the parked legacy analyzer.

Value captures require resolved captured identities and copy/sharing semantics.
Reference captures (`CLOSURE-USE-002`) additionally require the applicable lifetime
and escape rules from [chapter 07](07_references_lifetime.md). Arrow captures use the
same semantic basis; container returns/storage wait for chapter 06's relevant facts.
General escape analysis is not a prerequisite for a bounded nonescaping slice, but
unsupported escaping forms must not silently inherit unsafe C++ behavior. Imported
cards below retain their original order and provenance.

## Progress

### Current handoff — 2026-10-09

The capture-free slice and coordinated callable prerequisite are agreed in the
[closure foundation plan](../planning/closure_foundation.md). The first checkpoint
extracts authored signature data by composition and preserves existing named-callable
behavior. The next syntax checkpoint adds expression-owned closures with shared
body/scope construction and traversal, and one parameter grammar. Source-local
anonymous identities now survive body reuse and retire through join-ordered cleanup.
The dependency scheduler now accepts independently settled `contract_work`, with
named declaration lookup as an adapter; signatures no longer inherit named work.
Anonymous signature/body work now uses shared parameter/return checking and the
dependency scheduler, including retry and retirement. The capture-free vertical
slice supports distinct concrete types, inferred locals/copies, ordinary
invocation and native C++ lambda emission without type erasure;
explicit `function<R(Args...)>` locals and by-value parameters additionally support
exact signature conversion to `std::function`. Explicit callable locals default to absent;
presence checks and reset share the ordinary observation/cleanup paths.
Explicit by-value `use ($name)` captures now preserve ordinary copy/sharing semantics
and have read-only bindings. Shared object/dynamic contents remain mutable.
Mutable by-value bindings remain deferred. Arrows now reuse the same closure
node and capture fields with scope-aware automatic value-capture discovery. Explicit
parameter/return signatures must be authored or supplied by an explicit expected
callable contract. Concrete closures and arrows now share
function/method defaults, typed variadics and writable/read-only reference inputs.
Erased callable conversion remains fixed, required and by-value: defaults, packs and
references reject rather than silently losing their contracts. Concrete reference
returns now reuse the single caller-owned reference-parameter contract, including
aliases, forwarding, copying and recursive-component rejection. Captured/local/interior
storage cannot escape; erased reference-return signatures remain deferred. The imported rows below
are not implementation claims. Preserve parameter names for
future named arguments without confusing them with anonymous callable identity.

Bounded reference captures now execute using the approved
[lexical lifetime-region prerequisite](../planning/closure_foundation.md#reference-capture-prerequisite-discovered--2026-10-09).
Collected name scopes do not represent ordinary block storage lifetimes; neutral
semantic regions now own that proof. Whole locals/parameters and eligible aliases
can be borrowed, with readonly access and transitive local-copy checks. Erasure of
borrowing closures and captured-field borrowing remain rejected. Proof:
25 reference-capture programs and 70 existing closure programs, plus focused lifetime,
incremental and recovery checks; no native compiler rebuild.

Receiver capture now uses that same borrow path: explicit `use ($this)` and automatic
arrow captures borrow the current class/struct instance for the creating invocation.
Private/protected access, nested captures and trait consumers preserve the original
declaring context. `use (&$this)`, absent/static receivers and erased borrowing
environments reject. Owning/escaping receiver capture remains deferred.

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [CLOSURE-001](#closure-001) | implemented-bounded | `$f = function (int $a): int { if ($a === 1) { return 2; } return $a; };` | passed | passed | deferred | Capture-free explicit signatures; concrete local types; `closures.php` |
| [CLOSURE-CALL-001](#closure-call-001) | implemented-bounded | `$f(); $g(3); $h(3); $h(3, 9);` | passed | passed | deferred | Shared defaults, typed variadic packs and value/reference inputs; ordered arguments; generated execution |
| Concrete reference returns | implemented-bounded | `$f = fn&(int &$v): int => $v; $alias =& $f($value);` | passed | passed | deferred | Shared origin contract; capture/local escape and cycles reject; erased signatures deferred |
| Borrowed receiver capture | implemented-bounded | `$f = function () use ($this): int { return $this->count; };` | passed | passed | deferred | Shared borrow lifetime; arrows, nested access and class/struct receivers; no owning capture |
| Callable returns/factories | implemented-bounded | `function make(int $n): function<int()> { return fn(): int => $n; }` | passed | passed | deferred | Shared return conversion; exact erased signatures; owned environments only; `closures.php` |
| [SCOPE-VAR-003](#scope-var-003) | implemented-bounded | `$f function<int(int)>; if (true) { $f = fn($x) => $x + 1; } return $f(5);` | passed | passed | deferred | Absent defaults, presence, assignment/copy/reset and throwing invocation; `empty_callables.php` |
| [SCOPE-VAR-002](#scope-var-002) | implemented-bounded | `if (true) { $g = function (): int { return 1; }; } return $g();` | rejection passed | not emitted | deferred | Existing lexical scope; `closures.php` |
| [CLOSURE-STAN-001](#closure-stan-001) | implemented-bounded | `$f function<string()> = function (): int { return 1; };` | rejection passed | not emitted | deferred | Exact signature mismatch rejected in shared preparation |
| [CLOSURE-USE-001](#closure-use-001) | implemented-bounded | `$f function<int()> = function () use ($a): int { return $a; };` | passed | passed | deferred | Authored/contextual signature, copyable captures, read-only bindings |
| [CLOSURE-CALLABLE-001](#closure-callable-001) | implemented-bounded | `$f function<int()> = function () use ($a) { return $a; };` | passed | passed | deferred | Context supplies omitted types at initializer/assignment/argument/return boundaries; `contextual_closures.php` |
| [CLOSURE-CALLABLE-002](#closure-callable-002) | implemented-bounded | `$add = function (int $x, int $y) use ($base): int { return $base + $x + $y; };` | passed | passed | deferred | Explicit signatures and captures; inferred storage remains concrete, unlike imported v0.1 erasure |
| [ARROW-001](#arrow-001) | implemented-bounded | `$a = 10; $f = fn(int $x): int => $x + $a;` | passed | passed | deferred | Same closure node/body/capture pipeline; nested scope, snapshot, boundary and incremental proofs |
| [CLOSURE-USE-002](#closure-use-002) | implemented-bounded | `$f = function () use (&$a): int { return $a; };` | passed | passed | deferred | Concrete locals; lexical lifetime and transitive-copy checks; erased borrowing closures rejected |
| [CLOSURE-RETURN-001](#closure-return-001) | implemented-bounded | `$f = function () use ($a): vector<int> { return [$a, 2]; };` | passed | passed | deferred | Reuses ordinary function return/literal preparation; generated execution |
| [CLOSURE-REJECT-001](#closure-reject-001) | implemented-bounded | `$a = 1; $f = function () use ($a) { return $a; };` | rejection passed | not emitted | deferred | Missing return type rejects; `closures.php` |
| [CLOSURE-REJECT-002](#closure-reject-002) | implemented-bounded | `$items mixed = []; $items[] = function (): int { return 1; };` | rejection passed | not emitted | deferred | Established mixed/dynamic destinations reject callable payloads; `closures.php` |

### Reconciliation — 2026-10-09

All 13 imported IDs are implemented within their stated positive/rejection bounds,
including empty explicitly typed callable storage.
Concrete reference returns and borrowed receivers add two
implemented rows; callable returns/factories subsequently add a third. This is card reconciliation, not whole-chapter completion: imported
prose and extensions contain further capabilities.

| Remaining capability | Current boundary / owner |
| --- | --- |
| Authored callable property defaults | Explicitly deferred by user on 2026-10-09; [CH15-CALLABLE-DEFAULTS-01](15_blocking_debt.md#ch15-callable-defaults-01--authored-callable-property-defaults); use constructor inputs or ordinary assignments |
| Callable wrappers | Selected for CH12 follow-up on 2026-10-09; currently gated; discuss supported wrapper contracts, without assuming redundant nullable storage |
| Callable payloads in mixed/dynamic | Rejection is implemented; extension remains [CH15-CALLABLE-01](15_blocking_debt.md#ch15-callable-01--callable-values-in-mixeddynamic-entries) |
| Explicit reference returns | Selected for CH12 follow-up; preserve whole-reference-parameter origin and lifetime contract |
| Captured-field borrows | Selected for CH12 follow-up; establish stable field provenance and containing-storage lifetime checks |
| Defaults across erased callable boundaries | User-deferred; [CH15-CALLABLE-PARAM-DEFAULTS-01](15_blocking_debt.md#ch15-callable-param-defaults-01--defaults-across-erased-callable-boundaries) |
| Owning/escaping receiver captures | User-deferred; [CH15-CALLABLE-RECEIVER-01](15_blocking_debt.md#ch15-callable-receiver-01--owning-and-escaping-receiver-captures) |
| Named arguments | Existing [CH15 debt](15_blocking_debt.md#ch15-named-args-01--named-arguments-across-source-callables); no new anonymous naming contract |

Callable returns now use explicit `function<R(Args...)>` contracts across functions,
methods and closures. Existing erased conversion admits capture-free/copyable value
environments and rejects direct/transitive borrows. Nested callable signatures and
already-erased forwarding share that path; no new backend factory implementation.
Contextual signatures now fill omitted types from explicit callable contracts through
the shared expected-type consumer path. Authored annotations are not overwritten;
no body-based inference or new erasure permissions are introduced. Cross-file contract
edits preserve anonymous identity and rebuild semantic signatures, not source syntax.
Explicit callable instance/static properties and vector/hash elements now share
ordinary construction, conversion, copy and invocation paths. Property defaults are
absent; authored closure defaults remain deferred. Keyed presence tests observe the
selected callable's state without changing lazy path evaluation. Parenthesized
property invocation stays distinct from same-named methods. See `callable_storage.php`.
Void-valued return expressions now share ordinary return preparation across functions,
methods, closures and arrows. Only void-to-void forwarding is admitted; values are
never discarded implicitly. See `void_returns.php`.
Authored callable property defaults are explicitly deferred (2026-10-09). The
capture-free proposal, including callable leaves in instance-container defaults,
is a discussion candidate only, not an approved implementation contract. Revisit
creation context, initialization timing and constructor override rules when resumed.
User selection (2026-10-09): pursue explicit reference parameters, reference returns,
variadics, captured-field borrows and callable wrappers. Defer parameter defaults
across erased boundaries and owning/escaping receivers to CH15. These are selected
work items, not implemented capabilities or approval of unspecified type syntax.
Reference/variadic input contracts are now implemented: `function<void(int&)>`,
`function<int(const int&)>` and `function<int(int...)>` use name-free input syntax,
exact contract interning and shared parameter admission/packing. Context supplies
types but never invents reference/const/variadic markers. Defaults, borrowing
environments and erased reference returns still reject; see `callable_inputs.php`.
Next: reference-return origin contracts, captured-field borrows, then wrapper integration. Preserve existing
rejection boundaries until each shared model and its proofs are in place.

Evidence: `/tmp/my-try-ch12-reconciliation-01` runs focused PHP `closures` and
`closure_model` checks, including added exact rejection shapes. No compiler semantics,
native compiler build or generated-program execution changed in this reconciliation.

## CLOSURE-USE-001

**v0.2 decision / target C++:** Implemented with explicit signatures and ordinary copy/shared semantics.
Concrete locals lower to native lambdas; explicit compatible `function<...>` boundaries
use `std::function`. Explicit expected callable contracts may supply omitted types.

Different source versions share this ID. Both remain below for provenance; the
v0.2 decision above supersedes their differing inferred/erased signature behavior.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:153](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<int()> = function () use ($a): int { return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t()> f = [a]() -> int_t { return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target local has explicit `function<...>` type; closure captures simple local variables

**Normalized pattern:** typed callable local with closure `use` capture

**General rule:** Explicit strict callable locals lower to `std::function<...>` storage. Closure `use ($a)` lowers to a by-value C++ lambda capture at closure creation time.

**Diagnostics:** Error if the capture is not a simple visible local variable or if the closure cannot be rendered as a concrete callable initializer.

**Notes:** Callable locals are concrete typed values, not `mixed_t`.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:314](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function () use ($a) { return $a; };
```

**Existing C++ lowering / result**

```cpp
auto f = [a]() { return a; };
```

**Category:** Closure

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** every captured variable uses by-value capture syntax

**Normalized pattern:** `closure with use capture`

**General rule:** `use ($x, $y)` lowers to native lambda capture-by-value. The current pass copies the lowered local variables into the lambda capture list and preserves those locals inside the closure body.

**Diagnostics:** Emit an error for `use (&$x)` or other unsupported capture shapes.

**Notes:** This pass intentionally excludes PHP reference-capture semantics.


## CLOSURE-USE-002

**v0.2 decision / target C++:** Concrete local closures lower to native `&` captures
after shared whole-storage/access/lifetime checks. Copies preserve borrowing obligations.
The imported erased `function<...>` example below remains rejected in v0.2: erasure
cannot hide borrows until a nonescape contract exists.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:154](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<int()> = function () use (&$a): int { return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t()> f = [&a]() -> int_t { return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target local has explicit `function<...>` type; capture is explicitly by reference

**Normalized pattern:** closure `use` reference capture

**General rule:** Closure `use (&$a)` lowers to a by-reference C++ lambda capture so later writes are visible.

**Diagnostics:** Error if the referenced capture target is not a simple visible local variable.

**Notes:** This mirrors regular PHP capture intent within the current local callable model.


## CLOSURE-CALLABLE-001

**v0.2 decision / target C++:** Implemented: the explicit expected callable contract
supplies omitted return/parameter types. Shared prepared signatures retain the resolved
types; syntax remains unchanged. Authored types must match exactly. Without context,
omitted types reject. Body-based inference remains outside this contract.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:155](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<int()> = function () use ($a) { return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t()> f = [a]() -> int_t { return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target local has explicit complete `function<...>` type

**Normalized pattern:** callable local supplies closure signature

**General rule:** When a closure initializer omits its return type, an explicit callable local type may provide the expected return and parameter signature for lowering.

**Diagnostics:** Error if no expected callable signature exists and the closure contains a value return without an explicit closure return type.

**Notes:** The generator does not infer return types by inspecting return expressions.


## CLOSURE-CALLABLE-002

**v0.2 decision / target C++:** Implemented with a concrete anonymous type for inferred locals,
not implicit `std::function` erasure. Parameters/return remain explicitly authored.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:156](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$add = function (int $x, int $y) use ($base): int { return $base + $x + $y; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t(int_t, int_t)> add = [base](int_t x, int_t y) -> int_t { return base + x + y; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** direct local assignment from closure with explicit parameter and return signature

**Normalized pattern:** direct closure signature synthesis

**General rule:** A direct local assignment from a closure with a complete syntactic signature may synthesize the local `std::function<Return(Params...)>` storage type.

**Diagnostics:** Error if the closure signature is incomplete, if parameters are untyped, or if the assignment is not a direct local closure initializer.

**Notes:** This is structural synthesis only, not general semantic type inference.


## CLOSURE-RETURN-001

**v0.2 decision / target C++:** Implemented through shared return and data-construction preparation.
Returning a vector is supported; returning a callable is a separate deferred boundary.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:157](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function () use ($a): vector<int> { return [$a, 2]; };
```

**Existing C++ lowering / result**

```cpp
std::function<vector_t<int_t>()> f = [a]() -> vector_t<int_t> { return vector_t<int_t>{a, static_cast<int_t>(2)}; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** closure return type is scanner-owned and maps to `vector_t<T>`; returned literal is positional

**Normalized pattern:** closure vector return literal

**General rule:** Scanner-owned closure return annotations such as `: vector<int>` lower to mapped callable return types. A positional array literal returned from a `vector_t<T>` closure/function return context lowers to a typed vector literal.

**Diagnostics:** Error if the array literal contains explicit keys or unsupported element shapes.

**Notes:** This must not rely on php-ast doc-comment ownership for declared returns.


## CLOSURE-REJECT-001

**v0.2 decision / target C++:** Implemented rejection when the return signature is missing.
No body inference is performed. Contextual completion is separately implemented under
CLOSURE-CALLABLE-001.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:158](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function () use ($a) { return $a; };
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Closure

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** no explicit closure return type and no expected callable target signature

**Normalized pattern:** closure missing return type

**General rule:** Untyped closure assignment with a value return is rejected unless the closure has an explicit return type or the target provides a complete callable signature.

**Diagnostics:** Emit a clear generator diagnostic requiring an explicit return type or expected callable signature.

**Notes:** The generator does not infer return types from closure bodies.


## CLOSURE-REJECT-002

**v0.2 decision / target C++:** Implemented rejection for callable values in mixed/dynamic
entries. Explicit typed properties and vector/hash elements are admitted by their
separate shared storage slice; this does not enable boxed callable payloads.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:159](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$items[] = function (): int { return 1; };
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Closure

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** closure assigned into array/dynamic container slot

**Normalized pattern:** closure container storage

**General rule:** Closures are local concrete callable values and cannot be stored in array or dynamic container slots/literals in the current strict model.

**Diagnostics:** Emit a clear generator diagnostic instructing the user to assign the closure to a concrete callable local.

**Notes:** Keeps closures out of `mixed_t` and dynamic table payloads.


## CLOSURE-STAN-001

**v0.2 decision / target C++:** Implemented in shared semantic preparation, without invoking
legacy STAN. Incompatible erased callable signatures reject before C++ emission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:160](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f function<string()> = function (): int { return 1; };
```

**Existing C++ lowering / result**

```cpp
STAN ERROR
```

**Category:** Closure

**Rule kind:** STAN diagnostic

**Source support status:** supported-with-diagnostic

**Preconditions:** explicit callable local signature and explicit closure signature disagree

**Normalized pattern:** callable signature mismatch

**General rule:** A mismatch between an explicit callable local signature and the closure initializer's explicit signature is owned by STAN before build.

**Diagnostics:** STAN should report the mismatch before native generation/compile proceeds in strict projects.

**Notes:** The S2S generator remains structurally driven and does not perform broad callable compatibility inference.


## CLOSURE-001

**v0.2 decision / target C++:** Implemented for explicit signatures using the shared callable lifecycle,
body checking and native lambda lowering. Callable-returning factories now work with
strict explicit return contracts and copyable nonborrowing environments. Imported
legacy comment-based signature spellings are not enabled by this support.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:313](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f = function (int $a): int { if ($a === 1) { return 2; } return $a; };
```

**Existing C++ lowering / result**

```cpp
std::function<int_t(int_t)> f = [](int_t a) -> int_t { if (static_cast<bool>(a == int_t(1))) { return int_t(2); } return a; };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** anonymous function expression is assigned or passed as an expression

**Normalized pattern:** `<var> = function (...) { ... }`

**General rule:** Anonymous functions lower to a native C++ lambda. When the assignment target or inferred closure signature is concrete, the emitted local may use `std::function<Ret(Args...)>` initialized from that lambda. Closure parameters keep declared native PHP types when present; when a closure parameter has no native PHP type, Safe v1 also accepts an inline doc-comment type such as `/** function<int(int)> */ $fn` so the generator can emit a concrete `std::function<...>` parameter type. Closure factories that return another closure may also use the post-signature doc-comment form `$make = function () /** function<int(int)> */ { return function (int $x): int { ... }; };` in the simple single-return case, and the generator treats that attached doc-comment as the outer closure's explicit return type. Closure bodies reuse the normal statement-lowering pipeline, including `if`, `return`, `echo`, and expression statements within the supported subset. If a closure parameter or closure return is given both a native PHP type and a doc annotation, the generator rejects it as a conflicting type source.

**Diagnostics:** Emit an error only for unsupported inner statements/expressions inside the closure body.

**Notes:** This is the first closure pass and does not imply full PHP callable semantics.


## CLOSURE-CALL-001

**v0.2 decision / target C++:** Implemented through shared call admission: ordered arguments,
defaults, typed variadics and reference parameters for concrete closures. Erased callable
boundaries retain their narrower contract.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:315](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f(); $g(3); $h(3); $h(3, 9);
```

**Existing C++ lowering / result**

```cpp
f(); g(int_t(3)); h(int_t(3)); h(int_t(3), int_t(9));
```

**Category:** Closure

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** callee resolves to a closure-valued variable lowered into a native lambda-backed callable (typically `std::function<...>` when a concrete signature is required)

**Normalized pattern:** `closure-valued variable invocation`

**General rule:** Closure-valued variables are invoked with ordinary C++ call syntax. Exact-arity calls are emitted directly. Trailing default arguments work when the lowered native lambda parameter list carries a concrete type and native C++ default argument.

**Diagnostics:** Emit an error only when the closure expression or the underlying call arguments contain unsupported forms.

**Notes:** This pass does not attempt broader PHP callable normalization across strings, arrays, or invokable objects.


## SCOPE-VAR-002

**v0.2 decision / target C++:** Implemented lexical-scope rejection. A local first established in
a nested block is not visible outside it; no PHP-style function-scope hoisting.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:317](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
if (true) { $g = function (): int { return 1; }; } return $g();
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Scope

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** variable was first declared in a nested block and later used outside that block

**Normalized pattern:** `out-of-block variable use`

**General rule:** Variables and closure-valued locals do not escape `if`, loop, or other nested statement blocks in Safe v1.

**Diagnostics:** Emit an explicit generator error naming the variable and source line.

**Notes:** This keeps the lowering aligned with the emitted C++ lexical model rather than PHP variable-table semantics.


## SCOPE-VAR-003

**Strict-mode PHP input example:** `$f function<int(int)>; if (true) { $f = fn($x) => $x + 1; } return $f(5);`

This is the working source spelling. Imported examples and C++ expectations below remain reference material.

**v0.2 decision / target C++:** Explicit callable storage defaults to absent using
ordinary `std::function` default construction. `isset`, conditions and `!` test
presence; `clean`/`unset` reset through the existing whole-storage path. Concrete
closures remain always present and nonresettable. Invocation throws after callee
and argument evaluation; no definite-initialization analysis or nullable wrapper.
See the [normative contract](../../../../specs/language/closures.md#callable-presence-and-reset)
and `empty_callables.php` for state, copies, reset, rejection and ordering proofs.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:318](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$f /** function<int(int)> */; if (true) { $f = function (int $x): int { return $x + 1; }; } echo $f(5);
```

**Existing C++ lowering / result**

```cpp
std::function<int_t(int_t)> f; if (...) { f = [](int_t x) -> int_t { return (x + static_cast<int_t>(1)); }; } php::echo_one(f(static_cast<int_t>(5)));
```

**Category:** Scope

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** local is explicitly predeclared in the current block or an enclosing block

**Normalized pattern:** `typed local predeclaration`

**General rule:** Safe v1 permits a typed local declaration without initialization when the type is explicit. Function-valued locals must use `function<return_type(arg_types)>` annotations so the generator can emit a concrete `std::function<...>` declaration.

**Diagnostics:** Emit an explicit generator error when a later use occurs without an in-scope declaration or when the declared type syntax is invalid.

**Notes:** This is the supported way to predeclare a closure-valued local that will be assigned from child blocks.


## ARROW-001

**v0.2 decision / target C++:** Implemented with explicit signatures, a normalized
ordinary return body and scope-aware automatic value captures. Inferred locals remain
concrete native lambdas; only explicit callable boundaries erase their types.
Read-only captured bindings and ordinary shared-payload semantics apply. Void-valued
return expressions remain shared-return debt, not an arrow-specific exception.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:319](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = 10; $f = fn(int $x): int => $x + $a;
```

**Existing C++ lowering / result**

```cpp
auto a = static_cast<int_t>(10); std::function<int_t(int_t)> f = [a](int_t x) mutable -> int_t { return (x + a); };
```

**Category:** Closure

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** arrow function expression is assigned or passed as an expression

**Normalized pattern:** `fn (...) => expr`

**General rule:** Arrow functions lower to native C++ lambdas using implicit by-value capture inferred from referenced outer locals. php-ast exposes them as `AST_ARROW_FUNC` (kind 72) with the body normalized as a single return statement, so Safe v1 reuses closure parameter and return-type lowering while discovering captures automatically instead of reading a `use (...)` list. Existing callable-signature rules apply: native PHP parameter and return types work normally, inline doc-comment callable parameter types are accepted when needed, and captures are emitted as value captures.

**Diagnostics:** Emit an explicit generator error for unsupported parameter shapes such as variadics/default params or conflicting PHP-vs-doc type sources.

**Notes:** Arrow functions do not support explicit `use (...)`; capture is implicit and by value only.
