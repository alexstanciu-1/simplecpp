# Compound value-layout historical assessment
Doc Status: planning

Date: 2026-09-20

## Historical fidelity

Commit `7d58f273` changes twelve PHS source files under the historical
`compiler/v2/src` path. An exact-context audit against the pinned current workload
finds only two applicable record files. Nine other files have changed context or
file ownership, and `compile/pipeline/fixture_runner.phs` has retired. Reversing
just the two applicable records would remove fields still used by current callers.
The full patch is therefore excluded from exact replay in these surroundings.
No fuzzed or partial patch is counted as that historical commit.

The original 333-second evidence covers a build/gate cycle. It is motivation, not
a native baseline against which a new Ninja timing can be directly compared.

## Bounded experiment

Keep the existing value-publication output policy frozen. Directly edit its
scratch generated C++ to add the historical twenty field types under `latency_`
names: eight fields to `ProviderTraitDescriptorRow`, eight to `TypeTraitRow`, and
four to `TypeTraitTable`. Existing fields remain intact. Extend the real
`type_traits::row_from_descriptor` implementation to transfer all eight new row
fields. Add executable checks for their values, independent row copies, table
scalar copies, stored row values and table/vector independence (sizes 1:2).

This is a three-record layout analogue plus one writer and a test harness. It does
not include the historical new 53-method owner or the twelve-file feature change.
The generated C++ is an experimental artifact, not a production source fix.
Native value representations stay intact. Semantic S2S integration is a non-goal.

Run Clang/mold/Ninja with twelve jobs and cache launchers disabled. Measure native
build wall time, including linking and scheduling; add the assumed 1.5-second
frontend only when reporting estimated totals. Run three equivalent dirty work
sets if fast; retain one successful screen if above twenty seconds. Verify the
full application smoke suite before/after and restore all edited generated files.

The existing policy privately publishes the trait row and table, but not the
provider descriptor. This is deliberately a coverage test of the current policy,
not a claim that all three value dependencies have already been narrowed.

Status: bounded timing comparison completed and archived. Follow-up attribution and corpus verification are complete; see `s2s_compile_latency_provider_coverage.md`. The historical fixture remains blocked under both policies. Reproducible driver:
`tools/compile_latency/run_compound_value_probe.py`.

## Initial policy result

The corrected three-record analogue takes **169.197 seconds native**, in one
successful screening trial: **581 objects + project PCH + link**. All twenty
added field witnesses, the eight-field real writer transfer, row/table copy and
vector checks pass, along with the full application smoke suite. This is a
confirmed exception to the prior policy; the nine passing historical code cases
from the earlier fixed sample do not cover this compound layout change.

The first development screen took 169.069 seconds but failed the runtime witness.
Its driver edited an inspectable individual callable artifact, while the build
compiled a grouped definition. It is excluded from accepted timing results. The
corrected probe locates the unique grouped definition, asserts it is a native
Ninja input, and updates that active owner as well. Both generated patches and
the failed logs are retained. This reinforces that generated-file changes alone
do not prove the intended implementation was compiled.

## Provider publication candidate

Reuse `type_publication.py` for the unchanged 21-field value struct
`ProviderTraitDescriptorRow`, with explicit field/type metadata pinned to the
original generated header. `--isolate-provider-traits` requires the prior value
policy. No new accessors, pointer representation or runtime bridge are introduced.
Measure full conversion separately, then repeat exactly the corrected compound
probe with only the provider header publication changed. Verify standalone
provider-header compilation and independent copies in addition to the application
witnesses. This candidate does not imply that the previous fixed corpus has been
rerun under the new policy.

The candidate manifest identifies seven active provider consumers: frontend model
builder groups 1/2, primitive ABI adapter groups 1/2, generated semantic type-ref
provider groups 2/3, and type-traits group 3. The earlier hand-written `compile/`
source audit found three owners; generated semantic providers are an additional
owner. The generated graph is the relevant bounded consumer inventory. Full
conversion and execution remain necessary to validate that inventory.

### Failed conversion: inferred value dependencies

The first provider conversion failed in `type_traits_group_4.cpp`: its code calls
a value-returning helper directly as an argument and iterates over an `auto`
vector result. Neither expression spells `ProviderTraitDescriptorRow`, but both
require its complete definition. Seven lexical consumers were therefore an
incomplete dependency set. The failed compiler diagnostics are retained; no
incremental candidate timing is accepted from that failed conversion.

The common publication owner now accepts explicit `complete_value_producers`
metadata. For this record it lists the four generated functions returning the
record or its vector. Calls to these functions publish the complete type even
when inferred locals/temporaries hide its name. These facts are extracted from
verified generated declarations for this bounded experiment. A resolved AST must
supply equivalent expression and template-instantiation dependencies in a real
S2S; lexical type-name scans are not sufficient semantic analysis.

Conversion resumes after that correction. Its remaining native time is reported
only as setup completion, not as a full cold-conversion measurement. The exact
same compound edit will be measured only after a correct full baseline exists.


## Completed timing comparison

| Policy | Native time | Native work | Estimated total |
|---|---:|---|---:|
| Prior value policy, correct compound probe | 169.197 s, one successful screen | 581 objects + project PCH + link | 170.697 s |
| Provider publication with value-producer dependencies | 8.275 s median of three | 25 objects + link; no PCH | 9.775 s |

Candidate trials: 8.369 / 8.275 / 8.208 seconds. All use the same native work set
and reach twelve concurrent compiler jobs. All twenty field checks, the eight-field
real writer transfer, independent row/table copies and vector sizes 1:2 pass, as
does the full application smoke suite. Both accepted experiments restore their
edited generated files. This is a sequential bounded comparison, not a randomized
population study. With only about 0.225 seconds of median headroom under the
assumed total target, this compound case should be described as near the limit.

Final proofs pass for exact provider/row/table definitions, standalone headers,
independent copies, shared carrier identity/mutation, composition output, all 52
adapter normalizers, and 1,053 unique full-application link inputs. The known
literal fixture compiles through the current compiler to LLVM and executes with
exit code 42. Original external source hashes and scratch source hashes match;
the intentional main harness is unchanged. The final native build is a no-op.

### Historical feature check remains unresolved

The exact `7d58f273` int64 local-subtraction fixture is blocked by the current
compiler runner (completed=0, blocked=1); no LLVM artifact is produced. The
literal control passes. This is an explicit failed feature check, not a successful
historical replay. A paired baseline run of this fixture was not performed, so
preexisting incompatibility versus regression has not been established. Do not
claim that all historical feature validation passed, or infer that publication
caused the block. Investigating this limit is next, before promoting the candidate
as a generally validated replacement. The prior 9/9 fixed-corpus result belongs
to the previous value policy; it has not been rerun with provider publication.

Evidence: `tools/compile_latency/results/2026-09-20/compound-values/`, including
exact patches, accepted trials, failed development screen, failed conversion
log, blocked historical fixture, passing control, policy hashes and final graph.

## Lessons and next assessment

A three-record layout change can approach ten seconds while preserving native
value semantics, once incidental global dependencies are removed. Parallelism
alone did not rescue the original 581-object rebuild. However, the dependency
model must account for returned temporaries, inferred locals and container
instantiations. A type-aware S2S can obtain those dependencies from its resolved
expressions; lexical type-name scans miss real consumers.

Next establish a paired baseline result for the blocked historical fixture, then
rerun the frozen historical corpus under the candidate. Retain both failures and
context exclusions. The complete twelve-source/53-method historical feature
change remains unmeasured; this twenty-field analogue covers only its compound
layout component. Do not turn the passing median into a universal save-latency
claim or count this analogue as an additional historical replay.


## Follow-up completed

The paired baseline check now establishes that the blocked int64 fixture predates
provider publication: both policies export identical backend_text/reason5 rows.
The new candidate also retains 9/9 runnable historical code-case passes. The
initial unresolved-attribution statements above describe the earlier assessment;
see `s2s_compile_latency_provider_coverage.md` for the completed follow-up. The
historical feature itself and full twelve-source replay remain unproven.
