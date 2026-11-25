# Compiler mental model
Doc Status: supporting

## Project objective

Build a Simple C++ compiler with first-class **STAN (static analysis)** and a useful
direct LLVM compilation path targeting **`-O0` and `-O1`**, using LLVM's optimization
capabilities. Implementing an independent optimizer or replacing every capability
of the existing source-to-source (S2S) path is not required. S2S can continue to
serve broader compilation needs.

The next-generation S2S generator is intended to be part of this compiler,
consuming its resolved types, checked operations and lifetime/ownership analysis.
LLVM and generated C++ should preserve the same accepted source semantics and
safety requirements. Limitations of the existing S2S implementation are evidence
to investigate, not permanent limits on the language. Changes to explicit
contracts still require an agreed, documented decision and a safe implementation
path for both backends; this future integration is not implemented yet.
The language intent is natural, predictable behavior from locally declared contracts.
Missing analysis in the legacy generator must not define the language's limits;
our own analysis representation must not introduce accidental restrictions either.

**Depending on Clang, LLVM and the Simple C++ runtime is an accepted design choice.**
We already use that toolchain for runtime bridges, layout/ABI facts and native
output. Additional Clang-assisted preparation is allowed when it simplifies the
complete system, including nested types, incremental reuse and diagnostics.
Ownership follows origin: runtime implementations use Clang-prepared metadata and
LLVM; source-defined behavior, complete lifecycle composition and STAN belong to
our compiler. Mixed types retain both owners through an explicit bridge. Clang
may supply layout/ABI facts and native adaptations without duplicating the source
implementation. See the [ownership decision](details/clang_lifecycle_composition.md#ownership-decision).
The [source/native contract](details/source_native_contract.md) defines the planned
identity, layout, capability and import boundary for mixed types.

Follow Simple C++ contracts; determine unclear lifecycle behavior from generated
C++ and the actual runtime types. The optimization modes and broader STAN scope
above are objectives, not newly implemented features. The
[foundations tracker](planning/compiler_foundations.md) records
current support. [Clang and lifecycle composition](details/clang_lifecycle_composition.md)
records the agreed ownership and pending integration mechanism.
The [lifecycle contract investigation](details/lifecycle_contracts.md) records
event semantics, focused evidence and upstream discrepancies to resolve.

## Working model

Keep concepts simple, owners explicit and common behavior on one path. Active
implementation is in the [adopted PHP compiler](../README.md). The original
[PHP++ source](../reference/original-phpp/src/README.md) remains a reference; the full portability migration is authorized and precedes new functionality.

## Authoritative documents

- [Pipeline](compiler_pipeline.md): processing dependencies and incremental policy.
- [Code organization](code_organization.md): ownership, source layout and verification rules.
- [Type model](type_model.md): shared meaning, representations and capabilities.
- [Foundations tracker](planning/compiler_foundations.md): capability status,
  completion evidence and next work.
- [Coding guide](code_formatting.md): PHP/PHP++ formatting and navigation maps.

Three goals guide each slice: real source-to-output behavior, work partitioned for
future multithreading, and retained results with explicit incremental replacement.
Selected workers consume fixed inputs and produce private results; joins accept them.
Full rebuild selects all current work through the same algorithm. Current required
proof is a full build and one incremental attempt, which may report failure.

## Navigation

Start at the [compiler call map](../src/compile/calls.md). Adjacent group
README files show execution order; process `calls.md` files identify workers, joins
and outputs. [Core data diagrams](details/core_data_structures.md) illustrate the
main retained records and references.

Shared semantic contracts and canonical type storage live in
[type_model](../src/04_analyze/type_model/calls.md). Runtime import and
source type resolution produce those contracts. [Backend preparation](details/backend_preparation.md)
publishes verified layout and ABI facts before lowering and emission consume them.
[Runtime preparation](details/runtime_package_preparation.md) remains a separate tool.

Detailed feature contracts and verification evidence belong in `details/`, linked
from the foundations tracker. The [performance watchlist](details/performance_watchlist.md)
records deferred measurement targets. Historical investigations remain evidence,
not competing descriptions of current behavior. Update each rule in its owner and
link to it rather than copying it into entry documents.

Follow the [repository rules](../reference/source-repository/working_rules.md). Documentation links are relative;
references to the older compiler assume `simple_cpp_compiler` is a sibling repository.
