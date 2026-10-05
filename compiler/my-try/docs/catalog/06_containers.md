# 06. Arrays, vectors, hashes and iteration
Doc Status: planning

> **Important: LLVM is deferred by default. Work on the direct LLVM backend only when the user explicitly requests it. The v0.2 default scope is Frontend + C++ S2S.**

[Catalog and workflow](README.md) · [Source merge ledger](source_merge.json)

Requires values, expressions and basic calls. Establish reads versus writes before nested mutation and foreach.

**Chapter closeout is blocked by CH06-MIX-DYN-01 below.** Continue the agreed slices,
but discuss and resolve this debt with the user before declaring Chapter 06 complete.

Order is a discussion sequence, not a claim that every row is a prerequisite. Split combined examples before implementation.

## Agreed standard and revised grouping — 2026-10-05

The top-level [data initialization standard](../../../../specs/data_initialization.md)
owns language semantics and supersedes older construction defaults for v0.2. This
chapter owns implementation progress only. Documentation is complete for the agreed
contract; built-in construction now executes through the shared path described below.

The current sequence replaces the typed-first/dynamic-later handoff grouping:

1. Notation and recognized initialization-interface standard (documentation complete upstream,
   including exact factory/input API, lifetime rules and built-in policies).
2. Review compiler model and implement common
   construction for vector, hash, mixed and dynamic, including nested data.
3. Reads, writes/appends, typed boundaries and keyed probes across those families.
4. Value iteration across those families, with runtime checks for dynamic kinds.
5. Separately gated reference iteration.

Key decisions: positional brackets, keyed arrows, named braces, optional trailing
commas and explicit `Type::[...]` / `Type::{...}`; unknown targets fail preparation.
Factories perform runtime construction/data validation. User-defined types opt in
through recognized interfaces, not method-name detection. Their compiler connection
waits for class/struct method/interface support; automatic member mapping is deferred.
Nested mixed/dynamic construction inherits its enclosing family, but both store
mixed-only values; dynamic payloads use `dynamic_v` and preserve shared identity.
Function-valued entries are documented for later implementation. Compiler literal
pre-preparation belongs to the [v0.3 plan](../../../../docs/v0.3/literal_construction_preparation.md).

The notation documentation (step 1) and object initialization-interface documentation
(step 2) are complete in the top-level standard; built-in compiler/runtime construction
is implemented, while user-defined factory execution remains gated.
The adopted [hybrid input/lowering direction](../planning/data_initialization.md)
uses call-scoped read-only inputs rather than retainable owning sequences; enforcing
non-escape is a prerequisite. The general-stage versus C++ boundary is confirmed:
generic semantic checks precede emission; allocation and concrete construction do not
belong in parsing/collection/preparation. Bounded lifetime enforcement is required
before enabling source-defined factories. Do not mark imported untyped
literal examples supported using v0.1 fallback
typing. CH05-DYN-01 is spread across the common operation groups, not a second dynamic
pass. Existing `(object)` bridges remain separate from direct typed construction;
unrestricted casts, dynamic `->` and native interior references are not introduced.

## Blocking closeout debt

### CH06-MIX-DYN-01 — mixed/dynamic containment contract

Status: **open; blocking Chapter 06 completion**. Recorded at the user's request;
discussion is scheduled for the end of this chapter, not silently deferred beyond it.

The current runtime permits both relationships:

- [`dynamic_t<>`](../../../../runtime/include/scpp/dynamic_t.hpp) is a shared hash
  whose entry values are `mixed_t`.
- [`mixed_t`](../../../../runtime/include/scpp/support/mixed_t.hpp) can carry a dynamic
  handle under the `dynamic_v` kind.

The user questioned whether mixed and dynamic should be permitted inside one another
and requires the compiler to understand the intended distinction. Resolve whether
this concerns storage representation, nested-construction defaults, or a stronger
restriction on mixed/dynamic container families within one data tree. Do not infer
the answer from existing runtime support alone.

Until that discussion, continue under the current authoritative contracts; this debt
does not itself change boxing, identity or nesting semantics and does not block the
intermediate preparation/construction slices. Existing cross-family behavior must not
be treated as final design approval merely because it passes tests.

Closeout requires an explicit agreed decision, reconciliation of the owning
`specs/data_initialization.md` / `specs/dynamic_types.md` contracts, and any required
compiler/runtime changes with focused proofs. Do not close this debt solely by
documenting the current runtime, and do not mark the chapter complete while it is open.

## Handoff from chapter 05 — 2026-10-04

The syntax, binding, preparation and execution checkpoints below supersede this
historical handoff's construction prerequisites.

Chapter 05's [bounded positive pass](05_dynamic_boundaries.md#positive-pass-conclusion--2026-10-04)
is complete with named debt. Nullable/Result/mixed conversions, conditions, value
probes and explicit mixed/dynamic bridges are available; aggregate payload operations
are not implied. Reuse canonical template applications, prepared conversions and
the existing `index_node`; do not add separate dynamic ASTs for ordinary indexing.

The original typed-first discussion grouping is superseded by the 2026-10-05
standard/grouping above. CH05-DYN-01 includes `(object)[...]`/supported object
construction; unrestricted `(object)$value` is not implied. Dynamic `->` remains
a separate requested language discussion.
For mixed/dynamic iteration, distinguish valid static admission from runtime checks
of the actual iterable kind; do not reject all mixed simply for being dynamic.

CH05-OWN-01 remains with chapter 07; CH05-VAL-01 and CH05-TAKE-01 remain explicit
follow-ups, not container prerequisites unless a selected slice needs them.

## Existing foundations

Chapter 04 now packs typed trailing variadics into the existing canonical `vector<T>`
storage type. Pack values can be returned or passed to ordinary vector parameters;
generated-C++ probes verify their contents. This does not implement source vector
indexing/iteration. Reuse the ordinary parameter/vector representation here rather
than introducing a variadic-specific read or iteration path. See
[the variadic slice](04_functions.md#typed-trailing-variadics--2026-10-02).

Iteration already has a reserved `foreach_node extends loop_node` in
`03_parse/structures/structures_loops.php`. Complete that specialization when this
chapter reaches foreach; reuse its inherited `block_node` body, loop-local preparation
and nearest break/continue protocol. The [Chapter 03 loop design](03_control_flow.md#agreed-loop-slice--2026-10-02)
owns the shared foundation. Iterable/key/value syntax, binding/lifetime facts and
iteration-specific continuation are TODOs here; the placeholder is not parser support.

Follow the [catalog gates](README.md#prerequisite-gates) and the revised common-family
sequence above. Split `ARR-INIT-004` source variants against the known-target rule
before choosing its strict example. Key rules apply before keyed operations; row
placement does not defer them.

Reference iteration (`CTRL-FOREACH-003/004`) and reference-taking variants of
`FUNC-ARG-001` require the relevant [reference contracts](07_references_lifetime.md):
storage stability, aliasing and invalidation on mutation. They are gated, not implied
by successful value iteration. Casts and nullable forms require their corresponding
conversion prerequisites; shared construction explicitly includes mixed/dynamic and
nested data. Imported cards below retain their original order and provenance.

## Progress

### Shared data parser and AST — 2026-10-05

Syntax implemented, not factory preparation or C++ construction. One `data_construction`
parser path and `data_construction_node` replace the old positional-only array literal.
The node owns optional explicit type syntax and ordered `data_entry_node` entries;
keys are optional expressions, values required. PHP-like brackets admit positional
or arrow-keyed syntax; JS-like braces require quoted/bare names and colons. Both
support empty input, nested expressions and one trailing comma. Crossed separators,
missing values, holes, computed brace keys and unsupported spread reject with source
locations. There is no JSON-validity classification.

`source_representation(token_list)` derives the opening form from source tokens,
including explicit `Type::[...]` / `Type::{...}` and retained/compacted spans. No
representation flag is stored. Bare property names normalize through string-literal
syntax without name lookup; preparation uses the same string-decoding path.
Duplicate entries and explicit-null-versus-absent keys remain distinguishable.
Mixed positional/keyed forms can be represented but are not semantically admitted.

`tests/data_construction.php` covers tree initialization/ownership, lazy inspection,
maintenance, nesting, grammar failures, ordinary expression/block integration,
retained identity/span compaction, invalid-edit recovery and withheld preparation/C++
publication. Existing AST/dispatch tests follow the renamed structure. The parked
LLVM consumer has only a structural adaptation preserving its positional reader;
no new LLVM feature or native compiler proof is claimed.

Evidence: `/tmp/my-try-data-syntax-20261005-3` (focused PHP suites, shared runner,
12 slots, CLI fallback because private FPM sockets are unavailable). The separately
checked interpolation suite has an existing obsolete rejection for `"$x" . 2`;
the same failure reproduces on committed baseline `de9873cb` in
`/tmp/my-try-data-syntax-20261005-2/logs/baseline-interpolation.stderr`. Its assertion
repair is outside this parser slice; do not count that suite as passing.

### Callable model foundation — 2026-10-05

The common callable contract no longer requires a source function/parameter AST.
Existing `collected_function` identities and registered static runtime factories
implement `callable_target_i`; both expose `callable_signature` with ordered
`callable_parameter` contracts. Source parameter storage facts share those contracts
rather than copying type/mode/default state. Source completion/dependency tracking,
argument admission, defaults, reference checks and variadic packing remain shared.

Runtime factory identities carry their exact canonical target/return type. A separate
C++ binding supplies the static member and header; it cannot accidentally bind a
different target with the same registration key. Lifecycle resets align these roots
with canonical types. See [model ownership](../architecture/MODEL.md#callable-contracts).

Review correction: C++ target dispatch now lives entirely in `CPP_Callables`, not
on callable identities or their interface. Collection has moved under parsing.
The subsequent [stage-owned processing refactor](../planning/stage_owned_processing.md)
completed the agreed ownership repairs; it is no longer the Chapter 06 prerequisite.

This is model infrastructure, not container construction support: no production
executable factories are installed and no static-method source lookup is enabled.
Generic bindings and internal input identities are covered by the next checkpoint;
non-escape enforcement and factory execution remain pending. Classes, instance methods and
closures are not pulled into this slice. The v0.1 runtime-static-symbol catalog is
precedent for separate signatures/bindings, not a substitute semantic owner here.

`tests/callables.php` proves AST-independent registration, exact owner/result identity,
source contract sharing/dependencies, common argument admission, C++ reference/header
binding, semantic-default emission, duplicate/mismatched binding rejection and reset
lifetimes. Source-function fixtures cover existing execution and incremental recovery.
PHP/model and generated-program checks do not establish native compiler portability.

Evidence: `/tmp/my-try-callables-functions-20261005-2` (12 PHP suites and 77
PCH-backed generated C++ function programs), `/tmp/my-try-callables-20261005-5`
(callable model, preparation work and data syntax). Shared runner, 12 slots,
automatic CLI fallback because the sandbox cannot create FPM sockets. No native
compiler rebuild, conversion, LLVM work or broad regression run in this slice.

### Initialization binding and input model — 2026-10-05

One `initialization_declaration` / `initialization_binding` path covers vector, hash,
mixed, dynamic and separately registered runtime providers. Declarations identify
the exact registry definition, value/key type substitutions, contextual nested target,
optional exact template-argument constraints and provider-qualified factory identity.
Absent key type denotes `list_initializable`; a present key denotes
`key_value_initializable`, not a nullable key or a duplicated role flag.

`Initialization_Preparation` selects the applicable declaration, substitutes types,
checks copy/storage capability and validates one fixed sequence parameter plus the
exact target return. It shares the registered callable's sole signature. Built-in
differences are declarations only; no container-name branch exists in the binder.
Hash bindings currently cover ordinary int/string keys. Empty brackets prefer list,
then keyed; empty braces use keyed. Conflicting applicable bindings reject.
Mixed/dynamic share mixed input types but retain distinct nested-construction defaults.

`Model::$initialization_contracts` owns installed declarations, internal ordinary
template identities `initializer_list<T>` / `initializer_entry<V, K = string>` and
concrete binding identities. Definition identity supplies call-scoped/read-only
input roles. The view deliberately has no owning copy/storage capability; entries
are immutable copyable values whose key/value formation requires copyable storage.
At this model-only checkpoint, input types had no source exposure or C++ binding.
The execution checkpoint below adds runtime view APIs and backend bindings, not
source exposure or enforced source non-escape.

Binding queries revalidate source-derived input capabilities before cached reuse and
register declaration dependencies. Preparation reset preserves installed bindings;
syntax/type reset replaces them. Installed declarations freeze on first publication;
configuration changes require reset. Source-defined interface implementations remain
with future prepared declarations, never this runtime catalog.

Evidence: `/tmp/my-try-initialization-model-20261005-3`, ten focused PHP suites through
the shared 12-slot runner (CLI fallback). `tests/initialization.php` proves all eight
built-in bindings, shape rejection, signature rejection, input roles, nesting,
third-party declaration reuse, exact modified targets, source capability edit/recovery
and reset. No native compiler build, conversion, broad regression or executable
construction proof is claimed. The unrelated local dependency-cleanup test edit was
left untouched and is not included in this run.

The subsequent checkpoints implement contextual literal preparation, ordered
conversion facts and runtime adapters/C++ construction. Preserve the common-family path. User-written
factory execution still requires interface/method support and enforced non-escape.

Concrete-target/property clarification: a known concrete property type may supply
the target only if it has the applicable initialization opt-in. Interface-typed or
otherwise non-concrete destinations require an explicit concrete `Type::{...}` /
`Type::[...]`; no implementation is inferred from keys or from the currently known
implementation set. Construction and assignment compatibility are separate checks.
Property-driven object initialization and automatic key-to-property mapping remain
deferred. The [top-level rule](../../../../specs/data_initialization.md#concrete-targets-and-property-contexts)
owns this contract; it does not introduce source class/interface support here.

### Contextual literal preparation — 2026-10-05

`Expression_Preparation::prepare` accepts an optional expected type, passed explicitly
by typed declarations, established local/field assignments, fixed/variadic arguments
and function returns. Only direct data constructions consume it; ordinary expressions
retain their existing typing. Parenthesized literals reuse the same node. Explicit
`Type::[...]` / `Type::{...}` overrides context, with any outer conversion checked
separately. No expected-type inference through ternary/coalescing joins is introduced.

One `Data_Construction_Preparation` worker handles all registered families. It checks
shape, resolves the common initialization binding, and prepares each key before its
value in source order. It attaches `prepared_data_construction` with the binding/result
and `prepared_data_entry` with key/value conversions. Syntax remains the sole owner of
entry order and expressions; duplicates survive, and no second entry tree/hash exists.
Nested literals use the binding's contextual target, separately from its storage type.
Entry expressions can call functions or modify existing storage, but cannot introduce
locals. The construction result is not addressable; reference arguments reject it.

`initializer_key` / `initializer_value` conversion contexts reuse by-value argument
rules. The mixed conversion owner additionally admits dynamic-to-mixed boxing for
initializer values, preserving the existing `mixed_dynamic_box` decision; ordinary
implicit assignment remains unchanged. Statically invalid mixed key types reject;
mixed-typed keys remain admitted for runtime kind validation. This follows the current
contract and **does not resolve CH06-MIX-DYN-01**.

The existing cleanup traversal clears construction and entry facts. Function signature
edits reprepare retained literal bodies against the new target; failed preparation
clears stale facts and recovery rebuilds them. Known existing struct-field types can
supply context to field assignment; source object factories and automatic mapping of
initializer keys onto properties remain deferred.

Evidence: `/tmp/my-try-data-preparation-20261005-2` (ten focused PHP suites) and
`/tmp/my-try-data-preparation-regression-20261005` (conversion, mixed, incremental C++,
control-flow and S2S suites), shared runner, 12 slots, CLI fallback. The first focused
run exposed a test diagnostic-text mismatch, corrected before the passing run.
Tests cover supported boundaries/nesting, shape/key/value rejections, explicit target
precedence, context isolation, entry cleanup and incremental signature failure/recovery.
Generated-source regression exports are not execution proofs for this new feature.
All 1,049 S2S C++ exports are byte-identical to the pre-feature backend-boundary
checkpoint at `/tmp/my-try-backend-boundary-20261005-2/s2s`.
No native compiler rebuild, conversion or new runtime execution was performed.

This preparation-only checkpoint did not enable executable output. The following
execution checkpoint supersedes that gate for built-ins only.

### Built-in construction execution — 2026-10-05

`CPP_Data_Construction` consumes settled bindings and entry conversions through one
family-independent lowering path. Backend-only definition metadata selects
`scpp::data_initializer<Target>::from_list/from_key_value`; concrete callable bindings
remain separate from semantic factory identities. This supports alias-backed dynamic
without inventing members on its C++ type. Lifecycle installs backend metadata alongside,
not inside, semantic registration.

`scpp/data_initializer.hpp` provides immutable entries, a read-only call-scoped view
and bounded RAII backing. Empty literals pass an empty view with no input allocation.
Small inputs use bounded optional slots; larger ones use heap backing. The buffer
template depends only on element type, never literal length. Shared population helpers
fill final storage directly; mixed wraps shared-table storage, while dynamic retains
its shared handle and nested `dynamic_v` tags under the current contract.

Each key and converted value is captured once in source order, key before value.
Duplicate entries reach the factory unchanged and now reject at runtime after key
normalization (user clarification superseding the initial last-value-wins proposal).
Repeated names in separate nested constructions remain valid; ordinary key updates
are unchanged. Checked input-conversion failures and factory failures unwind input
and partial output; destination assignment occurs only after success. Earlier source
side effects are not rolled back. Nested constructions follow their binding's target.

Initial evidence: `/tmp/my-try-data-execution-20261005-2` (PCH) and
`/tmp/my-try-data-execution-20261005-3` (no PCH), five focused PHP suites plus generated
programs and a direct runtime lifetime/throwing-copy proof. These preceded the duplicate
policy clarification and are not evidence of its new rejection rule. Neither run
rebuilds the compiler itself natively. The unrelated local dependency-cleanup edit
remains untouched. Existing 1,049 pre-feature C++ exports remain byte-identical.

Final duplicate-rejection evidence: `/tmp/my-try-data-execution-20261005-4` — five
PHP suites, 40 PCH-backed generated C++ programs and the runtime lifetime proof pass.
This includes normalized collisions, null/false existing values, unchanged destination
after failure, and repeated names allowed in distinct nested objects.

Remaining construction gates/debt:

- Effective storage-modifier targets reject during preparation; exact modified-target
  model bindings remain representable. Executable modifier support needs a truthful
  storage/adapter path, not invalid `value_p` instantiation around handle-like dynamic.
- User-defined factories/input exposure require method/interface support and enforced
  non-escape. Built-in synchronous adapters cannot expose input views to source today.
- Expected-type propagation through selection joins, callable values and automatic
  object-property mapping remain deferred. Unknown untyped literals still reject.
- **CH06-MIX-DYN-01 remains open and blocking closeout**; current runtime success is
  evidence of implementation, not a new containment decision.

The following read slice implements the next agreed group. Dynamic `->` needs its
own language discussion; reference iteration remains separately gated.

### Shared container value reads — 2026-10-05

One `index_node` and the existing operator-selection boundary now serve vector,
typed hash, mixed and dynamic value reads. Exact definition identities select
registered key/result signatures and checked/null-on-miss operation policy.
`contract_type_reference` and analysis-owned substitution are shared with construction;
construction opt-in itself does not grant indexing. No parser family split or
container-name dispatch was added to preparation/emission.

Typed reads return copied `T`/`V` and throw on negative/out-of-range vector indexes
or missing hash keys. Mixed/dynamic return mixed, with null for absent keys, null
intermediates or empty handles. Wrong scalar receivers and runtime-invalid key kinds
throw. Static invalid types reject during preparation. Key types initially cover
ordinary int/string and checked mixed boundaries; numeric-string normalization stays
runtime-owned. The top-level array specification now explicitly distinguishes typed
containers from its former broadly worded null-on-miss dynamic-table rule.

C++ emission binds the receiver once, converts the key next, and copies out through
`scpp/index_read.hpp`. Whole vectors/hashes are not copied merely to read a slot.
Temporary receivers remain alive through lookup. No read autovivifies or returns
an addressable interior reference; shared dynamic payload identity remains shared.
The new checked dynamic helper leaves permissive legacy `mixed_t::get` unchanged.

Facts are attached directly to index syntax and cleared by ordinary cleanup.
Signature changes reprepare retained read bodies; failed key admission clears stale
facts/output, and recovery agrees with fresh emission. Indexed writes, updates,
native references, keyed probes, zero-argument brackets, strings and effective
storage modifiers remain explicit gates. Existing eager-parent effect restrictions
still apply recursively; this does not introduce a general effect analysis.

Evidence: `/tmp/my-try-container-reads-20261005-1` passed seven focused PHP suites
and 24 PCH-backed generated programs. `/tmp/my-try-container-reads-20261005-4`
passed seven PHP suites and the expanded 28-program set without PCH, including
nullable elements and native-supplied owned/shared/weak/dynamic table carriers.
Two added native probes initially used a wrong nullable accessor/missed their own
memory-helper header; their corrections are in the passing run. The final indexed
key rejection check passed in `/tmp/my-try-index-read-key-domain-3`; hashable-key
constraints already reject `hash<int,mixed>` before read selection.
All 1,089 pre-read S2S exports remain byte-identical. These are PHP-authored compiler
and generated-program proofs, not a native compiler rebuild or broad regression run.

Next discussion: common writes/appends, then keyed probes and value iteration.
**CH06-MIX-DYN-01 remains open and blocking Chapter 06 closeout.**

### End-to-end catalog rows

Edit these rows as work proceeds. Imported source support is recorded below, independently of this progress.

| Entry | Status | PHP input example | Frontend | C++ S2S | LLVM | Proof / blocker |
| --- | --- | --- | --- | --- | --- | --- |
| [ARR-INIT-001](#arr-init-001) | supported | `$v vector<int> = [];` | passed | passed | deferred | `data_empty` state proof |
| [ARR-INIT-003](#arr-init-003) | supported | `$v vector<int> = [10, 20, 30];` | passed | passed | deferred | `data_vector`, nested/large/order proofs |
| [ARR-INIT-004](#arr-init-004) | bounded-support | `$m hash<int> = ["alice" => 10, "bob" => 20];` | passed | passed | deferred | `data_hash`; imported untyped `$a = [1, 2, 3]` rejects without known target |
| [ARR-READ-001](#arr-read-001) | bounded-support | `$b vector<int> = [3]; $a = $b[0];` | passed | passed | deferred | `read_typed`, bounds/key/order/nested proofs; all four registered families |
| [ARR-READ-002](#arr-read-002) | bounded-support | `$b dynamic = {name: "Alex"}; $a = $b["name"];` | passed | passed | deferred | `read_dynamic`, missing/typed-boundary/wrong-kind proofs |
| [ARR-KEY-SEM-001](#arr-key-sem-001) | pending-discussion | `$a[1] = "int"; $a["1"] = "string";` | unverified | unverified | deferred | — |
| [ARR-WRITE-001](#arr-write-001) | pending-discussion | `$b[0] = 1;` | unverified | unverified | deferred | — |
| [ARR-APPEND-001](#arr-append-001) | pending-discussion | `$a[] = 1;` | unverified | unverified | deferred | — |
| [FUNC-DECL-003B](#func-decl-003b) | pending-discussion | `function f(vector<int> $a): void {}` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [FUNC-ARG-001](#func-arg-001) | pending-discussion | `f($x[0]);` | unverified | unverified | deferred | — |
| [CTRL-FOREACH-001](#ctrl-foreach-001) | pending-discussion | `foreach ($items as $item) { }` | unverified | unverified | deferred | — |
| [CTRL-FOREACH-002](#ctrl-foreach-002) | pending-discussion | `foreach ($items as $k => $v) { }` | unverified | unverified | deferred | — |
| [CTRL-FOREACH-003](#ctrl-foreach-003) | pending-discussion | `foreach ($items as &$item) { $item++; }` | unverified | unverified | deferred | — |
| [CTRL-FOREACH-004](#ctrl-foreach-004) | pending-discussion | `foreach ($items as $key => &$item) { if ($key > 1) $item++; }` | unverified | unverified | deferred | — |
| [CAST-ARRAY-001](#cast-array-001) | pending-discussion | `$a = (array)$b;` | unverified | unverified | deferred | — |
| [ARR-INIT-002](#arr-init-002) | pending-discussion | `$a = [];` | unverified | unverified | deferred | — |
| [ARR-INIT-002A](#arr-init-002a) | pending-discussion | `$a = null;` | unverified | unverified | deferred | — |
| [ARR-INIT-005](#arr-init-005) | pending-discussion | `$a = ["name" => "Alex", "age" => 12];` | unverified | unverified | deferred | — |
| [ARR-INIT-006](#arr-init-006) | pending-discussion | `$d = ["data" => [["id" => 1], ["id" => 2]]];` | unverified | unverified | deferred | — |
| [ARR-EXIST-001](#arr-exist-001) | pending-discussion | `isset($a["name"])` | unverified | unverified | deferred | — |
| [ARR-UNSET-001](#arr-unset-001) | pending-discussion | `unset($a["name"]);` | unverified | unverified | deferred | — |
| [ARR-EMPTY-001](#arr-empty-001) | pending-discussion | `empty($a["name"])` | unverified | unverified | deferred | — |
| [ARR-TYPE-001](#arr-type-001) | pending-discussion | `function f(array &$a): void { $a["x"] = 1; }` | unverified | unverified | deferred | — |
| [ARR-VALUE-001](#arr-value-001) | pending-discussion | `$v vector<int> = [1, 2, 3]; $d = ["data" => $v];` | unverified | unverified | deferred | Strict source adaptation; imported legacy form retained |
| [TYPE-PARAM-002](#type-param-002) | pending-discussion | `function f(string $s, vector_t $v): string { return $s; }` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003A](#type-param-003a) | pending-discussion | `function f(array $a): void { var_dump($a); }` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003C](#type-param-003c) | pending-discussion | `function f(array $a): void { $a[] = 1; }` | unverified | unverified | deferred | — |
| [TYPE-PARAM-003E](#type-param-003e) | pending-discussion | `function f(array $a): void { foo($a); }` | unverified | unverified | deferred | — |
| [NOTE-006](#note-006) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-022](#note-022) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-053](#note-053) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-055](#note-055) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
| [NOTE-058](#note-058) | pending-discussion | — (example pending) | unverified | unverified | deferred | Prose rule; extract/split examples |
## CAST-ARRAY-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:101](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = (array)$b;
```

**Existing C++ lowering / result**

```cpp
ERROR
```

**Category:** Cast

**Rule kind:** rejection

**Source support status:** rejected

**Normalized pattern:** `<var> = (array)<expr>`

**General rule:** Array casts are not supported.

**Diagnostics:** Emit error when `(array)` is encountered.

**Notes:** Arrays are not supported at the moment.


## CTRL-FOREACH-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:130](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
foreach ($items as $item) { }
```

**Existing C++ lowering / result**

```cpp
for (auto __scpp_foreach_entry_L : foreach_range(items)) { auto item = __scpp_foreach_entry_L.value_copy(); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source expression must lower to a typed `vector<T>` / `hash<T>` / `hash<T, T_KEY>` surface, to the current packed `hash_t<mixed_t>` / boxed-`mixed_t` array surface, or to an approved iterable wrapper success payload; nullable containers are not auto-unwrapped

**Normalized pattern:** `foreach (<expr> as <var>) <block>`

**General rule:** Value-only `foreach` lowers through the runtime `foreach_range(...)` iterable surface for typed containers, dynamic arrays, and approved wrappers. If the foreach value name already exists before the loop, the generated code reuses that outer binding; otherwise the generated foreach value variable is loop-local.

**Diagnostics:** Emit a `foreach_range(...)` loop and assign the current element to the foreach value variable on each iteration. Preserve the typed element payload when the source expression is a typed `vector<T>` or typed hash surface. If the value name is not already declared before the loop, declare it inside the emitted loop body only.

**Notes:** This is an intentional Prism++ policy difference from PHP: undeclared foreach value variables do not leak outside the loop.


## CTRL-FOREACH-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:131](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
foreach ($items as $k => $v) { }
```

**Existing C++ lowering / result**

```cpp
for (auto __scpp_foreach_entry_L : foreach_range(items)) { auto&& k = __scpp_foreach_entry_L.key(); auto v = __scpp_foreach_entry_L.value_copy(); }
```

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source expression must lower to a typed `vector<T>` / `hash<T>` / `hash<T, T_KEY>` surface, to the current packed `hash_t<mixed_t>` surface, or to an approved iterable wrapper success payload; nullable containers are not auto-unwrapped

**Normalized pattern:** `foreach (<expr> as <var> => <var>) <block>`

**General rule:** Key-value `foreach` lowers through the same `foreach_range(...)` iterable surface, with the key variable assigned from the runtime entry key each iteration. The key variable remains loop-local. The value variable follows the same reuse rule as value-only foreach: reuse an existing outer binding when present, otherwise keep it loop-local.

**Diagnostics:** Emit a `foreach_range(...)` loop, declare a fresh loop-local key variable each iteration, assign the runtime entry key to the key variable, and assign the current element to the value variable. Preserve the typed element payload when the source expression is a typed `vector<T>` or typed hash surface; the foreach key variable takes the actual typed hash key surface (`string_t` by default for `hash<T>`, explicit `T_KEY` for `hash<T, T_KEY>`).

**Notes:** The current pass supports only simple variable targets. This is an intentional Prism++ scoping policy difference from PHP for previously undeclared foreach value variables.


## CTRL-FOREACH-003

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:132](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
foreach ($items as &$item) { $item++; }
```

**Existing C++ lowering / result**

hidden-key slot rewrite; body rewrites `$item` to `$items[_item_key_]`

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported-with-known-limitations

**Preconditions:** a hidden key local is synthesized when no explicit key variable is declared; by-reference foreach lowering is currently modeled as source-slot rewriting rather than a standalone alias local

**Normalized pattern:** `foreach (<expr> as &<value-var>) <block>`

**General rule:** Value-only by-reference `foreach` synthesizes a hidden loop-local key and rewrites each use of the foreach value variable inside the loop body to the source slot expression indexed by that hidden key. This avoids exposing a native C++ reference to container interior storage.

**Diagnostics:** Lower as key-driven source-slot rewriting; synthesize a hidden key local such as `_<value-var>_key_` and replace loop-body uses of the value variable with `<expr>[<hidden-key>]`. Mark the behavior as provisional and subject to future improvement.

**Notes:** This rule overwrites older conflicting guidance.


## CTRL-FOREACH-004

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:133](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
foreach ($items as $key => &$item) { if ($key > 1) $item++; }
```

**Existing C++ lowering / result**

explicit-key slot rewrite; body rewrites `$item` to `$items[$key]`

**Category:** Control flow

**Rule kind:** generation

**Source support status:** supported-with-known-limitations

**Preconditions:** explicit key must remain available as the source slot key; by-reference foreach lowering is currently modeled as source-slot rewriting rather than a standalone alias local

**Normalized pattern:** `foreach (<expr> as <key-var> => &<value-var>) <block>`

**General rule:** Explicit-key by-reference `foreach` preserves the PHP key variable and rewrites each use of the foreach value variable inside the loop body to the source slot expression indexed by that key. This avoids exposing a native C++ reference to container interior storage.

**Diagnostics:** Lower as key-driven source-slot rewriting; keep the explicit key variable and replace loop-body uses of the value variable with `<expr>[<key-var>]`. Mark the behavior as provisional and subject to future improvement.

**Notes:** This rule overwrites older conflicting guidance.


## FUNC-DECL-003B

**Strict-mode PHP input example:** `function f(vector<int> $a): void {}`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:140](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(/** vector<int> */ $a): void {}
```

**Existing C++ lowering / result**

```cpp
void_t f(const vector_t<int_t>& a) {}
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter doc-comment type is explicit and supported

**Normalized pattern:** `doc-typed function parameter`

**General rule:** Function parameters may take their explicit type from an attached doc-comment when no native PHP type is present.

**Diagnostics:** Error if the parameter has no explicit type or if both native and doc-comment types are present.

**Notes:** No parameter may fall back to `auto`.


## FUNC-ARG-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:265](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
f($x[0]);
```

**Existing C++ lowering / result**

```cpp
f(x[0]);
```

**Category:** Function

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** call argument is a direct DIM expression

**Normalized pattern:** direct DIM call argument

**General rule:** Direct DIM expressions used as call arguments lower through the direct slot path `[]`, not `.get(...)`, regardless of whether the callee later treats the parameter as by-reference or by-value.

**Diagnostics:** Use the normal expression renderer for computed arguments instead of forcing the slot path.

**Notes:** This intentionally allows autovivification for direct DIM call arguments; callers who need read-only behavior should spell it explicitly (for example with `?? null`).


## ARR-INIT-001

**Strict-mode PHP input example:** `$v vector<int> = [];`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:283](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$v /** vector<int> */ = [];
```

**Existing C++ lowering / result**

```cpp
::scpp::vector_t<int_t> v = {};
```

**Category:** Array

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** assignment target carries an explicit local type comment mapping to `vector_t<T>`

**Normalized pattern:** `<typed-var> = []`

**General rule:** Empty array literals lower to an empty `vector_t<T>` only when the local target explicitly provides a vector element type.

**Diagnostics:** Emit an error for untyped empty literals only when a different rule cannot classify the target.

**Notes:** This is the typed-vector branch of literal lowering.


## ARR-INIT-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:284](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = [];
```

**Existing C++ lowering / result**

```cpp
mixed_t a = mixed_t{table_()};
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** untyped array literal

**Normalized pattern:** `<var> = []`

**General rule:** Untyped empty array literals lower to `mixed_t a = mixed_t{table_()};`. First-assignment `auto` deduction is intentionally bypassed for this case so the declared PHP local immediately exposes the fat `mixed_t` API.

**Diagnostics:** Emit an error only for unsupported nested element shapes outside the current literal subset.

**Notes:** Dynamic locals may also start from `mixed_t a;` or `mixed_t a = null` and autovivify later.


## ARR-INIT-002A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:285](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = null;
```

**Existing C++ lowering / result**

```cpp
mixed_t a = null;
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** first assignment to an untyped PHP local is the null literal and later fat-value operations may follow

**Normalized pattern:** `<var> = null`

**General rule:** Untyped first-assignment null literals lower to `mixed_t a = null;`, not `auto a = null;`, so the declared PHP local keeps the null-state `mixed_t` carrier required for later `append(...)`, `operator[]`, and `get(...)`.

**Diagnostics:** Emit an error only when a typed-local rule or another stronger declaration rule applies first.

**Notes:** This is the null bootstrap branch of fat-value local initialization.


## ARR-INIT-003

**Strict-mode PHP input example:** `$v vector<int> = [1, 2, 3];`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:286](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$v /** vector<int> */ = [1, 2, 3];
```

**Existing C++ lowering / result**

```cpp
::scpp::vector_t<int_t> v{static_cast<int_t>(1), static_cast<int_t>(2), static_cast<int_t>(3)};
```

**Category:** Array

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** assignment target carries an explicit local type comment mapping to `vector_t<T>`; all elements are positional

**Normalized pattern:** `<typed-var> = [<expr>, ...]`

**General rule:** Typed positional array literals lower directly to `vector_t<T>{...}`.

**Diagnostics:** Emit an error if a typed vector literal contains explicit keys or unsupported element shapes.

**Notes:** Typed vectors stay typed and do not lower through `hash_t`.


## ARR-INIT-004

**Strict-mode PHP input example:** `$m hash<int> = ["a" => 1, "b" => 2];`<br>`$a = [1, 2, 3];`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

Different source versions share this ID. Preserve both until their differences are discussed; neither is silently selected as the v0.2 decision.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:287](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$m /** hash<int> */ = ["a" => 1, "b" => 2];
```

**Existing C++ lowering / result**

```cpp
::scpp::hash_t<int_t> m = [&]() -> ::scpp::hash_t<int_t> { ::scpp::hash_t<int_t> __scpp_hash_value{}; __scpp_hash_value.set(string_t("a"), static_cast<int_t>(1)); __scpp_hash_value.set(string_t("b"), static_cast<int_t>(2)); return __scpp_hash_value; }();
```

**Category:** Array

**Rule kind:** generation-with-precondition

**Source support status:** supported-with-precondition

**Preconditions:** assignment target carries an explicit local type comment mapping to `hash_t<T>` or `hash_t<T, T_KEY>`

**Normalized pattern:** `<typed-hash-var> = [<key> => <expr>, ...]`

**General rule:** Typed keyed array literals lower directly to typed `hash_t<T>` / `hash_t<T, T_KEY>` construction/update code. `hash<T>` defaults to `string_t` keys; `hash<T, T_KEY>` requests an explicit supported typed key family.

**Diagnostics:** Emit an error only for unsupported key kinds or unsupported element shapes.

**Notes:** Typed hashes are first-class typed containers on the PHP surface and do not lower through `mixed_t` when the destination type is explicit. Append-style typed literals are semantically valid only for integer-keyed typed hashes.

### Imported version 2

**Source:** [generators/php/specs/rules_catalog.md:288](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = [1, 2, 3];
```

**Existing C++ lowering / result**

```cpp
mixed_t a = mixed_t{table_(table_item_(static_cast<int_t>(1)), table_item_(static_cast<int_t>(2)), table_item_(static_cast<int_t>(3)))};
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** literal is untyped

**Normalized pattern:** `<var> = [<expr>, <expr>, ...]`

**General rule:** Untyped positional PHP array literals lower to `mixed_t` initializers backed by `mixed_t{table_(table_item_(...), ...)}`; first-assignment `auto` deduction must not be used for these literals.

**Diagnostics:** Emit an error for unsupported element shapes.

**Notes:** Positional untyped literals are not inferred as vectors.


## ARR-INIT-005

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:289](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = ["name" => "Alex", "age" => 12];
```

**Existing C++ lowering / result**

```cpp
mixed_t a = mixed_t{table_(table_kv_("name", string_t("Alex")), table_kv_("age", static_cast<int_t>(12)))};
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** keyed literal elements are individually supported keys/values

**Normalized pattern:** `<var> = [<key> => <expr>, ...]`

**General rule:** Untyped keyed array literals lower to `mixed_t` initializers backed by `mixed_t{table_(table_kv_(key, value), ...)}`; first-assignment `auto` deduction must not be used for these literals.

**Diagnostics:** Emit an error for unsupported key/value expression shapes.

**Notes:** Keyed and unkeyed elements share the same recursive literal builder family.


## ARR-INIT-006

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:290](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$d = ["data" => [["id" => 1], ["id" => 2]]];
```

**Existing C++ lowering / result**

nested `::scpp::table_(...)` builders

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** nested literals recurse inside the current supported element subset

**Normalized pattern:** `nested array literal`

**General rule:** Nested untyped array literals recurse through the same `table_ / table_item_ / table_kv_` helper family.

**Diagnostics:** Emit an error only when an inner literal leaves the supported subset.

**Notes:** The catalog stores the recursive rule once rather than one row per nesting depth.


## ARR-READ-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:291](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b[0];
```

**Existing C++ lowering / result**

```cpp
auto a = b[static_cast<int_t>(0)];
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source form is a normal PHP array read

**Normalized pattern:** `<var> = <expr>[<expr>]`

**General rule:** PHP array reads lower directly to `operator[]` on `hash_t` / `mixed_t`, which keeps plain reads on the non-throwing null-on-miss path while still allowing typed-reference binding when the C++ use site requires it.

**Diagnostics:** Error if append form `[]` is used as a read expression.

**Notes:** This is the generator default for `hash_t` reads.


## ARR-READ-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:292](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a = $b["name"];
```

**Existing C++ lowering / result**

```cpp
auto a = b["name"];
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** source form is a normal PHP array read

**Normalized pattern:** `<var> = <expr>[<string-key>]`

**General rule:** String-key reads use the same direct `operator[]` rule as numeric-key reads.

**Diagnostics:** Error only for unsupported key expression shapes.

**Notes:** Generator does not special-case known-present keys into `.at(...)`; direct `operator[]` is the normal read path and `find(...)` stays reserved for presence-sensitive logic.


## ARR-WRITE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:293](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$b[0] = 1;
```

**Existing C++ lowering / result**

```cpp
b[static_cast<int_t>(0)] = static_cast<int_t>(1);
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** write target is a `hash_t`-style PHP array expression

**Normalized pattern:** `<expr>[<expr>] = <expr>`

**General rule:** PHP keyed array writes lower to direct `operator[]` assignment. Existing keys are overwritten and missing keys are materialized by the runtime.

**Diagnostics:** Emit an error only for unsupported target/key/value forms.

**Notes:** Direct `operator[]` assignment is the canonical keyed-write API for generator output.


## ARR-APPEND-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:294](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a[] = 1;
```

**Existing C++ lowering / result**

```cpp
(void) a.append(static_cast<int_t>(1));
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** write target is a `hash_t`-style PHP array expression

**Normalized pattern:** `<expr>[] = <expr>`

**General rule:** PHP append writes lower to `append(...)`; simple right-hand sides inline directly, while non-trivial right-hand sides may spill into a temporary so assignment-style lowering still names the value once.

**Diagnostics:** Emit an error if append syntax is used where a read/lvalue is required.

**Notes:** Append semantics are defined by `hash_t` (`max_existing_int_key + 1`).


## ARR-EXIST-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:295](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
isset($a["name"])
```

**Existing C++ lowering / result**

```cpp
php::isset(a, "name")
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is an array/hash-compatible carrier

**Normalized pattern:** `isset(<expr>[<expr>])`

**General rule:** `isset($a[k])` lowers through the runtime `isset(...)` helper and preserves the current null-sensitive subset rule (`missing` â†’ `false`, existing `null` â†’ `false`).

**Diagnostics:** Emit an error only for unsupported target/key forms.

**Notes:** The current subset does not treat `isset($a[k])` as pure key existence.


## ARR-UNSET-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:296](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
unset($a["name"]);
```

**Existing C++ lowering / result**

```cpp
a.remove("name");
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target is a `hash_t`-style keyed array slot

**Normalized pattern:** `unset(<expr>[<expr>])`

**General rule:** `unset($a[k])` lowers to `remove(k)` and remains a no-op when the key is absent.

**Diagnostics:** Emit an error for unsupported targets such as vector element unsets.

**Notes:** The runtime `hash_t` contract guarantees key stability after removal.


## ARR-EMPTY-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:297](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
empty($a["name"])
```

**Existing C++ lowering / result**

```cpp
php::empty(a["name"])
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** target/key expression lowers to a normal array read first

**Normalized pattern:** `empty(<expr>[<expr>])`

**General rule:** `empty($a[k])` is evaluated on the resulting value. Under the current Prism++ subset it is true only for `null`, `""`, and empty array/table values.

**Diagnostics:** Emit an error only for unsupported target/key forms.

**Notes:** This is a deliberate reduced emptiness rule, not full PHP falsiness.


## ARR-TYPE-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:298](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array &$a): void { $a["x"] = 1; }
```

**Existing C++ lowering / result**

```cpp
void f(mixed_t& a) { ::scpp::php::expect_array_argument(a, false, "a"); a[string_t("x")] = static_cast<int_t>(1); }
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** native PHP parameter/return/property type is `array`

**Normalized pattern:** `array` type declaration

**General rule:** Native PHP `array` declarations lower to `mixed_t`; explicit PHP by-reference parameters keep the C++ lvalue reference and the callee validates the argument before user code runs.

**Diagnostics:** Emit an error only when a future typed-array specialization conflicts with this default mapping.

**Notes:** This is the canonical fat-variable bridge from PHP `array` to the runtime.


## ARR-KEY-SEM-001

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:299](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$a[1] = "int"; $a["1"] = "string";
```

**Existing C++ lowering / result**

```cpp
a[1] = "int"; a["1"] = "string";
```

**Category:** Array

**Rule kind:** generation-with-note

**Source support status:** supported-with-note

**Preconditions:** current runtime contract is `hash_t` v1

**Normalized pattern:** `int key` vs `numeric-string key`

**General rule:** `hash_t` keeps integer keys and string keys distinct in v1.

**Diagnostics:** Document the PHP mismatch explicitly in the notes/specs.

**Notes:** This avoids numeric-string coercion in the current runtime/generator stage.


## ARR-VALUE-001

**Strict-mode PHP input example:** `$v vector<int> = [1, 2, 3]; $d = ["data" => $v];`

This is the working source spelling. Imported examples and C++ expectations below remain reference material; the v0.2 result still needs agreement and proof.

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:300](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
$v /** vector<int> */ = [1, 2, 3]; $d = ["data" => $v];
```

**Existing C++ lowering / result**

```cpp
vector_t<int_t> v{...}; auto d = ::scpp::table_(::scpp::table_kv_("data", v));
```

**Category:** Array

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** typed vector value already exists as an expression

**Normalized pattern:** `typed value inserted into untyped table literal`

**General rule:** Typed values may be inserted into untyped table literals through the same `table_kv_` / `table_item_` helpers.

**Diagnostics:** Emit an error only when the value expression itself is unsupported.

**Notes:** This keeps typed vectors and untyped tables separate without introducing structural type annotations.


## TYPE-PARAM-002

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:330](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(string $s, vector_t $v): string { return $s; }
```

**Existing C++ lowering / result**

```cpp
string_t f(const string_t& s, const vector_t& v) { return s; }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter and return types are explicit and supported

**Normalized pattern:** `string/vector parameter read-only convention`

**General rule:** Proven read-only by-value `string_t` and `vector_t` parameters lower to `const &` and return by value.

**Diagnostics:** Error if the generator rewrites proven read-only params to mutable reference without explicit PHP `&`.

**Notes:** Explicit PHP `&` disables the read-only `const &` convention.


## TYPE-PARAM-003A

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:332](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array $a): void { var_dump($a); }
```

**Existing C++ lowering / result**

```cpp
void_t f(mixed_t a) { ::scpp::php::expect_array_argument(a, false, "a"); var_dump(a); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter type is explicit native PHP `array`, PHP `&` is absent

**Normalized pattern:** `array parameter mixed_t ABI`

**General rule:** By-value PHP `array` params lower to `mixed_t` and are validated at function entry before user code executes. Runtime copy-on-write handles later separation on mutation.

**Diagnostics:** Error if the generator omits the entry guard.

**Notes:** Read-only nested access inside the body must use `get(...)` / `_find_val(...)`.


## TYPE-PARAM-003C

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:334](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array $a): void { $a[] = 1; }
```

**Existing C++ lowering / result**

```cpp
void_t f(mixed_t a) { ::scpp::php::expect_array_argument(a, false, "a"); (void) a.append(...); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** parameter type is explicit native PHP `array`, PHP `&` is absent

**Normalized pattern:** `array parameter mutating mixed_t ABI`

**General rule:** By-value PHP `array` params stay as `mixed_t` even when the body mutates them; runtime copy-on-write detaches shared table storage on the first write.

**Diagnostics:** Error if the generator omits the entry guard.

**Notes:** The runtime array guard prevents user code from running on invalid kinds.


## TYPE-PARAM-003E

**v0.2 decision / target C++:** Pending discussion.

### Imported version 1

**Source:** [generators/php/specs/rules_catalog.md:336](../../../../generators/php/specs/rules_catalog.md)

**PHP input example**

```php
function f(array $a): void { foo($a); }
```

**Existing C++ lowering / result**

```cpp
void_t f(mixed_t a) { ::scpp::php::expect_array_argument(a, false, "a"); foo(a); }
```

**Category:** Type system

**Rule kind:** generation

**Source support status:** supported

**Preconditions:** the body does not prove a write and the callee contract is not inspected by s2s

**Normalized pattern:** `unclear stays guarded mixed_t`

**General rule:** The generator keeps PHP `array` params as guarded `mixed_t` even when callee-side write information is unavailable.

**Diagnostics:** No alternate `hash_t<mixed_t>` ABI is introduced.

**Notes:** This avoids table/reference ABI splits in the fat-variable design.


## NOTE-006

**Source:** [generators/php/specs/rules_catalog.md:379](../../../../generators/php/specs/rules_catalog.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Nested table dim support
>
> - Nested table dim reads chain through `get(...)` / `_find_val(...)` so `$x["inner"][0]` stays non-mutating on the read path.
> - Nested table dim writes keep the full lvalue chain on mutating `operator[]` access, so `$x[0]["name"] = "first";` does not route intermediate segments through `get(...)`.
> - Nested append on a table-valued slot is supported through chained `operator[]` plus `append(...)` on `mixed_t` / `hash_t<mixed_t>`.
> - Table-valued assignments into table slots now use direct `mixed_t` assignment through the returned `operator[]` reference.
>
> ## Assignment-expression lambda fallback
>
> - Default rule: do not emit a helper lambda for ordinary assignment statements or simple assignment expressions.
> - Fallback rule: emit a helper lambda only in complex expression contexts where the generator must preserve PHP assignment-value semantics while also guaranteeing single evaluation, especially append expressions or larger composed expressions.
>
> - by-value `array` params from DIM/slot expressions lower directly through the normal `mixed_t` path; runtime copy-on-write preserves PHP by-value behavior without an explicit generator-side copy helper.
>
> - by-value assignment from a DIM read that yields a mixed runtime value also lowers directly; later writes detach shared table storage on demand.
>
>
> ## Return-by-reference warnings
>
> - Return-by-reference is not recommended in Prism++ and must always surface a generator warning even when generation is still allowed.
> - The generator must also warn for local copy-after-alias patterns rooted in a by-reference call result, for example `$inner =& get_inner($arr); $copy = $arr;`, because Prism++ may not preserve PHP alias semantics for that flow.
>
> ## Historical note â€” typed scalar by-reference proxy lowering
>
> Legacy helper/proxy infrastructure may still exist in the runtime, but it is not part of the supported safe subset. The current design direction is the native-reference safety rule documented in `specs/native_reference_safety.md`.

## NOTE-022

**Source:** [generators/php/specs/rules.md:317](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## 11B. Array subset (v1)
>
> Supported array lowering is intentionally narrow and split by target typing.
>
> Priority note:
> - `../../specs/dynamic_types.md` sections **1.2 Explicit Typed Boundaries** and **1.3 Technical Compromises to Preserve Explicit Typed Boundaries in v1** govern current typed-destination lowering from `mixed_t`
> - until symbol resolution/static analysis is strong enough to inject every required explicit cast at the exact site, generated/runtime-visible behavior must continue to preserve those v1 bridges
> - generator cleanup must therefore not assume that removing runtime bridge casts is safe merely because the long-term model prefers explicit emitted casts
>
> ### Untyped PHP arrays
> - untyped `[]` lowers to `mixed_t x = mixed_t{table_()}` when it is used as an explicit array-present initializer; dynamic locals may also start as `mixed_t x;` / `mixed_t x = null` and autovivify later
> - untyped `[v1, v2, ...]` lowers to `mixed_t x = mixed_t{table_(table_item_(...), ...)}`
> - first assignment declaration inference is intentionally overridden for fat-value bootstrap initializers: `$x = [];`, `$x = [ ... ];`, and `$x = null;` must declare as `mixed_t`, never `auto`, so the variable immediately exposes the fat `mixed_t` API (`append`, `operator[]`, `get`) and preserves null-state autovivification
> - nested append writes are full mutating LHS chains: `$x["users"][] = $v;` must lower through mutating access on `$x["users"]` and then `append(...)` (for example `x[string_t("users")].append(...)`), never through the read-only `.get(...)` path.
> - untyped `["k" => v]` lowers to `mixed_t x = mixed_t{table_(table_kv_("k", ...))}`
> - nested untyped arrays recurse through the same `table_ / table_item_ / table_kv_` helpers
> - PHP array reads in read-only contexts lower to `get(...)` / `_find_val(...)`; mutating contexts still lower to `operator[]` / `append(...)`
> - native PHP `array` type declarations now lower to `mixed_t`; function-entry guards enforce `array` vs `?array` before any user code runs, and explicit PHP `&` lowers to `mixed_t&`
> - PHP keyed writes now lower to direct `operator[]` assignment
> - PHP append writes lower to `append(...)`; simple right-hand sides inline directly, while non-trivial right-hand sides may spill into a temporary to keep assignment-style lowering explicit
> - `unset($a[k])` lowers to `remove(k)`; missing-key `unset` remains a no-op
> - `isset($a[k])` lowers through the runtime `isset(...)` helper and must preserve null-sensitive semantics (`missing` â†’ `false`, existing `null` â†’ `false`)
> - `empty($a[k])` lowers through the runtime `empty(...)` helper for the resulting value; under the current supported subset it is true only for `null`, `""`, and empty array/table values
> - the normative cross-runtime contract is defined in `specs/count_empty_isset_contract.md`
>
> ### Typed vectors
> - `/** vector<T> */ []` lowers to `vector_t<T>{}`
> - `/** vector<T> */ [e1, e2, ...]` lowers to `vector_t<T>{e1, e2, ...}`
> - typed vector literals must remain positional; explicit keys are rejected
>
> ### Typed hashes
> - Typed hash literals use the same expected-type initializer path inside struct fields and nested vector/hash/fixed-array literals as at typed local declarations. Known container element/value types must remain typed during recursive literal lowering.
> - `/** hash<T> */ []` lowers to `hash_t<T>{}`
> - `/** hash<T> */ ["k" => v, ...]` lowers to a typed `hash_t<T>` initializer sequence with the default `string_t` key surface
> - `/** hash<T, T_KEY> */ ...` lowers to `hash_t<T, T_KEY>` when an explicit typed key family is requested
> - typed hash literals may use keyed entries for all supported key families
> - append-style entries are structurally available on the runtime surface for generator compatibility, but are semantically valid only for integer-keyed typed hashes
> - typed hash read-only dim access lowers through checked keyed access
> - typed hash write dim access lowers through mutating keyed access / append on `hash_t<T>` or `hash_t<T, T_KEY>` as appropriate
>
> ### Intentional v1 deviations from PHP
> - `hash_t` keeps integer keys and string keys distinct (`1` != `"1"`)
> - `operator[]` is now the primary read/write surface for lowered PHP array access. Mutable paths autovivify missing slots; const paths return the static null-like value on miss. `find(...)` remains reserved for presence-sensitive logic; `at(...)` remains the runtime checked-access API.
> - typed value destinations reached from array reads keep the same missing-key read semantics first, then apply the ordinary typed-boundary rules from `../../specs/dynamic_types.md`
>
> See also `../../specs/array_semantics.md` for the authoritative current subset.

## NOTE-053

**Source:** [generators/php/specs/rules.md:1220](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Nested table dim support
>
> - Nested table dim reads chain through non-mutating reads so `$x["inner"][0]` lowers via `get(...)` / `_find_val(...)` and does not autovivify the right-hand side.
> - Nested table dim writes stay on the mutating path for the full lvalue chain, so `$x[0]["name"] = "first";` lowers through chained `operator[]` access, not through `get(...)` on intermediate segments.
> - Nested append on a table-valued slot is supported through chained `operator[]` plus `append(...)` on `mixed_t` / `hash_t<mixed_t>`.
> - Table-valued assignments into table slots now use direct `mixed_t` assignment through the mutating container API.
> - Reference assignment from a direct DIM slot is not part of the current safe subset.

## NOTE-055

**Source:** [generators/php/specs/rules.md:1236](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Array argument materialization
>
> - A typed PHP `array` parameter now lowers to `mixed_t` (or `mixed_t&` for explicit PHP `&` when the source expression is otherwise valid under the current safe subset).
> - There is no approved `mixed_t` to native typed by-reference normalization rule in the current safe subset.
> - Native references bind directly only from already-stable native-reference-bindable sources.
> - Any by-reference source rooted in `[]`, dynamic slot/property access, or `.as_*_ref()`-style interior extraction is rejected by design.
> - The generator emits a function-entry guard for every `array` / `?array` parameter before user code runs.
>   - `array` accepts only table-capable `mixed_t` kinds.
>   - `?array` accepts table-capable kinds plus null-kind `mixed_t`.
> - Read-only nested argument access uses `get(...)` / `_find_val(...)`; mutating by-value array paths stay on the mutating container API.
> - Example: `function touch(array $x): array { $x["name"] = "changed"; return $x; }` lowers to `mixed_t touch(mixed_t x) { expect_array_argument(x, false, "x"); x[string_t("name")] = string_t("changed"); return x; }`.
> - See `../../specs/native_reference_safety.md` and `../../specs/references.md`.

## NOTE-058

**Source:** [generators/php/specs/rules.md:1263](../../../../generators/php/specs/rules.md)

**v0.2 decision / examples:** Pending discussion. Imported prose follows; its authority labels and v1 implementation boundaries are source text, not this catalog's status.

> ## Direct DIM call arguments
>
> - Direct DIM expressions used as function-call arguments lower through the direct slot path `[]`, not `.get(...)`.
> - Example: `add($x[0])` â†’ `add(x[0])`.
> - Direct DIM call arguments are valid only for ordinary value passing. They are not native-reference bindable by virtue of being direct DIM expressions.
> - Computed expressions keep the normal read path. Example: `$x[0] + 10` remains `x.get(0) + 10` inside the larger expression.
> - This is an intentional simplification: direct DIM call arguments may autovivify/create a slot. Use an explicit read-only form such as `?? null` when that behavior is not desired.
