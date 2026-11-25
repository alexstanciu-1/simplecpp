# Optimization-aware lowering and outlined default constructors
Doc Status: planning

Date: 2026-10-16. Implemented on the isolated accounting checkout based on
`c4c1f96b`; exact authored source hashes and native executable identity are archived
with this report. The original working compiler tree was not modified.

## Implemented boundary

`cpp_lowering_policy` selects latency-oriented lowering at application O0/O1 and
execution-oriented lowering at O2/O3. The latter preserves all **292 accounting
artifacts byte-for-byte** relative to the shared-handle checkpoint. Native recipes
now explicitly admit levels 0–3. Standalone emission defaults to the previous
execution spelling; the S2S CLI accepts `--optimization 0|1|2|3`. Combined callers
pass the application's optimization to both lowering and native configuration.

The backend retains the versioned policy identity with its fragment table and
invalidates those fragments when the profile changes without source edits.
Publication records the identity under its ready digest. Both native consumers
reject latency-profile output at O2/O3 and unknown identities before launching
Clang. Execution spelling can still be built at O0/O1 for a controlled baseline;
pre-policy publications use that compatibility interpretation. Native flags remain
part of PCH/object identity, independently of the lowering profile.

Eligible ordinary classes have no authored constructor, base, data-initialization
contract, polymorphic lifecycle or authored destructor. Every instance field has
an explicit constant or empty typed hash/vector default, with at least one
container default. Existing expressions move, in field order, to an out-of-line
constructor member-initializer list. The type's existing source group owns the
definition; `record_definition_text` now covers both static storage and constructor
definitions. Allocation wrappers and copy/move/destruction behavior are unchanged.
Uninitialized fields are excluded to preserve value-initialization behavior.

The accounting sample selects **25 classes**. It still has 93 generated units and
292 artifacts; no automatic splitting, partial classes or new grouping policy was
introduced. Compiler incremental/MT internals were not redesigned or audited.
See the [configuration contract](../architecture/native_cpp_build.md#application-optimization-and-c-lowering).

## Protocol and corrections

dev002: Ryzen 9 5950X, native Linux, Clang 18.1.3, Ninja 1.11.1, Mold 3.0.0 with
8 threads, compiler jobs 12, Ninja jobs 8, runtime-only PCH and OS caching allowed.
Application optimization varies as labelled; native compiler is O2 with debug
information. A separate unchanged runtime copy and Git-restored accounting source
are used. Each build starts a fresh native compiler process; Ninja owns cross-run
native reuse. This is not a retained semantic-incremental timing result.

The local bootstrap was interrupted because WSL compilation was slow. The same
converted/generated compiler source and O2 wrapper were built on dev002 in 154.96 s
(227 Ninja actions), using the existing legacy S2S and --no-stan policy. The first
native/PHP latency comparison correctly stopped: an eligibility check compared a
weak constructor field directly with null. The authored fix uses `weakref_get`.
One targeted regeneration initially lacked the project declaration catalog and was
refused; after supplying that catalog, the header remained byte-identical and the
policy implementation was regenerated and rebuilt. There were three successful
native link invocations through final parity (initial build, a redundant old-policy
rebuild, corrected-policy rebuild), plus the interrupted local build. Only the final
corrected compiler's complete matrix is reported below.

PHP references were generated locally because the remote PHP installation lacks
mbstring. All 93 accounting source hashes match the remote copy. All 214 core PHP
source hashes match the native source snapshot; the additional experiment driver
has its own recorded hash. The final native compiler SHA256 is
`6fe560dd88052d9e157b8af6a8cb505ea58cd9c5be8e8f61b0383a83dc002046`.
All copied runtime files remain unchanged and the accounting Git tree is clean.

## Accounting build results

The body edit is a semantics-preserving `20000` to `20000 + 0` change in
`workflow_checks`, deliberately exercising the expensive unit. Each configuration
runs full, unchanged, edit, revert and revert-unchanged builds. There are three
trials per O0/O1 baseline/outlined configuration and one O2 preservation trial:
**65 builds**, **13 PHP/native artifact comparisons**, and **1,855 accounting
checks per build**, all passing with identical output. Configurations were run
sequentially, not interleaved; these are bounded workstation observations.

Times are from each case's median-total trial, in seconds. Peak is the maximum
sampled process-tree RSS across that case's trials (MiB), not PSS; the 10 ms sampler
can miss very short peaks and counts shared pages in multiple processes.

| Application mode | Lowering | Full total | Full peak MiB | Workflow body edit | Edit peak MiB | Unchanged total |
| --- | --- | ---: | ---: | ---: | ---: | ---: |
| O0 | Previous execution spelling | 11.860 | 1537.8 | 4.042 | 383.6 | 1.055 |
| O0 | Outlined | **9.221** | **1518.0** | **2.137** | **279.5** | 1.055 |
| O1 | Previous execution spelling | 22.405 | 1667.8 | 8.508 | 390.1 | 1.035 |
| O1 | Outlined | **17.708** | **1617.5** | **4.729** | **272.8** | 1.033 |
| O2 | Unchanged execution spelling, one trial | 23.774 | 1682.4 | 8.816 | 390.1 | 1.035 |

O0 improves about 22% for full builds and 47% for this expensive body edit. O1
improves about 21% and 44%, respectively. Every full build compiles 93 generated
and three runtime objects plus one PCH. Every edit/revert compiles exactly one
object, reuses 95, reuses the PCH, and links once. All 26 unchanged matrix runs
launch zero Clang/PCH/link actions.

| Outlined case | C++ ready | Active compile/PCH | Link | Other distributed work | Exe ready | Total |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| O0 full | 0.742 | 8.058 | 0.055 | 0.367 | 9.147 | 9.221 |
| O0 body edit | 0.779 | 0.941 | 0.052 | 0.365 | 2.064 | 2.137 |
| O1 full | 0.764 | 16.560 | 0.042 | 0.341 | 17.661 | 17.708 |
| O1 body edit | 0.781 | 3.574 | 0.034 | 0.340 | 4.678 | 4.729 |

Active compile/PCH is the union of overlapping tool intervals, not their sum.
Other work is the residual across the invocation, not exclusively post-link work.
Exe readiness is successful link-driver exit. The full per-stage setup/parsing/
preparation/emission data is in `comparison.json`. For the O0 body edit these are
0.020/0.071/0.439/0.248 s respectively; O1 is 0.020/0.075/0.440/0.245 s.

Across 279 generated-object compilations per O0/O1 configuration, the nearest-rank
p80 moves 0.686 → 0.679 s at O0 and 1.335 → 1.191 s at O1. The observed maxima move
2.955 → 1.045 s and 7.453 → 3.729 s. The p80 ≤3 s target is met on this sample;
the remaining O1 maximum is not a universal latency bound.

## Clang frontend versus LLVM backend

Twelve additional isolated serial compiles use matching existing PCHs and
`-ftime-trace`, after the unprofiled matrix. These are single diagnostic observations,
not the parallel build measurements above. Each row shows previous → outlined time.

| Mode/unit | Frontend seconds | LLVM backend seconds | Driver wall seconds | Peak process RSS MiB |
| --- | ---: | ---: | ---: | ---: |
| O0 workflow_checks | 2.129 → 0.631 | 0.758 → 0.281 | 2.93 → 0.94 | 297.0 → 190.2 |
| O0 closing_checks | 1.258 → 0.500 | 0.418 → 0.180 | 1.71 → 0.70 | 228.1 → 178.2 |
| O0 warehouse_desk (owner) | 0.708 → 0.792 | 0.187 → 0.205 | 0.92 → 1.02 | 190.1 → 191.7 |
| O1 workflow_checks | 2.175 → 0.646 | 5.164 → 2.869 | 7.40 → 3.54 | 304.1 → 198.0 |
| O1 closing_checks | 1.273 → 0.508 | 2.659 → 1.568 | 3.97 → 2.10 | 231.5 → 186.6 |
| O1 warehouse_desk (owner) | 0.718 → 0.746 | 0.980 → 1.042 | 1.72 → 1.82 | 195.3 → 197.5 |

Work does move to the owner, but the caller savings exceed that increase in this
sample. Both frontend and backend work fall in the callers. Nested trace events
overlap; their durations must not be added as independent cost categories.

## Policy invalidation and runtime witnesses

One reused native Ninja directory proves:

- O0 execution → latency profile: exactly 58 generated objects rebuild, matching
  the old depfile graph and changed artifacts; 38 objects and the PCH are reused.
- Identical profile rerun: zero actions.
- O0 → O1 and O1 → O2: all 96 objects and the PCH rebuild, followed by one link.
- O2 no-op: zero actions.
- O2 with latency publication: native exit 1 after C++ publication and before any
  Clang/link invocation. The host prints generic `Exception`; the harness was
  corrected to check phase/action evidence rather than match exception text.
- Recovery republishes execution output and succeeds. Ninja rebuilds 58 generated
  objects after the intervening publication mtimes changed; PCH/runtime objects
  remain reusable. No additional historical content cache was introduced.

The standalone runtime witness performs 200,000 iterations, each allocating two
four-hash Bag objects and an Item, checking alias mutation and independent empty
containers, summing 1,400,000, and verifying exactly 200,000 Item destructions.
Six configurations pass one warm-up plus seven measured runs each (42 timed runs),
alternating order between rounds. This is an allocation-heavy witness, not a general
application performance guarantee.

| Mode | Previous median wall | Outlined median wall | Peak RSS, previous → outlined |
| --- | ---: | ---: | ---: |
| O0 | 0.6952 s | 0.7059 s | 4.00 → 4.00 MiB |
| O1 | 0.05718 s | 0.05718 s | 3.50 → 3.75 MiB |

O0 is approximately 1.5% slower in median, with overlapping observed ranges
(0.678–0.708 versus 0.673–0.711 s). O1 medians are indistinguishable at this scale;
the short runs include process-start/scheduling noise. O2/O3 execution-spelling
witnesses also pass (0.05537/0.05323 s medians). No O2+ lowering change is adopted.

Focused local proofs additionally pass four compiled application modes, six retained
policy switches, independent-container/alias behavior, exclusion cases, release
mismatch rejection, and a default-value edit changing only the owning `.cpp`.
Existing publication/recovery/native-adapter checks and the 13-build Ninja suite
also pass. These are bounded proofs, not a full language regression or retained
native incremental/MT certification.

## Evidence and remaining scope

[Evidence directory](optimization_policy_20261016/) contains source/driver identities,
shape proof, focused-test summaries, comparison data, reproducible scripts and a
compressed raw remote archive. Remote workspace:
`/home/alex-ai/accounting-opt-policy-20261016/bench`. Initial rejected comparison
remains under `attempt-1`; it is excluded from accepted results.

Keep unit splitting and multi-file classes deferred. Broader constructor eligibility,
empty-container expression simplification and future execution-oriented patterns
remain separate experiments under CH15. The runtime is unchanged. No custom JIT,
new link layout, PCH composition change or compiler incremental/MT redesign was made.
