# Mixed and dynamic storage: layout and shared implementation
Doc Status: planning

Recorded 2026-10-05. This records the approved staged implementation; language
meaning belongs to the [v0.2 family-separation amendment](../../../../specs/dynamic_types.md#v02-separated-mixeddynamic-families--2026-10-05),
not this plan. It supports
[CH06-MIX-DYN-01](../catalog/06_containers.md#ch06-mix-dyn-01--mixeddynamic-containment-contract).

## Recorded improvement: MIX-LAYOUT-01

Keep the existing mixed borrow counter, but move it beside the kind tag, before
the union. Current x86-64 layout is 1-byte tag, 7 bytes padding, 16-byte union,
2-byte counter and 6 bytes trailing padding: 32 bytes. Proposed ordering is
1-byte tag, 1 byte padding, 2-byte counter, 4 bytes padding and 16-byte union:
24 bytes, with the same fields and intended semantics. The counter currently exists
in both debug and release builds, although its borrow checks are debug-only.

Status: implemented and measured at 24 bytes, alignment 8, with Clang 18 on x86-64
Linux in debug (-O0) and release (-O2, NDEBUG). The borrow counter is retained;
duplicate preprocessor branches declaring the same field were consolidated.
This is an ABI change: rebuild runtime libraries and generated consumers consistently,
including stale PCH/build artifacts. Do not mix the old 32-byte ABI with this one.

Focused evidence: `python3 compiler/my-try/tests/mixed_layout.py --results
/tmp/my-try-mixed-layout-20261005-2` passed two matching runtime builds and four
native test programs. The new `test_mixed_layout.cpp` checks size/alignment, inline
scalars, string ownership, copy/move/self-assignment, kind replacement, table COW,
owned/weak payloads and balanced shared-container lifetime. Its behavioral checks
remain enabled under NDEBUG; the existing array-semantics program also passes in
both modes. Existing dynamic-box behavior is intentionally unchanged at this first
checkpoint, not endorsed as the target separated-family contract.

The initial broader probe (`/tmp/my-try-mixed-layout-20261005-1`) also ran the older
`test_mixed_t_dynamic.cpp`. It fails in debug on its expectation that bool/int
equality throws, and in release on its string `+=` expectation. Both failures were
reproduced with the unmodified HEAD header/32-byte layout in matching fresh runtime
builds (`/tmp/my-try-mixed-layout-baseline`). They are baseline operator-test drift,
not layout regressions; no operator behavior or old expectations were changed here.
That suite is not counted as passing or included in the focused layout runner.

Generated-program integration: `/tmp/my-try-mixed-layout-calls-20261005-1` passed
five PHP suites and 28 PCH-backed container-call programs against a fresh runtime.
FPM socket startup was unavailable, so PHP used the recorded CLI fallback. This
is not a native compiler rebuild or a broad regression run.

## Dynamic direction from discussion

The user accepts a 24-byte target for a tagged dynamic value: inline null/bool/int/
float, indirect string storage and shared container storage. No shared allocation
per numeric/boolean scalar. The public dynamic is still a shared hash handle alias;
the internal tagged value foundation is implemented below, with public migration pending.

A layout-only sketch using current wrappers measured 24 bytes at both -O0 and -O2
on the above target. Inline `string_t` increased the sketch to 40 bytes. These are
object sizes, excluding string/container allocations and ownership control blocks;
they are not portable size guarantees or functional implementation proofs.

The intended semantic direction separates mixed-family value/COW containers from
dynamic-family shared containers while allowing scalar/null leaves in each. JSON
should offer family-specific decoding, defaulting to dynamic including scalar roots.
The core normative permissions and casts are reconciled in the v0.2 amendment.
The approved JSON names are `json_decode` (result<dynamic>), `json_decode_mixed`
(result<mixed>) and `json_encode` for either family. Runtime implementation is recorded
below; compiler registrations/public-type migration remain outstanding.

### Confirmed semantic decisions — 2026-10-05

- New dynamic has one null state; default initialization produces null, distinct
  from a constructed empty container. Retire the old absent-handle/boxed-null
  distinction rather than carrying it into the tagged representation.
- Explicit mixed/dynamic container-family casts reject initially. Do not preserve
  the old shared-handle bridge under the new separated-family model. Recursive
  conversion is deferred; explicit scalar conversions preserve their scalar value.
  Known invalid crossings reject preparation, value-dependent ones require runtime
  checks without modifying the destination on failure.
- Continue deriving array-like/object-like shape from the internal hash query
  (`is_packed()` today; `is_pure_array` in discussion). Do not add persistent `[]`
  versus `{}` identity now. The user deferred that design to
  [CH15-SHAPE-01](../catalog/15_blocking_debt.md#ch15-shape-01--preserving-array-versus-object-identity).
- Callable values, source `->` support and reference foreach remain outside the
  current refactor. The 24-byte target and private shared-mechanics direction remain;
  concrete storage layout and integration need implementation proof.

## Shared implementation direction

Prefer independent public types backed by private reusable tagged-value machinery.
Neither type should derive publicly from the other or expose an unrestricted common
value that lets callers bypass family admission. A small policy-parameterized internal
storage implementation is a candidate; a virtual abstract base is not needed for
sharing algorithms and would introduce polymorphic layout/dispatch concerns.

Candidate common work: inline scalar storage, indirect string lifetime, exact scalar
access, scalar dispatch and diagnostic construction, and active-union lifetime code
where the family storage alternatives genuinely match. Keep existing scalar operators
as the owners of scalar semantics. Share code only after confirming equal behavior;
do not infer JS numeric/key/truthiness rules merely from the dynamic family name.

Family-owned work: allowed container alternatives, recursive entry type, COW detachment
versus shared mutation, container equality/identity, key admission and future property
access. No implicit mixed/dynamic conversion follows from shared implementation.

Important integration checks:

- Current mixed's union contains `dynamic_t<>` directly. Replacing that alias with
  a 24-byte tagged value while retaining this member would grow mixed's union and
  defeat its proposed 24-byte target. Resolve the obsolete cross-family alternative
  and compatibility requirements explicitly rather than mechanically changing the alias.
- `hash_t<mixed_t, mixed_t>` has a specialization. A dynamic-family container must
  not accidentally fall through to unsuitable generic hashing/storage. Reuse suitable
  table machinery with an explicit key/entry contract; public key types need not be
  identical to entry-value types.
- A policy name or template is not sufficient if internal storage still accepts
  arbitrary cross-family values through mutation, casts, native adapters or aliases.
- Current scalar-returning dynamic reads expose mixed facts. Preparation, access and
  iteration contracts must preserve the selected family's type after the redesign.
- Keep substantial implementation out of consumer headers where practical; explicit
  instantiation/out-of-line helpers may contain template recompilation costs. Measure
  with the existing PCH workflow; no compile-time improvement is claimed yet.

The shared table boundary is now selected and implemented below. The tagged value
must choose its private scalar lifetime and recursive container representation.
Runtime layout/lifetime, family rejection,
copy/alias semantics, JSON modes and compiler facts require separate focused proofs.

## Implementation sequence and current handoff

1. Mixed layout checkpoint: implemented as above; dynamic and its old bridge API
   are unchanged. No native compiler rebuild is claimed.
2. Shared table mechanics are implemented in the checkpoint below. New tagged
   dynamic's internal storage and construction/indexing/iteration adapters are
   implemented below. Migrate remaining consumers before switching the public alias.
   Keep scalar
   semantics in their existing owners; remove obsolete mixed/dynamic coupling as a
   coordinated change rather than leaving both representations partially active.
3. Migrate runtime consumers together: initialization, reads/writes/paths, iteration,
   casts, truthiness/probes/count/identity, formatting/debugging and JSON. The inventory
   also finds dynamic assumptions in tasks and MySQL adapters and typed JSON conversion;
   reconcile these consumers rather than claiming only compiler-local impact.
4. Update shared compiler capability/conversion/access facts and backend bindings;
   add scalar-dynamic, same-family nested data and rejected-crossing proofs. Keep
   old object-only/shared-hash expectations separate from new normative semantics.
5. Core family contracts are reconciled below. Reconcile JSON API registrations, prove focused
   runtime/generated-program behavior and record remaining debt. Native compiler
   validation remains an explicit requested checkpoint.

### Shared packed/associative table foundation — 2026-10-05

Extracted the existing mixed-table algorithm into private implementation machinery
`hash_detail::packed_table<Value>`. The existing `hash_t<mixed_t, mixed_t>` now uses
that engine through private inheritance, with no virtual dispatch or extra wrapper
storage. Its setters preserve their public hash-reference result; `_find_val` keeps
its out-of-line mixed API and delegates to the engine's copied/default read.

Packed-to-associative shape, normalized int/string keys, insertion order, tombstones,
rehashing, storage accounting and traversal remain one implementation. Values are
now typed independently of those keys. The new dynamic can reuse the engine with
dynamic-valued entries instead of boxing them into mixed or duplicating hash logic.
Internal key carriers still use mixed scalar keys; that does not admit mixed-valued
entries. This does not register new source-language hash/key capabilities.

Entry-view/iterator implementation types now belong to the engine. Obtain them
through the existing range/begin/end APIs, not by constructing them from private
storage-owner pointers. Internal references remain subject to the existing access
boundary; no new source reference capability or mutation-during-iteration guarantee
is introduced. This foundation does not implement the new dynamic representation,
scalar storage sharing, separated-family casts or JSON changes yet.

`test_packed_table_storage.cpp` instantiates the engine with an independent value
that cannot be converted to mixed. It checks packed shape, key normalization and
wrong-kind rejection, copied/default/checked reads, 600-entry growth, tombstone
iteration order, container copy/move, failed value-copy replacement and the existing
mixed wrapper. Static checks prove private implementation separation, no polymorphic
base and no additional wrapper size. Behavioral checks stay enabled under NDEBUG.

Evidence: `/tmp/my-try-packed-storage-20261005-3` passed debug/release runtime and
JSON builds plus eight native test programs (mixed layout, array semantics, hash
and independent-value storage). The JSON object is linked because the existing hash
suite uses JSON encoding in debug assertions. Initial probe corrections were test
accessor spelling and that missing JSON link, not production algorithm changes.
The final static assertions were recompiled and executed in both modes under
`/tmp/my-try-packed-storage-final-20261005` using the matching runtime objects.
`/tmp/my-try-packed-storage-calls-20261005-1` passed five PHP suites and 28 generated
container-call programs. `/tmp/my-try-packed-storage-iteration-20261005-1`
passed five PHP suites and 34 generated iteration programs. These are focused
proofs, not a native compiler rebuild.
Final review corrected the engine's unsupported-reference overload to keep its
mixed scalar key parameter independent of `Value`; a non-mixed-value test now
exercises that rejection. `/tmp/my-try-packed-storage-20261005-4` rebuilt both
matching runtimes/JSON objects and passed all eight native programs after this fix.

Future callable-valued entries are recorded as
[CH15-CALLABLE-01](../catalog/15_blocking_debt.md#ch15-callable-01--callable-values-in-mixeddynamic-entries).
Their representation is a design consideration, not part of this refactor's
implementation scope or permission to introduce callable execution early.

### Contract reconciliation before the representation switch — 2026-10-05

The top-level dynamic/data-initialization/array/index/probe contracts now record
the approved v0.2 separation. This supersedes the earlier mixed-only dynamic-entry
proposal; it does not retroactively change v0.1 or claim the runtime is migrated.
Dynamic initialization interfaces now name dynamic input values, and dynamic reads
and iteration retain dynamic results. Scalar/null explicit family conversions and
container rejection are consistent across construction and mutation. Scalar copies,
shared-container aliases, null versus empty construction and non-container iteration
are explicit. Keys remain independent of entry-value types.

At this documentation checkpoint implementation remained at shared-table storage. No public tagged dynamic type,
family rejection, new JSON default or compiler capability change is claimed by
this documentation checkpoint. The old boxing/crossing tests describe the old
implementation and must be replaced during migration, not preserved as new semantics.
JSON family-specific decoding is recorded as direction without inventing a mode
parameter or function name before its callable/API migration.

### Tagged dynamic storage foundation — 2026-10-05

The user approved a coordinated v0.2 breaking runtime change, without preserving
the old v0.1 mixed/dynamic behavior. Migrate JSON, typed JSON conversion, tasks,
database adapters and compiler facts together; do not install a legacy-family
conversion bridge. Regenerate/rebuild consumers and PCH after the public switch.

`scpp::detail::dynamic_value` now implements the target storage independently of
mixed: a variant of null, inline bool/int/float, an owned string wrapper and a shared
`packed_table<dynamic_value>`. The existing table engine supplies recursive storage,
key normalization and traversal; no second hash implementation was introduced.
Scalar operator semantics have not been duplicated into this storage type.

Copies preserve scalar values, copy the owned string wrapper and share container
identity. Moves leave null; self-copy/move is safe. String copying completes before
destination replacement, and payload moves are statically required to be nonthrowing.
Only the internal factory can create a container payload, so an absent shared handle
cannot create a second null state. Exact access rejects wrong kinds without mutation.
Mixed construction/assignment is deleted; no implicit cross-family storage admission
is exposed. Explicit checked scalar conversions belong to the later cast integration.

This is an internal staging type, not a second source-language family or a
compatibility mode. The public `dynamic_t` alias, obsolete mixed `dynamic_v` carrier
and existing consumers are still unchanged. The next coordinated switch must use
this implementation and remove that old relationship. Do not claim source-level
family separation, dynamic scalar support or the new JSON default yet.

The focused `test_dynamic_value.cpp` measures 24 bytes/alignment 8 on this target
and checks exact scalar access, string lifetimes, copy/move/self-assignment, null
versus empty container, parent/child aliases, variable replacement, child survival
after removal, same-family missing reads, growth and typed iteration. Compile-time
checks reject mixed and the old dynamic handle. It is included in the shared
debug/release runner and CMake runtime tests.

Evidence: `/tmp/my-try-tagged-dynamic-20261005-2` rebuilt matching debug/release
runtime and JSON objects and passed all ten programs, including the existing mixed,
array/hash and shared-engine checks. `/tmp/my-try-tagged-dynamic-sanitize-20261005-2`
passed the new value test with AddressSanitizer and UndefinedBehaviorSanitizer.
Leak detection was disabled for that run: the initial sanitizer attempt failed
because LeakSanitizer cannot run under this sandbox's ptrace environment, not from
a reported payload defect. No leak-check pass, migrated source-program behavior,
native compiler rebuild or completed public dynamic migration is claimed.

### Tagged container-operation integration — 2026-10-05

The tagged value now participates in the existing data-initializer, copied-read,
direct store/append, nested-write validation/commit and value-iteration helpers.
Construction reuses `populate_list`/`populate_keyed`, including normalized duplicate
rejection. Const table iteration now uses one `detail::const_entry_range<Table>`;
the existing `foreach_const_hash_range` spelling remains an alias to that adapter.
No dynamic-specific traversal algorithm or mixed-entry boxing was added.

Tests exercise nested alias preservation through factories and reads, scalar value
copies during iteration, same-family null reads, wrong-kind/null write and iteration
rejection, missing/null nested-child rejection without creation, empty construction,
normalized duplicates and unchanged assignment destinations on factory failure.
The typed store boundary rejects mixed and old dynamic-handle values at C++ compile time.

The public alias has **not** switched. These adapters target the new internal type
so they become the ordinary overloads when the public spelling selects it. Old-handle
overloads are interim unmigrated consumers, not compatibility promises; remove them
at the switch. Remaining work includes scalar operators/conversions, probes/removal,
JSON/adapters, mixed carrier removal and compiler facts. No source-level support or
new JSON default is claimed by this checkpoint.

Evidence: `/tmp/my-try-tagged-dynamic-operations-20261005-2` passed matching
debug/release runtime/JSON builds and ten native programs. The first attempt exposed
a missing brace in the new test, corrected before this passing run.
`/tmp/my-try-tagged-dynamic-iteration-20261005-1` passed five PHP suites (CLI fallback
after sandbox FPM socket denial) and 34 generated iteration programs. Those generated
programs prove the const-range refactor preserves existing paths, not that the new
tagged type is emitted by the compiler. No native compiler rebuild was performed.

### Tagged scalar-family conversions and probe integration — 2026-10-05

The existing cast owner now provides explicit mixed/tagged-dynamic conversions
for null/bool/int/float/string only, preserving kind and value. Container payloads
reject in both directions before assignment replaces its destination. This is not
implicit boxing or recursive container conversion; the deleted mixed constructor
and typed entry boundary remain intact.

The existing truthiness, empty, isset, count and keyed-path owners now accept the
tagged value. Scalar rules delegate to their existing wrappers: string `"0"` is
falsey but not empty. Constructed dynamic containers are present in conditions,
including empty ones; emptiness inspects cardinality. Count rejects non-containers.
Typed-container isset now treats tagged null elements as absent, like mixed null.
Keyed probes treat missing/null/non-container paths as absent; removal rejects
wrong-kind parents, does not create storage and retains the existing lazy traversal
protocol. No new eager key evaluation or reference capability is introduced.

This remains internal migration support, not a switched public dynamic alias.
Arithmetic/comparison/text operators, required native typed extraction, JSON and
remaining adapters, old mixed-carrier removal and compiler registration still need
integration. No new public source support or complete migration is claimed.

Evidence: `/tmp/my-try-tagged-dynamic-probes-20261005-2` passed matching
debug/release runtime/JSON builds and ten native programs. The initial test link
failed because the test omitted the cast-definition header; adding its direct include
resolved that harness issue. `/tmp/my-try-tagged-dynamic-keyed-20261005-1` passed five
PHP suites via CLI fallback and 44 generated keyed-path programs. Generated checks
exercise existing public paths; the native tagged-value test proves the new internal
overloads. No native compiler rebuild or public-type switch is claimed.

### Tagged scalar extraction and basic arithmetic — 2026-10-05

Explicit tagged-to-bool/int/float/string casts dispatch to the existing primitive
cast rules; nullable extraction preserves absence. Required scalar boundaries reject
null, and required int requires an int payload rather than applying the explicit
float/string conversion rule. Container extraction into scalars rejects. No new
numeric parsing or implicit family conversion was introduced.

The arithmetic owner now provides tagged/tagged `+`, `-`, `*`, `/`, integer `%`
and unary `+`/`-`. A family-parameterized numeric dispatcher selects int/float payloads
and calls primitive wrapper operators, retaining their result type and zero-divisor
checks. Results are tagged dynamic values; no mixed temporary carries arithmetic.
The new owner is included by the runtime umbrella, not generated-operator edits.
Bool/string/null/container arithmetic rejects. This does not add bitwise operators,
compound assignments/increments, comparisons, implicit native operand boxing or a
new overflow policy; inherited primitive arithmetic/cast limitations are unchanged.

The first runtime build crashed Clang 18 during a redundant requires-expression
inside the generic lambda. Removing that unnecessary check simplified the dispatcher;
no compiler installation or generated output workaround was needed. The public alias,
JSON/adapter migration and compiler registrations still remain pending.

Evidence: `/tmp/my-try-tagged-dynamic-numeric-20261005-2` passed matching debug/release
runtime/JSON builds and ten native programs. The wider mixed runner under
`/tmp/my-try-tagged-dynamic-numeric-mixed-20261005-1` passed 14 of 15 PHP suites, but
stopped before native fixtures: `tests/mixed.php` still expects preparation rejection
for `isset($mixed[0])`, superseded by Chapter 06 keyed-probe support. No PHP implementation
changed in this slice. That stale assertion remains test debt; the full mixed runner
is not reported as passing. Exported numeric/cast fixtures were selected separately
for focused native regression verification.
All 46 selected programs passed under
`/tmp/my-try-tagged-dynamic-numeric-generated-20261005-1`. This is generated-program
verification, not a native compiler rebuild or a public tagged-dynamic switch.

### Tagged numeric ordering, strict identity and updates — 2026-10-05

Numeric `<`, `<=`, `>` and `>=` reuse the numeric dispatcher with a bool result;
primitive comparisons retain widening and NaN behavior. Nonnumeric ordering rejects.
Strict identity compares runtime kinds before scalar values and compares constructed
containers by shared target identity, not recursive contents. Null sentinels and
nullable delegation use the existing identity owner. Loose `==`/`!=` and implicit
native-operand boxing are not implemented by this checkpoint.

Numeric `+=`, `-=`, `*=`, `/=`, `%=` compute through the existing binary operators,
then one write-back helper requires the original stored numeric kind. Failed
computation or kind mismatch does not replace the lhs. Prefix/postfix increment and
decrement use that same path; postfix returns the previous value. Ordinary assignment
can still replace an int payload with a computed float, unlike compound assignment.
Bitwise operations, text/concat updates, indexed source updates and mutation-during-
iteration policy remain separate work.

The first native test build exposed an existing identity overload issue: the generic
same-type overload admitted null sentinels and tried native `null_t == null_t` during
nullable delegation. It now excludes sentinels (and the tagged type), leaving their
dedicated identity owners selected. Tests cover direct sentinel pairs, absent and
present-null nullable values, strict scalar kinds, NaN, container alias identity,
numeric ordering, preserved values on failures and prefix/postfix results.

Evidence: `/tmp/my-try-tagged-dynamic-updates-20261005-2` passed both matching
runtime/JSON builds and ten debug/release programs after the sentinel-overload fix.
`/tmp/my-try-tagged-dynamic-updates-generated-20261005-1` passed 40 exported mixed
comparison/update programs selected through the shared runner. This preserves the
earlier wider mixed-suite stale-assertion disclosure; it does not claim that suite
passed, that the compiler was rebuilt natively or that tagged dynamic is emitted yet.

### Shared scalar equality and tagged text updates — 2026-10-05

Scalar loose equality moved out of mixed's implementation into one family-parameterized
operator owner. Mixed and tagged dynamic now use that same algorithm for scalar pairs;
no mixed temporary or second numeric-string parser is used for dynamic comparisons.
The established null, bool/string truthiness, numeric-string parsing and string/string
rules are preserved. Mixed's remaining container/weak handling stays with mixed.
Tagged dynamic containers compare by shared target identity; container/scalar equality
is false, not truthiness coercion or recursive content comparison. Inequality negates
the same equality result.

Text context continues through the explicit scalar string cast. The concat-assignment
owner accepts tagged lhs plus an already-converted string RHS: convert/copy the lhs,
append, then publish the tagged string result. Failure preserves the destination;
an RHS referring to its old string remains valid until the completed replacement.
There is no dynamic string overload for arithmetic `+`.

Tests include independent expected equality cases and a mixed/tagged scalar-pair
parity matrix, container aliases versus separate empty containers, null/scalar
text updates, self-referential string RHS and rejected container text conversion.
The public alias, JSON and external consumer migration remain pending. Earlier
generated fixtures still exercise the old public types; they are regression checks
for the shared helper extraction, not evidence of a completed type switch.

Evidence: `/tmp/my-try-tagged-dynamic-equality-20261005-1` passed matching debug/release
runtime/JSON builds and ten native programs. A final rerun after include cleanup,
`/tmp/my-try-tagged-dynamic-equality-20261005-2`, passed the same checks.
`/tmp/my-try-tagged-dynamic-equality-generated-20261005-1` passed 52 exported mixed
comparison/text programs through the shared runner. The previously recorded wider
mixed-suite stale assertion remains unresolved; this is neither a full-suite pass
nor a native compiler rebuild.

### Tagged debug inspection — 2026-10-05

`dbg` and `var_dump` now inspect the internal tagged value directly, without a mixed
carrier or cross-family conversion. Scalar rendering is shared with mixed in each
existing formatter; table traversal is parameterized over the table implementation.
Dynamic containers retain their debug identity and use the existing seen-address
guards. This keeps repeated aliases/cycles bounded without inventing a new display
or ownership policy. `dbg` keeps its existing depth, item and string limits.

Boxed debug overloads are declared before recursive container/wrapper templates, so
nested mixed and tagged dynamic values select their handlers instead of the generic
not-inspectable fallback. Tests cover scalar formatting, nested values, typed
containers, wrappers, shared aliases, cycles, depth-zero output and unchanged shared
storage after inspection. The temporary test cycle is removed before assertions.

The public alias, JSON/API selection, remaining consumer migration and compiler
facts are still pending. No source-level type switch or native compiler rebuild is
claimed by this formatting checkpoint.

Evidence: `/tmp/my-try-tagged-dynamic-debug-20261005-2` passed matching debug/release
runtime/JSON builds and ten native programs. The first run compiled both runtimes
but exposed a test-only API typo (`erase` instead of the table's `remove`); the final
run includes that correction. No broader compiler regression suite was run.

### Family-specific JSON runtime — 2026-10-05

The user approved the names recorded above. The JSON module now has one
family-parameterized parser: `decode` constructs tagged dynamic directly, including
scalar roots, and `decode_mixed` constructs owned mixed trees. No old dynamic boxes
are produced by either decoder. Their copies follow the selected family's storage
contract. Parsing, key normalization and checked malformed-input handling remain
shared. This slice does not change duplicate-key parsing policy.

The writer shares scalar formatting and table traversal, directly accepts either
family and rejects active-path cycles as checked encoding errors. Noncyclic repeated
aliases are valid. The dynamic decoder uses a new out-of-line `decode_dynamic` symbol
behind `decode`, avoiding accidental linkage to the old return ABI. PHP runtime
adapters expose the same names. Existing typed `from_json(text)` now explicitly
selects mixed decoding and required result extraction; tagged input conversion and
the old dynamic target of that helper remain part of the coordinated consumer switch.

This is a breaking runtime checkpoint, not completed source integration. The public
`dynamic_t<>` alias, strict/legacy symbol registries, shallow builtin return contracts
and JSS `json.decode` return facts must switch together. Existing generated code and
old callers expecting mixed from `decode` require regeneration/adaptation; no old
decoder compatibility layer is retained. Tasks/MySQL consumers and broader typed
JSON adapters still require migration review. No native compiler build is claimed.

The focused runner includes JSON checks active under both debug and NDEBUG.
`/tmp/my-try-json-families-20261005-3` passed matching debug/release runtime and JSON
builds and all twelve native programs, including typed extraction from JSON text.
The first run exposed namespace shadowing in the PHP adapter; explicitly qualifying
`::scpp::detail` repaired it. No PHP compiler suite, generated-source integration
suite or native compiler rebuild was run for this checkpoint.

### Family-aware typed JSON extraction — 2026-10-05

`from_json` now parameterizes its existing scalar/nullable/vector/hash extraction
over the input family. `json_value` names the default tagged dynamic value. The old
mixed `dynamic_if` handle bridge is removed; the old public hash-handle alias is no
longer an admitted target of this helper. Same-family extraction preserves the
existing mixed clone or dynamic shared-copy behavior. Scalar/null crossings reuse
the explicit cast owner; container crossings report a path-qualified conversion
error, including at nested typed-container boundaries.

The text convenience now uses default dynamic decoding. Callers needing mixed
containers explicitly decode mixed before typed extraction. No automatic target-
selected decoder or recursive family conversion was introduced. Tests exercise
both input families, nested typed rows, same-family copy/identity, numeric widening,
nullable absence, nested crossing rejection and unchanged destination on failure.

Remaining coordinated work: public dynamic alias/old mixed-carrier removal,
`to_json` typed construction, tasks/MySQL/native consumers, compiler registrations
and prepared family facts. This checkpoint does not claim those are migrated.

Evidence: `/tmp/my-try-json-extraction-20261005-1` passed matching debug/release
runtime/JSON builds and all twelve native programs. JSON checks remain active under
NDEBUG. No source integration suite or native compiler rebuild was run.

### Direct typed JSON writing; tree helper removed — 2026-10-05

The user approved removing `to_json` or making it string-producing. The selected
option removes its header/API and records an optional checked string alias as
CH15-JSON-01. `json_encode` remains the string-producing public concept.

One writer now owns scalar output, escaping, checked errors and active-path cycle
tracking. Typed nullable/vector/hash traversal calls that writer directly; boxed
mixed/dynamic dispatch uses the same scalar methods and table traversal. No temporary
mixed trees or cross-family boxing are involved. Scalar/boxed formatting remains
out of line; only type-dependent traversal/admission is in the writer header.

Typed hashes now honor the existing JSON-object contract for empty/sequential-int
keys as well. Vectors remain arrays, while mixed/dynamic containers still use their
hash-derived shape. Tests include nested typed containers with nullable dynamic
entries, preserved shared identity, repeated aliases, cycle failure/recovery and
typed hash/vector shape. Public dynamic migration and registrations remain pending;
this is not a native compiler proof or a measured performance claim.

Evidence: `/tmp/my-try-json-direct-writer-20261005-2` passed matching debug/release
runtime/JSON builds and all twelve native programs, including the final direct-
writer tests. Repository code search found no remaining references to the removed
header/API. The deleted helper remains recoverable from Git history.

### Tagged MySQL row consumers — 2026-10-05

The MySQL result interface and mysqli wrapper now return the internal tagged dynamic
value. Both ordinary and prepared row builders create same-family containers and
scalar/null cells directly. The old mixed row plus `to_dynamic` conversion is removed
from this module. Existing text-to-scalar parsing, status reporting and null-on-
exhaustion policy are unchanged; no query/binding semantics were redesigned.

The focused `mysql_rows.py` runner builds matching runtime/JSON/wrapper objects and
both Connector/C-enabled and disabled modules for debug and release. Offline stub
result proofs check packed/associative rows, exhaustion/absent handles, shared row
copies, independent fetched rows and binding replacement. Checks remain active under
NDEBUG. Connector-enabled compilation covers the real ordinary/prepared builders,
but does not prove their interaction with a live database.

Evidence: `/tmp/my-try-mysql-rows-20261005-1` passed all four programs with the installed
Connector/C package. No database was contacted and no native compiler was rebuilt.
Tasks consumers, old public dynamic alias/mixed carrier, compiler facts and source
registrations remain pending; this is a breaking runtime-consumer checkpoint.
