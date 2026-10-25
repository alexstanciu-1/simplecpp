# Concrete C++ specialization
Doc Status: supporting

The default `prelower_templates` path uses the existing AST, registry identities and
prepared operation decisions. It adds no semantic pass or second type registry.
Ordinary C++ is an explicit opt-out. There is one specialization engine configuration;
handwritten family recipes and the former runtime-ABI emission mode have been removed.

## Selection and strict boundary

CLI, `Compiler` and standalone `CPP_Generator` default to the same general concrete
path through `cpp_lowering_options`. CLI `--prelower-templates` remains an explicit
selection; `--no-prelower-templates` selects ordinary compilation (the last mode flag
wins). API callers can set `$compiler->cpp_lowering->prelower_templates = false`,
or pass `new cpp_lowering_options(false)` to `Compiler` or as the generator's options.
Unsupported concrete operations fail; ordinary mode is never selected automatically.
The temporary `runtime_template_abi` and
`concrete_type_substitution` option fields no longer exist. The retained C++ program
stores only the effective `prelower_templates` setting. Switching it clears affected
C++ fragments without changing semantic preparation; unchanged mode replays demand.

```sh
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY --output ABSOLUTE_OUTPUT
php compiler/my-try/tools/build_cpp.php ABSOLUTE_OUTPUT ABSOLUTE_CACHE
# Explicit ordinary compilation, with optional runtime PCH:
php compiler/my-try/main.php --s2s SOURCE_DIRECTORY --output ABSOLUTE_OUTPUT --no-prelower-templates
php compiler/my-try/tools/build_cpp.php ABSOLUTE_OUTPUT ABSOLUTE_CACHE --pch
```

The coordinator publishes `application_templates: "forbidden"` for this mode.
Known unsupported representations/operations fail at their existing backend owners.
Native compilation rejects runtime PCH and active template tokens in all application
units and their transitive headers. Validated object reuse follows fresh dependency
identity through the existing native cache. Neither failure switches to ordinary
compilation. See [native enforcement](native_cpp_build.md#dependencies-and-pch).
Source publication readiness and successful native readiness are distinct.

Runtime and middleware implementations still use templates. Only application inputs
must be template-free. Metadata registration alone is not a support claim; consult
the [representation/operation coverage matrix](../../../../specs/planning/runtime_template_capability_matrix_2026_10_19.md#current-coverage-contract--2026-10-20).
Remaining concrete-operation limits are tracked in the coverage matrix; ordinary
mode retains the same source semantics.

Object tests and checked casts consume `prepared_object_type` through a compiled
runtime operation. The key includes operation, source type and target payload
(including the target of boolean tests). The bridge borrows its operand in the
caller's full expression, preserves shared identity, and retains existing runtime
failure behavior. Semantic admission is unchanged; no nullable-wrapper widening.

Source nominal handle facades expose a concrete identity-key interface. Their
`hash_key_hash`/`hash_key_equal` implementations delegate to native shared-pointer
key operations; `hash_t` composes these facades without changing key ownership or
identity. The marker and methods contain no application templates. Registered
static factories use the owner's composed payload arguments unless the binding
explicitly requires native payloads. Thus empty hash construction returns the same
physical representation used for stored hashes. See [object hashes](../../../../specs/portability/object_hashes.md)
for cross-route proofs and the portable carrier's restrictions.

Source exception hierarchies, throw/catch and value/loop-transfer finally use
compiled exception operations. The runtime payload base exposes a template-free
inheritance interface; typed extraction and shared ownership stay in middleware.
Finally uses the unified layout-probed storage for pending exceptions and values,
including non-reference values, loop transfers and async sequencing.
Inherited zero-argument runtime methods on `$this` borrow the original payload
through a compiled member bridge without manufacturing a shared owner. Root
message behavior and the bounded source contract are defined in
[exceptions](../../../../specs/exceptions_v0_2.md).

Enum name/value/reverse-value calls reuse settled `prepared_enum_call` facts and
ordinary ordered argument snapshots through the existing compiled-operation bridge.
One helper body serves both ordinary and compiled implementations; application units
see only concrete signatures. Plain enum-wrapper `isset` selects value presence
for nullable/structured/falseable Results, or sentinel-aware presence for boolean
Results. The latter invokes the existing runtime isset authority; it does not
replace payload availability used by extraction or coalescing. Both bridges borrow
once for the full expression without enabling payload truthiness. See [bounded proof and limits](../planning/enum_helper_transport.md).

Prepared `take`, nullable/Result construction and required extraction, and wrapper
usable-value coalescing use the existing compiled operation bridge. Provider
wrappers adapt their payload representation at the compiled boundary, preserving
null, false, true and error states. Concrete runtime member results use that same
owned-result transport. Provider-owned nominal handles do not expose their native
pointee headers to application units; source-owned pointee interfaces are retained.
Shared runtime instance calls retain the selected handle and invoke a check-only
`require_receiver` overload before argument evaluation. The opaque representation
owns this bridge; it delegates to the native handle's checked arrow operation and
exposes no payload pointer. Selected calls keep their existing ordered snapshots,
and concrete member adapters forward the prepared arguments. JSON reader and an
unrelated noncopyable ticket provider have ordinary/concrete execution proofs;
reference results, variadic transport and borrowed `$this` arguments retain their
existing exclusions. No source semantic or retained preparation protocol is added.
Error and mixed storage select concrete boundaries through their core metadata
recipes. See the [tokenizer/IO verification report](../planning/tokenizer_io.md)
for the bounded proof scope and remaining acceptance work.

Shared provider instance properties now use compiled read, target-check and plain
write operations for int, string and vector<string> values. The target handle is
retained and checked before RHS evaluation; vector fields copy between native and
concrete element representations. Reading a vector field for indexing uses a value
snapshot; indexed writes, updates, compounds, reference escape, static properties
and wider field types remain outside this transport slice. Prepared mixed-to-int
and mixed-to-string extraction uses the existing required-cast implementation in
the compiled conversion bridge. Prepared scalar/null wrapping into mixed or dynamic uses that
same bridge. Boxed null-index reads adapt the receiver and key and return an owned
result in the same family, preserving once-only ordered evaluation and existing key semantics.
Boxed initializer buffers store native boxed elements; generic containers retain
substituted concrete elements. Direct boxed writes unwrap their key/value operands.
Nested boxed store/append stays inside one compiled helper: source keys and RHS are
already snapshotted, then the existing path owner validates the entire path before
mutable descent and the final write. Native interior references never cross into
application wrapper references. This preserves mixed copy-on-write and dynamic
shared identity without changing runtime algorithms.
Concrete registered provider value/read-only vector<string> parameters copy to
native elements; inferred generic contracts keep substituted physical types.
Prepared explicit runtime casts use the existing recursive provider transport to
unwrap composed payloads before calling the native cast. Presence, sentinel and
error behavior remain owned by the runtime cast. Native-payload split bindings use
the same bridge for returned vector elements. `tests/native_value_transport.py`
proves ordinary/concrete execution, carrier states and evaluation order.
Mutable provider references reject pending explicit write-back transport. Boxed extraction beyond the existing int/string slice, indexed updates/compounds,
and broader keyed probes remain separate. See [curl integration](../planning/curl_integration.md).

## Existing owners

Source-class self retention uses prepared `shared_self` acquisition and construction
facts. `CPP_Self` requests an internal layout-probed observer through the existing
specialization engine; application classes share its template-free virtual base.
Native observer storage contains a weak owner without a separate heap allocation.
Both constructor transport and outlined allocation bind after successful source
construction. Acquisition borrows the raw source payload and returns the usual
owned handle facade. It does not interpret `this` as handle storage. v0.1's
`shared_self` implementation and generic shared-pointer transport remain unchanged.
Broader native adoption bypassing source construction is not integrated by this slice.
See [the source contract](../../../../specs/instance_methods_v0_2.md#shared-class-self-values--2026-10-23)
and `tests/shared_self.py` for inherited lifetime and retained-recovery proofs.

`CPP_Callback_Transport` provides owned callable storage for fixed value parameter
lists, including zero inputs and void results. Provider adapters own their callback
copy, allowing it to survive the compiled helper's return. This is a transport
contract: preparation must still authorize captures and execution context before
background use. Reference inputs/results, variadics and borrowed erased captures
remain restricted. The [async/tasks prerequisite](../planning/async_tasks.md) records
the default-prelowered proofs and the still-open task integration.

Free runtime bindings can explicitly request PHS file/line context. `CPP_Callsites`
reads the current token snapshot (including retained-range translation); ordinary
calls and the existing compiled-operation bridge prefix the same native arguments.
Location enters operation identity. Only fragments consuming source context retain
a source digest, so whitespace edits refresh their locations without inventing
semantic signature changes. Core scalar `dbg` uses this path; native runtime
inspection stays behind the compiled boundary. See the
[S08/S05 report](../planning/scalar_debug_closure_reconciliation.md).

| Responsibility | Owner and contract |
|---|---|
| Source identities, storable/copy requirements, ownership and source capabilities | Existing registry and completed preparation; backend reads settled facts |
| Runtime provider variation | Existing `cpp_types` recipes and callable/initialization/indexing/iteration bindings; no family registry in the engine |
| Concrete physical selection | `CPP_Types`, `CPP_Type_Bindings`, `CPP_Opaque_Representation`; stored and payload argument roles remain distinct |
| Demand and artifacts | `CPP_Specializations` and retained fragment requests; only demanded types/operations and their dependency closure |
| Entry/runtime error boundary | `CPP_Entry_Support`; shared CLI initialization and runtime exception reporting in middleware |
| Source fields, signatures, members and sequencing | Ordinary C++ emitters; no duplicate source-implementation partition |
| Initialization | Existing prepared factory/entry conversions, `CPP_Data_Construction`, `CPP_Initializer_Buffer` |
| Operations | `CPP_Runtime_Operations`, concrete member/index/iteration/mutation adapters; native renderers/runtime algorithms remain authoritative |
| Constructor transport | `CPP_Source_Calls` reuses selected call boundaries and native argument rendering; ordinary source methods use ordinary emission |
| Layout, publication and reuse | Existing Clang probes, `Output_Publication` and native builder/cache; no new persistent engine/cache |

Native outer implementations contain concrete stored arguments where selected by the
recipe. Opaque storage owns the actual native object; it does not introduce a second
payload or mandatory heap box. Borrowed transport designates existing objects and owned
results construct in final storage. Source evaluation order, temporary lifetime and
cleanup remain with existing emitters. The scalar unary rendering used by the general
path stays in the ordinary operation emitter; it is not a container-family recipe.

`CPP_Specializations` distributes implementation definitions across up to 256
`specializations/containers_bXX.cpp` units. The first two hexadecimal digits of
SHA-256 over the canonical specialization name select the bucket; entries within
each bucket retain name order. Each definition has one owner, and additional demand
does not move existing definitions. Headers and topological layout probes retain
their existing ownership. The manifest lists only occupied buckets; ordinary
publication retires obsolete buckets and the former `containers.cpp`. This is a
fixed partition policy, not a hard byte-size limit. It increases translation-unit
count and repeated header parsing in exchange for parallel build opportunities and
smaller invalidation groups. Ordinary output is unaffected.

Runtime operation prototypes request `stored_value` completeness for their result.
Concrete handle storage is complete, while its class pointee need not be: including
that class before declaring a null-conversion bridge creates a cycle when a field
initializer calls the same bridge. Native operation bodies retain their complete
dependencies. The runtime-type binding suite compiles conversion headers in isolation
and checks partition stability, warm publication and obsolete-file cleanup.

Changed fragments render in source batches using `Compiler::$jobs`. Each batch has
a private `cpp_program` and specialization engine; prepared semantic facts are
read-only. Before rendering, the coordinator installs C++ initializer adapters
from the settled semantic binding catalog. After all tasks join, it publishes fragment replacements
and merges specialization entries by canonical name, never worker-local entry ID.
Artifact assembly, layout ordering and filesystem publication remain serial.
Failed tasks do not publish partial fragment replacements. Warm fragments still
replay their canonical demands through the existing specialization path.

Oversized source implementation groups split at whole-definition boundaries using
stable callable/record identity digest prefixes, with a 180 KB target including
preambles. Small sources retain their original path. Type-owned static storage
remains in the original source unit in source order; neither a function nor ordered
storage is split textually. A single oversized definition remains an explicit size
review item. Native compile costs and duplicated includes still require measurement.
Layout probes split at complete layout requests within each dependency depth,
also targeting 180 KB. Manifest order remains depth-first, then part order, so
existing consumers resolve all prerequisite macros before later levels. Adding a
request may rebalance subsequent parts within its depth; this adds no persistent
assignment registry or parallel native layout scheduler.

Layout probes determine native size/alignment and special-member traits. Source copy
permission still comes from prepared capabilities. Dependent layouts follow the existing
generated-header graph, with explicit rejection of unsupported cycles. A layout match
alone does not authorize byte copying or arbitrary provider interoperability.
See the [runtime physical contract](../../../../runtime/specs/concrete_type_interoperability.md).

Source method prototypes use concrete forward-declaration headers where a name is
sufficient, including methods accepting or returning their own `value<T>`. Stored
fields still require complete concrete storage. Modifier pointer interfaces use the
runtime's `pointee_modified_type` binding and retain by-value const access; ordinary
member emission and prepared empty-owner conversions remain with their existing owners.

Low-level `request_opaque`/`request_observation` helpers remain for direct storage and
observation proofs of the shared representation worker. They are not a selectable
source-lowering mode. Public requests always use concrete argument substitution.

## Generated naming

`CPP_Artifacts::bounded_component` supplies concrete type labels: up to 48 readable
bytes plus the full SHA-256 of semantic identity and physical spelling. With the
`concrete_` prefix, identifiers are at most 124 bytes; nesting does not expand the
public filename. `CPP_Runtime_Operations::name` uses the operation kind and a full
SHA-256 of its selected contract/types/binding. The engine sorts emitted entries by
name, so discovery order and compact entry IDs do not determine artifact bytes.

The readable prefix is a debugging aid, not identity. Distinct deep types may have
the same prefix. Generated implementation aliases and bridge definitions show their
physical targets; no additional debug-name registry is retained. Hash collisions
have no separate detection/disambiguation mechanism; these names rely on SHA-256.
Do not describe this as a proven collision-free encoding.

The inventory proof exercises ten nested template levels with different leaves,
shared readable prefixes, distinct type/operation identities and bounded filenames.
It tests naming only, not execution of every deep operation. Source callable/type/unit
paths and low-level proof-only `opaque_` names still use reversible hex encoding and
can grow with the original name. General path-limit handling and richer debug name
maps remain non-blocking debt; this review changes no output spelling or cache keys.

## Proofs and remaining work

The agreed supported path is solidified by the [fresh combined O0 sign-off](../../../../specs/planning/results/prelower_solidified_acceptance_2026_10_20/README.md)
at `da17e1ba`: all eight phases pass, accounting is identical (998/0 in each mode),
and the complete 16-unit application closure is template-free. This status retains
the explicit unsupported-operation boundary and deferred broader runtime coverage.

Run the combined current acceptance with
`python3 compiler/my-try/tests/prelower_acceptance.py --results FRESH_DIRECTORY`.
It composes the existing O0 proof owners and retains per-phase evidence; failures
cannot be hidden by a later successful accounting run.

- [Native compiler execution](../../../../specs/planning/results/native_prelower_accounting_2026_10_20/README.md):
  O2 compiler executable with O0 downstream builds; PHP/native artifact parity,
  repeated concrete emission, strict rejection/recovery, accounting and full closure.
  The existing validator's `--prelower-accounting` gate owns reproduction.
- [Fresh public accounting acceptance](../../../../specs/planning/results/prelower_public_accounting_2026_10_20/README.md): ordinary/prelowered 998/0, identical output, complete 16-unit no-PCH closure and warm reuse.
- `tests/prelower_null_output.py`: public CLI/API parity, single-option lifecycle/replay, strict rejection/repair, PCH rejection and complete fixture closure.
- `tests/prelower_contracts.py`: explicit unsupported-boundary failure/recovery and exact audit command coverage.
- `tests/prelower_header_boundaries.py`: O0 output/indexing/compound behavior, reference identity, order and matching allocation/copy counters.
- `tests/prelower_layout.py --o0-only`: shared low-level observation/storage layout and cache proof, including failure/recovery and dependency edits. The default extended run also includes optimization/sanitizers.
- `tests/prelower_source_lifecycle.py`: ordinary/concrete source methods and constructors,
  receiver retention, ordered/default/reference arguments, owned results, copy-failure
  cleanup, ownership identity, strict rejection/recovery and method/layout edits at O0.

Current reproduction and proof limits are in [testing](../testing.md). Comparison-only
recipe/runtime-ABI harnesses are preserved as non-executable historical source in
[the proof archive](../../tests/historical/prelowering/README.md), with their migrated
behavior mapped to current fixtures. They do not justify keeping obsolete production branches.

The [solidification TODO](../../../../specs/planning/prelower_templates_solidification_todo_2026_10_20.md)
tracks consolidated acceptance and remaining contracts. Full runtime coverage,
wrapper removal, new optimization and ThinLTO remain deferred; see the
[tentative v0.3 runtime review](../../../../specs/planning/runtime_prepared_facts_review_v0_3_2026_10_20.md).
The [pre-cleanup history](cpp_template_prelowering_history_2026_10_20.md) retains older
implementation checkpoints and evidence links without defining current configuration.

## Async implementation placement

Async callable signatures expose concrete move-only task storage. The registry
owns an internal task identity parameterized by the canonical payload; shared
preparation retains the body payload return and settled suspension facts. The C++
opaque representation uses recursive payload identity for stable artifact names.

The existing implementation-group assembler places async bodies and concrete
entry bridges in per-source `specializations/async/` units. Coroutine headers stay
behind that compiled boundary; application units keep the template prohibition.
This is a placement policy over existing source work, not another semantic owner.
Normal fragment retirement/republication also governs async groups.

`CPP_Sequence` consumes prepared expression suspension facts. Expressions that
suspend use an immediately awaited, owned coroutine scope so ordered snapshots
survive suspension. Lazy branches stay within that scope and remain lazy. The
runtime `invoke_owned` helper owns its callable until completion. Other expressions
retain ordinary scopes. See the [async checkpoint](../planning/async_tasks.md) for
proof boundaries and deferred contracts.

Shared runtime handles also use the existing concrete-member owner for
zero-argument instance methods. The compiled method dereferences the retained
native handle; application code sees only concrete storage. Methods with arguments
remain rejected until receiver checks can precede argument snapshots through that
boundary. Provider property observation additionally transports bool and mixed
values; their mutable property transport is not added by the tasks slice.

When tasks are selected, entry support drains runtime-retained background batches
before returning the application's exit status. Exceptional exit also drains work
and preserves the original application failure. Ordinary tasks programs reuse the
same entry-support implementation. Retention and join bookkeeping belong to the
tasks provider; the compiler does not maintain a second batch registry.

Source `throw`/`catch` now use canonical Exception operations through the compiled
runtime-operation bridge. Matching preserves the shared object; runtime guard and
unmatched exceptions propagate. `CPP_Finally` publishes inline saved-exit storage
through the existing specialization/layout owners. Protected async bodies use
owned coroutine invocation; async handler bodies run after native catch matching
so they can suspend. Return and loop exits resume after cleanup. See
the [V-D06 evidence](../planning/finally_value_transfers.md).
