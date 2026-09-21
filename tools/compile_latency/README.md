# Common-edit native compilation experiment
Doc Status: planning

This is an experimental output-generation path, not production compiler support.
It assumes the future frontend has complete correct semantic information and a
fixed 1.5-second budget. It measures Ninja invocation through linked executable
against an 8.5-second native budget. Both original external repositories stay
read-only; all application mutations and build artifacts belong in a scratch copy.

The scripts use the existing S2S for per-file lowering, then change output
partitioning. No runtime checks, method bodies, ownership operations, or full
application link inputs are discarded. The selected grammar is deliberately
bounded: one generated `scpp` namespace, uniquely named out-of-line methods, no
namespace-scope data, overloads, free statics, or templates in split files.
Unsupported shapes fail. The original 452-unit program remains linked.

## Technical handoff

For the next S2S, start with
[semantic inputs and incremental C++ generation](../../specs/planning/s2s_next_generator_technical_handoff.md).
It distinguishes demonstrated output rules from proposed interfaces and optional
optimizations, and records the user's acceptance of the roughly 30-second compound
case. Earlier experiments below describe the progression, not one final policy.

## Experiment layout

- `make_corpus.py`: freezes source hashes and 20 body-edit witnesses from the actual
  application, and adds direct result checks to its smoke entrypoint.
- `regenerate.php`: uses a specified Simple C++ checkout's S2S to regenerate selected
  sources, preserving unchanged header/source contents. It uses the existing
  declaration-kind catalog; corpus edits do not alter type declarations.
- `experiment.py`: emits stable groups of complete methods, a retained membership
  map, manifest, alternate Ninja graph, and one project-declaration PCH. Unsplit
  objects keep their original commands and scoped include packs.
- `run_corpus.py`: changes real source bodies, regenerates output, compares native
  builds with caches disabled, and checks the compiler smoke and all 20 method
  results after every measured build. Baseline and experimental order alternate
  between rounds. Shared unsplit objects are explicitly invalidated for both runs.
- `location_metadata.py`: optional follow-up experiment that moves changing source
  line numbers into separately compiled data, keeping diagnostic function/file
  identities and call-depth checks intact. `verify_locations.py` forces a real
  call-depth failure and checks the reported source line.
- `extra_probes.py`: tests largest-group recompilation, two simultaneous groups,
  source-line insertion, and adding a private helper with only its real consumer
  receiving the new declaration. Touch-based stress tests are labeled separately
  from source-edit trials.

The body-edit witnesses replace constant getters with equivalent local arithmetic
and a branch. They sample implementation-unit sizes and dependency costs, not an
empirical distribution of developer edits. Do not extrapolate their pass rate to
all daily edits or all applications. Larger-function and dependency-change probes
are necessary complements. Correctness checks establish these witnesses and the
existing smoke path, not exhaustive compiler correctness.

## Reproduction

Use `prepare_snapshot.py --source SOURCE --vendor VENDOR --scratch NEW_DIRECTORY`
to create the copy and required input manifest. Use an isolated root such as
`/tmp/scpp-edit-latency-20260919`. The tool copies the compiler
`src/` to `app/`, sibling `tests/` to `tests/`, and its vendored Simple C++ to
`vendor/`, excluding `.git` and `.prism`. Capture exact revisions, content hashes,
tool versions, hardware and flags before building. Do not copy existing caches.

From the copied application, establish the baseline with:

```sh
SCPP_CXX=clang++ SCPP_CXX_LAUNCHER= SCPP_NINJA_JOBS=16 \
  php ../vendor/bin/scpp.php build --no-stan --build-runtime --timings
./.prism/build/main
```

`--no-stan` is intentional: future analysis is an assumption, not a measured stage.
The first runtime build is setup. The ordinary no-cache development flags are
retained for every comparison; no optimization/safety flags are reduced to win.
Use only one active build/measurement process per copied application.

Run `make_corpus.py APP`, then `regenerate.php REPO APP main.phs`. Copy the generated
Ninja graph to `APP/.prism/build/latency-baseline.ninja` and change only its `main.o`
force-include to `../generated/__project_units.hpp`: the added verification calls
need these declarations. This is harness setup, excluded from edit measurements.
Build and verify that instrumented baseline before running experiments.

Call `experiment.prepare(app, first_twelve_corpus_sources, 180)` (Python API), then
build `latency-experiment.ninja`. Once setup succeeds:

```sh
python3 tools/compile_latency/run_corpus.py \
  --app /tmp/scpp-edit-latency-20260919/app \
  --repo /home/alexv/__AI/simple_cpp/simple_cpp_01 \
  --rounds 5 --out /tmp/scpp-edit-latency-20260919/corpus-results
```

Run additional probes only after the corpus finishes. All result rows record the
native wall time, exact command, exit status, and Ninja object/link step durations.
Cache hits are not needed for the core feasibility claim. Keep setup timings and
failed setup attempts separate from edit latency statistics.

## Interpretation and migration

A future compiler can emit the groups directly from checked method bodies. The
text splitter merely lets this assessment reuse current lowering. Retained
allocated group membership keeps body changes local; new methods get new groups.
A production implementation must additionally define deterministic clean-build
membership, overloaded identities, deleted groups, debug/source-map updates, and
visibility/ODR rules for internal-linkage entities. Those are outside this bounded
splitter, not claims of supported behavior.

The declaration PCH is compiler/flags/runtime specific and is prepared once.
Changing its inputs can invalidate all consuming groups. It is an experiment in
reusing stable declarations, not a recommendation to include every future private
helper in a permanent global interface. The helper probe demonstrates a narrower
consumer-only declaration edge while retaining the stable PCH.

Source line numbers embedded in method bodies can create unrelated generated
changes after insertions. Measure that explicitly. Do not erase source diagnostics
or count a content-changing dependency as unchanged to make the benchmark pass.

## Historical pain cases and callable declarations

Read `specs/planning/s2s_compile_latency_historical_pain_cases.md` before choosing
more workloads. It maps compiler history and GitHub reports to concrete edits.
`historical_profile_probe.py` showed that a public helper addition makes the
project-wide PCH layout substantially worse. It is a rejected general solution.

`callable_surface.py` explores a different ownership model: source static methods
remain methods at the language level, but their native symbols and declarations
are separate free functions. Each generated caller includes only the callable
headers it uses. The two selected classes are non-inherited static helper classes;
class identity helpers remain consistent in every translation unit. Entire-app
objects are initially rebuilt in an independent tree, keeping the original scoped
include packs and runtime-only PCH. No mixed class definitions or omitted link
inputs are used to claim speedups. Methods retain their original call-depth guard
function/file names; separately compiled line data preserves source positions.

Run `callable_surface.py --app APP`, build `latency-callables.ninja` once, then
`run_callable_probes.py --app APP --rounds 3`. These replay public helper additions
and dispatcher changes in profiling and token-kind owners, plus entrypoint calls
that verify the new behavior. `run_signature_probe.py --app APP` changes an actual
profiling method signature and updates its actual source callers.

This lowering is an assessment path, not a production static-method ABI. A future
semantic generator must own stable symbol identities, visibility, overloads,
calling conventions, virtual dispatch exclusions, method references, and complete
signature dependencies. The experiment reuses existing scoped packs for those
type dependencies; its callable headers are not standalone public C++ headers.

`partitioned_callables.py` combines isolated callable headers with stable body
partitions for the five real consumers of the profiling signature. It also uses
a project declaration PCH for those partitions, the entrypoint, and the two static
helper owners. Crucially, the selected callable declarations are absent from that
PCH, so changing them does not invalidate it. Other class/type declarations are
still present: this is NOT a general solution for shared layout changes.

`run_signature_probe.py` compares all three layouts and explicitly invalidates
changed shared inputs before each experimental measurement. Use
`run_callable_probes.py --app APP --combined-only --rounds 3` to exercise the
public additions against the combined layout. `run_layout_probe.py --app APP`
measures a real added field in `CompilerProfileEventRow` as a boundary case.
`verify_callable_diagnostics.py` forces a real call-depth failure and checks the
original source method name and source line after static methods become functions.

`layout_isolation.py` preserves the by-value row representation while outlining
`CompilerProjectRunReport` special members and removing unnecessary complete-row
includes. Its independent full-app object tree avoids ODR inconsistencies. It
checks move exception guarantees against every report member. Run the shared-field
probe with `run_layout_probe.py --app APP --isolated` after its setup build.

`run_parallelism_probe.py --app APP --rounds 2` measures the same real signature
edit at 1, 2, 4, 8, 12, and 16 Ninja jobs. It verifies an identical work set and
unchanged timestamps for every other linked object. No-op runs establish that
Ninja passes no compilation work to Clang when inputs are settled. Genuine broad
changes are allowed to take longer; the objective is to avoid false dependencies
and schedule the required compilation efficiently.

`run_linker_probe.py --app APP` compares full relinks with mold (default and
1/4/8 threads), LLD, and GNU ld, three trials each. Objects remain fixed and every
executable is checked. Previous experiments already used mold.

`run_cache_probe.py --app APP` compares the real eleven-object signature work set
with direct Clang, a fresh private ccache, and two exact cache replays, three rounds.
It requires `parallelism-results/verified-work-set.json`. Separate timestamp-free
PCHs are prepared outside the edit timing; 522 unchanged objects remain shared.
The selected eleven objects have separate output paths. Each timed run rebuilds
exactly those objects and links/checks the full executable. Private cache stats
are retained, and no shared cache is cleared. Required PCH cache settings are
`pch_defines,time_macros`; this is a bounded generated-code experiment, not a
recommendation to enable those assumptions for arbitrary user C++ macros.
Cache replay means identical previously compiled inputs, not a fresh code edit.

Add `--depend` to measure ccache depend mode separately. It uses the existing
`-MMD` dependency flags, so this fixed-toolchain experiment does not validate
system-header updates. A production integration must use `-MD` or otherwise
invalidate cache identities when the system headers/toolchain change.

`run_parallelism_probe.py` also accepts `--jobs 12 16 24 32 --result-name
concurrency-boundary-results`. An eleven-object work set cannot exploit more than
eleven compiler jobs; timings above that width mostly expose run variation.
`run_wide_scheduling_probe.py --app APP` forces 32 small callable bodies to compile
at job limits 8/12/16/24/32, twice in opposite order. This is synthetic scheduling
stress, not a source-edit performance claim; exact work and executable checks apply.

`run_broader_probes.py --app APP` measures coordinated mapping branches across three
numeric implementation files plus an entrypoint witness, followed by extraction
of real MT heartbeat key construction into a new native TU. Each runs three times
with caches disabled. The latter checks stable native graph extension, not today's
new-PHS discovery path; the former is not complete new numeric-type support.

`run_history_followup.py --app APP --case frontend` screens the public-helper +
frontend/backend edit shape from historical commit `60011d4c`, with one measured
run. The first run exposed a 45-second PCH fanout regression in the experimental
shape; do not turn that one screening run into a statistical estimate.
`--case new-source` actually authors/transpiles a new PHS helper, redirects an
existing MT method, adds direct/indirect witnesses, and measures three builds.
The future symbol-to-header and source-to-object metadata are supplied explicitly;
this tests emitted-code feasibility, not today's automatic discovery pipeline.
Both cases restore scratch sources and check the restored complete executable.

`expanded_layout.py --app APP` extends the same callable-isolation emitter to
`frontend_model_builder` and stable implementation partitioning to
`llvm_text_from_plan`. It emits an independent `latency-expanded.ninja` graph,
object tree, generated mirror, and PCHs. Record its full setup separately.
Run `run_history_followup.py --app APP --case frontend --expanded` to replay the
45-second public frontend edit three times against this layout, asserting no PCH
rebuild and identical work sets. `run_counter_flow_probe.py --app APP` uses the
expanded layout to add a field, update its existing writer and gate-summary reader,
and verify the value through the real report vector and summary paths.

The expanded emitter regenerates the intermediate callables/layout mirrors. Its
final graph and object tree are independent; after these runs use
`latency-expanded.ninja` as the active experiment. Recreate the old layout with
its default emitters and rebuild it before using the earlier graph again.

`run_numeric_pipeline_probe.py --app APP` verifies a previously rejected numeric
source alias through the edited compiler and emitted LLVM, including native exit
code 42. Fixture processing/LLVM execution are correctness checks, separate from
timed compiler rebuilds. `run_multiconsumer_extraction.py --app APP` moves the real
MT publication gate into a new PHS owner and redirects eight production call sites
in seven files. Add `--partition-consumers` after preparing the expanded layout
with `prepare(app, partition_consumers=True)` to partition its six remaining large
consumer implementations. That mode preserves identical native class definitions
and reuses the same PCHs; only implementation-object ownership changes. Record its
one-time partition setup separately. Future metadata for the new file is explicit.

`verify_callable_diagnostics.py --app APP --expanded` checks the expanded frontend
callable's real call-depth exception for original method identity, source file,
and source line. It links a separate diagnostic executable against the complete
object set; it does not replace the application's executable.


`run_over20_hunt.py --app APP --case model-table-helper` screens a central
model-table public helper addition with two redirected production callers.
`--case frontend-counter-layout` screens a genuinely shared counter-class layout
change with an active writer and reader. Both retain the final expanded layout
with publication consumers partitioned, run one cache-disabled `-j12` full-link
measurement, and restore/rebuild/verify the original scratch source afterward.
Allow time for a second broad build during restoration. See the over-twenty-second
planning note for historical priority and measurement-boundary qualifications.


Add `--solutions --trial N` to either over-twenty-second probe to use the
extended table-callable emitter plus the counter factory/field-access boundary.
Prepare this layout explicitly with `expanded_layout.prepare(app,
partition_consumers=True, isolate_tables=True, isolate_counters=True)` and time
its first build separately. `counter_boundary.py` is bounded workload metadata,
not general semantic inference; production lowering needs resolved expression
identities. `measure_counter_boundary_runtime.py --app APP` measures five paired
debug runs of direct versus accessor field updates, outside native build timing.
See `s2s_compile_latency_over20_solutions.md` for tradeoffs and migration scope.


`run_return_policy_probe.py --app APP` exercises nine parser signatures and sixteen
actual call sites on the solutions layout, including the incremental parser owner.
It measures three equivalent work sets, checks an accepted LLVM executable and a
new frontend rejection, and restores source. The policy marker is an experiment
witness, not language design. See the signature-propagation planning note for the
historical arithmetic baseline limitation and the exact extent of this proof.


`run_value_trait_probe.py --app APP` screens a by-value `TypeTraitRow` field
addition with its real writer, an independent copy and vector-storage witness.
It keeps the original value representation and measures one full-link trial;
allow time for a broad restoration build. `run_literal_history_replay.py --app APP`
reverses then reapplies the exact `1610af1a` source hunk within the current program,
verifies both literal-return LLVM paths, and measures three identical work sets.
Neither script is a full historical-toolchain replay. See the historical-patterns
planning note for patch classification and the independent 15-commit sample.

`coverage_sample.py --app APP --out PATH` audits exact textual applicability for
all fifteen independently selected source-history commits. `run_coverage_replay.py
--app APP --commit HASH` uses the frozen solutions layout, establishes the reversed
hunk baseline, reapplies it, and times up to three full builds (one successful
screen if native time exceeds twenty seconds). It records replay incompatibilities
and always attempts restoration. Newly added native objects are forced missing.
Validation is full smoke plus literal-return LLVM exit42 before/after, not an
exhaustive witness for each changed feature. `report_coverage.py --root STUDY`
keeps comments, timing passes/failures, pending cases and replay limits distinct;
its counts are not a population estimate of normal editor-save latency.

`expanded_layout.py --app APP --partition-consumers --isolate-tables
--isolate-counters --uniform-helpers` applies the bounded class-level eligibility
policy across the workload and uses stable implementation buckets. Run
`audit_callable_ownership.py --app APP --out PATH` on the restored pinned baseline
to check neighboring class preservation and exact method grouping. Unsupported
class surfaces remain explicit in the manifest; inline normalization templates
are not yet handled. `run_coverage_replay.py --app APP --commit HASH
--uniform-helpers --output-root STUDY` reuses the same history replay protocol in
a separate results directory. Record conversion costs separately and do not mix
its results with the previous selected-owner policy. See the uniform-helper
planning note for scope and limitations.

`adapter_catalog.py --generated DIR --out PATH` audits generated normalization
wrapper closures without changing output. `prove_callable_file_ownership.py`
compiles and executes a synthetic two-helper/one-carrier ownership regression
fixture; run it separately from native latency trials. It is correctness evidence,
not a compile-performance data point. The completed uniform-helper candidate
results are in `results/2026-09-19/uniform-coverage/`; that candidate does not
replace the previous configuration as a claimed optimum.


`expanded_layout.py --app APP --partition-consumers --isolate-tables
--isolate-counters --isolate-adapters` extends uniform callable ownership to the
validated inline wrapper/normalizer closure. `audit_callable_ownership.py --app
APP --isolate-adapters --out PATH` checks this larger policy. Run
`verify_adapter_semantics.py --help` for the original-versus-extracted normalizer
and real file-wrapper proof. It preserves typed reference mutation and existing
mixed-reference rejection; it does not enable the disabled runtime bridge.
`run_coverage_replay.py --app APP --commit HASH --isolate-adapters --output-root
STUDY` measures the same frozen corpus in a separate archive. See
`specs/planning/s2s_compile_latency_adapter_boundaries.md` for current status.
`--isolate-composition-type` is a separate experimental policy requiring
`--isolate-adapters --isolate-counters`. It uses bounded class metadata to publish
`BackendFunctionCompositionInput` outside the shared PCH, preserving exact class
bytes and shared-handle signatures. `verify_composition_type.py --app APP --out
PATH` checks identity, aliasing and real composition output. Use the same flag
with `run_coverage_replay.py` and a separate output root. See the type-publication
planning note for current measurement status; the adapter archive remains intact.


`--isolate-shared-carriers` extends the composition-type policy to the two LLVM
carriers in historical patch `e8e8a063`; it requires `--isolate-composition-type`.
All three types use `type_publication.py` with explicit field/dependency metadata
from `type_publication_metadata.json`. Unknown fields/types or unsupported header
consumers fail explicitly. `verify_shared_carriers.py --app APP --out PATH`
checks exact definitions, standalone headers, identity, shared references and
vector contents. Use a separate replay output root; see the shared-carrier
planning note for current timing/coverage status.


`--isolate-value-traits` extends the carrier policy to the unchanged value structs
`TypeTraitRow` and `TypeTraitTable`, with an explicit complete-row include in the
table's private header. `verify_value_traits.py --app APP --out PATH` proves
standalone headers and independent copies. `run_value_trait_probe.py --app APP
--isolate-value-traits --output-root PATH` repeats the real-writer field edit,
checks row and table/vector copy witnesses, and restores sources. Use the same
flag with the historical replay runner and a separate archive. See
`specs/planning/s2s_compile_latency_value_publication.md` for results and limits.

`--isolate-provider-traits` extends value publication to the unchanged provider
trait descriptor, using the same bounded metadata owner. It requires
`--isolate-value-traits`. `run_compound_value_probe.py --app APP --out NEW_PATH`
screens the twenty-field, three-record C++ analogue under the existing policy;
add `--private-provider` after preparing the provider-publication candidate.
The probe updates the real writer's active grouped implementation and validates
all added fields and independent copies. It preserves failed trials and restores
edited generated files. `verify_value_traits.py --provider --app APP --out PATH`
also proves standalone provider headers and copies. See
`specs/planning/s2s_compile_latency_compound_values.md` for historical replay limits.


`run_coverage_replay.py --isolate-provider-traits` passes the provider candidate
through the same fixed history protocol; also supply its prerequisite policy
flags. Completed coverage and paired fixture evidence are in
`results/2026-09-20/provider-coverage/`. See the provider-coverage planning note for
counts, exclusions and correctness scope.


`run_coverage_replay.py --sample-dir PATH` selects a separately archived history
manifest and exact patches; omitting it retains the original sample. Use a new
output directory so earlier coverage remains frozen.

`run_coherent_history.py --app SCRATCH_APP --repository READ_ONLY_REPOSITORY
--commit HASH --out NEW_PATH` reconstructs complete historical PHS states, except
the existing instrumented main harness. Repeat `--commit` for additional cases.
It rejects file-set/config changes requiring graph redesign, restores historical
smoke fixtures, validates before and after, and restores the pinned scratch state.
The generator/runtime remain current: this is not a historical toolchain replay.

`expanded_layout.py` accepts `--type-metadata PATH`; the default publication
metadata remains unchanged. `historical_type_metadata.py` projects exact historical
generated field definitions into the existing bounded roles, including a verified
old snapshot-name alias. It does not infer arbitrary semantic dependencies or
privately publish every class. See the history-expansion planning note and
`results/2026-09-20/history-expansion/` for retained failures, accepted measurements
and the next untested publication candidates.

`minimal_type_layout.py --app SCRATCH_APP` derives a separate per-type mirror
from the expanded policy, writing `latency-minimal.ninja` and `minimal-main`.
No project-type PCH or aggregate/scoped type header is reachable. Definitions
remain exact; callable/local type dependencies, inferred signature types and
accessed-field closures drive individual includes. The extractor is conservative
and bounded, not a replacement for a resolved AST. Unsupported ownership or
definition shapes fail explicitly. Its manifest records each active unit's
included types and each definition's hash/dependencies.

`verify_minimal_types.py --app SCRATCH_APP --out PATH` adapts the existing value,
carrier, composition and adapter witness sources to link against minimal objects;
first generate those fixtures with the existing provider-policy proof tools.
`run_coherent_history.py --minimal-types` uses this policy and its separate
executable, recording before/after graphs and dependency manifests alongside the
historical replay evidence. See the minimal-type-publication planning note for
current validation and measurement status.

`run_coverage_replay.py --minimal-types` selects the minimal mirror and executable
for the existing exact-context history protocol. Supply the same prerequisite
expanded-policy flags; use fresh result directories to preserve earlier results.

`run_coherent_history.py --minimal-types --inspect-only` captures only reachable
generated files in both historical states, without accepting new timing or
historical correctness results. It restores/builds/checks the pinned state on exit.
`analyze_compound_inputs.py --captured CASE_DIR --measured PRIOR_CASE_DIR --out
NEW_PATH` requires matching historical graphs/dependency manifests, classifies
changed C++ inputs, and records changed-header paths for unchanged consumers.
Its text categories do not prove semantic equivalence or that a rebuild is avoidable.
