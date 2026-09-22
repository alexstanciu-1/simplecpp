# PHP prototype tests
Doc Status: supporting

Run the complete suite from the repository root:

```sh
python3 tests/run.py
```

The runner lints all prototype PHP files, then runs PHP and Python tests with up
to **10 concurrent jobs** by default. Each fixture gets its own temporary sample
copy and working directory; Python checks also create their own workspaces.
Output is grouped by completed test, and failures produce a nonzero exit after
all selected tests finish. Use `--jobs 1` for serial debugging or `--jobs N` to
change concurrency. `--timeout SECONDS` sets the per-test timeout (default 180);
individual fixtures may also enforce shorter internal timeouts. The job limit
counts fixtures; their internal tool processes can add concurrency. Native checks
use the configured backend tools and execute the generated programs. These are
correctness checks, not benchmarks.

[Single-file session](integration/single_file.php) and [CLI](integration/single_file_cli.py)
checks cover virtual projects, exact source membership, native defaults, body-edit
reuse, source protection and Windows naming from real Clang target probes.

## Where a test belongs

Numbered folders mirror the owning processes under `src/`:

```text
tests/
    01_prepare_inputs/
        read_manifest/
        read_sources/
    02_tokenize/
    03_parse/
    04_analyze/
        collect_symbols/
        resolve_symbols/
        resolve_types/
        check_bodies/
        analyze_lifetimes/
    05_generate_code/
        lower/
        emit_llvm/
    06_build_output/
        build_native/
    compile/
    simulate_increment/
    features/
    integration/
    support/
    run.py
```

Choose the folder by the contract being proved, even when the test uses the
compiler to prepare its inputs or executes output as additional evidence.

| Location | Responsibility and examples |
|---|---|
| Numbered process folder | Stage data, workers, joins, diagnostics and reuse. Examples: [parser updates](03_parse/parse_updates.php), [parameter binding](04_analyze/resolve_symbols/parameter_binding.php), [module assembly](05_generate_code/emit_llvm/module_assembly.php), [native object batches](06_build_output/build_native/native_modules.php). |
| `compile/` | Session coordination, incremental selection and lock ownership. |
| `simulate_increment/` | Folder-swap simulation and recovery. |
| `features/` | Complete language-feature scenarios across stages: [locals](features/local_lowering.php), [parameters](features/parameter_lowering.php), [integer conversions](features/integer_conversions.php), [addition](features/addition.php), [control flow](features/control_flow.php), [program entry](features/program_entry.php). Existing descriptive filenames are retained. |
| `integration/` | Contracts spanning compiler processes: native publication/reuse, CLI behavior, process exclusion and external-job cancellation. `boundaries.php` covers input/storage boundaries across processes. |
| `support/` | Shared bootstrap/assertion and stage-building helpers, plus PHP subprocess probes launched by Python tests. These are not standalone suite entries. |

The frontend storage proof stays with parsing because it covers the complete
token/tree result. Shared type-storage tests live under `04_analyze/type_model/`;
backend preparation tests live under `05_generate_code/prepare_backend/`, matching
their production owners. See the [ownership review](../docs/details/backend_organization_review.md).

## Adding or moving a test

1. Put a stage contract in its process folder, or a complete feature scenario in
   `features/`. Keep each scenario intact; use shared support without importing
   another executable test.
2. Register the relative path in `PHP_FIXTURES` or `PYTHON_FIXTURES` in
   [run.py](run.py). The runner rejects missing, duplicate or unregistered test
   files outside `support/`.
3. Resolve compiler and helper paths relative to the test file. Keep generated
   files and edited source fixtures in the runner's temporary workspace.
4. Update documentation links and any subprocess callers when moving a test.

The directory organization changes neither assertions nor execution order. It
does not introduce a new testing framework or duplicate feature tests under
every stage. Add subfolders within `features/` only when a feature has several
separate scenarios that justify them.

[Shared lifecycle support](support/step_support.php) checks state ordering and
concrete result/store identity when fixtures execute a complete step. Private
selection access is test-only, for independent worker/join proofs.
[All-step lifecycle proof](compile/step_lifecycle.php) runs all 20 owners, validates
retained input/output equivalence and checks native candidate cleanup without
publication. Its runner disables Xdebug diagnostic trace retention so abandoned
objects can be destroyed immediately. Benchmarks are separate from this suite.

[Join contracts](compile/join_contracts.php) cover all 21 join owners through the
shared interface, including descriptive *_join.php owners and array outputs.

[Resource contracts](04_analyze/analyze_lifetimes/resource_contracts.php) check the
compact ownership algebra against an independent finite-relation model, plus exact
path/endpoint projection. The growing-list integration also checks reordered
dependency maps, private worker inputs and equal-summary identity reuse.

[Runtime ABI integration](integration/runtime_abi.php) prepares an isolated provider
and proves adapter validation, real integer calls/conversions, body reuse, provider
invalidation and compiler-generated full/ThinLTO consumers. It requires the same
Clang 18/LLD setup as the standalone preparation proof.

[Inline runtime integration](integration/runtime_inline.php) proves two metadata-defined
opaque types, direct local/temporary construction, call-scoped borrowing, measured
alignment, object identity, loop storage reuse and one body-edit increment. It also
checks fixed lowering workers and explicit rejection of unsupported ownership contracts.

[Runtime cleanup integration](integration/runtime_cleanup.php) compares managed
object traces with a native C++ oracle: full-expression temporaries, reverse
cleanup order, branches, repeated loops and void/scalar returns. Separate
full-build/one-increment sessions prove unchanged cleanup reuse and replacement
of an edited managed body, including preserved caller/unrelated objects and prior
snapshots. It also checks fixed analysis workers and rejects missing or reordered
cleanup obligations, invalid destruction metadata and unsupported managed copying.
Cleanup preparation also checks fixed tasks, reverse completion, full/empty
selection, partial replacement, target reuse/removal, and rejection of incomplete,
duplicate, unselected or stale worker batches without changing retained inputs.

`integration/runtime_copy.php` proves metadata-authorized local copy construction
against native C++ for independent resource owners and cleanup-free objects,
control-flow cleanup, pure lifecycle/analysis/lowering work, malformed contract
rejection and one managed-body incremental replacement.

`integration/runtime_assignment.php` proves live replacement and native
self-assignment for real strings and a configured allocation-owning observer.
It covers source-field composition, default-generic permission, exact cleanup,
private lifecycle/lowering joins, one body increment and O1/ThinLTO. Malformed
assignment metadata, missing permission and deleted native assignment reject.
`integration/provider_family_append.php` also copies and assigns a prepared vector,
checking replacement, self-assignment, independent contents and package reuse.

`integration/runtime_strings.php` prepares the real Simple C++ string provider plus
a second type declared only in configuration, compares binary output and cleanup
against native C++, and proves literal-body replacement with unchanged module reuse.
The ABI, string, conversion and console fixtures prepare the public Simple C++ runtime
header surface and have separate 180-second fixture timeouts;
other PHP fixtures retain their existing bound.

`integration/runtime_conversions.php` proves conversion-purpose selection, owned
Simple C++ string results and a configured noncopyable/nonmovable second type. It
checks native values/cleanup against C++, worker purity, one body increment and
invalid metadata. Like the string fixture, its provider build has a 180-second
timeout.

`integration/runtime_console.php` proves strict parsing, line input, concatenation,
byte length and the combined console flow. It checks malformed/overflow input,
EOF/read errors, binary/UTF-8/long lines, fixed workers, one body replacement,
package reuse and composed ordinary/full/ThinLTO execution.

`integration/source_structs.php` proves source records through common definitions and
locations: zero and explicit by-value construction, independent copies/assignment,
public scalar field reads/writes, native target layout comparison, distinct nominal
identities, normalized producer inputs, worker purity/reversed joins, invalid inputs,
exports and one body increment with unchanged layout/module/object reuse.

`integration/provider_records.php` proves JSON-to-Clang preparation and adapter import
of complete plain records, renamed/read-only fields, common source/provider shapes and
execution, fixed worker joins, body-edit reuse, provider replacement and rejected native
layout/lifetime/field contracts. Aggregate by-value calls remain explicitly unsupported.

Struct consolidation: [syntax roles](03_parse/struct_parsing.php),
[source values](integration/source_structs.php) and [provider values](integration/provider_records.php)
cover shared field views, source diagnostics, invalid worker contracts, value
self-assignment, field control flow and read-only-field versus whole-value copying.

`integration/runtime_record_borrows.php` proves metadata-prepared const record
parameters, existing-local address identity, independent value copies, nested calls,
rejected nominal/temporary/escaping contracts, fixed signature/body workers and one
body replacement retaining unchanged provider signatures and layouts.

`integration/runtime_record_strings.php` retains the mixed pre-template proof:
source/provider records, metadata-defined record borrowing with an owned string
result, independent copies, loop/early-return execution, one body increment and
snapshot purity. It also checks exact annotation diagnostics for unused source
functions with unsupported object parameters/results. Runtime preparation has the
same 180-second timeout as the other public-header composition fixtures.

`05_generate_code/prepare_backend/backend_preparation.php` checks implementation
fingerprints using private source copies: layout/binding edits, automatic nested
policy-file addition/removal, tool-execution versus ABI-policy separation and warm
probe reuse.

`03_parse/metaprogramming_parsing.php` proves general ordered template arguments
(including two-type and nested families), declarations, constants, compile-time
condition syntax, exports/comparison and malformed-input diagnostics. Deep/wide
inputs exercise the shared iterative expression parser. Fixed workers, reversed
joins and one real source-edit replacement preserve retained inputs. Public
compilation rejects unsupported semantics without publication. This is a parser
proof, not an executable template/constant-evaluation proof.

`04_analyze/resolve_symbols/template_bindings.php` proves shared ordinary/template
annotation binding, ordered type/value parameter scopes, forward and recursive
references, constant visibility and 2,000 nested family applications. It checks
exports, private workers, reversed/rejected joins, changed formal dependencies,
provider identity and retained snapshot purity. Native ordinary code runs beside
unused templates and survives one body increment; unsupported expression
evaluation fails without publication.

`04_analyze/instantiate/explicit_instances.php` proves executable type/value
specializations, multi-parameter records, nested arguments, deduplication, recursive
calls, native output and one incremental demand replacement. It also checks
original AST retention, private workers, reversed/rejected joins, type lineage,
exports and source diagnostics for unsupported compile-time execution.

[Custom lifecycle](integration/custom_lifecycle.php) compares source constructor and
destructor bodies with independent C++, including managed fields, early returns,
implicit template demands, O1/ThinLTO, private joins and a custom-body increment.

## Default generic contract proof

[generic_contracts.php](04_analyze/check_templates/generic_contracts.php) covers
native copy/assignment/forwarding, independent formal slots, const record borrows,
unauthorized operations in unused definitions, imported/source-composed capability
rejection, private joins and reuse after one ordinary body edit. Concrete instances
share definition checks. Growing-list storage/ownership proofs use concrete element
records; fixed-array proofs retain templated capacities with concrete elements.
Their generic element migration is explicitly deferred, without compiler exemptions.

`integration/provider_family_methods.php` proves metadata-declared method demands,
concrete and generic source calls, two ordered type parameters, non-leading receiver
positions and compatible package coverage growth on one body increment. It checks
exact type/callable reuse, snapshot purity, operation join identity/coverage and
signature exports. An injected preparation result drops a previously needed method;
the coverage join must reject it before replacing accepted associations. Scalar-address
calls remain outside this fixture's current scope.

[provider_family_append.php](integration/provider_family_append.php) completes the
const integer-borrow proof using actual Simple C++ `scpp::vector_t` preparation. It
covers matching locals/projected fields, literals, expressions, exactly-once call
results and permitted widening before borrowing. Ordinary metadata functions prove
same-place address reuse and distinct temporary addresses. Generic forwarding,
one body increment with package reuse, immutable snapshots and reversed lowering
worker joins exercise the common pipeline. Wrong types, source scalar references
and mutable scalar-address metadata are rejected. External O1/full/ThinLTO links
prove artifact compatibility; they do not add a compiler optimization/LTO mode.

`integration/owned_results.php` exercises caller-owned source results, primitive
record stores, fresh forwarding, native move/copy selection, automatic source field
composition and user-declaration suppression. It also covers const opaque borrows,
generic copy permission, early/temporary cleanup, fixed body workers, one body edit
and missing-operation rejection. External O1 must preserve its event trace.
`growing_list.php` additionally checks custom-copy owning results, composed return
resource summaries, worker joins and one producer edit; `runtime_console.php` carries
real source string returns through external full/ThinLTO linking.

`integration/provider_family_nested.php` proves metadata-driven nested runtime
families with real vectors and a two-argument native family. It checks canonical
argument imports, copy independence, owned early returns, observed allocation
balance, reversed joins, snapshot purity and one body increment with exact package
reuse. Wrong identity/layout/target, missing owners and stale header inputs reject.
O1/ThinLTO and address instrumentation consume the same compiler/native artifacts;
LeakSanitizer is disabled under the traced harness, not counted as a passing check.

`integration/provider_family_runtime_types.php` proves ordinary prepared strings and
a second configured managed type as native family arguments: canonical imports,
copy/assignment independence, owned reads and returns, observable cleanup, one body
increment with package reuse, stale dependencies, and O1/ThinLTO links. The same
preparation description owner serves ordinary types and nested specializations.

`05_generate_code/prepare_backend/layout_preparation.php` covers fixed dependency
views, canonical store growth after selection, shared early/final layout batches,
reverse joins, nested dependency invalidation, target/definition/lineage rejection
and one retained attempt. Source structs, fixed arrays and managed-field integration
fixtures exercise the same production coordinator through real compilation.

`05_generate_code/prepare_backend/source_exports.php` consumes real compiler results
through explicit source export preparation. It checks nominal separation despite equal
layout, tagged/nested template arguments, primitive and managed complete plans, rejected
nested custom bodies, all six role states, private/reversed joins, stale ABI/target/
lineage rejection, body-only contract reuse, and stable symbols across a relocated
rebuild with different local IDs. It does not claim native adapters or export linking.

`integration/source_project_preparation.php` uses the shared managed-source fixture
in `support/source_export_support.php`. It projects actual accepted compiler exports,
prepares two source shapes through family metadata, checks real native adapter/crossing
artifacts and all LLVM variants, rejects unauthorized imports and incompatible ABI,
checks profile/crossing/project authorization, rejects ordinary package import and
proves stable artifact reuse after one source body edit. Final linking of those project
modules is deliberately outside this preparation gate.

The source/native execution fixture `integration/source_family_execution.php` covers
managed, nested and cleanup-free source records plus nested native families through
compiler-routed preparation, explicit project import and real source export linking.
It checks balanced field allocations, canonical source bindings, project output scope,
missing/duplicate/incompatible imports, one body increment with old-snapshot purity and
unchanged native artifacts, O0/O1 and ThinLTO. `Source_Export_Test` supplies the shared
instrumented native field fixture; no test includes and executes another test.
