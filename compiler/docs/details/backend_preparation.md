# LLVM backend preparation
Doc Status: supporting

Status: the PHP pipeline now prepares real callable bindings after lifetime
analysis. [Instruction lowering](lowering.md) consumes these bindings. [Native builds](native_executable.md) now add a separately prepared entry
adapter, file-module emission and optional object/link/publication stages.

Backend preparation lives in `05_generate_code/prepare_backend/`, with separate
contract files, selected workers/joins and `tools/toolchain.php`. Body and
native-entry plans remain in `lower/`. Both compiler tools and runtime preparation
use the independent `tool_process/` service. See the [ownership review](backend_organization_review.md)
for the boundary rationale and [call map](../../src/05_generate_code/prepare_backend/calls.md)
for navigation.

## Policy and verified target

[`LLVM_Backend`](../../src/05_generate_code/prepare_backend/main_prepare_backend.php) owns the initial policy:
one LLVM module per source file, `external` project-function linkage, explicit `ccc` on definitions
and calls, direct scalar parameters, explicit plain-record reference parameters and scalar/void returns. Logical
symbol IDs produce collision-free project link names, independent of source
spelling and file order. They are stable within the resident session, not a
persistent-cache identity guarantee. The language entry uses this same path.

LLVM defines [external linkage](https://llvm.org/docs/LangRef.html#linkage-types)
for resolving references across objects and requires matching caller/callee
[calling conventions](https://llvm.org/docs/LangRef.html#calling-conventions).
This policy does not claim C source-level interoperability for arbitrary language
types. Foreign/runtime calls and aggregates need their own prepared contracts. The native `main` adapter has a separately prepared plan and is emitted in the
manifest entry file's module. Ordinary functions use the same prepared contract
whether called from their own file or another file.

[`tools/backend.json`](../../tools/backend.json) selects the Clang executable and
an optional target triple. `compile_jobs` is an optional positive integer
controlling external object-job concurrency (the repository sets 20); it does
not change backend meaning or invalidate accepted objects. A bare executable name is resolved through `PATH`;
a relative path containing `/` is relative to this configuration file. A null
target requests Clang's default. There is no fallback if that executable or target
fails. This is the target backend toolchain, separate from the custom Simple C++
toolchain used for the eventual compiler port.

`linker` separately selects a linker executable using the same path rules. The
repository selects `mold`; omission or null asks Clang for its default linker.
An explicit missing or failing linker is an error, never a silent fallback.
`LLVM_Toolchain::link_configuration()` prepares a shared `link_configuration`
from the target configuration and resolved executable identity/stat facts.
`Native_Artifact` retains that contract; a link-only change relinks retained
objects without invalidating backend bodies, LLVM modules or object generation.
The native builder passes fixed object paths and this contract to the tool
service, which checks the linker again before invocation. Runtime libraries and
transitive tool dependencies still require a session restart after upgrades.

[`LLVM_Toolchain`](../../src/05_generate_code/prepare_backend/tools/toolchain.php) reads Clang's version
and compiles a small isolated C probe to IR. Triple, layout, CPU and features come
from that output. It rejects missing or ambiguous facts. For each required scalar signature (return and ordered parameter spellings)
it compiles an isolated caller/callee probe to an object, forwarding the incoming
arguments through the prepared calling convention. These are toolchain capability probes, never the user's program; they
are not run or linked. Temporary output is removed on success and failure.
Probes use argument arrays, separate output streams and a bounded process wait.

[`LLVM_Types`](../../src/05_generate_code/prepare_backend/utilities/llvm_types.php) owns primitive LLVM type
spellings derived from representation kind/width/format, not language names.
Preparation probes and emission share this translation; definitions/imports use
the same parameter formatter. Recognition
alone is insufficient: an unsupported object-generation probe fails preparation.

Successful target probes are cached in the session-owned toolchain object.
Semantic configuration fields, resolved executable metadata and provider implementation
content invalidate this cache. Version output contributes to backend identity.
Each required scalar signature is verified once per target snapshot; failed
probes are not cached. Executable metadata is a lightweight change detector;
restart the session after upgrades to dynamically loaded toolchain libraries or
changes that preserve the executable's metadata. Provider caches contain verified
toolchain facts, not accepted program state.

The [code-generation organization](code_generation_handlers.md#source-fingerprints)
keeps preparation and tool execution as separate process owners. Its source
policy fingerprint discovers the owner's PHP files recursively, including layout,
binding/data, utility and join contracts; `tools/` services contribute separately.
Native-entry adaptation remains an explicit external policy input. Tests verify
source edits, nested file addition/removal and unchanged probe reuse in private copies.

## Work, replacement and proof

Select a callable when its configuration, shared signature or return/parameter
definitions have changed, or `full_rebuild` selects everything. Body-only changes preserve its
binding. The coordinator verifies selected primitives before workers read the
fixed configuration and `Type_Resolution`. Each task is an existing
`Callable_Signature`; `signature_for(symbol_id)` and `definition_for(type_id)`
return shared contracts without exposing type-table storage. Ordered
`callable_parameter` rows retain parameter IDs and those same definitions. Body
edits preserve them; a parameter-only definition replacement invalidates the
binding even if its return definition and signature shape are unchanged. Preparation does
not depend on body checking or lifetime results. Workers produce separate bindings; the
join validates membership/completeness, reuses unchanged rows, and excludes
removed owners. Equal complete contexts retain their identity.

`Compiler_Session::compile(manifest_path)` invokes this provider automatically.
The temporary caller-supplied `Backend_Context` argument has been removed; stale
bindings are now regenerated by their owner. The optional constructor argument
`backend_toolchain_path` selects another configuration file. An invalid target
or probe failure leaves every accepted semantic/backend snapshot unchanged.
Repair resumes through the same pipeline. Backend-only changes preserve valid
language analysis and invalidate downstream lowering inputs through context
identity. Native entry planning is exported separately with the emitted program;
the backend context continues to own project callable contracts.

[`backend_preparation.php`](../../tests/05_generate_code/prepare_backend/backend_preparation.php) proves
real probes, primitive verification caching, scalar/void/float bindings,
preparation before body checking, independent workers, reverse joins,
stale/incomplete rejection, body-only reuse,
automatic signature refresh, target failure/repair, explicit target selection,
removal, full rebuild, fresh equivalence and debug output. The CLI's two-refresh
simulation also checks complete prepared bindings. These proofs establish
preparation and object-code capability, not execution of the source program.


## Composed source lifecycle

The accepted type model now also supplies complete source operation plans.
Selected lifecycle ABI tasks and their join handle both imported and source-owned
operations. Layout preparation measures opaque field alignment with layout-only
Clang shells and verifies their primitive storage against LLVM; Clang supplies no
source construction/destruction implementation. LLVM emission consumes the accepted
member plans in separate selected tasks and joins their private definitions.
Generated definitions belong once to the entry module and are reused on body-only
updates. See [bounded composition](lifecycle_contracts.md#first-compiler-implementation-bounded-composition).
