# Compiler conversion quality and frontend direction
Doc Status: planning

Started: 2026-10-16. Typed string-join slice implemented and focused proofs passed.
Other proposals remain discussion-only; see the implementation checkpoint below.

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
