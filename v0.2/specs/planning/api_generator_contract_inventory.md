# G1: existing API generator contract and parity inventory

> Status update (2026-10-29): Rust implementation and toolchain retired.
> This note is historical context; current owners are the PHP compiler and PHP API
> generator. See [compiler status](../../compiler/README.md).

Doc Status: planning

Historical donor inventory: the described C++ generator was retired at the full
Rust cutover. Frozen fixtures retain this contract; production commands and current
owners are documented in [Runtime API tooling](../../tools/api_generator/README.md).
Date: 2026-10-29
Reference: C++ generator sources at f9e6b0774, unchanged by G1.
Scope: contract capture and test infrastructure only; no Rust generator or layout
probe is implemented. The C++ generator remains the production generation path.

This is migration evidence under the [Rust tooling plan](rust_compiler_migration.md),
not a new source-language specification. Adopted runtime family contracts remain
semantic authority. Observed implementation details below must be reviewed explicitly
if changed during G2/G3; recording a behavior is not an endorsement of every detail.

## Implementation owners and dependencies

| Files under v0.2/tools | Current responsibility |
| --- | --- |
| api_generator/main.cpp | CLI argument checks, input reads, inventory/freshness handling, output writes and summary/status |
| api_generator/value.hpp / value.cpp | Metadata tree, field access/checks, JSON loading and deterministic serialization |
| api_generator/runtime.hpp / runtime.cpp | Typed plans, policy validation, concrete request resolution and physical description |
| api_generator/emission.cpp | Public C++ declarations, private definitions, deleted native fences and output inventory |
| api_generator/ownership.cpp | Record/value/const-pointee publication, qualification plans and ownership emission |
| support/json/pull_reader.hpp / reader_detail.hpp | UTF-8/JSON grammar, duplicate keys, nesting and read diagnostics |

Nine handwritten C++ files, 2,146 physical lines and 133,862 bytes including private
JSON support, excluding tests/metadata/generated output. The C++ generator links its
own metadata/preparation libraries and standard library, not scpp_runtime. It emits
calls/includes for handwritten numeric, sequence/carrier and ownership primitives;
it does not implement a source parser or symbol resolver. Ownership record headers
are supplied by consumers and validated for C++ capabilities when compiling output.

## Input and validation contract

The command is:

```text
scpp_api_generator RUNTIME.json PUBLICATION.json OUTPUT [--check]
```

Both inputs require numeric version token `1`; `1.0`, `1e0` and string `"1"` are
not equivalent versions. Publication namespace is exactly scpp. Unknown fields are
rejected at the owning validation sites, not globally ignored. Required fields are
read there; optional families can be absent. Policy vocabulary and implementation
bindings are deliberately bounded, not arbitrary code templates supplied by JSON.

Metadata root groups: version, types, representation, construction,
promotion_policies, operations, values, source_types, aliases, floating,
scalar_casts, string, vector, carriers and ownership. Publication groups: version,
namespace, types, operators, headers, values, short_aliases, string, vectors,
carriers and ownership. Tests use the current tracked inputs rather than copying a
second entire metadata registry into a fixture directory.

| Family | Preparation responsibilities and rejection boundaries |
| --- | --- |
| Integers | Eight supported signed/unsigned widths; identity agrees with signedness/width; explicit checked constructors; configured domain maxima; 33 operator bindings/forms; published operands/execution types; duplicate requests rejected |
| Native numeric adapters | Registered fundamental inputs and exact native ranges; no native-only runtime overloads; native bridge requires matching same-type execution publication; unsupported native conversions receive deleted fences in emitted APIs |
| Bool/null/aliases | Concrete identities and operations; explicit native bool condition; no null condition; int_t/float_t follow configured source defaults; selective short names |
| Float64 | IEEE binary64 native double; 20 bindings/forms; narrow integer mixed operations only; wide int64/uint64 operations require explicit casts; requested casts bind fixed checked/rounding policies |
| String | Private storage, signed count and bool dependencies, explicit construction, requested operations and numeric/bool text casts; null text casts rejected |
| Vector | Published scalar/string elements, signed count, value reads/explicit set, optional native adapters, requested equality needs element equality, elementwise casts need published source and element conversion; no identity cast wrapper |
| Carriers | Four configured families/default states; published scalar/string/vector payloads; nullable requires null_t; result requires error string/int64 dependencies; no nested/null payload; implicit or explicit checked one-layer extraction |
| Ownership/value | Registered record names/headers/scalar constructors; requested families; weak requires matching shared including constness; explicit add-const conversions only; inline value capabilities remain payload-dependent; no invalid/duplicate/conflicting identities |

Public header names are constrained scpp/...hpp paths and must not collide.
Constructor argument order, operand order and cast direction are meaningful.
Known set-like publication lists are normalized deterministically. The parity suite
reverses only those lists, never arbitrary arrays such as constructor arguments.

A runtime-only mixed operator may emit its own private implementation directly;
it does not require a separate public same-type operator request. The extra
same-type publication dependency is enforced specifically for native adapters.
This distinction is covered by runtime_mixed_only and missing_native_execution.

## JSON boundary

The reader validates UTF-8 and JSON grammar, rejects trailing documents, leading
zero numbers, invalid Unicode escapes, lone surrogates and duplicate decoded keys
(including escaped-equivalent names). Default input nesting limit is 128. Numbers
are preserved as lexical tokens in the metadata tree: very large integers,
negative zero and huge exponents are not rounded through a native double merely
by parsing. Semantic fields then impose their own restrictions.

The serializer sorts object keys, preserves array order, uses classic locale,
escapes control characters and retains valid raw number tokens. It rejects malformed
internal values and excessive nesting. CLI parse diagnostics include input filename,
byte position and bracket-style JSON path; private read_error also carries category
and detail. The CLI has no machine-readable error schema yet.

G1's CLI corpus checks representative JSON failures and version/width token rules.
The existing json_reader and metadata_value tests additionally prove valid Unicode,
number-token round trips, depth boundaries, reader protocol and serialization errors.
Their APIs need not be recreated in Rust; their applicable behaviors need replacement
proofs. Selecting a JSON package does not automatically preserve these behaviors.

## Prepared facts and emitted artifacts

prepared.json currently has kind=prepared_runtime_api but no independent schema
version or complete target fingerprint. That is a G2 interface-design gap, not an
invitation to infer policy independently in the compiler.

The model records integer/native representations, conversion sources/targets and
failure/losslessness facts, resolved operation forms/operands/adaptations/execution
and result types/categories, checked compound writeback, aliases/source identities,
string/vector operations and methods, carrier states/extraction/error details, and
ownership lifecycle/layout/qualification facts. Source-symbol resolution, expression
sequencing and proof of safe check omission are outside this model.

Mandatory outputs are prepared.json, inventory.json, src/runtime.cpp and five public
headers (types, values, operators, native bridge, short names). Published string,
vector and carrier families add one header each. Ownership adds its selected public
header and src/ownership.cpp. Actual public paths come from validated publication
requests. inventory.json is a sorted array of all emitted paths **except itself**.

| Baseline publication | Runtime types in CLI summary | Numeric signatures | Artifact count |
| --- | ---: | ---: | ---: |
| Default | 25 | 151 | 11 |
| Compiler | 5 | 6 | 8 |
| Ownership proof | 18 | 0 | 10 |
| G1 numeric forms fixture | 11 | 306 | 8 |

CLI counts cover generated types and numeric signatures; they are not counts of all
methods, conversions or string/vector/ownership operations. Generated output is not
checked into Git; the parity snapshot stores only hashes, selected native facts,
input fingerprints, diagnostic fragments and summaries as an intentional test fixture.

## CLI and filesystem behavior

- Success exits 0, has no stderr, and prints Generated/Verified type/signature counts.
  Failure exits 1, reports an error on stderr and does not print a success summary.
- Invalid inputs fail before output is created. Invalid argument count, unknown
  option and unreadable input are failures. Output directories are created as needed.
- --check compares every expected artifact byte-for-byte without rewriting it;
  missing output fails without creating files. Normal generation repairs stale or
  missing artifacts by rewriting expected outputs, even when content was unchanged.
- Existing inventory must parse as an array. A listed path absent from the new output
  set fails before writes with an obsolete-output diagnostic. No automatic pruning
  or destructive cleanup occurs; callers must generate into empty staging instead.
- An empty prior inventory is accepted by normal generation. Check still detects
  inventory content differing from the expected inventory. Unlisted unrelated files
  are ignored by check and preserved by generation: this is not a directory-purity test.
- Output I/O errors are failures. Writing multiple files is not transactional;
  no rollback/atomic-publication promise is inferred. Check does not promise a stable
  snapshot against concurrent writers. Generator and checker tests use private staging.

The parity suite verifies these cases for both reference and optional candidate.
Only stable diagnostic fragments are asserted, case-insensitively; absolute paths,
full prose and OS-specific filesystem diagnostics are not exact-string contracts.
Selected fragments are migration compatibility checkpoints: changing them requires
review, not necessarily permanent preservation of C++ exception phrasing.

## Native facts and the future probe

| Current source of facts | G2 probe / output obligation |
| --- | --- |
| numeric_limits on int/unsigned int/long/unsigned long/long long/unsigned long long | Carry signedness and value-bit digits for configured native inputs; emitted C++ asserts them again |
| numeric_limits<double> | Require IEC559, radix 2, 53 digits, maximum exponent 1024; repeated in generated float implementation |
| sizeof/alignof shared_ptr<void>, weak_ptr<void>, unique_ptr<unsigned char> | Record ownership storage dimensions; generated code asserts each concrete smart pointer and public wrapper layout |
| __INTn_TYPE__/__UINTn_TYPE__ spellings and __SIZEOF_INT128__ conditionals | Preserve target compiler representation/fence capabilities; review as target requirements rather than Rust host measurements |
| Concrete payload C++ traits and value sizeof/alignof | Remain checks in generated consumer C++, including nothrow destruction and copy/move requirements; do not pretend a generic probe knows external record definitions |

The G1 fixture was captured on native x86-64 Linux with Clang/libc++: int has 31
signed value bits, long and long long 63; unsigned variants have 32/64/64. Shared
and weak storage is size 16/alignment 8; unique is 8/8, for mutable and const
pointees. The baseline checks these emitted facts before matching output fingerprints.
They are only observable facts, not a complete ABI identity or proof that differing
standard libraries are interchangeable. Another target needs an explicitly reviewed
baseline; the test must not silently recapture itself.

No probe exists in G1. G2 must add explicit target/configuration identity, schema
validation and cache invalidation as specified in the migration plan. Cross-target
execution and stale facts cannot be solved by using the Rust generator's host layout.

## Parity corpus and comparison gates

`tests/generator_parity/corpus.py` supplies 17 accepted cases: default, compiler,
ownership, minimal scalars, bool/null only, nullable only, string storage only,
runtime mixed only, one-way native, configured default alias, explicit carriers,
custom headers, vector casts/all six element identities, value-only, unique-only,
const-only ownership and full-width numeric forms. It supplies 37 rejected cases
covering policy, dependencies, identities, header paths, signatures, ownership and
JSON boundaries. Named cases in the corpus and baseline are the exact inventory.

`baseline.json` is a frozen G1 oracle captured from the unchanged C++ implementation
at the reference revision above. Every input pair has a fingerprint. Every accepted
case has artifact hashes, a canonical prepared-model hash and summary. This detects
drift even when the same executable is passed as reference and candidate.

The initial gate is strict: exact artifact paths and bytes, complete prepared facts,
success summaries, rejection cases/fragments and CLI behavior. Canonical JSON hashing
normalizes object key order/whitespace only as supplementary evidence; artifact
byte hashes still reject formatting differences. Array order, booleans versus numbers,
missing fields and native layouts are not discarded. A future intentional emission
or schema change needs a narrowly reviewed comparison/baseline change and compiled
consumer proof; do not normalize away entire files, headers or plan sections.

`test_comparison.py` deliberately mutates artifact sets, C++ bytes, JSON formatting,
semantic fields/array order and native facts to prove that the comparison detects
drift. CTest runs the C++ executable in both slots now, testing the harness and frozen
oracle, **not claiming Rust/C++ parity before Rust exists**. A future candidate can
be supplied through --candidate using the compatible positional CLI. G2 may extend
that invocation explicitly for probe inputs once their interface is agreed.

Existing semantic preparation, publication, consumer, template-free closure,
compile-failure, layout-mismatch, lifetime and allocation tests remain required.
The G1 corpus is representative, not exhaustive, and does not replace the larger
integer_full_publication Cartesian matrix or metadata tree tests. G3 must run the
consumer suites against Rust-generated artifacts, beyond matching these fingerprints.

## Reproduction and validation

From v0.2 (Python 3.10+ is now a test-only CMake dependency):

```sh
cmake --preset default
cmake --build --preset default
ctest --preset default -R generator_parity --output-on-failure
python3 -B tests/generator_parity/parity.py \
  --reference build/clang/scpp_api_generator \
  --candidate build/clang/scpp_api_generator \
  --work build/generator-parity-manual
```

Omit --candidate for reference-versus-frozen-baseline checks only. Temporary inputs
and generated code live under --work and are removed automatically; summary.json
remains under the ignored build tree. The runner never writes runtime/generated.
An explicit --capture PATH writes a proposed new snapshot for review; CTest never
uses it. Compare/review changes before replacing the tracked fixture. Do not capture
from an unreviewed replacement generator to make a failing parity check pass.

G1 validation: all 36 CTest checks pass in Release and UBSan with Ninja/eight build
jobs/Clang/libc++/mold, including the parity contract and four comparator tests. No
production generator, runtime, Rust compiler or generated artifact is changed by G1.

## Fixture location after compiler foundation retirement

The former compiler/publication.json now lives at
tests/generator_parity/compiler_publication.json as an intentional consumer fixture.
Its contents and the frozen G1 baseline are unchanged. The corpus and native-probe
integration both load this location; neither requires the retired C++ compiler.
