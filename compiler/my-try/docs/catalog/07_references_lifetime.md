# 07. References, ownership and lifetime
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires storage places and calls. Discuss aliasing and lifetime before enabling reference paths.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Handoff from chapter 06 — 2026-10-05

Chapter 06's [bounded positive pass](06_containers.md#positive-pass-conclusion--2026-10-05)
is complete. Do not restart its container implementation or re-enable reference
foreach: that feature and its safety foundation remain in Chapter 15.

Existing foundation to reconcile first: explicit typed reference parameters already
bind exact-type, addressable whole local/parameter storage through shared call
preparation. Container proofs cover vector/hash/mixed mutation and dynamic binding
replacement. Indexed reads remain non-addressable, including container-valued slots;
temporaries and inexact reference arguments reject. See `tests/container_calls.php`,
`tests/fixtures/container_calls.php` and Chapter 04's function proofs. This is not a
general alias/provenance model or evidence for reference returns.

Proposed discussion groups (not yet implementation approval):

1. Existing reference-parameter behavior and storage eligibility: FUNC-REF-001,
   strict TYPE-PARAM-003 counterparts and const-reference questions. Verify current
   support before assigning progress to imported rows.
2. Ownership-type classification and copying: CH05-OWN-01, shared/unique/weak and
   effective value restrictions. Keep registry identity, ownership and capabilities distinct.
3. Local bindings and reset/removal: reference locals, `unset`/`clean`, with explicit
   lifetime and alias effects. Keyed container removal is not local destruction.
4. Reference returns and retained aliases: provenance, escape and owner lifetime
   first; container interiors additionally require COW/storage-stability rules.

The initial handoff did not approve new behavior. The subsequently agreed model
slice below establishes provenance; source aliases and reference returns remain gated.

## Storage provenance and reference-return model — 2026-10-06

Agreed sequence: preserve the existing reduced alias restrictions, establish the
model first, then enable local aliases and bounded reference returns separately.
No PHP-style rebinding, conditional/loop alias declarations or container/dynamic
interior borrows are added. Initial reference returns will select one explicit
reference parameter, with an explicit exact return type and a single return.
Locals, by-value parameters and temporaries cannot supply an escaping reference.
Forwarding requires a proven origin, not merely an addressable result.

Implemented at the model-only checkpoint (source integration follows below):

- `prepared_expression::place` replaces the independent addressability flag.
  `addressable()` derives from its presence. A `prepared_place` identifies an
  original local declaration or parameter, optionally projected through direct
  stable struct fields. Index reads and temporary-derived fields have no place.
- Place records observe storage facts weakly and share their base place. They do
  not retain syntax, local environments or workers, and do not certify escape safety.
- Optional `callable_signature::reference_return` holds an immutable symbolic
  parameter index. Structural validation requires a fixed reference parameter and
  the exact return/storage type. Value returns use absence, not a second kind flag.
- Signature comparison observes result mode and origin. Static initialization
  factories remain value-returning; reference-return calls reject during preparation
  until source-body lifetime validation and call-site origin substitution exist.

Existing whole-storage reference calls and direct stable struct-field arguments
continue unchanged, including by-value versus by-reference dynamic binding behavior.
At that checkpoint, source reference returns and local alias syntax were not enabled.
Ownership-template classification, const semantics and escaping initialization views
remain separate work; reference foreach stays disabled under Chapter 15.

Proof: `tests/reference_model.php` covers root/field identity, lexical shadowing,
value-versus-reference parameter origins, invalid contracts, the execution gate,
signature comparison and cleanup/re-preparation with unchanged C++ output. The
2026-10-06 focused run passed 19 suites. Two additional older suites (`interpolation`
and `logical`) stop at stale rejection expectations; both failures reproduce on
unmodified `07ddd9a0`. Reconcile these in their operator-owning slice, not as reference
semantics. Evidence: `/tmp/my-try-ch07-model-20261006`,
`/tmp/my-try-ch07-model-remaining-20261006`, and
`/tmp/my-try-ch07-baseline-proof-20261006`; the final model recheck is under
`/tmp/my-try-ch07-model-final-20261006`. No native compiler rebuild for this slice.

The next checkpoint below integrates those previously gated source paths.

## Local aliases and bounded reference returns — 2026-10-06

Implemented both `$alias =& $value` and `$alias T =& $value` using the existing
assignment/declaration nodes with explicit syntax markers. Function syntax records
`function &name`. Parsing/collection do not decide lifetime safety.

`Reference_Preparation` owns alias admission, original-place selection, symbolic
return obligations and body validation. Bindings retain an optional shared
`alias_target` place; reads retain their own declaration/name but use that original
place. Ordinary writes do not rebind it. No reference type is added to the registry.
New alias roots reject, and no conversion/copy is performed at a binding boundary.
Transient context flags prohibit new bindings within conditional/loop/switch regions.
Plain blocks follow existing lexical lifetime and shadowing rules.

Reference-returning signatures require exactly one fixed reference parameter of
the exact return type. Other by-value parameters remain possible. Declaration work
publishes the origin without inspecting a body; body work independently requires
one final unconditional return of that whole parameter, directly or through aliases.
Every statement is checked, including unreachable returns. Successful preparation
of the whole compilation remains required before emission. Body-only unsafe edits
therefore withhold output even when the published signature remains unchanged.

Calls substitute the actual argument's stable place. C++ signatures and sequencing
lambdas both return `T&`; alias declarations emit `T&`, ordinary assignments still
copy, and verified returns bypass value conversion. Direct stable struct fields may
be call arguments, but projecting a field within the reference-returning body is
outside its whole-parameter contract. No owner lifetime is extended.

Limits at this checkpoint: source reference-returning calls inside reference-returning
functions were gated pending the forwarding integration below.
Installed runtime reference returns, const semantics, reset/removal and ownership
classification are not added. Reference foreach stays in Chapter 15.

**CH07-WARN-01:** the existing return-by-reference advisory remains intended, but
my-try has no retained warning/reporting path. Add it through a diagnostics owner,
with incremental replacement/cleanup and host/native delivery, rather than emitting
backend-specific warnings from preparation. Safety rejections already happen during
preparation; this debt concerns non-fatal advice only.

Proof runner: `python3 compiler/my-try/tests/references.py --results FRESH`.
It checks alias/parameter origin, scope, exact typing, rebindings, temporary/interior
rejections, alias returns, value copies, left-to-right argument evaluation and
incremental signature/body failure/recovery. It compiles and executes generated C++;
it does not rebuild the compiler natively. See the authoritative
[reference contract](../../../../specs/references.md#v02-bounded-local-aliases-and-reference-returns).

Verified evidence: `/tmp/my-try-references-verified-20261006` passes 8 focused PHP
suites, 34 rejection cases and all 11 generated-C++ executions. Six additional
retained suites pass under `/tmp/my-try-references-regression-20261006` and
`/tmp/my-try-references-calls-20261006` (the calls suite was rerun with its required
output-directory argument). No native compiler rebuild was performed. The two
pre-existing operator-suite failures from the model checkpoint remain separate debt.

## Nonrecursive reference forwarding — 2026-10-06

Implemented direct forwarding and forwarding through local aliases while preserving
the single-reference-parameter, exact-type, sole-final-return contract. Calls already
substitute their actual argument's place; the final return must still select the
enclosing function's whole reference parameter. Local borrows through calls are valid
for local use, but returning them rejects. No parser or C++ lowering changes were
needed: the existing call sequencing already preserves `T&`.

The blanket ban on reference-returning calls inside reference-returning bodies is
replaced by a post-settlement cycle check. `Reference_Cycles` consumes current
collected function/call indexes and prepared call targets, including unchanged bodies.
Its transient integer-edge graph uses strongly connected components to reject cycles
containing reference-returning functions, even through by-value helpers. Ordinary
by-value recursion remains permitted. The structural check includes unreachable calls
and unused functions; it does not attempt termination analysis.

The preparation worker marks cyclic reference-return bodies failed using its normal
retry/publication protocol. Body-only edits, no-change retries, recovery, and removal
of previously cyclic declarations are covered. No retained graph, body dependency
protocol, runtime borrowing mechanism or new backend operation is introduced.

Proof: the shared references runner now exports 15 generated programs and checks 39
source rejections. `tests/reference_forwarding.php` adds cross-file preparation and
incremental cycle/lifetime recovery. Cross-file execution is not claimed: current C++
assembly still accepts one prepared source file. Runtime-registered reference returns,
recursive reference returns, const/ownership extensions and CH07-WARN-01 remain debt.

Evidence: `/tmp/my-try-forward-proof-20261006` passes 9 focused PHP suites and all
15 generated-C++ executions. `/tmp/my-try-forward-final-php-20261006` repeats those
PHP checks after adding the single-file failed-emission guard, and
`/tmp/my-try-forward-regression-20261006` passes four additional retained suites.
The compiler itself was not rebuilt natively for this slice.

## Whole-storage resets — 2026-10-06

Agreed and implemented: `unset($owner)` resets nullable storage; native aliases
and reference parameters cannot be unset. `clean($variable)` resets bool/integer/
float/string, vector/hash and mixed/dynamic storage, including write-through aliases
and reference parameters. Neither removes a name or changes its lifetime. Mixed and
dynamic become null; a separate copied dynamic binding retains its old shared object.
The [normative contract](../../../../specs/native_reference_safety.md#v02-whole-storage-reset-and-aliases--2026-10-06)
also distinguishes keyed removal, whose lazy/no-creation behavior is unchanged.

One `cleanup_node` abstract owns the target syntax/maintenance; final `unset_node`
and `clean_node` retain the distinct language operations. `Cleanup_Preparation`
selects either a `prepared_storage_reset` with the admitted canonical type or a
`prepared_keyed_removal` owning the established keyed recipe. Reset admission uses
semantic roles and exact registered built-in identities, not C++ names, generic
default constructibility or incidental indexing permission. Alias unset checks the
named binding rather than its flattened original place. No new lifetime graph exists.

C++ consumes that decision: `target = ExactType{};` for resets, the existing lazy
path lowerer for removal. The fixed reset form needs no helper/template framework,
interior borrow or duplicate per-container generator. Compile-time improvement is
not claimed without measurements. This supersedes the imported `php::clean` spelling
for this compiler. Legacy runtime scalar `unset` overloads remain implementation
evidence, not permission to bypass the narrower source contract.

Ownership-pointer resets were deferred here and are integrated in the empty-state
checkpoint below. Remaining non-goals/debt: arbitrary structs/Result
cleanup, direct property resets, indexed `clean`, variadic/value-producing forms,
PHP-style symbol removal and reference detachment. Legacy runtime overload cleanup
is separate from this compiler admission slice; no runtime behavior was broadened.

Proof: `tests/cleanup.py` runs retained preparation/recovery suites and generated C++
cases for defaults, alias persistence, reference/value parameters, field aliases,
mixed COW and dynamic shared-object preservation. `tests/keyed_paths.py` verifies
existing lazy keyed behavior after the shared syntax/fact refactor. These are
generated-program proofs, not a native compiler rebuild.

Evidence: `/tmp/my-try-cleanup-final-20261006-2` passes seven PHP suites,
22 reset rejection cases and all 12 reset programs without PCH. The earlier
`/tmp/my-try-cleanup-20261006-3` passes the same 12 program scenarios with PCH;
the final run additionally strengthens type-change/arity checks and float-default
verification. `/tmp/my-try-cleanup-keyed-20261006` passes five PHP suites and
44 existing keyed programs. `/tmp/my-try-cleanup-final-php-20261006` repeats all
seven focused PHP suites after final source consolidation. The runner used its documented CLI fallback because
the sandbox denied FPM socket creation.

## Ownership model and direct nesting — 2026-10-06

Implemented model slice: one final `ownership_type_definition` extends the template
abstract and implements `ownership_semantics`; its immutable `ownership_kind`
distinguishes shared, unique and weak. Definitions/application identities and C++
bindings remain separate. The role exposes the exact target type; no parser names,
backend spellings or new top-level type family identify ownership.

The registry owns direct type-composition invariants: reject an immediate ownership
target in all nine combinations, and reject `value<ownership-wrapper>` before
publishing a type use. Source preparation reports these as ordinary diagnostics.
Shared/weak handles are copyable independently of target copyability; unique handles
remain noncopyable. No recursive ban on ordinary containers or named holders is added.

The [runtime contract](../../../../runtime/specs/spec.md#v02-ownership-boundary--2026-10-06)
records C++ ownership parity, checked empty access, explicit weak locking, deferred
raw-pointer APIs and the deliberate no-direct-nesting language restriction.
Non-goals: allocation, source move syntax/transfer analysis, weak lock execution,
truthiness, pointer reset integration and class/member lifetime rules. Existing
default handle declarations/backend spellings remain unchanged; model formation
does not certify every operation on an otherwise valid composed type.

Proof: `tests/ownership_model.php` covers all nine nesting combinations, role and
target identity, renamed/aliased definitions, an unrelated same-spelling template,
fixed handle capabilities, arity and modifier rejection before publication.
`tests/ownership.php` adds source rejection, failed-update/no-change retry, recovery
and four generated C++ programs preserving empty-handle declarations, handle copies,
named holders, containers and whole-handle reference returns. It does not claim
allocation, move or weak-lock execution.

Evidence: `/tmp/my-try-ownership-final-20261006` passed nine focused PHP suites,
17 source rejection cases and four generated C++ executions without PCH. The
runner used its documented CLI fallback because FPM socket creation was denied.
No native compiler rebuild, runtime wrapper change or performance claim is included.

## Ownership empty state and presence — 2026-10-06

All three ownership families support default empty construction, null storage
initialization/assignment and whole-variable `unset`. Native alias/reference-parameter
null assignment works; `unset` directly on those references still rejects. `clean`
does not accept ownership handles. Unique null construction is a prepared
`ownership_empty` conversion, not a relaxation of its noncopyable capability.

Shared/unique conditions (including loops and ternary conditions), unary `!`,
`isset`, `empty` and strict null comparisons inspect handle presence without copying
or reading the pointee. Weak implicit presence forms reject during preparation.
The existing nullable comparison worker is generalized as `Presence_Operators`;
all families use shared conversion/probe/reset paths, not new per-family AST nodes.
Parsing remains unchanged and C++ emission consumes prepared decisions.

Null argument/return conversions, explicit bool casts, direct handle `&&`/`||`,
handle-valued selection joins, allocation, source moves and weak locking remain
outside this slice. Use boolean presence probes for combined conditions.
Nonempty destruction/weak expiration proofs await construction/locking integration.

Evidence: `tests/ownership.py`, `/tmp/my-try-ownership-state-20261006-final`:
12 focused PHP suites, 34 source rejections, seven generated C++ programs without
PCH. Recovery includes changing a shared signature to weak while retaining its
presence-using body, no-change retry, repair and cleanup/re-preparation. FPM socket
restrictions required CLI fallback. The compiler itself was not rebuilt natively.

## Default owned construction — 2026-10-06

Implemented one `new T(args...)` syntax node with owning type/argument edges and
ordinary inspection, cleanup and token relocation. Preparation currently admits
only zero-argument construction of source structs whose fields have proven defaults.
Default `new T()` yields shared ownership; an explicitly typed direct local initializer
can select exact shared/unique ownership. Weak allocation and mismatched targets reject.
See the [normative bounded construction contract](../../../../specs/type_use_modifiers.md#v02-bounded-owned-construction--2026-10-06).

`Object_Construction_Preparation` owns selection and the bounded default proof,
observing nested record declarations through the existing dependency protocol.
Fresh-value initialization is separate from copying in `Capability_Preparation`;
unique remains noncopyable. C++ calls `scpp::shared<T>()` or `scpp::unique<T>()`
directly. No intermediate shared allocation, raw-pointer adoption or new runtime
wrapper implementation is added.

Focused evidence: `/tmp/my-try-owned-construction-20261006-verified`, using
`tests/ownership.py`: 12 PHP suites, 42 source rejections and 11 generated C++
executions without PCH. Includes real allocation, retained shared copies after reset,
unique reference reset, noncopyable pointees and nested-default incremental failure,
no-change retry, repair and cleanup/re-preparation. FPM used CLI fallback. No native
compiler rebuild, allocation benchmark or user-destructor execution proof is claimed.

Remaining: user constructors/destructors, runtime value construction, pointee access,
unique reassignment/argument/return transfer and explicit moves; weak derivation/locking
is a separate slice. New syntax alone does not enable those paths.

## Explicit local unique transfers — 2026-10-06

`$b = move($a)` and `$b unique<T> = move($a)` now consume exact-type unique
ownership from whole local/parameter storage, including valid native aliases.
The source remains empty usable storage. Existing destinations release their old
ownership; self-transfer, including aliased reference parameters, preserves the object.
Ordinary unique copies, field projections/known field aliases, discarded/probed moves,
value-producing/chained assignments, shared/weak moves and consuming calls/returns reject.

One move syntax/fact family and `Move_Preparation` own admission. Immediate consumer
context replaces the prior construction-only boolean, without propagating to children.
The existing initialization capability path consumes fresh construction or explicit
transfer, while type copyability stays unchanged. C++ emits `Unique(std::move(source))`
to materialize ownership at evaluation time; no runtime wrapper changes are required.
The normative [transfer contract](../../../../specs/type_use_modifiers.md#v02-explicit-unique-transfer--2026-10-06)
records alias effects, self-transfer and no rollback after a later failure. The old
non-authoritative implicit-move example in `runtime/specs/config.review.md` is corrected.

Evidence: `/tmp/my-try-move-local-20261006-final`, `tests/ownership.py`: 12 focused
PHP suites, 62 source rejections and 15 C++ executions without PCH. Native instrumentation
calls the unmodified generated transfer function, verifying object identity, old
destination release, self-transfer, source emptiness and release during exception
unwinding. Retained checks cover signature edits to shared, no-change failure retry,
repair and cleanup/re-preparation. FPM required CLI fallback. This is not a native
compiler rebuild or source-language exception/user-destructor integration proof.

Next: consuming by-value arguments and unique returns, using the same transfer model
and deliberate temporary forwarding. Weak derivation/locking follows separately.

## Ownership-type validation debt from chapter 05

**CH05-OWN-01 — resolved:** the model slice above supplies semantic ownership
classification and rejects direct nesting/value wrapping by definition role.
Direct nesting is deliberately excluded from v0.2, not pending automatic enablement.

**CH07-OWN-EXEC-01 — remaining ownership operations:** broader construction,
consuming argument/return transfer integration, weak locking,
class/member lifetime rules and precise additional target constraints
remain separate work. Current formation of a type does not certify those operations.
Low-level pointer escapes and specialized deleters remain deferred. Do not revive
source weak truthiness from the runtime's expiration-only bool convenience.

The normative [v0.2 modifier contract](../../../../specs/type_use_modifiers.md)
allows redundant `value<value<int>>` to normalize to int; repeated effective class-like
modifiers remain rejected. Nullable/Result nesting is a separate value contract:
canonical formation alone does not establish nested payload operations.

## Progress

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [FUNC-REF-001](#func-ref-001) | supported | `function f(int &$a): void {}` | prepared | verified | deferred | Existing call path plus references proof |
| [FUNC-REF-002](#func-ref-002) | rejected-bounded | `function &f(): int {}` | rejects | no output | deferred | Requires exactly one reference parameter |
| [FUNC-REF-003](#func-ref-003) | supported-with-debt | `function &f(int &$a): int { return $a; }` | prepared | verified | deferred | Whole-parameter lifetime proof; CH07-WARN-01 |
| [FUNC-REF-004](#func-ref-004) | rejected | `function &f($a) { return $a; }` | explicit types required | no output | deferred | Existing signature grammar |
| [VAR-UNSET-001](#var-unset-001) | supported-bounded | `$a ?int = 1; unset($a);` | prepared | verified | deferred | `tests/cleanup.php`; nullable owner only, alias/reference rejection |
| [VAR-CLEAN-001](#var-clean-001) | supported-bounded | `$a int = 1; clean($a);` | prepared | verified | deferred | `tests/cleanup.php`; defined scalar/container/boxed resets |
| [REF-RETURN-001](#ref-return-001) | supported-bounded | `function &bounce(int &$a): int { return id($a); }` | origin checked | verified | deferred | Nonrecursive whole-parameter forwarding; unsafe origins reject |
| [REF-RETURN-002](#ref-return-002) | rejected | Reference return of an indexed element | rejects | no output | deferred | Whole-parameter return only; no native interior reference |
| [REF-BIND-001](#ref-bind-001) | rejected | `$inner =& $arr["inner"];` | rejects | no output | deferred | No native container-interior alias |
| [TYPE-VAR-003A](#type-var-003a) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-003B](#type-var-003b) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-003C](#type-var-003c) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-PARAM-003](#type-param-003) | pending-discussion | `function f(string &$s, vector_t &$v): void {}` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003B](#type-param-003b) | pending-discussion | `function f(array &$a): void {}` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003F](#type-param-003f) | pending-discussion | `function f(const vector<int> &$values): int { return count($values); }` | unverified | unverified | deferred | — |
| [WARN-TYPE-PARAM-001](#warn-type-param-001) | pending-discussion | `function f(array $a): void { $a = []; }` | unverified | unverified | deferred | — |
| [TYPE-VAR-007A](#type-var-007a) | pending-discussion | Strict example to define (legacy spelling retained below) | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-VAR-008](#type-var-008) | supported-strict | `$r int =& $x;` | prepared | verified | deferred | Reduced local binding; legacy annotation spelling not added |
| [NOTE-038](#note-038) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-056](#note-056) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-059](#note-059) | debt | Reference-return advice | warning delivery deferred | — | deferred | CH07-WARN-01 |
| [NOTE-060](#note-060) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## FUNC-REF-001

**v0.2 decision / target C++:** See the [bounded alias/reference-return checkpoint](#local-aliases-and-bounded-reference-returns--2026-10-06) and progress row above. Imported v1 behavior below is historical, not broader v0.2 admission.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:102](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:263](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(int &$a): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(int_t& a);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** reference syntax is explicit and valid

**Normalized pattern:** free-function reference parameter

**General rule:** Reference parameters are supported for free functions.

**Diagnostics:** Error if generator drops the reference marker.

**Notes:** Added after explicit approval.


## FUNC-REF-002

**v0.2 decision / target C++:** See the [bounded alias/reference-return checkpoint](#local-aliases-and-bounded-reference-returns--2026-10-06) and progress row above. Imported v1 behavior below is historical, not broader v0.2 admission.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:103](../../../../generators/php/specs/catalog.md), [generators/php/specs/rules_catalog.md:264](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &f(): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f();
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** reference syntax is explicit and valid

**Normalized pattern:** free-function reference return

**General rule:** Reference returns are supported for free functions when the PHP return type is explicitly declared, and must preserve the native `&` marker in both declaration and definition.

**Diagnostics:** Error if generator drops the reference marker or returns a copied value.

**Notes:** Added after explicit approval.


## FUNC-REF-003

**v0.2 decision / target C++:** See the [bounded alias/reference-return checkpoint](#local-aliases-and-bounded-reference-returns--2026-10-06) and progress row above. Imported v1 behavior below is historical, not broader v0.2 admission.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:104](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function &f(int &$a): int {}
```

**Existing C++ lowering / result**

```cpp
int_t& f(int_t& a);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit reference markers present

**Normalized pattern:** free-function reference preservation

**General rule:** Reference markers are preserved literally in generated free-function signatures when the PHP return type is explicitly declared, and return statements stay on lvalue-capable paths; direct calls statically known to return by reference are also accepted.

**Diagnostics:** No reference inference is performed.

**Notes:** Also subject to the global no-overloading rule.


## FUNC-REF-004

**v0.2 decision / target C++:** See the [bounded alias/reference-return checkpoint](#local-aliases-and-bounded-reference-returns--2026-10-06) and progress row above. Imported v1 behavior below is historical, not broader v0.2 admission.

### Imported version 1

**Source:** [generators/php/specs/catalog.md:105](../../../../generators/php/specs/catalog.md)

**PHP input example**

```php
function &f($a) { return $a; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** by-reference return lacks an explicit PHP return type

**Normalized pattern:** untyped free-function reference return

**General rule:** By-reference function returns must have an explicit declared PHP return type so the generated C++ signature can use a concrete `T&` instead of deduced `auto&`.

**Diagnostics:** Emit an explicit generator error when a free function returns by reference without a declared PHP return type.

**Notes:** This avoids forward-declaration and deduction issues for reference-returning functions.


## VAR-UNSET-001

**v0.2 decision / target C++:** See [whole-storage resets](#whole-storage-resets--2026-10-06).
Preparation admits nullable owner variables and emits exact-type default assignment;
alias/reference-parameter unset rejects. Keyed removal retains its separate contract.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:49](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
unset($a);
```

**Existing C++ lowering / result**

```cpp
php::unset(a);
```

**Category:** Variable

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** lowered target type is nullable / pointer-like / handle-like and supports an empty state

**Normalized pattern:** `unset(<var>)`

**General rule:** `unset` must be mapped to the PHP runtime only for resettable nullable / pointer-like targets. It must not be used as a fake delete for non-nullable value types, containers, or native references.

**Diagnostics:** Emit a generation error or route to `clean` when the target type has no null/empty state compatible with `unset`.

**Notes:** Current project rule: `unset` is intentionally narrower than PHP variable removal semantics.


## VAR-CLEAN-001

**v0.2 decision / target C++:** See [whole-storage resets](#whole-storage-resets--2026-10-06).
Admitted value variables and aliases reset by exact-type default assignment. Dynamic
copies preserve the old object; native aliases observe reset storage. Unsupported
types reject during preparation rather than relying on a C++ default constructor.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:50](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
clean($a);
```

**Existing C++ lowering / result**

```cpp
php::clean(a);
```

**Category:** Variable

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** lowered target type is non-nullable but has a defined default/empty cleanup state

**Normalized pattern:** `clean(<var>)`

**General rule:** `clean` is the current reset/cleanup path for non-nullable value/container-like targets that should not use `unset`. It resets the value to its defined default/empty state rather than modeling PHP symbol removal.

**Diagnostics:** Emit a generation error if the target type has no supported cleanup/default semantics.

**Notes:** Temporary project-level direction until/if a different reset API is chosen.


## REF-RETURN-001

**v0.2 decision / target C++:** See the [bounded alias/reference-return checkpoint](#local-aliases-and-bounded-reference-returns--2026-10-06) and progress row above. Imported v1 behavior below is historical, not broader v0.2 admission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:266](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &bounce(array &$a) { return get_inner($a); }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** forwarded reference target is not proven native-reference safe

**Normalized pattern:** direct ref-return call forwarding

**General rule:** Forwarding a by-reference call result is rejected unless the returned target is already known to satisfy the native-reference safety rule.

**Diagnostics:** Emit an explicit generator error when the forwarded reference target is unresolved or rooted in dynamic/container interior storage.

**Notes:** See `specs/native_reference_safety.md`.


## REF-RETURN-002

**v0.2 decision / target C++:** See the [bounded alias/reference-return checkpoint](#local-aliases-and-bounded-reference-returns--2026-10-06) and progress row above. Imported v1 behavior below is historical, not broader v0.2 admission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:267](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function &get_inner(array &$arr): array { return $arr["inner"]; }
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** returned expression is rooted in dynamic interior slot access

**Normalized pattern:** simple direct slot ref-return

**General rule:** Returning a direct array/property slot chain by native reference is outside the current safe subset because it would expose interior storage owned by another object.

**Diagnostics:** Emit an explicit generator error.

**Notes:** See `specs/native_reference_safety.md`.


## REF-BIND-001

**v0.2 decision / target C++:** See the [bounded alias/reference-return checkpoint](#local-aliases-and-bounded-reference-returns--2026-10-06) and progress row above. Imported v1 behavior below is historical, not broader v0.2 admission.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:268](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$inner =& $arr["inner"];
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Function

**Rule kind:** rejection

**Source support status:** rejected

**Preconditions:** reference assignment source is a direct DIM expression rooted in dynamic interior storage

**Normalized pattern:** direct DIM reference binding

**General rule:** Reference binding from a direct DIM slot is outside the current safe subset.

**Diagnostics:** Emit an explicit generator error.

**Notes:** See `specs/native_reference_safety.md`.


## TYPE-VAR-003A

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:326](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** weak<A> */ = null;
```

**Existing C++ lowering / result**

```cpp
weak_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit runtime weak-handle PHPDoc variable type present

**Normalized pattern:** `typed weak handle local variable`

**General rule:** Explicit local PHPDoc handle aliases `weak<T>` and `weakref<T>` lower directly to `weak_p<T>` and must not be wrapped again as object handles.

**Diagnostics:** Error if generation emits `shared_p<weak_p<T>>`, `shared_p<weakref<T>>`, or any other nested wrapper shape.

**Notes:** This rule treats runtime weak handles as first-class handle values.


## TYPE-VAR-003B

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:327](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** shared<A> */ = null;
```

**Existing C++ lowering / result**

```cpp
shared_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit runtime shared-handle PHPDoc variable type present

**Normalized pattern:** `typed shared handle local variable`

**General rule:** Explicit local PHPDoc handle alias `shared<T>` lowers directly to `shared_p<T>` and must not be wrapped as `shared_p<shared_p<T>>`.

**Diagnostics:** Error if generation introduces nested shared-handle wrapping.

**Notes:** This keeps runtime handle aliases canonical.


## TYPE-VAR-003C

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:328](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$x /** unique<A> */ = null;
```

**Existing C++ lowering / result**

```cpp
unique_p<A> x = null;
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit runtime unique-handle PHPDoc variable type present

**Normalized pattern:** `typed unique handle local variable`

**General rule:** Explicit local PHPDoc handle alias `unique<T>` lowers directly to `unique_p<T>` and must not be wrapped again as an object handle.

**Diagnostics:** Error if generation emits a nested wrapper shape such as `shared_p<unique_p<T>>`.

**Notes:** Unique handles are treated as first-class runtime handle values.


## TYPE-PARAM-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:331](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(string &$s, vector_t &$v): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(string_t& s, vector_t& v) {}
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit PHP reference syntax present

**Normalized pattern:** `explicit mutable string/vector reference`

**General rule:** Explicit PHP `&` must be preserved literally and disables the default `const &` convention for `string_t` and `vector_t`.

**Diagnostics:** Error if the reference marker is dropped or rewritten to `const &`.

**Notes:** Applies to free functions and methods.


## TYPE-PARAM-003B

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:333](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array &$a): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(mixed_t& a) { ::scpp::php::expect_array_argument(a, false, "a"); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** explicit PHP reference syntax present

**Normalized pattern:** `explicit mutable array reference`

**General rule:** Explicit PHP `&` must be preserved literally and lowers to `mixed_t&` for the fat-variable array surface.

**Diagnostics:** Error if the reference marker is dropped or rewritten away from `mixed_t&`.

**Notes:** Applies to free functions and methods.


## TYPE-PARAM-003F

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:337](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(const vector<int> &$values): int { return count($values); }
```

**Existing C++ lowering / result**

```cpp
int_t f(const vector_t<int_t<>>& values) { return count(values); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** prefix parameter syntax contains leading `const`, the parameter type is explicit, and optional PHP `&` is present before the variable

**Normalized pattern:** `explicit const parameter`

**General rule:** Source-level `const` on a function, method, closure, interface, or abstract method parameter is a semantic read-only contract, separate from the generator's automatic read-only `const &` convention. Explicit `const T&` lowers to a generated C++ `const T&` parameter and bypasses mutable by-reference normalization.

**Diagnostics:** STAN must report a build-blocking diagnostic if the body writes, reassigns, unsets, increments/decrements, or writes through a slot/property rooted at the const parameter. Generated C++ signatures must preserve `const`.

**Notes:** First pass supports scanner-owned prefix syntax such as `const T $x` and `const T &$x`; existing doc-comment-only parameters remain non-const unless a later doc syntax is specified.


## WARN-TYPE-PARAM-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:339](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array $a): void { $a = []; }
```

**Existing C++ lowering / result**

```cpp
void_t f(mixed_t a) { expect_array_argument(a, false, "a"); a = table_(); }
```

**Category:** Type system

**Rule kind:** warning

**Source support status:** supported with warning

**Preconditions:** by-value composite param is reassigned

**Normalized pattern:** `reassignment replaces the guarded local value`

**General rule:** Reassigning a by-value `array` param is supported and simply replaces the guarded local `mixed_t`.

**Diagnostics:** Warning-level documentation only.

**Notes:** Prefer a new local when the distinction between replacing the local and mutating nested slots matters.


## TYPE-VAR-007A

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:359](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$p /** unique<Point> */ = new Point(1, 2);
```

**Existing C++ lowering / result**

```cpp
unique_p<Point> p = ::scpp::unique<Point>(static_cast<int_t>(1), static_cast<int_t>(2));
```

**Category:** Type system

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** explicit unique local wrapper annotation present and the initializer is exactly `new Point(...)`

**Normalized pattern:** `typed unique object local variable`

**General rule:** The local PHPDoc form `unique<T>` lowers to `unique_p<T>`. When the initializer is `new T(...)`, generation must use `::scpp::unique<T>(...)` rather than `create<T>(...)` so ownership remains unique. The generator must reject mismatches such as `/** unique<A> */ = new B()` instead of default-initializing `A` or silently discarding the RHS constructor target.

**Diagnostics:** Error if the generator falls back to `create<T>(...)`, `shared_p<T>`, or any non-unique ownership path for this explicit local form.

**Notes:** This applies both to the canonical `unique<T>` form and to the bare `/** unique */` shortcut after it is normalized to `unique<T>`.


## TYPE-VAR-008

**Strict-first scope:** Define the strict source form and verify its type/ownership contract when this feature is discussed. The imported annotation spelling below is legacy reference only.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:360](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$r /** ref int */ = &$x;
```

**Existing C++ lowering / result**

```cpp
int_t& r = x;
```

**Category:** Type system

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** explicit reference local annotation present

**Normalized pattern:** `typed reference local variable`

**General rule:** The local PHPDoc form `ref T` lowers to a native C++ lvalue reference over the lowered declared type. Object-like locals therefore become handle references such as `shared_p<T>&`, not nested wrapper shapes. The feature remains a reduced write-through alias and is not a promise of full PHP `&` semantics.

**Diagnostics:** Emit an error if generation would require forbidden type-definition syntax such as `&&` or `*`, or if initialization is not a reference assignment.

**Notes:** This rule keeps the reference feature aligned with the native C++ no-layering contract and with the explicit non-PHP-reference scope.


## NOTE-038

**Source:** [generators/php/specs/rules.md:771](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 9. Parameter passing rules
>
> ### 9.1 Pass by value
> - `int_t`
> - `float_t`
> - `bool_t`
> - `nullable<int_t>`
> - `nullable<float_t>`
> - `nullable<bool_t>`
>
> ### 9.2 Pass by const &
> - `string_t`
> - `vector_t`
> - `nullable<string_t>`
> - `nullable<vector_t>`
> - future heavy wrapper types

## NOTE-056

**Source:** [generators/php/specs/rules.md:1249](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Warning: reassignment of by-value composite params
>
> - Reassigning a by-value parameter of type `array`, `string`, or `vector_t<...>` is supported, but it is **not recommended** for large values.
> - Once the function body proves a reassignment or write on that parameter, the emitted C++ signature becomes owning `T x` instead of `const T& x`.
> - That means the **entire incoming value is copied at function entry**.
> - This can be expensive for large `hash_t`, `string_t`, or `vector_t` values, even if the original incoming value is used only briefly before reassignment.
> - Prefer introducing a new local variable instead of overwriting the parameter when avoiding that full copy matters.

## NOTE-059

**Source:** [generators/php/specs/rules.md:1272](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Return-by-reference warnings
>
> - Return-by-reference is not recommended in Prism++ and must always surface a generator warning even when generation is still allowed.
> - The generator must also warn for local copy-after-alias patterns rooted in a by-reference call result, for example `$inner =& get_inner($arr); $copy = $arr;`, because Prism++ may not preserve PHP alias semantics for that flow.

## NOTE-060

**Source:** [generators/php/specs/rules.md:1277](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Historical note â€” typed scalar by-reference proxy lowering
>
> The runtime may still contain legacy helper/proxy infrastructure, but that legacy path is not part of the supported safe subset. The current design direction is the native-reference safety rule documented in `../../specs/native_reference_safety.md`.
