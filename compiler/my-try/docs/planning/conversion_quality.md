# Compiler conversion quality and frontend direction
Doc Status: planning

Started: 2026-10-16. Status: discussion only; no implementation approved.

Current focus: improve the existing PHP→PHS converter. The shared my-try frontend
proposal is parked, possibly for v0.3; that version is not a commitment.

## Scope and user questions

Review the compiler itself first: authored convertible-PHP → generated PHS →
generated C++. Keep compiler execution costs distinct from the costs of programs
that the compiler subsequently generates. PHP remains authored source.

The user explicitly asks, for each reviewed piece:

1. Was there a more elegant solution in the destination code (PHS only)?
2. Are we losing performance or memory efficiency in PHS and C++?

Additional proposed review dimensions: semantic fidelity (including identity,
mutation, evaluation order, errors and lifetime), conversion/build cost,
diagnostic provenance, deterministic output, incremental correctness/recovery,
portability and maintenance cost. These are review dimensions, not findings.

User proposal for discussion: compile convertible-PHP with my-try through a custom
parser that populates the same structures, then generate PHS during lowering.
The user has authorized planning documentation, not compiler changes or migration.

## First review: lexical type lookup

Reviewed `Scope_Lookup::types()` in [lookup.php](../../03_parse/scopes/lookup.php),
its helpers in [structures.php](../../03_parse/scopes/structures.php), and the
[native Key_Storage_List binding](../../../../runtime/include/modules/compiler/key_storage_list.hpp).
This path exercises names, imports, shared records, collection snapshots and weak
scope links. Dependency-observing lookup remains separately owned by analysis;
this review does not propose merging it with plain lexical lookup.

### Evidence and limits

Inspected existing artifacts under `/tmp/scpp-ch13-native-20261016/`:

- `source/03_parse/scopes/{lookup,structures}.php`
- `phpp/03_parse/scopes/{lookup,structures}.phs`
- `phpp/.prism/generated/03_parse/scopes/{lookup,structures}.cpp`
- `candidate.json`, `summary.json`, and `phpp/.prism/build/build.ninja`

Both input PHP files matched the current checkout by SHA-256:

| File | SHA-256 |
| --- | --- |
| lookup.php | `429f54392f4b629e1103d54b1edfde54134b7aa212b1254586347145ee9ef5a8` |
| structures.php | `1c4e4dd38e5607e6b3a07efde5b2c734ad4671725d0835cc58ccc48f1091339b` |

Artifacts used the sibling `simple_cpp_02` toolchain; the recorded candidate hashes
for `key_storage_list.hpp`, `vector_t.hpp` and `shared_p.hpp` matched this checkout.
This does not establish that every toolchain file matches. Existing build flags
were `-O0 -g` with call-depth guards enabled. No new conversion, build, benchmark or
assembly inspection was performed. Temporary artifacts may expire; the observations
below retain the inspected shapes without claiming optimized timing results.

### Observations

| Finding | Interpretation and owner |
| --- | --- |
| PHP's annotated array return becomes `vector<type_definition_i>`, then `vector_t<shared_p<type_definition_i>>`. | Precise types and shared identity survive. No record cloning or mixed fallback in this path. |
| PHS uses an explicitly typed `$items` local before `named()`. | Clear but mechanical. Direct `$this->types->named($name)` would be more concise; current portable guidance uses typed receivers to accommodate type-blind lowering. Direct access has not been proved on this path. |
| Native collection-local assignment copies a shared state handle. | It aliases membership, without copying the collection or allocating another state. Reference-count overhead remains possible. |
| `named()` builds a vector snapshot of candidate handles. | Result allocation and handle retention are real work; the PHP helper already promises an independent membership snapshot. Return-by-value does not itself imply repeated deep copies: moves/elision can transfer the vector. |
| Native `named()` constructs `std::string(key.native_value())` before lookup. | Copies key bytes; long keys may allocate even on misses. Native runtime implementation opportunity. |
| Snapshot append does not reserve the known bucket size. | Multiple matches can cause vector growth. Runtime-local opportunity, with benefit dependent on pool sizes. |
| Qualified-name handling repeats separator scans and member extraction. | Authored algorithm opportunity: compute needed components once. Bounded repeated linear work, not a demonstrated quadratic algorithm. |
| Scope arguments/locals copy shared handles; parent/publication reads acquire weak links. | Ownership bookkeeping exists. Its optimized cost is unmeasured; eliminating ownership requires a lifetime argument. |
| Import foreach uses `value_copy()`. | Copies an import handle per iteration, not an import record or a whole vector per iteration. |
| Removal clears an ordered slot; later insertions append. | Long-lived Key_Storage_List storage can grow with historical insertions. Removed handles release records; retained empty slots are compaction debt, not proof of an object leak. |
| Conditions contain nested typed helper calls and casts. | C++ readability issue; optimized overhead is unproved. Do not equate source verbosity with machine-code cost. |

Provisional assessment: generated PHS is understandable and faithful. Prioritize
temporary lookup keys and snapshot allocation, retained slots under incremental
churn, then ownership traffic. These priorities are hypotheses for measurement,
not approved fixes or claims that native execution is slower than PHP.

A borrowed candidate view would change the snapshot/lifetime contract. It is not
a spelling cleanup and must not be substituted without discussing mutation safety.

## Proposed convertible-PHP frontend

Deferred by user decision on 2026-10-16. Retained below for future discussion;
its proposed next steps are not active work.

### Existing ownership and constraints

The [documentation index](../README.md) already describes other frontends
normalizing toward PHS-shaped concepts. The [model](../architecture/MODEL.md)
owns retained identity and lifecycle; parser-invoked collection belongs to
`03_parse/collect/`, shared semantic preparation to `04_analyze/prepare/`, and
target spelling/emission to backend workers. No second semantic authority should
be introduced in a PHP parser or PHS emitter.

The current [converter](../../../../tools/php_portability/README.md) is structural,
with explicit annotation/function policies and a narrow declaration/trait index.
It currently uses PHP tokenization. The my-try tokenizer skips comments, including
documentation comments; it does not currently interpret portability annotations.
A native-capable frontend must deliberately preserve/decode semantic annotations
and provide its own supported lexical path rather than depend on host token_get_all.
This is broader than attaching a parser to an existing token stream.

### Candidate architecture (assistant recommendation, not an agreement)

```text
convertible-PHP → portable-profile frontend/normalization ─┐
                                                       ├→ shared syntax + collection
strict PHS      → existing PHS frontend ─────────────────┘
                          → shared preparation
                              ├→ C++ backend
                              └→ canonical PHS backend (optional artifact)
```

Populate shared syntax and collected identities through their owning protocols.
Do not manufacture settled preparation facts from the PHP frontend. Preserve PHP
locations for diagnostics, including origins of normalized/synthesized constructs.
Reuse nodes when the meaning matches; reject or explicitly normalize mismatches.
Do not force PHP-specific behavior into a superficially similar PHS node.

Two distinct possible PHS products need a decision:

- A normalized syntax export for inspecting conversion, potentially before semantic
  preparation. It cannot promise resolved types or semantic validity.
- A prepared canonical PHS artifact with enough explicit syntax to preserve selected
  semantics when reparsed. This needs a representability and equivalence contract.

Neither requires dumping C++ helper choices or internal facts into PHS. A PHS backend
should read shared syntax/facts and own its traversal; it must not mutate semantic
state or add a universal AST visitor. It also needs its own publication/consumption
protocol if retained alongside C++ artifacts.

If PHS is mandatory for delivery to another toolchain, emit it and reparse it there.
If native compilation is the objective, direct shared-model → C++ avoids an otherwise
redundant text generation/reparse cycle. Optional PHS remains useful for review and
independent equivalence checks. These are alternatives, not an approved pipeline.

### Pros

- Shared symbol/type/lifetime knowledge could remove accommodations such as typed
  receiver aliases when they exist solely to help the old structural lowerer.
- Source PHP diagnostics could identify unsupported semantic combinations before
  C++ compilation, with fewer intermediate-file-only errors.
- Both inputs could use the same prepared decisions, C++ backend and incremental
  lifecycle instead of relying on separate lowering interpretations.
- A canonical PHS output could be a reviewable explanation of the normalized program.
- Direct native output could skip generating and reparsing PHS; actual speed and
  retained-memory effects still require measurement.
- The compiler could exercise its own supported language and runtime contracts.

### Cons, risks and limits

- Portable annotations, helper mappings, exception adapters, collection bindings,
  traits, value records and weak/ownership conventions all need an explicit migration
  inventory. Sharing syntax does not supply these contracts automatically.
- Executable PHP and strict PHS do not have identical semantics. Scope, copy behavior,
  text helpers, nullability and ordering must follow the portable profile's intended
  contract; general PHP acceptance is a non-goal.
- The retained model includes spans, collection identities, dependencies and recovery
  handoffs. A second frontend must participate correctly, not merely allocate nodes.
- PHS serialization is a real backend: names/imports, declarations, literal spelling,
  conversions, ownership and synthesized constructs must survive reparsing. Some
  prepared decisions may lack a suitable source spelling; reject unsupported export
  explicitly rather than silently change meaning.
- Self-compilation requires feature coverage plus a reproducible bootstrap seed and
  escape path. A compiler feature can become a prerequisite for building the compiler
  implementing that feature. PHP-hosted development helps but does not remove this.
- Shared implementation can reproduce the same bug on both input paths; independent
  PHP expectations and independent behavior assertions remain necessary.
- No automatic fix for snapshot allocation, key copying or retained holes follows
  from a frontend change. Those costs have their own runtime/model owners.
- Migration spans frontend, preparation integration, backend, lifecycle and tooling.
  Implementation would require a separately agreed bounded plan, not a local patch.

### Options and proposed next design step

| Option | Benefit | Main cost |
| --- | --- | --- |
| Keep converter → PHS; evaluate my-try as the PHS consumer | Separates backend readiness from new frontend work; smallest comparison baseline. | Keeps structural conversion accommodations and intermediate parsing. |
| Add portable frontend → shared model; make PHS optional | Strongest reuse and direct compilation path. | Frontend/profile integration, bootstrap readiness and optional exporter work. |
| Add portable frontend → shared model → mandatory PHS → compiler | Useful when a PHS deliverable or independent downstream toolchain is required. | Duplicate processing and semantic round-trip obligations. |

Proposed next step: inventory the actual compiler source requirements against my-try
and the portable policy, then choose one representative declaration/collection slice
on paper. Determine whether PHS is a required deliverable or a diagnostic artifact
before designing its backend. No implementation is started by this document.

Future proof gates, if implementation is approved: supported-profile admission and
rejection; original-PHP versus native behavior; direct versus exported/reparsed PHS
behavior; fresh/incremental/recovery equivalence; original source diagnostics;
bootstrap reproducibility; separately measured conversion time, optimized execution,
allocations and retained memory. Exact byte parity is required only where contractual.

## Current direction: improve the existing converter

Keep the structural converter and existing pipeline. Improve explicit local
translation, usability and evidence without introducing whole-program type
inference, changing ownership automatically or reviving the deferred frontend.
The portable-PHP skill remains the guide for interpreting these boundaries.

### Code inspection, 2026-10-16

Inspected [Converter](../../../../tools/php_portability/src/converter.php),
[Container_Type](../../../../tools/php_portability/src/container_type.php),
[publication CLI](../../../../tools/php_portability/convert.php) and
[Project_Cache](../../../../tools/php_portability/src/project_cache.php).
No converter behavior was changed or newly tested.

- `methodSignature()` currently accepts nullable-null and matching bool literal
  defaults, and rejects other defaults. Scalar literal defaults are a bounded
  extension candidate, not a proven target capability on every selected toolchain.
- Type spellings and admission rules are distributed among signature, local,
  storage and container annotation handling. A shared explicit-type representation
  could clarify the grammar while keeping site-specific admission separate.
  This must not silently widen supported annotations or infer missing types.
- Tokens preserve source file and line, including expanded trait origins. `Node`
  retains only a line, and `emit()` returns text without an output-origin map.
  Converter failures have some provenance already; downstream PHS/C++ diagnostics
  do not gain an original-PHP source map from this emitter.
- Emission mixes retained trivia with synthesized punctuation/declarations. This
  explains mechanical spacing and inconsistent presentation; formatting should
  be addressed at owned emission boundaries, never with global text replacements.
- The CLI stages all changed output strings in `$pending` before publication.
  This protects against publishing after a conversion error, but retains changed
  output bytes in memory. File-backed staging is a possible future optimization
  only if measured peak memory warrants its IO/cleanup complexity.
- Existing discovery/token caching, dependency fingerprints and content-aware
  publication already avoid substantial repeated work. Preserve these guarantees;
  measure cold, unchanged and one-file/trait edit cases before changing caches.

### Candidate priorities (assistant proposals, not approved implementation)

| Candidate | Benefit | Boundaries and proof needed |
| --- | --- | --- |
| Explicit scalar literal defaults | Reduces source boilerplate and keeps generated signatures closer to intent. | First identify real compiler methods affected. Prove omitted/explicit arguments and literal representation on the actual target. Keep enum/symbol/expression defaults separate. |
| Consistent explicit-type parsing | One interpretation of nullable/container/record annotations; clearer diagnostics and fewer spelling-specific branches. | Start with one touched family; keep declaration-site restrictions explicit and preserve all accepted/rejected cases. No semantic symbol lookup. |
| Original-source output mapping | Trace generated failures to authored PHP, especially expanded traits and synthesized constructors. | Preserve file and span through emitted fragments; map generated ranges without claiming full C++ diagnostic integration. Agree retained mapping/publication shape before implementation. |
| Canonical PHS presentation | More readable generated declarations and stable review diffs. | Preserve comments, literal bytes, precedence and explicit type boundaries. Do not assume syntax accepted by my-try is supported by the current downstream generator. |
| Converter cost visibility | Identify real conversion time/peak-memory costs before optimizing. | Add timings/measurements only in an agreed slice; separate conversion from C++ compilation and compiler execution. Existing `--stats` reports cache counters, not a full performance breakdown. |

Recommended first discussion: scalar literal defaults, using actual compiler
methods and historical conversion accommodations to choose one bounded need.
If that has little present benefit, prefer original-source traceability rather
than a general converter refactor. These are design choices still to be agreed.

The lookup review's collection snapshot allocation, native key copies and retained
slots are runtime costs, not PHP→PHS converter defects. Removing typed receiver
locals, introducing borrows/moves or changing classes to value records cannot be
justified by text shape alone. Keep such changes outside the current converter slice.

## Decision log

- 2026-10-16: user requests analysis of the compiler conversion flow and explicitly
  adds PHS elegance and PHS/C++ performance/memory questions.
- 2026-10-16: first lookup review recorded above; no code changes or measurements.
- 2026-10-16: user requests persistent planning notes and discussion of a portable-PHP
  frontend feeding shared structures and PHS lowering. Design mode remains active.
- 2026-10-16: user parks the shared frontend idea, possibly until v0.3, to preserve
  current focus. Discussion now concerns improvements to the existing PHP→PHS
  converter. Design-only status remains; no implementation slice is approved.
- Deferred questions: adopt a new frontend at all; whether PHS output is mandatory; syntax-only
  versus prepared PHS export; migration scope and bootstrap prerequisites.
