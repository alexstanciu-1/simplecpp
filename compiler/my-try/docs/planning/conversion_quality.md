# Compiler conversion quality and frontend direction
Doc Status: planning

Started: 2026-10-16. Typed string-join slice implemented and focused proofs passed.
Other proposals remain discussion-only; see the implementation checkpoint below.

Current direction: pause further converter slices and define convertible-PHP as a
well-defined profile, with stricter validation and an explicit mapping to Simple C++
concepts. The completed slices and their proofs remain evidence for that design.
The shared my-try frontend proposal is still parked, possibly for v0.3; that version
is not a commitment.

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
The initial authorization covered planning only. The user subsequently approved
implementation and focused proofs for the typed string-join slice; migration remains deferred.

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

The initial scalar-default suggestion is superseded by the concrete source review
below: typed string joining was selected and subsequently approved for implementation.
The checkpoint below records the completed bounded slice.

The lookup review's collection snapshot allocation, native key copies and retained
slots are runtime costs, not PHP→PHS converter defects. Removing typed receiver
locals, introducing borrows/moves or changing classes to value records cannot be
justified by text shape alone. Keep such changes outside the current converter slice.

## Concrete workaround review and selected string-join design

Reviewed in the isolated clone on 2026-10-16. Git history provides causation,
not merely a guess from unusual source shape:

| Workaround | Current source / history | Priority and interpretation |
| --- | --- | --- |
| Manual comma joining replaces `implode` | `05_backend/cpp/syntax.php::comma_list`; repair `84716579` replaced two closure-rendering joins and introduced this helper. | First design slice: restores a general operation and may reduce intermediate string work. |
| Negative field defaults moved into constructors | `03_parse/structures/structures_construction.php`; `04_analyze/prepare/data/structures.php`; repair `480b2543`. `prepared_data_entry` also has a constructor solely setting `input_position = -1`. | Small subsequent syntax slice; no material speedup expected. |
| Integer defaults replaced by separate diagnostic methods | `03_parse/parser.php::error_message` / `error_message_at`; repair `4a8aa89c`. | Proven converter restriction, but the resulting current-position/explicit-position API is useful. Do not automatically undo it. |
| Enum defaults replaced by named forwarding entrypoints | `04_analyze/prepare/semantics/expressions.php::prepare` and `prepare_for_consumer`; `semantics/value_boundaries.php::decide_value` and `decide`; portability guide records this accommodation. | Lower priority: names now communicate meaningful modes. |

The function map has no join entry. The target already has `implode` for
`vector<string>` under the [builtin contract](../../../../specs/builtins/string/implode.md).
The native PHP adapter delegates to `scpp::str::join`, taking the vector by const
reference. Initial runtime inspection found an in-place `concat_assign` helper. Subsequent
inspection of the actual old generated `comma_list` found that lowering does not
use that helper: it emits `result = result + ...`, rebuilding the growing prefix.
The implementation checkpoint measures that emitted form, correcting the earlier
assumption. Potential costs also include foreach string copies and separator-plus-element
temporaries; no allocation count was measured.

### Selected public contract

Authored PHP: `q_implode(string $separator, array $pieces): string`, with explicit
`vector<string>` intent on portable declarations. Generated PHS:
`implode($separator, $pieces)`. Exactly two arguments, separator first.

- Join strings in list order; put separators only between elements.
- Empty list yields an empty string; singleton returns its contents.
- Empty elements and an empty separator are valid.
- Preserve bytes, including NUL and invalid UTF-8 received/constructed at runtime.
  This is byte joining, not code-point interpretation or UTF-8 validation.
- Do not coerce integers, booleans, null, arrays or objects into strings.
- Host adapter validates a packed PHP list (`array_is_list`) and string elements,
  then uses native PHP `implode`. Invalid-list/element failure:
  `InvalidArgumentException`; freeze stable diagnostic expectations in the proof.
- Do not bind the PHP facade directly to raw PHP `implode`: its coercions and
  broader signature would weaken this bounded contract.

The converter remains type-blind: it checks mapped-call arity and emits the target
name. It cannot prove a remote argument is `vector<string>`. Wrong element types
belong to host adapter checks and downstream typed validation. The native builtin
also accepts typed hashes; this slice neither exposes/proves that portable input
nor promises that the converter rejects it. A hard native vector-only rejection
would require a separate typed facade and is outside this direct-binding proposal.

### Selected implementation scope

| File | Intended change |
| --- | --- |
| `tools/php_portability/runtime/strings.php` | Add `scpp\compat\implode(string $separator, array $pieces): string`; validate list/string payloads, preserve bytes, delegate to `\implode`. Bootstrap already loads this file. |
| `tools/php_portability/function_map.php` | Register `q_implode`: PHP implementation `scpp\compat\implode`, target `implode`, arity `2`. Use existing dispatch; no `Converter::sequence` special case. |
| `tools/php_portability/runtime/global_functions.php` | Regenerate through `generate_global_functions.php`; do not hand-maintain the wrapper. Confirm its exact two-required-argument signature. |
| `compiler/my-try/05_backend/cpp/syntax.php` | Replace only the two `self::comma_list(...)` calls in closure emission with `q_implode(', ', ...)`; remove the now-unused private helper. Both inputs already have typed vector intent. |
| `tests/portability/string_join.py` (proposed new focused proof) | Host behavior/rejection, conversion shape/arity, native execution and artifact inspection. Follow existing CommandRunner/shared-runner conventions with isolated outputs. |
| `specs/portability/string_join.md` (proposed supporting contract/proof note), portability reading links, this plan | Record bounded contract, actual target revision, evidence and exclusions. Existing language builtin semantics need no expansion. |

No change is expected in the C++ runtime, parser, annotation grammar, project cache,
framework installer or native string helper implementation. Existing map fingerprints
already invalidate affected conversion output. If the actual target cannot compile
this binding, report the owning blocker rather than silently widening the slice.

Do not rewrite `CPP_Types` or other incremental string builders in this slice:
creating vectors merely to join them can add allocations. No generic bulk replacement
of concatenation, no hash/mixed/coercing/legacy-swapped overloads, no UTF-8 scanning,
no runtime reserve optimization and no claim of general self-compilation readiness.

### Required proofs and acceptance

1. **Host contract:** independently specified byte results for empty/singleton/list,
   empty parts, empty/multibyte separators, Unicode text, runtime NUL and invalid
   UTF-8. Verify input list contents are unchanged. Reject non-list keys and each
   non-string element category. Do not compare two implementations as the only oracle.
2. **Converter/facade:** `q_implode` emits one `implode` call with original argument
   order and explicit vector intent preserved; wrong arities reject with source
   location. Check generated facade freshness. Reuse existing mapping/cache tests
   for rule invalidation, failed-conversion publication and unchanged-output behavior.
3. **Focused native behavior:** build a small converted fixture on the actual selected
   downstream toolchain with framework assembly; run the same independent byte cases.
   Construct binary inputs at runtime, since binary literals remain outside the
   converter profile. Test invalid typed payloads as target type failures, without
   claiming the structural converter detects them or that runtime exception modes
   must match a statically rejected native program.
4. **Native shape:** inspect generated PHS/C++ to confirm the typed join binding;
   vector passes to the builtin by const reference, without mixed boxing or a newly
   synthesized per-element join loop. Record any call-site temporary/vector copy;
   runtime signature inspection alone does not prove call-site behavior.
5. **Compiler integration:** run focused closure model/behavior checks through the
   shared runner; compare representative emitted closure artifacts before/after
   byte-for-byte, including empty and nonempty capture/parameter lists, and execute
   representative generated programs. A PHP-host compiler run alone does not prove
   the converted native compiler uses the new helper successfully. Before closing
   the slice, rebuild the native compiler and compare those focused outputs and
   executions. Do not launch the full cumulative native suite automatically.
6. **Performance evidence:** separately compare old loop and join in an optimized
   native fixture on short realistic lists and larger lists/strings. Verify bytes,
   consume outputs, exclude fixture setup from timing and repeat trials. Inspect or
   measure allocations if claiming their reduction; RSS is not an allocation count.
   A performance claim requires results. No fixed speedup threshold: cleaner code
   with equivalent behavior is useful, but investigate a material regression before
   adoption. Debug-build results must not stand in for optimized performance.

Record source/toolchain hashes, flags (including call-depth instrumentation), commands
and proof scope. These criteria originated as a design plan; the checkpoint below records their
execution and limits. The full cumulative native harness remains outside this slice.

## Typed string-join implementation checkpoint — 2026-10-16

The user authorized this slice in the isolated clone. Implemented the checked
`scpp\compat\implode` adapter, `q_implode` function-map entry and regenerated PHP
facade. Only the two closure capture/parameter joins in `CPP_Syntax` changed;
its private `comma_list` helper was removed. No other compiler algorithm, runtime
join implementation, parser rule or type-inference rule changed. The maintained
[portable contract](../../../../specs/portability/string_join.md) owns usage guidance.

Evidence root: `/tmp/scpp-string-join-20261016/`. Base revision:
`8471657979ff4e2c621dc79fa8a10a8643a39558`, plus this slice. Temporary evidence may
expire; retained tests and the benchmark fixture provide reproducible entrypoints.

- `focused-final/summary.json`: nine exact-byte PHP/native join cases, seven host
  invalid-input cases, three converter arity rejections and a native `vector<int>`
  type rejection. Covers binary NUL/invalid UTF-8, nonmutation, facade freshness,
  warm reuse and unchanged prior output after rejected conversion.
- `valid-generated.cpp` in that directory shows direct `php::implode(..., pieces)`
  calls. The rebuilt compiler's generated `syntax.cpp` similarly passes both local
  vectors directly; the existing builtin takes const references, with no vector
  temporary or mixed boxing at those call sites.
- `integration/summary.json`: closure model/behavior suites pass; all 83 existing
  generated closure artifacts match the pre-change baseline byte-for-byte. The
  compiler itself was rebuilt, then three focused cases (empty, single, multiple
  capture/parameter lists) matched PHP artifacts byte-for-byte and executed with
  independently specified exit codes 8, 7 and 14. Native driver also checks repeated
  no-op preparation. This is not a broad native rejection/incremental audit.
- Native compiler SHA-256:
  `83068cd277028679ab501166ceea0be315bc465d09dbf338cecb3e919a96a6dc`.
  `integration/source_hashes.json` identifies all compiler inputs; final closure
  source matches the copied native-build input exactly. Build used Clang 18, debug
  `-O0 -g` and call-depth guards, `--no-stan`, with parked LLVM excluded. The cold
  native build recorded 660.619 seconds; this is one observed build, not a benchmark.
- `prologues.py` passes. Existing `run.py` and `check.py` fail on rejection expectations
  that also fail from pristine HEAD: respectively an explicitly annotated nullable
  local and a nullable interface return. Baseline logs retain that evidence; no
  broad-suite pass or unrelated expectation repair is claimed.
- Initial proof attempts required harness corrections: explicit CLI execution for
  process-exit/build commands and byte payloads for artifact decoding. These did not
  require production changes. The retained proof scripts pass after those corrections.

### Optimized performance experiment

Retained fixture: `tests/portability/fixtures/string_join_bench.cpp`. It compares the
old emitted loop body with the existing typed native `implode`, keeping both calls
noninline and call-depth guards disabled. Clang 18 `-O2`, seven trials per size,
alternating order; inputs are built outside timing and output is consumed. The final
run occurred after the native build, replacing preliminary contended measurements.

| Parts / bytes per part | Old median ns/join | Typed join median ns/join | Ratio old/new |
| --- | ---: | ---: | ---: |
| 3 / 12 | 185.19 | 79.28 | 2.34 |
| 128 / 80 | 31,177.95 | 2,115.01 | 14.74 |
| 2,048 / 80 | 3,333,805.58 | 315,461.36 | 10.57 |

Raw results: `benchmark.csv`, `benchmark.json`; compiler command and version in
`benchmark-logs/` and `benchmark-final-logs/`; source/toolchain hashes and flags in
`evidence.json`. This is a Linux native helper microbenchmark, not an end-to-end
compiler speedup, host-PHP benchmark, allocation-count proof or peak-memory result.
The generated shape avoids rebuilding the accumulated prefix, but no numerical
memory reduction is claimed. Runtime join reservation tuning stays outside scope.

Reproduction commands for the two focused proofs are in the portable contract.
No cumulative native suite was run. The new frontend remains parked.

## Signed integer field defaults: plan and implementation (2026-10-16)

Design baseline: clone revision `cd14f71022816b55eaeff010c0bae427bd30fa85`.
The initial analysis was documentation-only. The user subsequently approved this
slice and its focused proofs, limiting compiler migration to
`prepared_data_entry::$input_position`. Implementation evidence is recorded below;
the shared-frontend idea remains parked.

### Observed owner and failure

`tools/php_portability/src/converter.php`, `Converter::referenceClass()`, consumes
one significant token after a plain scalar field's `=`. Both the ordinary `int`
and nullable `?int` branches require that token to be `T_LNUMBER`. A sign is a
separate token, so `-1` fails before its magnitude is read. Static fields pass
through the same scalar-default branches. This is a local structural grammar gap,
not missing compiler type inference or a missing runtime integer operation.

The downstream generator already routes property defaults through
`renderInitializerExpr()` and emits ordinary defaults as C++ in-class member
initializers (`generators/php/src/Generator/Generator.php`, class property loop).
Its unary-expression renderer has plus and minus cases. Static fields use a
separate definition path. These are implementation anchors, not a native proof
that every newly accepted initializer works, especially nullable/static cases.

Concrete compiler workarounds:

| Source under `compiler/my-try/` | Sentinel assignment | Migration judgment |
| --- | --- | --- |
| `04_analyze/prepare/data/structures.php`, `prepared_data_entry` | `input_position = -1` in its only constructor statement | Recommended first consumer: declare the default and remove this constructor. |
| `03_parse/structures/structures_construction.php`, `object_construction_children_iterator` | `next_argument = -1` | Later analogous consumer; retain the constructor and promoted source parameter. Prove target-type-first traversal. |
| `04_analyze/prepare/data/structures.php`, `reference_cycle_vertex` | `visit_index = -1`, `low_link = -1` | Later analogous consumer; retain actual target/body/label initialization. Prove fresh graph-visit state. |
| `04_analyze/prepare/data/work_records.php`, `type_semantic_work` | `source_revision = -1` after `parent::__construct(...)` | Defer: declaration initialization changes its position relative to the base constructor; audit base construction and observable reads first. |

Historical change `480b2543` also records moving negative initializers into
constructors for the iterator and cycle vertex. This supports identifying those
assignments as portability workarounds, rather than guessing from their spelling.

### Proposed grammar and exact converter change

Recommend adding one private, field-specific integer-literal reader in
`Converter`, used by both ordinary and nullable integer defaults. It takes the
already-read first token and returns initializer text; callers keep ownership of
`null`, field type/visibility, the terminating `;`, and property-node emission.
Do not manufacture a fake integer token containing a unary expression.

1. An existing unsigned `T_LNUMBER` remains accepted with its existing spelling.
   Do not silently tighten existing positive literals while adding signs.
2. For a first token `+` or `-`, consume exactly one following significant token.
   Require `T_LNUMBER` and decimal spelling matching `^(0|[1-9][0-9]*)$`.
   Return sign plus magnitude, without numeric evaluation or a float conversion.
   Whitespace/comments between the sign and magnitude follow existing significant
   token handling. Preserve `+` and `-0` text; their values must be proved.
3. Reject every other first token or signed operand with a source-located
   integer-field-default diagnostic. Keep the existing semicolon check so a
   trailing expression cannot become an accepted prefix.
4. Route only explicit `int` initializers through this helper, including `?int`
   when the initializer is not `null`. Leave bool/string/named defaults on their
   existing validation paths. This removes the duplicated integer-literal rule
   from the two scalar branches without redesigning the whole class parser.

The new signed domain deliberately excludes hex/octal/binary, digit separators,
parenthesized literals, repeated signs, constants, calls and arithmetic. Existing
unsigned-token acceptance is unchanged; broader numeric spelling is separate debt.
On a 64-bit host, the intended new domain is decimal magnitudes representable as
`T_LNUMBER`, including `9223372036854775807` and its negative. Do not claim that
`-9223372036854775808` is covered: tokenization of its positive magnitude and PHP
property type compatibility must be recorded in the boundary proof. Reject a
`T_DNUMBER` operand rather than special-casing it into an integer. The proof must
record `PHP_INT_SIZE`; this slice adds no cross-host integer-width emulation.

No change is proposed to function bindings, the runtime, my-try semantic
preparation, or C++ emission. Keep the separate `uint32` annotated-field branch,
`@scpp-struct` value-record rules, readonly fields, constructor/promoted parameter
defaults and method defaults unchanged. If the focused native proof exposes a
required change in another owner, report that blocker before expanding the slice.

### First compiler consumer and expected quality/cost

Change only `prepared_data_entry::$input_position` to `public int $input_position = -1;`
and remove its assignment-only constructor. Keep its explanatory field comment.
The intended PHS is `public int $input_position = -1;`, using the converter's
existing ordinary nonnullable property form, with no synthetic constructor or
helper call. Other negative-sentinel migrations are not part of this first slice.

This makes the default visible at its declaration and eliminates boilerplate in
PHP and PHS. The C++ expectation is the same integer member with a direct default
initializer. It does not reduce object size or introduce new storage. Optimized
constructor assignment may already cost the same; do not claim a measurable
speedup, allocation reduction, or peak-memory reduction. Inspect generated code
for an unexpected conversion/boxing/helper, but do not add a benchmark without
such evidence. Existing nullable representation costs are not caused by the sign.

### Focused proofs required before acceptance

Add a retained `tests/portability/signed_integer_fields.py` proof with independent
expected values and output-shape assertions; reuse the shared runner (12 slots,
PHP-FPM by default, documented CLI exceptions for process-exit/build commands).
Run dependency chains in order. Suggested proof groups:

| Group | Required evidence |
| --- | --- |
| Converter positives | Instance/static `int` and `?int`; `-1`, `-17`, `+17`, `-0`, `+0`; public/protected/private coverage; sign separated by whitespace/comment. Exact field text and no injected constructor. |
| Unchanged paths | Positive/zero defaults, required uninitialized int, nullable null, bool/string/named defaults, valid uint32 defaults. Run existing `nullable_fields.py` alongside the focused proof. |
| Rejections | Missing magnitude, double signs, signed float/exponent, signed nondecimal/separated digits, parentheses, constants/calls, and trailing arithmetic. Negative uint32 and unchanged parameter/readonly/value-record restrictions remain rejected. Keep checks about this grammar, not broad PHP validation. |
| Boundaries | Record host tokenization and PHP property acceptance, then conversion for positive/negative maximum decimal magnitude and out-of-range magnitude including the minimum-integer spelling. Verify no overflow or implicit float coercion is introduced. |
| Failure publication | Source filename/line on rejection; pre-existing generated output/cache stays intact. Successful output must not be partially replaced by a rejected initializer. |
| PHP versus native fixture | Build converted fixture with `--no-stan`; independently assert signed values, nullable null versus present zero/negative, static initial value/shared mutation, fresh-instance reset, default visible in constructor body and explicit constructor override. Compare PHP and native results. |
| Generated C++ | Direct integer default, unchanged mapped member type, no mixed box/allocation/helper. Inspect both nullable construction and the static definition, not only PHS output. |
| Compiler integration | Construct `prepared_data_entry` and assert `input_position === -1`, then set/read a nonnegative index and create a fresh entry. Exercise record/sequence preparation consumers and compare expected results before/after migration. Rebuild the compiler itself for the native version of this proof; a compiled standalone fixture alone is insufficient. |

For compiler integration, retain a focused driver using the existing compiler
bootstrap/native build path, verify the native build input contains the migrated
class, and save source/toolchain hashes and commands. Check generated program
behavior for a record initializer and a sequence initializer with independently
specified results; compare compiler artifacts where stable. Do not require the
compiler's own generated C++ to be byte-identical: moving a constructor assignment
to a declaration intentionally changes it. Do not run cumulative native_validate.

The older broad `run.py`/`check.py` suites have known baseline rejection-expectation
failures recorded in the typed-join section. Keep those distinct from new failures;
this slice does not include repairing them.

Implementation closeout would update the portable authoring guide (and its linked
contract if needed), retain the focused proof/fixtures, and replace this proposed
status with measured evidence and explicit limitations. Stop if removing the
constructor exposes target construction semantics requiring cross-owner work.
The initial design entry used source/history inspection only. Subsequent
implementation and execution evidence is recorded separately below.

### Implemented and proved (2026-10-16)

The approved slice is complete on the clone based on `cd14f710`. The converter
now owns signed integer field literal parsing in `integerFieldDefault()`, shared
by ordinary and nullable integer fields. Only
`prepared_data_entry::$input_position` migrated in compiler code; its assignment-only
constructor was removed. Other sentinel sites and all deferred grammar remain
unchanged. The current supported boundary and reproduction command live in
[the portability contract](../../../../specs/portability/signed_integer_fields.md).

Evidence root: `/tmp/scpp-signed-fields-20261016/`.

- `fields-final/summary.json`: focused PHP/native fixture passes; 39 converter
  rejections, output/cache preservation, unchanged neighboring defaults and the
  existing nullable-field regression suite pass. The host is 64-bit PHP.
  `tokens.json` records the minimum-integer magnitude as `T_DNUMBER`;
  `logs/php-minimum.stderr` independently records PHP's typed-property rejection.
- `fields-final/generated.txt`: plain fields use `int_t<>` and direct signed
  integer initialization. Nullable fields retain `nullable<int_t<>>`; static
  definitions use the same direct literal construction. No new helper or box is
  introduced. The compiler header similarly emits
  `int_t<> input_position = (-static_cast<int_t<> >(1));`, with no declared
  `prepared_data_entry` constructor. No runtime/backend change was needed.
- `baseline/` and `baseline-logs/` retain pre-migration consumer results.
  Both `data_preparation` and `data_records` pass after migration. All 28 record
  C++ artifacts match the baseline byte-for-byte. The preparation suite checks
  semantic facts/recovery internally and does not publish comparison artifacts.
- `integration/summary.json`: compiler itself rebuilt; its driver checks initial
  `-1`, mutation to `4`, and an independent fresh entry at `-1`. Two focused
  record/sequence cases match PHP compiler artifacts byte-for-byte and execute
  with independently specified exit codes **21** and **12**. Record input order
  differs from field declaration order; sequence evaluation order is observable.
  The existing native driver also checks repeated no-op preparation.
- Native compiler SHA-256: `b58dc693fdd0b20c0f207df8934e4b658d53254b744174a153fdbfa8cc879593`.
  `integration/source_hashes.json` records the build input, including the migrated
  class and proof driver; the copied class matches the final authored source.
  `candidate_hashes.json` identifies the converter, migrated source and retained
  proof scripts. `review-logs/toolchain.stdout` records Clang 18. The cold compiler
  build took 602.086 seconds, an observation rather than a benchmark.
- All proof commands/results are journaled under their `logs*/commands.json`.
  Native builds used `--no-stan`; LLVM stayed parked. Baseline execution fell back
  to PHP CLI because the sandbox prevented FPM socket binding; final focused
  execution used the shared runner's FPM pool, with explicit CLI build/rejection
  exceptions. No broad native validation or performance benchmark was run.

Commands used for the final focused runs:

```sh
python3 tests/portability/signed_integer_fields.py --results /tmp/scpp-signed-fields-20261016/fields-final
python3 tests/portability/signed_integer_fields_compiler.py --results /tmp/scpp-signed-fields-20261016/integration --baseline /tmp/scpp-signed-fields-20261016/baseline --resume
```

The initial proof attempts exposed two harness assumptions: the generated native
integer type is `int_t<>` with explicit literal casts, and `data_preparation` does
not publish `.cpp` files. Correcting these checks required no production change.
Final retained scripts pass. Cleaner PHP/PHS is established; no speedup or memory
reduction is claimed. Inherited-field migration remains deferred.

## Integer decrement syntax: plan and implementation (2026-10-16)

Design baseline: clone revision `a62a9689`. The initial review was analysis only.
The user subsequently approved decrement support and focused proofs, limiting
compiler migration to the two for-condition counters. Execution evidence is
recorded below; the original recommendation follows for context.

### Why this is a real converter workaround

`Converter::sequence()` in `tools/php_portability/src/converter.php` admits
`T_INC`, `T_PLUS_EQUAL` and `T_MINUS_EQUAL`, but not `T_DEC`. Ordinary `--` therefore
falls through to `unsupported syntax`. A pair of separate `-` punctuation tokens
is not PHP's decrement token. This is structural admission asymmetry; the
converter does not need to infer an operand type or rewrite an expression.

History identifies two exact compiler adaptations in `bc76b7d5` (control-flow
portability repair):

| Current source under `compiler/my-try/` | Historical change | Current purpose |
| --- | --- | --- |
| `04_analyze/prepare/semantics/loops.php`, `Loop_Preparation::for_header()` | `$remaining--;` became `$remaining -= 1;` | Prepare only the final condition as the loop's boolean test; earlier conditions are evaluated for effects. |
| `05_backend/cpp/syntax.php`, `CPP_Syntax::generate_for()` | `$remaining--;` became `$remaining -= 1;` | Emit earlier conditions as discarded values and the final condition as the native test. |

Both sites still contain the workaround. Each starts with `q_count($conditions)`,
decrements once per condition, then tests for zero. The count cannot become
negative on this traversal. This is stronger evidence than treating every `-= 1`
in the compiler as an accidental workaround.

A separate confirmed example is `Parser_Run`'s `$this->position -= 1;` in
`03_parse/parser.php`: repair `feab90db` replaced `$this->position--;`. Leave that
parser migration out of the first slice. Other decrements in runtime-call arity,
cycle stacks and reverse traversal have not all been traced to conversion repairs;
do not bulk-rewrite them.

### Recommendation and alternatives

Prioritize **decrement admission plus the two for-condition counters**. The benefit
is modest but concrete: complete an already-supported scalar mutation family and
remove two history-proven accommodations without changing an API or model.

| Alternative | Why not first |
| --- | --- |
| Integer method-parameter defaults | `methodSignature()` still admits only nullable-null and matching bool defaults. `4a8aa89c` confirms parser diagnostics were split because of integer defaults. However, current `error_message()` / `error_message_at()` names usefully distinguish current and explicit positions; restoring a negative-sentinel API is not automatically an improvement. A future slice should identify a consumer that benefits without undoing that distinction. |
| Enum method defaults | `prepare` / `prepare_for_consumer` and `decide_value` / `decide` are proven accommodations, but also communicate meaningful modes. Constant/name emission and default-argument declarations need separate proof. |
| Eliminate typed receiver locals or traversal-state records | These can encode target typing, aliasing and ownership contracts. Removing them is not a lexical converter extension. |
| More signed field migrations | Capability already implemented; remaining migrations are consumer cleanup, and inherited initialization ordering remains a separate risk. |

### Exact proposed change and ownership

1. Add `T_DEC` beside `T_INC` in the ordinary `sequence()` token admission list.
   Preserve the token's text/order through existing token-node emission. Do not
   lower decrement to assignment: postfix must return the old value, prefix the
   new one, and a receiver must not be duplicated.
2. Change only the two listed `$remaining -= 1;` statements to `--$remaining;`.
   Prefix is intentional because neither site consumes the old value. Keep the
   subsequent zero comparison, traversal order and prepared facts unchanged.
3. Add a retained `tests/portability/decrement.py` focused proof and a small
   compiler integration driver, following the existing signed-field proof pattern.
   Update the portability authoring/operator guidance and this evidence section
   only after actual support is demonstrated.

The existing structural boundary admits the operator token in both prefix and
postfix positions. It cannot honestly promise an integer-only admission filter
without type analysis. The proved authoring domain should be explicit ordinary
integer locals and integer fields, with integer loop updates. Leave strings,
mixed/nullable values, unsigned-width boundaries, container element mutation,
overloaded targets and compound side-effect ordering outside the demonstrated
contract. Do not claim the converter rejects all those forms. Invalid typed
operands belong to the target type/operator checks, as with existing `++`.

The downstream anchors already exist: `Generator::renderExpr()` handles
`AstKind::PRE_DEC` and `POST_DEC` through `renderAssignmentTarget()` and emits
`(--target)` / `(target--)`. Native integer operators delegate to `prefix_dec` /
`postfix_dec` in `runtime/include/scpp/generated/operator_detail.hpp`; postfix
copies the previous integer value. The operator profile also distinguishes valid
assignable targets from plain values/temporaries. These source observations are
not a completed PHP/native proof. No runtime, backend, semantic-model or generic
operator framework change is expected. Report any newly discovered cross-owner
requirement before expanding the slice.

### Proofs needed if approved

- **Conversion shape and checker agreement:** prefix/postfix locals, explicitly
  typed integer fields, discarded statements and a `for` update. Preserve operator
  position and distinguish decrement from `- -1` and a negative literal. Check
  successful reuse and unchanged generated-file timestamps on a second conversion.
- **PHP/native values:** independent expected pairs for prefix result/new value
  and postfix result/new value; positive, zero and modest negative starting values.
  Prove field mutation and fresh-object independence. Include one supported
  receiver-producing call with a counter to prove single receiver evaluation;
  keep sequencing explicit rather than combining competing mutations in one call.
  Exclude overflow boundaries from PHP/native equivalence claims.
- **Failure boundaries:** invalid mutation targets must fail at their owning
  PHP parse or target type-check boundary. Retain a native invalid typed-operand
  witness rather than pretending the converter validates types. Confirm malformed
  input reports source context and preserves previously published output/cache.
  Re-run signed-field rejection cases so admitting `T_DEC` in expressions does
  not admit `--1` as a field initializer through its separate literal reader.
- **Generated C++:** prefix/postfix retain correct value semantics and one target
  evaluation. The two migrated counters should use native prefix decrement, with
  the same integer type and no helper allocation or synthetic temporary in PHS.
- **Compiler consumer proof:** capture the existing `tests/control_flow.php`
  artifacts before migration, run it after, and require byte parity for unchanged
  user-program emission. Add independent empty/single/multiple-condition `for`
  cases, including observable effects in earlier conditions and a false final
  condition. Prove that only the last condition controls continuation and that an
  empty header has the existing behavior. Execute the generated programs.
- **Rebuilt compiler:** rebuild my-try with exactly the two source edits, retain
  source/toolchain hashes, and run those same focused cases through PHP and native
  compilers. Compare artifacts and independent execution results; include repeated
  no-op preparation. A standalone generated decrement fixture is insufficient to
  claim the compiler itself works natively. Use the shared 12-slot runner,
  PHP-FPM/default fallback and `--no-stan`; leave LLVM and broad native_validate
  outside this slice.

### PHS elegance and runtime costs

`--$remaining` directly states the counter operation and is carried unchanged into
PHS. `-= 1` is already readable, so this is a small improvement rather than a major
simplification. Both operate on the same scalar with the same object layout and no
heap allocation. Prefix avoids requesting the postfix old-value snapshot; an
optimizer may already make discarded postfix, prefix and subtraction equivalent.
No speedup or memory reduction is established. Inspect optimized output only if a
cost difference is suspected; no benchmark campaign is warranted by spelling alone.

Documentation follow-up: the current adaptation summary in
`docs/portability/conversion_review.md` still says negative integer field sentinels
must use constructors. The signed-field capability now makes that blanket advice
stale. This review only records the drift; a future implementation closeout should
link the supported signed-field contract while retaining the inherited-ordering
caution. Do not reinterpret historical checkpoint descriptions as current limits.

### Decrement implementation and evidence (2026-10-16)

Completed on the clone based on `a62a9689`. `T_DEC` is admitted beside `T_INC`;
existing token emission preserves both operator positions. Only
`Loop_Preparation::for_header()` and `CPP_Syntax::generate_for()` migrated from
`$remaining -= 1;` to `--$remaining;`. No runtime, backend-generation rule, semantic
model, API or other compiler counter changed. The current authoring boundary is
recorded in [the decrement contract](../../../../specs/portability/decrement.md).
The current portability adaptation summary now links this capability and corrects
its stale signed-field advice, retaining the base-constructor ordering caution.

Evidence root: `/tmp/scpp-decrement-20261016/`.

- `operators-final/summary.json`: standalone PHP/native proof passes. Six local
  value cases cover prefix/postfix at positive, zero and negative starting values.
  Independent assertions also cover integer fields, fresh objects, both forms on
  receiver-producing calls with exact invocation counts, discarded statements,
  loop updates, negative literals and subtraction of a negative literal.
- Checker/converter agreement and unchanged conversion reuse pass. Four malformed
  mutation/field-initializer cases reject with source context and leave all
  published output/cache bytes and timestamps intact. Invalid string decrement
  passes structural checking/conversion and fails native compilation with
  `cannot decrement value of type 'string_t'`, demonstrating the intended boundary.
- `operators-final/valid-generated.phs` and `valid-generated.cpp` retain prefix/
  postfix shape and one receiver expression. Both migrated compiler `.cpp` files
  are checked for `(--remaining)`; authored files match the copied native-build
  inputs. No synthetic PHS assignment or operand duplication is introduced.
- `operators-final/signed-fields/summary.json`: the retained signed-integer-field
  proof passes, including its 39 rejections, PHP/native fixture, nullable regression
  suite and output preservation checks. Decrement admission does not broaden the
  separate field-literal reader.
- `baseline/` and `baseline-logs/` retain the pre-migration control-flow suite.
  Post-migration `control_flow.php` passes and all **137** C++ artifacts match
  byte-for-byte. This comparison does not mean every baseline artifact was executed
  anew; independent native execution in this slice is the four cases below.
- `integration/summary.json`: compiler itself rebuilt and four focused cases pass
  PHP/native compiler artifact parity plus emitted-program execution: empty
  conditions → **3**, single condition → **2**, multiple conditions → **43**,
  false final condition → **10**. Earlier false/effect expressions do not determine
  continuation; the final condition does. The existing native driver also verifies
  repeated no-op preparation produces unchanged artifacts.
- Native compiler SHA-256: `85c246f134b199a6f08f5391adc1d251f8a04e20a862dda349cea14d347f3ac2`.
  `integration/source_hashes.json` records all build inputs;
  `candidate_hashes.json` identifies the converter, two migrated sources and proof
  scripts. Commands, executor modes and outcomes are journaled in each `logs/`
  directory. Clang 18 is recorded in `operators-final/logs/toolchain.stdout`.
- The cold compiler build took **1004.267 seconds**; it overlapped standalone
  proof work initially. This is an observed duration, not a benchmark or evidence
  of a decrement-related performance change. Builds used `--no-stan`; LLVM and
  broad native_validate remained excluded. No speedup or memory reduction is claimed.

Final commands:

```sh
python3 tests/portability/decrement.py --results /tmp/scpp-decrement-20261016/operators-final
python3 tests/portability/decrement_compiler.py --results /tmp/scpp-decrement-20261016/integration --baseline /tmp/scpp-decrement-20261016/baseline
```

The initial standalone attempt exposed the checker's process-exit contract under
FPM. Its two checker calls now explicitly use CLI execution through the shared
runner. That harness correction required no production change; final retained
proofs pass. The original design-only findings above remain provenance, not an
outstanding implementation request.

## Integer method defaults for body preparation: plan and implementation (2026-10-16)

Design baseline: clone revision `2e819bae`. The initial review was analysis only;
the user subsequently approved integer method defaults and focused proofs, with
compiler edits limited to `prepare_statements()` and its two local default-zero
calls. The prior decrement, signed-field and typed-join slices remain complete.
Implementation evidence is recorded separately below.

### Concrete remaining workaround

`Body_Preparation::prepare_statements()` in
`04_analyze/prepare/semantics/bodies.php` currently requires `int $start`.
Portability repair **`d5cc41c4`** removed its original `= 0` and added explicit zero
arguments in both `prepare_statement()`'s function-body branch and
`prepare_block()`. Those exact accommodations remain in the current source.

The parameter has an ordinary, meaningful default: visit the body from its first
statement. The implementation skips indexes less than `$start`, then composes
statement completion. It has a real nondefault caller too:
`prepare_callable_statements()` passes a computed `$start`, which can be **1**
after `Constructor_Preparation::prepare_base()` processes an explicit parent
constructor call. That call must stay explicit; restoring a default must not
reprepare the already-handled base-call statement.

There is also an explicit-zero call in `04_analyze/prepare/worker.php`'s
`prepare_file_body()`. Leave it unchanged in the first slice: explicit zero remains
valid, and that entrypoint need not migrate to prove the new capability.

The cause remains `Converter::methodSignature()`: after `=`, only nullable `null`
and matching nonnullable boolean literals are accepted. `int $start = 0` fails
with the method-parameter-default diagnostic. Constructor parameters have a
separate parser; field defaults now have their own integer literal reader. This
is a missing declaration grammar case, not missing semantic preparation.

### Recommended scope

**Add nonnullable integer method-parameter literal defaults and restore this one
body-preparation default.** Compiler edits should be confined to `bodies.php`:

1. Restore `int $start = 0` on `prepare_statements()`.
2. Omit the trailing zero in the two local calls identified above.
3. Preserve the explicit `$start` forwarding call and the worker's explicit zero.

This is a better first consumer than undoing the parser diagnostic split:
`error_message()` and `error_message_at()` now express useful current-position
versus explicit-position modes. Keep that API. Likewise, enum-default wrappers
`prepare_for_consumer` and `decide_value` communicate modes and stay out of scope.
String/float defaults, enum/constants, expressions, nullable integer non-null
defaults, constructor/promoted parameters and callable-reference adaptation are
not part of this proposal. Other already-supported zero defaults/arguments are
not a reason for repository-wide cleanup.

### Converter owner and downstream considerations

- Generalize `integerFieldDefault()` to a truthfully named private integer-literal
  reader shared by fields and the new method branch. Preserve existing field
  acceptance and diagnostics; pass a small diagnostic context or retain a thin
  field wrapper rather than duplicating sign/magnitude parsing. Do not turn this
  into a generic constant-expression parser.
- Recommend reusing the exact field lexical contract: an unsigned `T_LNUMBER`
  keeps its spelling; one optional sign is accepted only before a plain decimal
  magnitude that is also `T_LNUMBER`. Consequently positive base-prefixed and
  digit-separated forms inherit the existing field rule and need explicit method
  proofs; signed variants remain rejected. This asymmetry must be documented,
  not silently advertised as arbitrary PHP integer constant syntax.
- In `methodSignature()`, add the explicitly nonnullable `int` branch after
  existing nullable-null/bool handling. Use the shared reader, then the existing
  separator and `$optionalSeen` logic. Preserve rejection of a required parameter
  following an optional one. Do not widen `?int` beyond its current `null` default.
- This signature owner is shared by ordinary instance/static methods, expanded
  traits, abstract declarations and interfaces. Do not pretend a change there
  affects only the selected static compiler method. The proof must cover those
  admitted declaration forms; do not misuse the existing container-return flag
  as a new default-policy switch.
- Existing generator anchors are `renderParams(..., $includeDefaults, ...)` and
  the method declaration/definition emitters. Declarations pass `true`; out-of-line
  definitions pass `false`. Expect one typed C++ default in the declaration, no
  duplicate definition default and no synthetic forwarding overload. This needs
  actual native proof, especially across files.
- Virtual defaults have a known language-boundary risk: C++ defaults are selected
  using the static declaration, while PHP method invocation can differ when an
  override supplies a different default. Prove ordinary calls and representative
  interface/abstract calls with matching defaults. Include a mismatched-default
  probe to expose the actual target boundary; do not claim general PHP override
  equivalence or add symbol inference. If honest support requires a new cross-owner
  dispatch/default protocol, stop and report the blocker before widening scope.

No change to my-try's user-language default semantics, runtime, function map or
native argument binder is expected. Its own compiler implementation merely starts
using a capability in the existing PHP→PHS conversion path. Do not patch generated
C++ if a downstream mismatch appears.

### Required focused proofs

| Proof | Independent acceptance criteria |
| --- | --- |
| Literal conversion/checking | Zero, positive, signed positive/negative and signed zero defaults retain intended PHS. Check unsigned hex/octal/binary/separator forms because the proposed shared grammar admits them. Required argument followed by multiple optional integer arguments is preserved. |
| Omitted versus explicit calls | PHP/native results for omitted arguments, explicit zero, nonzero and negative replacements, and partial omission. Exercise instance/static visibility, expanded trait and declaration-only forms; use matching defaults for the basic polymorphic witness. |
| Cross-file generated declarations | A caller in a separate file can omit the argument; inspect header default and definition without a repeated default. No added wrapper, nullable box or helper call. Verify the method's required nondefault parameters remain required. |
| Rejections and stability | Wrong bool/string/float/null operand for `int`, constants/calls/arithmetic, repeated signs, signed nondecimal spelling and out-of-range magnitude reject. Required-after-optional still rejects. Existing nullable-null/bool defaults and constructor rules stay unchanged; rerun signed-field and relevant method/nullable signature proofs. Failed conversions preserve prior output/cache and source diagnostics; unchanged conversion reuses output. |
| Numeric bounds | Record PHP width and host property/parameter acceptance at maximum magnitude and out-of-range magnitude. Keep `T_DNUMBER` rejected; do not introduce a minimum-integer float coercion. The shared field reader's previously proved boundary must not regress. |
| Real body-preparation behavior | Compare omitted start with explicit 0 on a small known statement body. Exercise explicit start 1 separately and assert the first statement is skipped while later statements and completion facts are handled. Use a deliberately unsuitable first statement or observable preparation facts so a broken skip cannot pass unnoticed. |
| Compiler regression and native integration | Capture pre-change `control_flow.php`, `constructors.php` and `inheritance.php` focused artifacts; compare after migration where emitted user-program bytes should be unchanged. Rebuild the compiler and run ordinary body/block cases plus an explicit-parent-constructor case through PHP/native compilers. Compare artifacts and execute programs against independent results, including exactly-once parent initialization and child-body effects. Retain no-op preparation and fresh/incremental agreement in the relevant existing suites. |

Use the shared 12-slot runner, PHP-FPM with documented CLI exceptions, isolated
results and source/toolchain hashes. Native checks use my-try's `--no-stan` policy;
LLVM and cumulative native_validate stay outside this slice. A standalone default
fixture does not establish that the compiler itself was rebuilt successfully.
Existing broad-suite baseline failures remain separate from new regressions.

### PHS elegance and cost assessment

A default belongs at the declaration when it expresses the usual operation. Here
`prepare_statements($body, $context)` says “prepare the whole body”, while an
explicit start preserves the exceptional skip. This removes repeated zero literals
without adding another entrypoint or obscuring the meaningful offset.

PHS should retain the default directly. C++ is expected to pass the same integer
zero at omitted calls; default arguments do not themselves add a runtime branch,
allocation or field. No speedup, object-size reduction or memory improvement is
claimed. The value is source/API clarity and removing a documented converter
restriction. Inspect emitted code for unexpected wrappers; benchmark only if
inspection identifies a real new cost.

### Integer method defaults: implementation and evidence (2026-10-16)

Completed on the clone based on `2e819bae`. `integerLiteral()` now owns the shared
field/method literal grammar with site-specific diagnostic context; existing field
diagnostic wording is preserved. `methodSignature()` adds only the nonnullable
`int` branch. Compiler changes are exactly the restored `int $start = 0` and its
two local omitted-zero calls in `bodies.php`. Explicit computed constructor skips
and the worker's explicit zero are unchanged. No runtime, dispatch protocol or
backend implementation changed.

The [current contract](../../../../specs/portability/integer_method_defaults.md)
documents the admitted declaration families, numeric spellings and matching-default
requirement across overrides. Signature/nullable/field guides now link it. The
already-touched authoring guide also had an overlooked sentence claiming postfix
decrement was rejected; that stale advice now links the completed decrement slice.

Evidence root: `/tmp/scpp-method-defaults-20261016/`.

- `methods/summary.json`: standalone PHP/native proof passes for **12** literal
  forms, omitted/explicit/partially omitted arguments, visibility, static/instance
  methods, expanded traits, matching-default interface/abstract declarations and
  separate-file callers. **19** structural rejections preserve prior output/cache
  and source attribution. A missing required argument passes structural conversion
  and fails native compilation, preserving the existing validation boundary.
- `methods/model.hpp` and `model.cpp`: typed defaults occur in declarations and
  are absent from definitions. The compiler header similarly declares
  `int_t<> start = static_cast<int_t<> >(0)` once; the implementation takes an
  ordinary `int_t<> start`. The two local generated calls omit it, and the computed
  offset call still supplies `start`. No new forwarding overload/box is introduced.
- `methods/virtual-default-boundary.json`: **PHP returns 9, native returns 4** for
  a base default of 4 and overriding default of 9 through a base-typed caller.
  This is a deliberately retained nonportable case, not a parity success.
  Matching defaults across the override/interface chain are required for omitted
  polymorphic calls. The converter remains type-blind and does not validate that
  requirement. Supporting differing defaults with PHP dispatch semantics would
  require separate cross-owner work and was not attempted. The migrated compiler
  method is static and does not encounter this boundary.
- `methods/logs/php-minimum.stderr` records PHP rejecting the minimum-integer
  spelling as a typed parameter default because its magnitude is a float token.
  The nested signed-field proof records the 64-bit host/token boundary; no numeric
  evaluation or float-to-int coercion was added to the converter.
- `methods/signed_integer_fields/summary.json` passes the retained PHP/native field
  proof, **39** rejections and nullable-field regression suite.
  `methods/method_signatures/` and `methods/nullable_parameters/` pass their PHP and
  conversion regressions; these two older runners were not rerun against their
  historical pinned native checkout. Relevant current native declaration forms
  are covered by the new standalone fixture.
- Two obsolete rejection expectations were updated deliberately: the method-signature
  suite now rejects `int $x = 1 + 2` rather than the newly supported literal `1`;
  the field regression's method case rejects `?int $value = -1` rather than the
  newly supported nonnullable form. Both new accepted forms have positive proof.
- Pre-migration `baseline/` and post-migration integration artifacts match exactly:
  **137 control-flow + 60 constructor + 59 inheritance = 256 C++ artifacts**.
  Existing suites include their semantic/recovery/incremental checks. This does
  not claim fresh native execution of all 256 programs.
- `fixtures/method_default_body.php` (under `tests/portability/`) is injected into
  the native proof driver and also executed directly in PHP. Omitted start and
  explicit zero prepare the first transfer and return break completion. Start 1
  skips an invalid first continue, leaves its facts absent, and prepares the later
  break. Omitted/zero calls on that same invalid-first body fail as expected.
  This is a targeted transfer-preparation witness, not whole-AST validation.
- `integration/summary.json`: the compiler itself rebuilt. Three focused cases
  match PHP/native artifacts and emitted programs return **7** (ordinary body),
  **9** (block), and **12** (parent initialization once, then child effect).
  The direct witness runs inside each native invocation; the existing driver also
  checks no-op preparation. Final copied compiler source equals authored source.
- Native compiler SHA-256: `4ef6480eb73d1d4321de822440f0a6cd332f108d7eb122f7809f756e1f71fc8f`.
  `integration/source_hashes.json` includes compiler and injected driver inputs;
  `candidate_hashes.json` identifies the changed converter/compiler source,
  retained proof scripts/fixture and adjusted regression scripts. Commands and
  outcomes live under each `logs/commands.json` (legacy runners use their own
  journals). Clang 18 is recorded in `methods/logs/toolchain.stdout`.
- Cold native compiler build: **662.251 seconds**, an observation rather than a
  benchmark. Builds used `--no-stan`; LLVM and broad native_validate stayed out of
  scope. No runtime speedup, object-size or memory reduction is claimed.

Final proof commands:

```sh
python3 tests/portability/integer_method_defaults.py --results /tmp/scpp-method-defaults-20261016/methods
python3 tests/portability/integer_method_defaults_compiler.py --results /tmp/scpp-method-defaults-20261016/integration --baseline /tmp/scpp-method-defaults-20261016/baseline
```

Both new proof runners passed on their first run. No native-support repair outside
the converter owner was required. The virtual-default mismatch remains documented
as the explicit portability boundary identified by the design probe.

## Typed string splitting for build contracts (2026-10-16)

Initial review: analysis only at clone revision `da34f4c8`. The user subsequently
approved this bounded implementation; implementation evidence follows the original
plan below.

### History-proven workaround and recommendation

Recommend **two-argument typed `q_explode()`**, migrating only the two calls to
`Runtime_Build_Contract::split_byte()` in `compiler/runtime_catalog/build.php`
(paths in this section are relative to `compiler/my-try/` unless noted).

Portability repair **`84716579`** replaced:

- `explode('=', $text)` in `definition_name()` with `self::split_byte($text, 61)`;
- `explode('/', $source)` in `validate()` with `self::split_byte($source, 47)`;

and introduced the private byte-scanning helper. Both calls and the helper remained
in the reviewed source. The helper scans every byte, appends a slice at each matching
byte and always appends the trailing slice. Its comment explicitly requires empty
components to survive so malformed wire spellings are rejected.

At the review baseline, `tools/php_portability/function_map.php` had no
`explode`/`q_explode` binding.
This is a missing portable builtin exposure, not a need for semantic inference or
a new string algorithm. The strict target already registers `explode` as
`php::explode`; its two-argument overload delegates to `scpp::str::split` with the
maximum positive limit. The normative
[explode contract](../../../../specs/builtins/string/explode.md) specifies a typed
string vector, byte semantics, full separators and an empty-separator error.
`tests/runtime_strings.php` contains existing user-PHS split cases; those are
supporting target evidence, not proof that authored compiler PHP can convert the
unmapped function today.

This removes more source machinery than another punctuation/default extension:
explicit separators replace numeric byte codes, and one standard operation replaces
a private loop. It also avoids undoing meaningful enum-default entrypoint names.
The constructor empty-vector default workaround in `template_type_parameter`
(`?array $dependencies = null` plus a normalized vector, also introduced in
`84716579`) is another real candidate, but requires separate container-default and
construction proofs. Leave it for a later slice.

### Proposed bounded contract and changes

Authored PHP: `q_explode(string $separator, string $string): array`, used with
explicit `vector<string>` intent. PHS: `explode($separator, $string)`, returning
`vector<string>`. Exactly two arguments, separator first; no limit overload yet.

- Split on the full, nonempty separator byte sequence, consuming nonoverlapping
  matches from left to right. Preserve leading, trailing and adjacent empty parts.
- An absent separator yields one part containing the original string; an empty
  input with a nonempty separator yields one empty string, not an empty vector.
- Preserve arbitrary bytes, including NUL and invalid UTF-8. Do not normalize
  paths, trim components, filter empties or interpret the separator as a regex.
- Empty separator retains the builtin's `ValueError` behavior. See the exception
  boundary below; do not silently substitute an empty-vector result or another
  error family. This is distinct from the valid empty-input case.
- Supported callers supply strings. The structural converter checks arity, not
  remote operand types; no PHP coercion compatibility or runtime type inference
  is added by this binding.

| Owner/file | Proposed change |
| --- | --- |
| `tools/php_portability/function_map.php` | Add `q_explode` with `php => explode`, `target => explode`, `arity => 2`. |
| `tools/php_portability/runtime/global_functions.php` | Regenerate through `generate_global_functions.php`; verify exactly two required string parameters and array return. The generator already reflects only the mapped maximum arity. |
| `compiler/my-try/compiler/runtime_catalog/build.php` | Use `q_explode('=', $text)` and `q_explode('/', $source)`; retain the typed local in `definition_name()` and remove the now-unused private `split_byte()`. Leave all validation and encoding logic unchanged. |
| Retained focused tests and portability guide/contract | Add split behavior/boundary proofs and the narrowly scoped compiler integration driver, then record actual evidence here. |

A host adapter in `runtime/strings.php` is **not currently needed**: unlike join,
two string inputs do not require validating packed-array membership or element
coercions, and PHP `explode` already returns a packed array of strings. The
existing facade generator can expose its first two parameters directly. Confirm
this through reflection/facade checks rather than adding a redundant wrapper.
No parser special case, native runtime change or new preparation protocol is
expected. The function map participates in conversion fingerprints already.

Do not add the three-argument limit surface, change other split/join loops, or
rewrite runtime build publication. In particular, do not introduce a limit of two
for definitions: full splitting and the exact existing validation path/diagnostics
must remain intact for `NAME=1=2`.

### Exception boundary to prove explicitly

The native builtin throws `scpp::ValueError`, and PHP throws `\ValueError`.
The portability `Exception_Policy::TYPES` currently has no `ValueError` entry.
Therefore native builtin availability does **not** establish that convertible PHP
can write a typed `catch (\ValueError ...)` or that the generic exception bridge
preserves its family. This limitation must be documented, not hidden by a passing
valid-input fixture. Both selected compiler call sites use fixed nonempty literals,
so they do not encounter this error path.

Use host-only PHP and a small native runtime exception probe to establish the
builtin empty-separator behavior without extending portable catch syntax. Inspect
and record the generated-program uncaught-error behavior if exercised; do not
claim exact exit-code or portable typed-catch parity from a raw C++ catch test.
If completing the agreed portable error contract would require a general exception
bridge change, report that as a separate blocker rather than adding a `ValueError`
family silently. This slice should not promise a new portable typed catch form.

### Required focused proofs if approved

1. **Independent split expectations:** empty input, separator absent, singleton,
   leading/trailing/repeated separators, separator-only input, multi-byte separator
   and overlapping candidates. Include UTF-8 byte strings plus runtime-constructed
   NUL/invalid-UTF-8 input and separator. Compare exact part counts, per-part lengths
   and bytes; a joined string alone cannot distinguish missing empty elements.
2. **Facade/conversion:** reflection and generated-facade freshness, two-argument
   mapping to `explode`, explicit string-vector output, unchanged inputs, repeated
   conversion reuse, and zero/one/three-argument rejection. Preserve previously
   published output/cache and source diagnostics after rejection. Native typed
   rejection of an incompatible operand proves the target boundary; the converter
   need not reject every invalid type.
3. **Native shape/error:** execute the converted valid fixture and compare against
   independently specified PHP results. Inspect direct typed `php::explode` use
   and vector output, without mixed boxing or a handwritten byte loop. Prove empty
   separator errors at the explicitly stated layers above.
4. **Build-contract behavior:** capture and rerun `tests/runtime_build_contract.php`.
   Retain its checks for `NAME`, `NAME=`, `=1`, `NAME=1=2`, leading slash, doubled
   slash, traversal, conflicting definitions and forbidden target override.
   Add focused exact-result/diagnostic cases for valid nested paths and definitions,
   separator-only definitions, repeated equals, `./file.cpp`, `folder/../file.cpp`,
   and path trailing separators (which may fail the existing `.cpp` suffix check
   before splitting). Assert the existing validation order, not a newly chosen error.
5. **Encoding and merge parity:** independently assert valid encoded build JSON,
   duplicate handling and ordered source/library/definition merges. Rejection
   witnesses must fail, not merely produce the same empty output in two runtimes.
6. **Rebuilt compiler integration:** retain the original helper's PHP outcomes,
   rebuild the compiler with only the two-call migration, and run the contract
   cases inside a native driver. Verify the built source contains the migrated
   calls and lacks `split_byte()`. Compare valid encoded specs and error messages
   with PHP. Run a small runtime-module consumer through PHP/native compiler
   emission, compare artifacts and compile/execute it to prove provider-build
   requirements remain usable. Use the existing focused runtime-build fixtures;
   no full catalogue or cumulative native_validate run is required.

Use the shared 12-slot runner and PHP-FPM/default fallback, with explicit CLI
exceptions for build/process-exit commands; native checks use `--no-stan` and
exclude LLVM. Retain source/toolchain hashes, commands, precise evidence limits
and any baseline failures separately. A compiled split fixture is not proof that
the compiler itself was rebuilt.

### PHS elegance and potential runtime costs

`explode('=', $text)` and `explode('/', $source)` state the operation and delimiter
directly. The generated PHS no longer needs the private byte loop. This is a clearer
improvement than renaming an API that already conveys meaningful modes.

The old helper scans bytes and copies each output slice once; it is not the
repeated-prefix-concatenation pattern found in the earlier join review. The native
splitter uses string search and substring construction, which may reduce per-byte
wrapper/check overhead. Both return owning strings in a vector and retain empty
parts; neither is a zero-copy view. No asymptotic speedup, allocation-count
reduction, peak-memory reduction or whole-compiler speedup is established.
Inspect emitted code first. A focused microbenchmark is optional only if a
performance claim is sought; it is not necessary to prove this code-quality slice.

### Implementation evidence (2026-10-16)

Candidate: `da34f4c8` plus this slice. Added the function-map binding and regenerated
facade, changed only the two `Runtime_Build_Contract` calls, and removed
`split_byte()`. No runtime, parser, preparation or exception-policy changes.
The [portable contract](../../../../specs/portability/string_split.md) records the
supported surface and proof commands.

Evidence root: `/tmp/scpp-string-split-20261016/`. `provenance.json` records source,
converter and runtime hashes; compiler integration retains its copied PHP hashes.
Toolchain: Ubuntu Clang 18.1.3 and PHP 8.5.7 (`toolchain/` logs).

- `baseline-2/`: unchanged compiler passes the retained build-contract test and
  independent direct assertions for 15 invalid specs, valid exact JSON, ordered
  deduplicated requirements, and a cross-module definition conflict.
- `standalone-2/`: `tests/portability/string_split.py` passes 12 exact split cases
  in PHP and native execution, including full multibyte separators, overlapping
  candidates, empty parts and runtime binary bytes. Checks compare counts, lengths
  and bytes against independent expectations, and verify unchanged inputs.
  Facade reflection/freshness, direct typed PHS/C++ shape, conversion reuse and
  source/output/cache preservation pass. Zero/one/three arguments fail with source
  diagnostics; a vector operand converts structurally and is rejected natively.
- Empty separator: two host-only PHP cases and two raw C++ runtime cases throw
  the expected ValueError family/message. No portable typed-catch or uncaught-exit
  behavior is claimed.
- The native compiler rebuilt successfully in 617.655s. The direct contract
  assertions passed natively, but the additional fresh-publication attempt failed
  later in `Output_Publication::publish()`. `integration/debug-logs/backtrace.stdout`
  locates `hash_t::at: not found` in the generated `$current[$path] ?? ''` expression
  (`compiler/output_publication.php:120`). This unchanged source lowers to
  `coalesce_eval` with a throwing `current.at(...)` left operand. It is outside the
  two-call split migration; no publication or coalescing repair was made.
  The PHP publication and its managed provider build/execution passed before this
  failure. This does **not** prove native filesystem publication.
- Integration now uses the originally planned emission boundary: compare emitted
  artifacts and encoded `CPP_Runtime_Build::requirements()` from PHP/native, then
  materialize those artifacts and compile using the declared sources, definitions
  and libraries. This avoids expanding the slice into publication lowering.
  `integration/logs-3/` and `integration/summary.json` record the passing result:
  all 15 invalid-spec diagnostics, exact valid encoding, ordered/deduplicated merge,
  cross-module conflict, repeated no-op emission, byte-identical PHP/native artifact
  bundles and exact build-requirement encoding. Both independently built provider
  consumers exit with the expected 8. The driver-only incremental rebuild took
  19.46 seconds; both compiler builds used `--no-stan` and left LLVM parked.

Proof development corrections were confined to fixtures: expected JSON needed the
existing escaped slash spelling; the host ValueError message uses “must not be
empty”; nonempty container expressions needed supported typed locals. The first
baseline attempt used the runner's documented CLI fallback because sandboxed FPM
socket startup failed; subsequent authorized runs used FPM. Attempt logs remain.
These are not product failures or requests to expand converter support.

No timing/allocation benchmark was run. Both paths retain owning string/vector
materialization; direct builtin spelling establishes code quality, not a measured
runtime or memory improvement. Legacy STAN was bypassed and LLVM left parked.

## Native publication missing-key coalescing (2026-10-16)

Initial design review at clone revision `e8296160` read source and the previous
split-proof evidence only. The user subsequently approved this bounded repair
with compiler source unchanged. Implementation evidence follows the original plan.

### Failure and correct owner

`compiler/output_publication.php:120` constructs journal entries with
`$current[$path] ?? ''` and `$hashes[$path] ?? ''`. Both containers are explicitly
local `hash<string>` values. On first publication, `$current` has no previous
artifact digest. On retirement, `$hashes` has no next artifact digest. Absence is
intentional: the empty string is the wire representation of no before/after digest.
A warm unchanged-output check alone would miss both important boundaries.

The existing trace is retained at
`/tmp/scpp-string-split-20261016/integration/debug-logs/backtrace.stdout`.
It reaches `Output_Publication::publish`, then the left lambda of `coalesce_eval`,
then `hash_t::at`. The split-related native contract assertions had already passed.
The PHP publication/provider build passed, but native fresh publication failed.

The precise chain is:

1. `tools/php_portability/src/converter.php::sequence()` already admits
   `T_COALESCE` and preserves the operator. Its grouped bracket syntax becomes the
   same indexed expression in PHS. There is no missing PHP-to-PHS token/binding.
2. `generators/php/src/Generator/Generator.php::renderCoalesceExpr()` renders the
   left operand with the ordinary `renderExpr()` path. `DIM` delegates to
   `renderDimAccess()`, yielding `current.at(cast<string_t>(path))`.
3. `runtime/include/operators/coalesce/coalesce.hpp::coalesce_eval()` calls the
   left thunk once **before** testing whether it contains a usable value. A thrown
   missing-key read is not a nullable result, so fallback cannot be selected.

This is a **PHS-to-C++ generator access-context defect** for the needed source
shape, not a `q_explode` regression or a PHP-to-PHS converter extension. Do not
rewrite publication into `isset` ternaries, patch generated C++, or catch arbitrary
`out_of_range` exceptions around the left thunk. A key expression can itself throw;
that exception must propagate rather than masquerade as a missing map entry.

### Recommended bounded repair

Keep both authored expressions and their generated PHS unchanged. Repair the
coalescing-left read in the legacy generator, using a named local helper such as
`tryRenderCoalesceLookup()` invoked by `renderCoalesceExpr()`.

Initial admission: one nonempty `DIM` whose receiver is a simple variable with a
locally recorded exact `hash<string>` type (string keys, string payloads). Both
publication locals meet this rule. Reuse the generator's existing local type
metadata, type mapping and hash-type decomposition; do not add remote member,
return-type, alias or whole-program inference. Document this narrow structural
exception in the generator rules catalog. The supported authoring guarantee is
an explicitly typed local; do not promise general inference from PHP arrays.

The helper should produce a **presence-bearing read**, not an entire second
conditional implementation. Reuse `hash_t::find()` and the existing coalescer:

```cpp
php::coalesce_eval(
    [&]() -> auto { return current.find(cast<string_t>(path)); },
    [&]() -> decltype(auto) { return string_t(""); });
```

`hash_t::find()` already performs a non-mutating lookup and returns an owned
`nullable<string_t>`: absent for a missing key, present even for an empty string.
The existing `nullable<string_t> ?? string_t` normalization produces an owned
`string_t` and evaluates the fallback only on absence. No new runtime helper,
function-map binding, host facade or compiler preparation model is needed.

Implementation details to preserve:

- Recognize the left operand's shape before ordinary indexed rendering; never
  render `.at()` and attempt to repair its emitted text afterward.
- Normalize/check the recorded hash type with existing type utilities. Do not
  match variable names, source filenames, PHP spelling fragments or empty-string
  fallback literals. The rule must also work for a nonempty/computed string RHS.
- Reuse ordinary hash key conversion/rendering. If that logic is embedded in
  `renderDimAccess()`, extract a small shared key-rendering helper within the same
  owner rather than duplicating a subtly different conversion rule.
- Evaluate the key once, keep fallback lazy, do not copy the hash or return a
  borrowed element. Use an explicit value-returning left lambda for the nullable
  lookup. Do not infer RHS types; existing C++ coalescer rules still govern joins.
- For nonmatching forms, preserve the existing coalescing path. This is a bounded
  repair, not a claim that all previously accepted indexed `??` forms are correct.
  Record the unproved forms in portability guidance rather than masking them.

Changes expected if approved:

| Owner | Change |
| --- | --- |
| `generators/php/src/Generator/Generator.php` | Named coalescing lookup renderer and, only if needed, shared local hash-key rendering extraction. |
| `generators/php/specs/rules_catalog.md` | Explicit narrow local-type/structural rule, non-throwing lookup, evaluation and ownership boundaries. |
| `specs/conditional_expression_matrix.md` | Bounded keyed-absence amendment: missing typed string-hash entry supplies absence to the existing nullable/string coalescer; present empty string is usable. The current matrix describes selected values, not a universal missing-index protocol. |
| `specs/portability/authoring_guide.md` and focused supporting note | Supported source form, remaining unsupported/unproved forms and actual evidence. |
| Focused tests | Standalone PHP→PHS→C++ lookup cases plus native compiler publication integration. Keep publication cases separate from the split helper's standalone tests. |
| `tools/php_portability/src/converter.php`, runtime, `Output_Publication` | No production changes expected. Converter behavior is exercised as part of the pipeline proof. |

Non-goals: `??=`, nested index paths, uninitialized variables/properties, nullsafe
receivers, arbitrary receiver calls, hash fields, vectors, mixed/dynamic containers,
object-key hashes, nullable/wrapper/object hash payloads, general exception bridging,
STAN repair or v0.2 semantic preparation changes. In particular, blindly changing
all hash reads to `find()` would create nested-nullable/wrapper selection questions
and alter ordinary missing-index behavior. Do not generalize that way in this slice.
If the narrow declaration metadata cannot truthfully distinguish this shape, or
native publication exposes a second owner defect, report the blocker before
expanding the implementation.

### Required focused proofs

1. **Retain the failing baseline.** A minimal portable fixture with an empty
   `hash<string>`, an indexed `??` and nonempty fallback must pass in PHP, convert
   successfully, and fail natively with the current missing-key read. Capture the
   generated PHS/C++ and source/toolchain hashes. Reuse the existing publication
   trace as supporting evidence, not as a substitute for a minimal regression.
2. **Independent values and effects.** Missing key selects fallback; present
   nonempty string, empty string and string `'0'` retain their values. Use a counted
   key expression to prove exactly one evaluation. Use counted/throwing RHS methods
   to prove skipped fallback when present and exactly one call when absent.
   A throwing key expression must propagate, not select fallback. Compare exact
   outputs and counters, not just successful exits or PHP/native equality.
3. **Purity and ownership.** Missing lookup must not insert a key or change count;
   present lookup must not mutate data. The selected string must remain valid and
   independent after subsequent hash mutation. A fallback that intentionally changes
   the hash may have those explicit effects; the lookup itself must add none.
   Mutations of the receiver from within the key expression are not implicitly
   covered by ordinary single-evaluation tests; investigate separately before
   promising that additional evaluation-order contract.
4. **Shape, joins and scope.** Check preserved `??` in generated PHS, `.find()` only
   in the admitted coalescing-left context, `nullable<string_t>` intermediate and
   `string_t` result without mixed boxing/map copying. Ordinary `$map[$key]` must
   still use checked access and throw on absence. Test nonempty/computed fallback,
   a coalesce chain of admitted reads, method/function scopes and an unrelated
   same-named variable in another scope. Reuse focused existing nullable/mixed/
   wrapper coalescing cases to show their old lowering and behavior are unchanged.
   Incompatible typed fallback remains a target type failure, not a new converter
   inference task. Keep excluded shapes explicitly unproved.
5. **Converter regression.** Assert unchanged source files, PHS spellings,
   content-aware reuse and published output/cache after any existing rejected-input
   case used by the fixture. No new PHP parser acceptance or arity rule is expected.
6. **Native publication lifecycle.** Rebuild my-try with its unchanged publication
   source. Use a retained native driver to publish real compiler artifacts and run
   the same scenario in PHP: fresh output (missing before hash), unchanged warm
   publication, changed artifact, added artifact, retired artifact (missing after
   hash), and missing owned file restored. Compare exact artifacts, manifest and
   ready-status bytes; no-op publication must preserve file identity/mtime where
   the existing contract promises it. Observe journal before/after digest semantics
   via retained pending-state recovery cases; do not add production test hooks.
7. **Ownership protection and real provider consumer.** Reuse relevant cases from
   `tests/fixtures/output_publication.php` for unowned collision, edited owned output,
   retry and persisted pending-journal recovery. Keep the host suite as regression
   evidence, but adapt a bounded typed driver for native execution rather than
   assuming the host fixture itself is convertible. Restore the runtime provider
   scenario that failed in the split review: publish from PHP and native compilers,
   compare manifests including ordered build requirements, use the existing managed
   native-cache driver to build each publication, and execute with expected exit 8.
   Remove the split integration's documented publication exclusion only after this
   independent evidence passes; the split-specific emission proof can stay focused.

Use the shared 12-slot runner (FPM by default, explicit CLI reasons for build/exit
contracts). Bypass legacy STAN and leave LLVM parked. No cumulative native sweep.
Save failed baseline and later passing attempts separately, including copied-source
hashes, rebuilt executable hash, emitted shapes, manifests and build/run commands.
Do not report native publication repaired from a PHP suite or an emitted-program
execution alone.

### PHS quality and runtime costs

The authored PHP and generated PHS are already concise and communicate absence
correctly. The gain is faithful lowering and removal of a native execution blocker,
not shorter source. `find()` uses one hash lookup and does not allocate/copy an
entire hash or insert a missing entry. It does materialize an owned nullable string.
Inspection suggests a present value may be copied into that nullable and again
when the existing coalescer normalizes its lvalue into an owned string. Do not
promise fewer string allocations or faster present-key execution. Missing-key
execution avoids the current exception/failure; that is correctness evidence, not
a benchmark. Measure copies/allocations only if making a performance claim; any
coalescer-wide move/lifetime optimization is a separate slice.

### Coalescing implementation evidence (2026-10-16)

Candidate: `e8296160` plus this slice. `tryRenderCoalesceLookup()` in the legacy
PHP generator recognizes the admitted single-level local string hash and reuses
`renderHashKeyExpr()` and existing `hash_t::find()`. Its nullable value goes through
unchanged `coalesce_eval()` normalization. No PHP-to-PHS converter, runtime or
compiler implementation file was changed. The [portable guide](../../../../specs/portability/hash_coalesce.md)
and bounded conditional/rule-catalog amendments record this exact surface.

Evidence root: `/tmp/scpp-publication-coalesce-20261016/`.

- `baseline/`: minimal empty typed hash with nonempty fallback executes in PHP,
  converts, builds, then fails natively with `hash_t::at: not found`. The original
  generated PHS/C++ and command logs remain available.
- `standalone-2/`: independent value/effect transcript matches in PHP/native.
  Empty strings and `'0'` remain present; key calls occur once; fallback is lazy;
  exceptions propagate; missing lookup does not insert; selected result survives
  later hash mutation. Scope/vector and nullable cases remain valid. Ordinary
  missing access still fails; incompatible fallback is rejected by the target.
  The existing runtime wrapper-normalization proof also passes. Converter reuse,
  preserved PHS operators and rejected-input output/cache preservation pass.
- `standalone-final/`: final fixture adds a compile-time assertion that the actual
  emitted selection has `string_t` type and passes (`logs-2/`). The first syntax-only
  assertion command omitted the generated portable exception declarations; adding
  that test include fixed the harness without changing production code.
- `mixed-regressions/`: the two retained direct-PHS mixed coalescing fixtures pass
  generation, native build and their independent expected transcripts (basic and
  chained cases). These checks also run from the retained standalone proof helper.
- `integration/summary.json`: native compiler rebuild passes in 573.378s; all
  copied compiler implementation sources match the clone byte-for-byte. The native
  driver and PHP driver agree on fresh, warm, changed/added/retired publication,
  restoration of missing owned output, and two persisted pending-journal recovery
  states (with/without manifest replacement). Warm artifact/manifest inode and
  mtime are preserved. Unowned collisions and edited owned outputs are rejected,
  including retry, without overwriting user bytes. Ready-state/manifest/artifact
  snapshots match exactly between runtimes.
- Both real provider publications have identical bytes and ordered runtime build
  requirements. The existing managed cache driver builds directly from each
  published manifest, and both programs exit with the expected 8. Provider builds
  take 19.275s (PHP publication) and 27.163s (native publication); these are proof
  command timings, not comparative performance measurements.

`provenance.json` records baseline/current generator and touched proof/runtime
hashes; `toolchain/` records Clang 18.1.3 and PHP 8.5.7. Each attempt retains its
logs. The native executable hash and 209 copied PHP source hashes are retained in
integration evidence. This closes the missing-key native publication blocker
recorded by the preceding split slice. LLVM remained parked and legacy STAN was
bypassed; no cumulative suite or performance benchmark was run.

The initial fixture used a free named PHP function, outside this converter's
supported authoring forms; it was changed to a static method. This was test-source
adaptation, not an added converter feature. Fixture scope checks still exercise
separate method/main local metadata. No benchmark or generic coalescer ownership
optimization was performed; the possible extra owned-string copy remains explicit.

## Parked recommendation: empty typed-vector constructor defaults (2026-10-16)

Status: parked by user pending the broader convertible-PHP design. Analysis only
at clone revision `2cd77da7`. No converter probes, builds,
benchmarks or implementation edits were performed for this review.

### Concrete workaround and recommendation

Recommend **empty defaults for explicitly typed vector constructor parameters**,
with compiler migration restricted to `template_type_parameter::$dependencies`
in `03_parse/types/model.php:429` (paths here are relative to `compiler/my-try/`
unless rooted at the repository's tooling/spec folders).

History establishes the workaround: commit `84716579` replaced

```php
array $dependencies /** vector<generic_contract> */ = []
```

with `?array ... = null`, then introduced `$effective_dependencies`, initialized
it to an empty typed vector and conditionally copied the supplied nullable vector
into it. Validation iterates that normalized local and the field stores it.
The constructor's real data is a vector of capability dependencies; this null state
only supplies an empty default. Restoring the original nonnullable signature removes
that artificial state and the normalization local/branch.

**Do not change `$contracts`.** Its null value means the source-language bare-T
formation default (`copyable_value`); an explicit empty list means no formation
requirements. That distinction is intentional and tested in `tests/type_catalog.php`.
The third argument, nullable `$default_type`, also retains its current meaning.

The audited direct call sites either omit dependencies or pass a typed vector:
`03_parse/types/register.php`, `compiler/initialization/catalog.php`,
`compiler/runtime_catalog/types.php`, and retained type/capability/Result tests.
No direct call found passes explicit null as the fourth argument. This is a
repository call-site audit, not proof about external or dynamically constructed
callers. Restoring `array ... = []` deliberately stops accepting explicit null for
that argument in PHP. Record that boundary; do not describe it as preserving every
call accepted by the temporary nullable API.

This is more useful than another scalar-default spelling extension: it removes an
artificial nullable container and makes the constructor's domain truthful. Other
nullable inputs, parent-constructor helper extraction, enum-default factories and
Storage aliases should not be swept into this change. Their semantics or target
constraints differ. The preceding native publication `??` blocker is already
closed and is not a reason to widen this slice.

### Owner, exact changes and native feasibility gate

`tools/php_portability/src/converter.php::constructorDeclaration()` already parses
ordinary/promoted constructor parameters, `array` plus explicit container annotation,
and nullable-null defaults. It currently rejects every other array default with
`constructor containers require explicit arguments; only nullable defaults may be null`.
The constructor parameter's own annotation is the truthful type owner; no callee
lookup, destination inference or whole-program type analysis is needed.

Proposed supported form: nonnullable `array` annotated `vector<T>`, with exact empty
literal `[]`. Use the existing container annotation parser for `T`; do not add a
second type grammar or infer element type from PHP values. Support ordinary and
promoted constructor inputs through the same default parser and existing promotion
expansion, with focused proof of both. Compiler migration uses only an ordinary input.

Emit an explicitly typed construction expression in PHS:

```php
$dependencies vector<generic_contract> = new vector<generic_contract>()
```

Do **not** emit a bare `[]` default. `sequence()` already uses typed `new vector<T>()`
for prefix-annotated empty container expressions. The generator's `renderExpr(NEW)`
recognizes explicit vector construction and emits a concrete vector value; parameter
rendering emits default expressions at declaration sites when `includeDefaults` is
true. Reuse those concepts, rather than making the S2S generator infer an empty
literal's type from the destination parameter.

There is an important feasibility gate: the existing
[constructor portability note](../../../../specs/portability/compiler_token_store_slice.md)
records incorrect native empty-vector default lowering on its earlier target.
Source inspection suggests explicit typed construction can avoid that ambiguity,
but **this review has not proved it works in a default argument**. First compile a
small two-file constructor fixture with an omitted typed-vector argument, using the
current target and emitted headers. Prove the concrete default, declaration/definition
placement and object lifetime before migrating the compiler. If typed construction
still fails in the parameter/default pipeline, identify that owner and report the
required expansion instead of adding a generator hack or patching emitted C++.

Expected implementation shape if approved:

- In `constructorDeclaration`, recognize this nonnullable vector case before the
  existing blanket array-default rejection. Consume exactly `[` then `]`, allowing
  normal insignificant whitespace/comments; then resume existing comma/`)` parsing.
  Render the typed empty construction using the already parsed vector type.
- Prefer a small named helper for typed empty-default parsing/rendering if needed;
  keep ordinary/promoted construction on one path. Do not broaden ordinary method,
  closure/interface defaults, scalar defaults or signature inference while here.
- Keep existing `?array ... = null` behavior. Reject `?array ... = []`, bare/untyped
  arrays, hash/Storage defaults, nonempty arrays, `array()` spelling, calls/constants,
  null on nonnullable vectors and malformed/missing annotations in this slice.
  Target type support for an element still belongs to the target; parsing a type
  annotation is not proof of every element representation's native support.
- In `template_type_parameter`, restore nonnullable `$dependencies = []`, remove
  `$effective_dependencies` and its normalization branch, and use `$dependencies`
  directly for the existing validation loop and field assignment. Keep validation
  order, messages and all other fields/parameters unchanged. No call-site migration
  is expected after the audit.
- Update the constructor row in `specs/portability/authoring_guide.md`, the current
  constraint summary in `specs/portability/debt.md`, and add a focused supporting
  contract/proof note. Mark the older token-store observation as historical with a
  link to the newly proved bounded form; do not imply its other gaps are repaired.

Non-goals: general array defaults, nonempty typed literals, hashes, Storage/object
hashes, ordinary method/interface defaults, nested-container-default guarantees,
shared mutable defaults, nullable-container redesign, user-language v0.2 default
semantics or unrelated `compiler/src` migrations. Any additional generator/runtime
owner repair must be made concrete and discussed before expanding this slice.

### Focused proofs required

1. **Baseline and native gate.** Retain the converter's current rejection with file/
   line attribution. Capture PHP compiler model outcomes before migration. Prove
   explicitly typed empty construction in a default across two generated translation
   units, with a named enum element as used by the compiler; do not infer native
   support from PHP execution or conversion alone.
2. **Constructor behavior.** Omitted argument and explicitly supplied empty vector
   produce independent empty values; supplied nonempty vectors retain order/content.
   Exercise ordinary and promoted constructors, construction from another file,
   earlier optional arguments followed by the vector default, and repeated omitted
   calls. Assign constructed objects to locals so constructor behavior is observable.
   Mutating one object's vector must not affect another or a supplied caller vector;
   do not accidentally demand deep cloning of object elements.
3. **Default placement and C++ shape.** Require typed PHS `new vector<T>()`, a concrete
   native vector default in declarations, no duplicated default on definitions, no
   `mixed` carrier or nullable vector for this parameter, and no reference retained
   to a destroyed default temporary. A const-reference input is acceptable only if
   stored state owns its copy. Verify concrete runtime sizes/content after the call.
4. **Rejections/regressions.** Nonempty, nullable-empty, hash/Storage, unannotated,
   malformed and nonliteral defaults reject with source diagnostics and preserve
   previous output/cache. Repeated conversion reuses unchanged output. Retain existing
   nullable-null, scalar/named constructor and ordinary integer-method default cases;
   the new grammar must not leak into those parameter sites. Explicit null at the
   restored nonnullable dependencies call must fail in PHP and at the native typed
   boundary; the structural converter need not infer remote argument types.
5. **Compiler model.** Run `tests/type_catalog.php`, `tests/type_capabilities.php` and
   the relevant `tests/result_model.php`/`tests/ownership_model.php` checks before
   and after. Add direct portable/native witnesses for omitted/empty/nonempty
   `capability_dependencies()`, ordering, duplicate rejection, and forbidden
   `result_payload` dependency. Preserve the intentionally distinct omitted/null
   versus explicitly empty **contracts** input and unchanged default-type behavior.
   Sparse PHP-array rejection is host evidence; do not pretend typed native vectors
   can represent sparse PHP keys.
6. **Rebuilt compiler integration.** Copy the migrated source and verify only this
   constructor/normalization changed. Rebuild natively, run the direct constructor
   witnesses there, and compare selected existing type-capability/container consumers
   through PHP/native compiler emission and generated-program execution. Use retained
   fixtures with independent expected capability/results, including explicit nonempty
   dependency lists; omission alone would not prove the existing capability gate.

Use the shared 12-slot runner, FPM/default fallback and explicit CLI reasons for
build/exit contracts. Native proofs use `--no-stan`; LLVM stays parked. Retain
baseline/current source and toolchain hashes, generated signatures, rejection logs,
native executable hash and exact command timings. No cumulative native sweep.

### PHS elegance and potential runtime costs

The desired PHS expresses an empty vector as the default instead of manufacturing a
nullable state and normalizing it in the constructor body. This is a real readability
and type-domain improvement. Native code can lose a nullable wrapper, branch and
normalization assignment, but the field still stores an owned vector and explicit
inputs can still require copies. An empty default is constructed per invocation;
it must not become a shared static container. No measured allocation, memory or
runtime improvement is established by this analysis. Inspect generated signatures
and copy/lifetime behavior before making a performance claim.

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

- 2026-10-16: user selects typed string joining for detailed planning and asks to
  retain concrete workaround findings in the clone. Documentation only; code
  implementation and native execution remain unapproved.

- 2026-10-16: user approves implementation and focused proofs for typed joining,
  confined to the two closure-emission joins. Slice completed as recorded above.

- 2026-10-16: user selects signed integer field defaults for design-only analysis.
  Proposed shared field-literal reader, one initial compiler consumer, explicit
  numeric grammar boundaries and PHP/native acceptance proofs recorded above.

- 2026-10-16: user approves signed integer field defaults and focused proofs, with
  compiler migration limited to `prepared_data_entry::$input_position`. Completed
  with standalone PHP/native and rebuilt-compiler evidence above.

- 2026-10-16: user requests the next limitation review, analysis only. Recommend
  decrement-token admission with only the two for-condition counter migrations;
  source/history findings, alternatives and future proof gates recorded above.

- 2026-10-16: user approves decrement support with migration limited to the two
  for-condition counters. Completed with focused PHP/native, signed-field
  regression and rebuilt-compiler proofs recorded above.

- 2026-10-16: user requests the next workaround review, analysis only. Recommend
  nonnullable integer method defaults, with compiler migration confined to
  `Body_Preparation::prepare_statements` and its two local default-zero calls.
  History, shared-signature implications and proposed proof gates recorded above.

- 2026-10-16: user approves integer method defaults with compiler migration
  confined to `prepare_statements()` and its two local zero calls. Completed with
  focused PHP/native and rebuilt-compiler proofs; differing virtual defaults are
  recorded as an explicit nonportable boundary.

- 2026-10-16: user requests another analysis-only workaround review. Recommend
  two-argument typed splitting for the two `Runtime_Build_Contract` calls; preserve
  empty components and explicitly retain the existing ValueError catch boundary.

- 2026-10-16: user authorizes typed string splitting, restricted to the two
  `Runtime_Build_Contract` calls and removal of `split_byte()`; focused proofs only.

- 2026-10-16: user requests design-only analysis of the native publication `??`
  failure. The proposed repair belongs to the legacy PHS-to-C++ generator, with
  no compiler-source migration or PHP-to-PHS converter change expected.

- 2026-10-16: user authorizes the bounded native publication coalescing repair and
  focused proofs, keeping all compiler implementation source unchanged.

- 2026-10-16: next design-only recommendation is an explicitly typed empty-vector
  constructor default, migrating only `template_type_parameter::$dependencies`.
  Preserve semantic null contracts and prove the historical native default boundary
  before removing the normalization workaround.

- 2026-10-16: user parks further slices in favor of designing the convertible-PHP
  concept, stricter validation, and a well-defined mapping to Simple C++. The empty
  typed-vector default proposal remains unimplemented. A proposed design framework
  is source concept → admission/validation rules → PHS/native meaning → parity
  proofs; validator implementation and the custom-parser proposal are not authorized
  by this direction-setting discussion.
