# Proposed implementation order for the v0.3 bootstrap

Doc Status: planning

Date: 2026-11-14. Based on the [language audit](v03_bootstrap_language_audit.md) and the subsequent implementation-order discussion. This is a proposed sequence, not authorization to implement every item or a claim that the features are delivered.

## Approach and scope

The audit is organized by subject. Implementation should follow dependencies and prove small, executable parts of the actual compiler. Preserve behavior, type relationships, ownership, identity and failure recovery. A readable native-oriented translation may use deliberate local adaptations instead of reproducing every PHP mechanism, but that translation policy remains a discussion point.

Use existing metadata, type handlers, argument binding, collection machinery and incremental ownership. Do not implement every PHP construct simply because it occurs in the compiler. Discuss substantial architectural substitutions and wide refactors before proceeding. Coordinate collection changes with the workspace that owns them.

Numbers give the proposed implementation order within each milestone. They are discussion-sized groups, not single batch implementation requests. Reassess the next group after each executable proof. Language readiness and runtime/host readiness are separate tracks. Inventory host dependencies during milestone 1, even though their integration is milestone 7.

## Status checkpoint — 2026-11-19

The original language audit remains a dated baseline, not a current feature checklist.
This checkpoint separates implemented language support from translated-compiler proofs.

| Group | Current status | Remaining boundary |
| --- | --- | --- |
| 1.1 Integer representations/literals | Hexadecimal/octal literals and five optional integer widths implemented | uint64 and operations requiring its promotion remain deferred; unavailable constant-evaluation outcome exists |
| 1.2 Backed enums | Bounded scope implemented, including string backing and public instance methods | Broader enum integration is separate |
| 1.3 Application-defined collection elements | Published specializations retain common calls/fields; application vectors and hashes support construction, indexing, copies, fields, calls, traversal and generated has/remove/clear; vector and integer-key hash append | Writable parameters implemented; recursive vector/hash values implemented; typed populated vector locals implemented; broader element/key families and keyed literals remain separate |
| 1.4 Collection traversal | Value/keyed foreach for vector/hash/mixed/dynamic and metadata-defined custom Iterator implemented | Application-defined vector/hash value/key traversal implemented, including yield foreach; references/destructuring remain separate |
| 1.5 Construction-time immutability | Bounded stored-value readonly fields implemented | Valid-data token/source-location proof complete; exception parity, packing, broader readonly/const-method contracts and delayed attachment stay separate |
| 2 Selection and lookup | Bounded integer/enum match and match(true), collection has/remove/clear and traversal, shared hash_contains and published runtime hash_find implemented | Bounded nullable fallback and local conditional assignment implemented; application-value lookup, broader fallback, populated tables and tokenizer bindings remain |
| 3 AST relationships | Common class/struct record model, single inheritance, shared base views, virtual/abstract methods, explicit ancestor calls, class/interface checked casts and capability tests, the first class-interface slice, protected instance methods, final classes, and a weak-parent/retained-root lifetime proof implemented | Broader interface relationships, staged attachment and native compiler translation remain |
| 4 Source failures and cleanup | Runtime checked failures exist | Source throw/catch/rethrow and guaranteed cleanup remain |
| 5–7 Translated stages and bootstrap | PHP compiler infrastructure exists and is tested | Native translation proofs, host integration and bootstrap are not completed |

Synchronous yield additionally supports locals, branches, while/do-while/for and
foreach over collections/custom Iterator, with retained receivers and cleanup.
The remote integration preserves cursor-layout publication and concrete adapter
publication together. Iterator remains the existing explicit struct contract: this
merge does not enable class implementations, inherited/virtual traversal methods or
producer methods. Class values may use their existing checked conversions within
free-function producers.
Nested generated cursors, delegation and producer methods remain deferred. This
is sufficient to pause yield expansion for the current bootstrap focus; see the
[yield backlog](yield_completion_backlog.md) for the remaining independent slices.

Next proposed discussion: the smallest tokenizer-path slice in milestone 2. The bounded collection goal, stored-value readonly fields and valid-data token/source-location proof are implemented.
Preserve the common collection protocols, metadata ownership, light
retained facts and single-pass preparation. Do not imply authorization for a broad
collection implementation or runtime redesign. The valid-data token/source-location proof now uses the bounded readonly contract. This completes the bounded milestone-1 valid-data proof, not a full source-model translation
or native compiler bootstrap. PHP incremental
recovery is already implemented; milestone 6 concerns translating that model faithfully.

## 1. Typed data foundation — tokens and source snapshots

| Order | Group | Initial scope |
| --- | --- | --- |
| 1.1 | Integer representations and literals | Activate the widths actually needed by the compiler through metadata. Add hexadecimal and octal literals with checked bounds. |
| 1.2 | Backed enums | Named cases, integer/string backing, equality, backing-value access and checked construction. Reuse method machinery for enum methods. |
| 1.3 | Application-defined collection elements | Start with vector of token, copying, parameters, returns and field storage. Coordinate with collection work. |
| 1.4 | Collection traversal | Ordered value iteration, then keyed map iteration. Set explicit mutation and element-copy behavior. |
| 1.5 | Construction-time immutability | Readonly fields initialized during construction. Keep delayed AST attachment separate. |

Proof: translate the token representation and source-location lookup. Construct, pass and iterate a token list. Verify spans and line/column results in a native executable.

Valid-data proof complete: [native token/source-location verification](../../compiler/docs/token_source_proof.md)
compares all three flavors with the production PHP models, including incremental
method edits, deletion, retry and recovery. Exceptions, packing and full source-model
translation remain deferred; this does not claim a native compiler bootstrap.

Do not block this proof on every optional width, populated collection literals or full PHP readonly behavior. Start the host/runtime binding inventory alongside this work.

1.2 status: the [first integer-enum slice](../../compiler/docs/enums.md) supplies
named literal cases, compact/exact storage, same-enum equality, typed calls and
`enum_value`, with incremental recovery. Enum instance fields with explicit case
defaults and function/method/constructor enum parameter defaults are also implemented.
Checked integer reverse construction is also implemented, reusing generated integer conversions.
`enum_name` and byte-exact string backing/helpers are implemented using compact ordinal storage.
Public instance enum methods now reuse the function binder and common method ownership.
The bounded 1.2 scope is implemented; unit enums, uint64 backing, static/private enum
methods, reflection and broader enum integration remain separate follow-ups.

## 2. Readable selection and lookup — a tokenizer path

| Order | Group | Initial scope |
| --- | --- | --- |
| 2.1 | Selection | Match with one subject evaluation, lazy selected arms and a clear result-type rule. Start with enum/scalar subjects, then match on true. |
| 2.2 | Container presence and mutation | Contains/find, removal, missing-key handling and ordered keyed traversal. Distinguish missing from stored null. |
| 2.3 | Lazy fallback | Coalescing, then coalescing assignment through the same presence model. Avoid repeated lookups and eager defaults. |
| 2.4 | Text and table construction | Raw/multiline strings and bounded populated collection forms required by compiler tables. See the [private consuming-input contract and first vector slice](collection_initialization.md); flat published integer/string vector locals implemented; user factory inputs remain read-only and provider bindings stay fixed per compiler session. |
| 2.5 | Native tokenizer path | Review and port the native tokenizer from the earlier v0.1/v0.2 runtime outside this workspace folder. Inventory its required runtime bindings; PHP PCRE is not a bootstrap requirement. |

Integration: one metadata-defined ordered initialization capability now preserves
  nested/application coverage and selects presized bulk transport for eligible published
  vectors, with generated append fallback for other specializations.

Proof: tokenize representative input through a translated path and compare token kinds, positions and diagnostics with PHP.

Keep ternary, switch, assignment expressions and general destructuring outside the critical path unless actual translation demonstrates a need. Isolated uses may have clear alternatives, while extensive enum maps warrant a direct language facility.

### 2.1 First match slice — implemented, verification recorded below

The [bounded language contract](../../../specs/match_v0_2.md) and
[compiler note](../../compiler/docs/match.md) own the completed behavior.
All flavors use `match (subject) { label => result, default => fallback }`.

- Integer and backed-enum subjects; exact-type literal/named-constant/case labels.
- One subject evaluation and lazy selected result; one mandatory default.
- Identical canonical arm result types; ordinary enclosing conversions remain.
- Native switch, directly in identity-return position; expression wrapper otherwise.
- AST owns only subject and syntax arms. Temporary duplicate checks disappear
  after preparation; no persistent maps, secondary prepared tree or global cache.
- Existing symbol watches track labels, result calls and enum contracts. Reset and
  collection traverse every arm; unchanged caller bodies/native objects remain reusable.

Verification: `compiler/tests/match_native.py` covers cpp/php/js with Clang and GCC:

- Integer limits, byte labels, integer/string-backed enums, default anywhere,
  nested matches and both return/initializer lowering paths.
- Single subject evaluation, selected-only effects, checked failure, surrounding
  short-circuiting, string value copies and embedded NUL preservation.
- Missing/duplicate defaults, duplicate labels/constant aliases, invalid subjects,
  dynamic labels and inconsistent result types.
- Arm edits, label constant edits/deletion/restoration, enum case removal/recovery,
  repeated failure, fresh-output equivalence and no-op preparation/emission/native reuse.
- Native caller object reuse after a function-body-only edit.

First-slice deferrals (condition follow-up below): `match(true)`, plain string/float subjects, computed or grouped labels,
exhaustiveness, implicit common result types, void/generator/reference results,
ternary/switch syntax and the 2.2–2.5 collection/tokenizer work. This slice does not
change section 1 work owned by the other workspace or claim compile-time gains.

### 2.1 Ordered match conditions — implemented

Extend the existing match syntax with literal `match(true)`. Boolean conditions run
in source order until the first true condition; only its result runs. Default remains
mandatory and runs last if no condition succeeds, even when written first. All result
types must agree exactly. Repeated conditions are permitted; skipped arms are still
semantically checked. General boolean subjects and truthiness coercion are deferred.

Ownership and cost: derive the mode from immutable subject syntax; add no AST fields,
branch maps, retained indexes or preparation pass. Prepare fallback after conditions
so reference-call invalidation reaches its conversions. Lower through existing bool
condition handlers to early-return `if` statements; reuse the existing match wrapper
only in expression contexts. This does not change eager operand-order contracts.

Verification completed with `match_conditions.php`, `match_conditions_native.py`
and the original `match_native.py`; related logical, constants, incremental frontend
and compiler style checks also pass.

Verification checklist:

- [x] Ordered conditions, first match, repeated conditions, default anywhere and only default.
- [x] Skipped side effects/failures, condition/result failure, nested selections and short-circuit surroundings.
- [x] Default conversion remains checked after a false condition mutates a local by reference.
- [x] Exact bool conditions/result types; missing/duplicate defaults; invalid skipped arms.
- [x] AST ownership, one visit per expression, pure emission and reset/reprepare equivalence.
- [x] Predicate signature edits, constant/result edits, deletion/retry/recovery against fresh artifacts.
- [x] No-op preparation/emission/native reuse and unchanged caller objects after body-only edits.
- [x] cpp/php/js on Clang and GCC; original integer/enum match regression remains green.

Deferred: general boolean subjects, string/float matching, computed integer/enum labels,
label grouping, exhaustiveness and common-result inference. Section 2.2–2.5 work,
other-workspace collection changes and new runtime APIs remain outside this slice.

### 2.2 First key-presence slice — implemented

Use `hash_contains(collection, key)` for key existence, independently of payload
truthiness/null state. `isset` remains null-sensitive. Existing runtime hash methods
already supply `has`; this slice connects one generated capability for runtime and
application hashes through the same immutable runtime-call binding. Native code uses
`receiver.has(key)`, with ordinary strict-key argument lowering and receiver lifetime
protection. No new AST state, lookup carrier, runtime storage or preparation pass.

Verification checklist:

- [x] All four runtime hashes plus struct/shared-class values with both key types across cpp/php/js on Clang/GCC.
- [x] Missing keys never insert; present zero/empty strings, NUL and numeric-looking keys retain identity.
- [x] Single evaluation, temporary/field receivers, owner replacement/expiry, empty-owner and key failures.
- [x] Key presence remains true for a native-inserted empty shared payload; source insertion rules unchanged.
- [x] Strict key/arity/capability diagnostics; declared function precedence, missing imports and constant aliases.
- [x] Canonical binding reuse, const modes, one preparation per operand, pure lowering and reset/reprepare.
- [x] Body/layout edits, declaration deletion, invalid shadowing, recovery against fresh output and native caller reuse.
- [x] Provider relocation/retirement plus existing collection member regressions.

Deferred: `hash_find` and application nullable carriers, new removal/clear spellings,
coalescing, nullable payload publication, boxed families, and broader application
collection methods/traversal. Existing implementations are not reclassified as missing.

### 2.2 Published optional lookup — runtime hashes, implemented

`hash_find(h, key)` reuses the published `find(const Key&) -> nullable<Value>` binding
for the four runtime int/string hashes. Absence is empty, present zero/empty string is
a value, and success returns an owned copy. Indexed reads remain throwing. Generated
`key_lookup_type` supplies the existing runtime-call protocol; no new AST field,
preparation pass or backend operation is added.

Application lookup remains deferred. Optional inline structs need a value carrier;
nullable class handles alone would conflate a missing key with a present empty handle.
Do not synthesize application carriers or expand the runtime ownership model here.

Verification passed in `hash_find_native.py`, shared `hash_contains.php`, the
`hash_contains_native.py` regression and generator/provider/member checks.

Verification checklist:

- [x] Four runtime hashes across cpp/php/js on Clang/GCC; missing/present zero/empty text.
- [x] NUL/numeric-looking keys, strict key types and no insertion on miss.
- [x] Copied string payload survives overwrite/clear and source owner destruction.
- [x] Single evaluation, temporary/field receivers, owner retention/expiry and failure order.
- [x] Indexed miss still throws; result uses the published nullable identity and headers.
- [x] Canonical binding reuse, single-pass preparation, pure emission and reset/reprepare.
- [x] Body/signature/layout edits, deletion/retry/recovery against fresh artifacts; no-op and native caller reuse.
- [x] Function/import precedence, constant aliases, unsupported application lookup and provider relocation/retirement.

Deferred: application nullable values, nullable payload publication, boxed/nested
lookup and new removal/clear syntax. Coalescing and local conditional assignment follow below.

### 2.3 Lazy fallback — first published nullable slice implemented

`nullable<T> ?? T` uses the published carrier's presence/extraction and requires
an exact payload fallback. Zero, false and empty string select the present value.
The left executes once; the fallback executes only for absence. Both branches
are validated. Right association permits `a ?? b ?? fallback`.

Reuse binary AST ownership and existing result facts; no new fields, indexes or
preparation pass. Carrier admission belongs to generated metadata code, while
preparation validates the join and lowering preserves lazy selection and value
copies. See [implementation notes](../../compiler/docs/coalesce.md).

Verification completed:

- [x] cpp/php/js on Clang/GCC, precedence and surrounding short circuit.
- [x] Single evaluation, skipped fallback/failure, left and selected-right failures.
- [x] Present zero/false/empty text; NUL string ownership and vector copy independence.
- [x] Conditional reference mutation invalidates facts before checked conversion.
- [x] Single-pass preparation, syntax ownership, pure emission and reset/reprepare.
- [x] Body/signature edits, repeated failure, deletion/restoration and fresh equivalence.
- [x] Native caller reuse and no-op builds; generator/provider regressions.

Deferred for `??`: application/shared carriers, nullable RHS results, homogeneous
wrapper fallback extraction, result/sentinel families, bare-null/nonnullable left
operands and widening joins. This is a compiler availability slice, not a change
to the broader normative conditional matrix.

### 2.3 Conditional assignment — declared nullable locals, implemented

Implemented statement-form `$n ??= fallback;` using generated presence and ordinary
assignment conversion. Present zero/false/empty text remain unchanged; absent locals
select the fallback once. Null/same-carrier RHS assignments use existing semantics.
Checked casts stay lazy and a failed conversion performs no final store. Earlier
RHS effects remain, including changes to the destination itself.

Reuses the assignment AST and conversion facts. The syntactic `calculates_value()`
query distinguishes eager compound calculations from conditional writes. No new
AST fields, indexes, preparation passes or generated capability are needed. Emits a
plain presence-guarded C++ `if`, with no lambda or snapshot.

Verification checklist:

- [x] cpp/php/js on Clang/GCC; skipped and selected calls, single evaluation.
- [x] Present zero/false/empty string; null and same-carrier assignments.
- [x] Checked conversion failures and success; conditional reference invalidation.
- [x] Destination-mutating fallback and independent NUL string/vector copies.
- [x] Token boundaries, AST ownership, one preparation visit, pure emission and reset.
- [x] Body/signature edits, repeated failures, deletion/restoration and fresh equivalence.
- [x] Native caller reuse, no-op builds and bounded-scope rejection.

Deferred: parameters, global/property/indexed destinations, for-header writes,
assignment expressions, application/shared carriers and new implicit conversions.

Verification passed: the two coalescing-assignment tests and seven existing
coalescing/logical/compound/loop tests (9/9), plus tokenizer, parser and style checks.

### 2.4 Typed populated vector locals — implemented

[Sequence initialization](../../compiler/docs/populated_vectors.md) uses metadata-owned
bulk construction where eligible and generated append bindings otherwise, with resolved
element contracts and direct nested literals.
Inference, keyed literals and additional destination contexts remain separate.


### 2.4 Typed keyed initialization — implemented

The [first keyed slice and checklist](collection_initialization.md#first-typed-keyed-local-initialization-slice--implemented)
covers explicitly typed local hashes with published integer/string keys and values,
duplicate rejection, ordered private input, complete presizing and incremental recovery.
Implementation and cpp/php/js native/incremental proofs are recorded in the
[keyed initialization note](../../compiler/docs/keyed_initialization.md). Constants/static
tables, broader element families and nested keyed construction remain follow-ups.

### 2.5 First tokenizer proof — implemented

**Question.** How close does an ordinary scanner written in Simple C++ come to
handwritten C++ when both recognize the same input and return the same tokens?

The v0.1 runtime at tag `v0.1.77` uses a scan-local `std::string_view` for spelling
comparisons. The branch's `my-try-cpp` scanner compares spans in an immutable byte
owner and gives its token builder the previous token count as a capacity hint.
Use these as references for the experiment. The current PHP tokenizer owns the
lexical contract. [v0.1 scanner](../../../runtime/include/modules/tokenizer/tokenizer.hpp)
· [my-try-cpp scanner](../../../compiler/my-try-cpp/src/tokenize/scanner.cpp)
· [PHP tokenizer](../../compiler/02_tokenize/tokens.php)

**Source slice.** Write the byte loop, lookahead, keyword classification and token
append in Simple C++ in cpp/php/js flavors. Start with the existing checked byte
index operation. Scan valid ASCII identifiers (including underscores and digits
after the first byte), a small documented keyword set drawn from the selected
flavor, space/tab/CR/LF, and the overlapping operators `=`, `==` and `===`.
Match a whole identifier before classifying it and take the longest operator.
Reuse the milestone-1 logical token model. One result owns the source snapshot
and an ordered vector of kind, byte offset and byte length. Compare spelling
in place. Inspect emitted C++ for byte-access calls, copied substrings and token
storage growth. [Token/source proof](../../compiler/docs/token_source_proof.md)

**Verification checklist**

- [x] Use the production PHP tokenizer to generate expected output per input flavor.
  Compare count and every ordered (kind ID, byte offset, byte length) tuple, including
  exact source slices. Cover empty and whitespace-only files, CRLF, long identifiers,
  keyword prefixes/suffixes, adjacent operators and an operator ending at EOF.
  For an unsupported ASCII byte selected from the PHP oracle,
  compare the first diagnostic and byte offset. Choose malformed fixtures against the
  oracle separately for each flavor. A valid token outside the proof vocabulary is
  outside the comparison corpus.
- [x] Rescan after a small source edit, then an invalid edit and recovery. Compare
  recovered tokens with a fresh scan. A retained earlier result must still resolve
  its spans against the original bytes. Exercise an unchanged sibling source to
  verify reuse at the existing incremental boundary.
- [x] On dev002, build generated cpp/php/js and a handwritten C++ scanner with
  both Clang and GCC. Use identical corpus bytes, lexical work, logical token
  fields, output ownership and capacity policy. Record native token size and
  initial capacity for each implementation. Measure a cold estimate separately
  from an edited-input scan given the prior count wherever the storage API supports
  that hint. Disclose any capacity mismatch in the comparison.
- [x] Benchmark in-memory scans on identifier-heavy, operator-heavy and mixed
  inputs. Preload bytes, warm up and repeat, consume the result and verify the complete token dump
  against PHP in a separate run of the same executable. Report median and spread in MiB/s and tokens/s,
  with corpus sizes and token counts. Exclude file I/O, parsing and output formatting.
  Count allocations and allocated bytes per scan in a separate instrumented run.
- [x] Record clean compile and link wall times, emitted C++ bytes, and separate
  Clang frontend/backend profiles. Report Simple C++ prepare/emit separately.
  Fix and record compiler versions, flags, optimization, jobs, runtime-object reuse
  and cache settings. Report generated-to-handwritten ratios per compiler and corpus.
  Use any measured gap to select a follow-up in byte access, spelling comparison
  or token storage.

The first proof ends with parity results, generated-code inspection and a dev002
comparison. Full lexical coverage (including UTF-8 validation, strings, comments
and flavor-specific syntax), production integration, public string views and the
PCRE library port are later decisions.

**Results on dev002, 2026-11-19.** All 12 combinations of source flavor, scanner
implementation and native compiler matched PHP. The resident compiler reused the
unchanged sibling token list, preserved its output through a failed edit, and
matched fresh emission on recovery. The native test retained earlier scanner
results while later input changed and failed. The proof represents the first
lexical failure with its message and byte offset in the returned result.

Without a previous-count hint, generated throughput was 0.35–0.42× handwritten
for identifiers, 0.54–0.70× for operators and 1.19–1.29× for mixed input. Generated
scans used six additional allocations per corpus. Handwritten scans given the
previous revision's count used two allocations total and led on all three corpora.
The application vector currently has no source reserve operation. Separate byte
access and token-vector publication measurements would help identify where to
invest next. [Report and reproduction](benchmarks/tokenizer/report.md)

### 2.5 Token storage experiment — measured on dev002

All four combinations of prior-count reserve and final vector copy/move passed
PHP token parity and retained-result edit recovery across cpp/php/js with Clang
and GCC. In the mixed corpus, reserve alone gave 1.86–2.12× throughput and reduced
allocation calls from 19 to 8. Moving alone gave 1.01–1.08× and reduced calls
from 19 to 17. Together they yielded 1.94–2.30× and six allocation calls.
The experiment modified isolated emitted units. A candidate first language slice
is a metadata-published reserve operation that checks the capacity request and
preserves vector size, element lifetimes and checked indexing. The caller can use
the last successful count as a bounded hint. Last-use ownership transfer needs its
own proof before generalization. [Storage comparison](benchmarks/tokenizer/storage/report.md)

### 2.5 Source-level vector reserve — implemented

The scanner in all three flavors calls the metadata-published vector member.
Its driver starts with a zero hint and preserves the last successful count through a
failed edit. The scanner caps the requested capacity at the new source byte length.
Source lowering emits the direct member call and retains final vector copy assignment.

On dev002 the cpp/php/js programs matched the PHP oracle on Clang and GCC for
zero, low and high hints, an invalid edit and restoration. Both published integer
vectors and concrete token vectors passed size, read/write bounds, negative count,
overflow, failed allocation and safe subsequent growth checks. With near-exact
hints the mixed corpus used eight allocations instead of nineteen and ran at
212–266 MiB/s. [Measurements](benchmarks/tokenizer/storage/source-reserve.dev002.json)

**Capacity at initialization — proposed.** Allow an empty destination value to
request capacity when it is constructed. A candidate PHP-flavor spelling is
`$items = vector<Token>::with_capacity($previous_count);`. This is a named factory
proposal. It returns size zero with room for at least the requested number of
elements; the first append produces element zero. Reuse the published reserve
checks for zero, negative and excessive counts and preserve safe later growth.
Keep the provider responsible for storage. Check existing static-factory syntax
for parameterized types before choosing the final spelling. In standard C++,
`std::vector<Token>(n)` constructs **n elements**. Rust's
`Vec::with_capacity(n)` is precedent for the named capacity contract.
[Standard C++ vector constructors](https://eel.is/c++draft/vector.cons) ·
[Rust Vec capacity factory](https://doc.rust-lang.org/std/vec/struct.Vec.html#method.with_capacity)

### 2.5 Tokenizer integration roadmap — planned

The isolated whole-file prototype reached 98.5% of handwritten throughput on Clang
and 99.2% on GCC for one warm scan of each of nine sources on dev002. Those figures
describe the combined prototype. Use the individual experiments to decide which
changes to integrate and verify each through the ordinary source compiler.
[Whole-file comparison](benchmarks/tokenizer/byte_access/source_native.md) ·
[Native-work inventory](benchmarks/tokenizer/native_work.md)

| Order | Integration decision | Owner and reuse |
|---|---|---|
| In use | Reserve using a previous count when available. | The caller chooses the hint. The published vector operation preserves size and checked access. |
| 1 | Transfer an owned value at a proven last use in assignment. Start with publication of the tokenizer’s local vector. | Body preparation proves eligibility. The value provider defines transfer semantics and the C++ emitter selects the assignment form. |
| 2 | Establish one retained source owner, then acquire a scoped checked byte view. | The scanner chooses the owner. A runtime capability can serve parsers, decoders and other byte readers. Verify validity for inline and heap strings before exposing the view. |
| 3 | Construct fresh values directly in destination storage when the destination supports it. | Preparation preserves argument evaluation and construction semantics. The destination provider owns capacity, lifetime, growth and failure. Prove reuse with a second record type. |
| 4 | Use ordinary constant lookup tables where source workloads justify them. Keep the tokenizer’s short-circuit equals lookahead. | Byte classifications belong to the scanner. Constant table lookup can also serve decoding and escaping. |
| Later | Revisit range facts when a second consumer justifies their preparation cost. | The measured nonnegative cursor is a local proof. General range analysis and unchecked indexing are outside the first slice. |

### 2.5 First integration slice — last-use transfer of owned values

**Contract.** Source assignment retains value semantics. Lowering may consume a
private source value when the destination receives the same value and no permitted
observer can distinguish the transfer through later reads, aliases, cleanup,
identity, required checks or effects. Eligibility belongs to the assignment of
the **whole value**. A concrete provider must support a value-preserving,
nonthrowing transfer assignment, release previous destination ownership correctly,
and leave the source safe to destroy. Verify that eliding the copy is allowed by
the source contract, including any observable copy hooks or required failures.
A C++ move constructor or an element-level move trait alone does not establish
this contract. [Runtime ownership design debt](safe_low_level_concepts_debt.md)

**First admitted shape.** At the successful exit of the scanner, a plain owned
local is assigned with an identity conversion to an exact-type field of a
distinct, private local result, immediately followed by returning that result.
Earlier loops may populate the local and earlier exits may discard it. The
publication itself executes once, on a straight-line tail. For
`out.tokens = items; return out;`, C++ may select the provider’s move assignment.
An early lexical error still drops the unpublished local. Keep returning the
result through its existing lowering. The field name, vector family, token type
and source spelling play no part in the eligibility query.

**Ownership and preparation budget.**

- Reuse resolved storage identities, prepared assignment conversion and the
  type provider’s generated facts. Body preparation owns the last-use and
  alias decision. Start at candidate tail assignments and use existing facts or
  a bounded body-local use check. Keep syntax nodes light. A missing proof selects
  ordinary copy assignment.
- Check the source’s history as well as the tail. A writable reference, escaped
  handle to the local or its elements, captured binding, or observable cleanup
  can survive the last textual mention. Distinguish such aliases from independent
  earlier value copies. A later use of the same storage, even under another name,
  invalidates transfer. Require a stable destination disjoint from the source.
- Known nonescaping operations such as populating the vector must remain eligible
  under their actual contracts. Treat unknown alias effects conservatively.
  Parameters, globals, fields as sources, self/overlapping assignment and
  uncertain control flow retain existing lowering in this slice.
- The prepared decision belongs to the body revision. C++ field-assignment emission
  consumes it without a second liveness walk or a handwritten vector branch.
  Reuse the existing provider/dependency publication path for type eligibility.
  Review the existing field-assignment owners in
  [preparation](../../compiler/04_analyze/prepare/prepare.php) and
  [C++ emission](../../compiler/05_backend/cpp/emit.php).

**Implementation and verification, 2026-11-19.** The vector provider opts into
`private_value_nothrow` for the whole value. Preparation screens the final two
statements, then reads existing block occurrence indexes for the candidate's two
resolved locals. The assignment carries one boolean. Earlier ordinary copies,
append and validated nonretaining receiver operations are admitted. Unknown
uses, for/foreach headers and nonlocal storage select the existing copy path.
No program-wide liveness state or cross-body reads are needed.

- [x] The original scanner emits `std::move` across cpp/php/js. Matched native
  runs on dev002 with Clang and GCC verified all 11 oracle fixtures, full spans,
  empty input, lexical error with no partial tokens, recovery and retained results.
- [x] With the same 5,120-token mixed input and reserve hint, all six native
  pairs measured 8 allocations / 286,834 bytes with the capability disabled and
  6 / 163,930 with transfer. The difference comprises 122,880 record bytes
  and 24 storage bytes. No scanner throughput claim follows from allocation counts.
  Replacing an already populated destination preserved token parity. A separate
  counted-element probe checks release of the old element, absence of element
  copy/move during transfer, and final destruction.
- [x] A later read or earlier writable reference restores copy. Removal restores
  transfer. Tests include nested shadowing, an independent earlier value copy,
  overlapping field input, ordinary string assignment, a nonidentity conversion,
  and diagnostics plus recovery after an invalid edit. Removing provider eligibility
  in fresh compiler sessions restored the copy without changing scanner source.
- [x] Resident output matched fresh output across all three flavors. No-op builds
  prepared and emitted zero bodies. Native Ninja builds reused the caller and
  records objects on scanner body edits on both compilers.
- [ ] Full caller **preparation and emission** reuse remains open. Even with the
  records in a separate unit and an unchanged signature, retiring a function
  whose signature watches an application type conservatively notifies its callers.
  Here the scanner and its direct caller are prepared again, and the unrelated
  function is reused. Reworking nominal-signature invalidation must preserve
  notification on simultaneous type replacement and import rebinding.

**Preparation cost on dev002.** PHP 8.5, Xdebug off, CPU 7, medians of 11 warm
batches. Full-body measurements include reset and preparation, with parsing and
emission excluded. Proof-only measurements run on the already prepared tree.
The screening column uses the fixture's entry body, which has no candidate.

| Flavor | Scanner body before (µs) | Scanner body after (µs) | Candidate proof alone (µs) | Noncandidate screen (µs) |
| --- | ---: | ---: | ---: | ---: |
| cpp | 532.96 | 542.78 | 9.14 | 0.09 |
| php | 457.71 | 467.06 | 8.36 | 0.08 |
| js | 519.93 | 525.22 | 8.81 | 0.08 |

The resident project check also records per-edit and no-op wall times (including
frontend, preparation, emission and bundle I/O). In the local verification run,
restoration took 5.2–6.3 ms and the following no-op 1.5–1.8 ms.
[Preparation measurement](benchmarks/tokenizer/transfer_preparation.php) ·
[Incremental proof](../../compiler/tests/last_use_transfer.py) ·
[Lowering catalog](../../compiler/docs/php_lowering_catalog.md#published-vectors-and-hashes)

**Deferred.** New source move syntax, consuming call parameters, whole-program
alias analysis, general control-flow liveness, changing shared/unique semantics,
source/view integration, direct slot construction, cursor proofs and scanner
classification changes are separate decisions. The first slice changes only the
lowering of proven assignments.

## 3. Object relationships — a small AST family

Discuss inheritance, handles and interfaces together before implementing this milestone.

| Order | Group | Initial scope |
| --- | --- | --- |
| 3.1 | Base storage and inheritance | One base, derived fields, inherited access, protected access and construction. No multiple class inheritance. |
| 3.2 | Object contracts, then behavior through base views | First class/shared defaults and explicit function nullability with the temporary relaxed property rule below; then ownership-preserving shared upcasts; then virtual/abstract methods and overrides. Preserve destruction and allocation identity. |
| 3.3 | Type tests and narrowing | Subtype tests, then interface-capability tests. Keep exact concrete type identity distinct. |
| 3.4 | Interfaces | Interface-typed parameters/references and multiple capabilities using the dispatch model. |
| 3.5 | Non-owning AST links | Define validity of parent links and retained-generation references. Reuse weak handles where suitable; discuss borrow/reference machinery where needed. |
| 3.6 | Staged initialization and composition | Write-once attachment, settling ranges and a declaration trait/interface use. Evaluate bounded traits versus a simpler truthful owner. |

Proof: one base node, two concrete expressions, child attachment and a base-typed operation. Check overrides, original-object identity, destruction and invalid access.

Do not translate every PHP object reference into an owning shared handle. Ownership cycles and generation lifetimes must be addressed explicitly.

### Agreed implementation direction — 2026-11-14

Use native C++ inheritance and virtual dispatch for the C++ backend. Do not build a
custom vtable, object-layout engine or general ABI framework for this milestone.
This is a planning decision, not implementation or a new source-language contract.

- **Compiler / static analysis:** resolve bases and interfaces; check inheritance
  cycles, visibility, construction, abstract obligations, overrides and admitted
  handle conversions. Prepare these facts before emission. Emit source-oriented
  diagnostics rather than relying on native compiler errors as semantic validation.
- **C++ backend / native compiler:** lower the accepted hierarchy to native base
  classes, virtual methods and abstract interface classes. Let C++ perform layout,
  dispatch, base/interface pointer adjustment and base/member destruction. Use a
  virtual destructor for polymorphic bases/interfaces; do not add source-level
  explicit delete as part of this work. Preserve source mappings for debugging.
- **Runtime ownership:** retain/release, weak observation, concrete payload
  destruction and allocation release belong to the ownership mechanism. Native
  dispatch does not determine which source links own objects or how long a
  generation must remain alive.

An upcast changes the accessible view, not the owned allocation. Converting a
shared Concrete to a shared Base/interface must preserve one control block,
reference counts, complete-object destruction and allocation identity. Copies
retain that owner; moves transfer it; absent handles remain absent. A base/interface
view can have an adjusted address, so view-address equality alone must not become
allocation identity. Never construct a second independent owner from an adjusted
raw pointer. Weak locking must preserve both the owner and the applicable view.

Current implementation evidence: application-record shared/weak handles are
exact-type generated owners in `compiler/05_backend/cpp/emit.php` (`owner_methods`).
Their allocation embeds the payload and strong/weak counts; final release directly
invokes the statically known payload destructor. There is no generic concrete
payload destruction callback in this path today. This differs from the published
runtime ownership wrappers. Generalizing application ownership requires an explicit
runtime/generator ownership design, not a reinterpret-cast between typed allocation
structures or extra semantic decisions embedded in the C++ emitter. Decide the
control/view representation before implementation; no two-pointer layout or extra
allocation is mandated by this note. Review any wider ownership relocation first.

### First extends slice — base storage, inherited fields and construction

Scope: the bounded base-storage part of 3.1. The first proof does not require
virtual methods, abstract classes, base-typed owning links or shared-handle upcasts.
Those belong to the subsequent polymorphic AST slice, not prerequisites hidden in
this one. Source spelling must follow the active flavor/record contracts; this
planning note does not introduce a new class-versus-struct ownership default.

| Compiler stage | Responsibility in this slice |
| --- | --- |
| Tokenizing | Recognize the flavor's base-declaration syntax (`extends` where applicable), retaining normal keyword/source-position handling. |
| Parsing | Store one optional owned written base-type reference on the record declaration. Preserve its spelling and source location even when resolution fails. Do not infer the base or copy inherited members into syntax. |
| Declaration collection / resolution | Use existing namespace/import and declaration lookup to resolve the base. Store one non-owning resolved-base definition reference, separate from the written syntax. Register dependencies through the established incremental owner. |
| Preparation | Validate an admissible single base and acyclic hierarchy; resolve inherited field lookup and visibility, field-name conflicts, base construction and argument conversions. Diagnose unsupported/invalid forms before emission. Prepare reusable facts rather than repeating semantic work in the emitter. |
| Lowering | Emit native single inheritance, the derived type's own fields, prepared inherited accesses and base-before-derived constructor initialization. Reuse published names and include dependencies. Let C++ implement base layout and destruction; do not introduce custom layout or dispatch. |

Start inherited lookup by checking the current declaration and following its
resolved base through the existing member-resolution owner. Do not add a flattened
inherited-members index unless evidence warrants it and invalidation is defined.
Use existing dependency tracking for base edits rather than an independently owned
reverse index. Written base syntax is owned; resolved bases and lookup results are
non-owning references with explicit publication/invalidation boundaries.

Preparation remains bounded but is not merely index population: STAN must detect
invalid inheritance without requiring C++ emission/compilation. Lowering consumes
the settled relationship and construction plan. The exact field-conflict and base-
constructor source rules must be checked against their owning contracts before
implementation; this note does not silently adopt all PHP or C++ behavior.

Proof: a concrete Node record with a small source-range field, and two concrete
records such as IntegerLiteral and Addition extending it. Give each derived record
its own scalar fields. Construct and use each through its exact concrete type,
read inherited public fields and access protected base fields from derived code.
Exercise supported base-constructor arguments/defaults and prove base-before-
derived initialization. Addition need not own polymorphic children yet; the complete
AST tree and base-typed operation remain the later milestone proof.

Implementation status: the first single-base **struct** slice is implemented.
See [the compiler support boundary](../../compiler/docs/inheritance.md) and
[the repeatable native proof](../../compiler/tests/extends_native.py).
The written base is owned syntax; the resolved base is a non-owning canonical
layout dependency. Inherited fields/constructors retain their declaring owner.
The emitter uses native inheritance and braced base initialization, with existing
exact-type ownership and split-header publication. Production runtime storage is
unchanged. Ordinary inherited-method/static-member lookup and protected ordinary
methods/static members remain follow-up work alongside the polymorphic slice.

Verification checklist:

- [x] Execute the same bounded proof across cpp/php/js on Clang and GCC.
- [x] Verify inherited storage and derived fields are independent and initialized
  correctly; inherited access does not duplicate or copy the base payload.
- [x] Verify base construction, applicable defaults/conversions and failure cleanup
  follow the source contract. A failed base construction must not run the derived
  constructor body or leak already-created resources.
- [x] Reject unknown/invalid bases, cycles, unsupported multiple bases, inaccessible
  fields and invalid constructor arguments before emission (grammar rejects
  multiple bases; preparation checks semantic errors). Apply the owning
  field-conflict rule rather than relying on native C++ rejection.
- [x] Keep virtual overrides, abstract declarations and derived-to-base shared
  conversions outside this slice; do not accidentally admit them through C++.
- [x] Preserve existing exact-type inline/shared/weak behavior, including copies,
  alias mutation, absence, destruction and weak expiry where currently supported.
  Use native destruction instrumentation if source destructors are unavailable.
- [x] Incremental base/member/constructor edits, failed resolution and recovery
  match fresh output; replace stale resolved references and inherited lookup facts.
- [x] Verify declaration ordering, include selection and cross-unit base lookup
  through existing owners, without emitter-specific semantic decisions.

Verified with `XDEBUG_MODE=off python3 -B v0.2/compiler/tests/extends_native.py
--memcheck`: cpp/php/js passed on Clang and GCC. The native tests cover explicit
and implicit base initialization, inherited constructor contracts/defaults,
protected receivers/constructor visibility, private/private field-name reuse, checked
conversion failure before later arguments/body, namespace and split-header
publication with default or disabled application namespaces, inline copies, shared
alias mutation and weak expiry. Qualified adapter calls remain visible in native
base initializers outside constructor-body imports. Generated code
passed ASan/UBSan; separate Valgrind runs reported all heap blocks freed. The
unchanged runtime archives were compiled with the matching native compiler and
standard library, without sanitizer instrumentation.

Retained/fresh tests include base removal/cycles, field conflicts, constructor
signature/default/access edits and error retries/recovery. Equal-size edits
explicitly advance filesystem timestamps under the existing mtime/size policy;
unchanged child sources retain their timestamps. CLI parse/prepare inspection
recognizes the new relationship and base-initialization nodes in all flavors.
Existing constructor, weak-owner and static-property native suites passed all
three flavors; AST ownership/location checks and compiler writing checks passed.
The broader `tests/cli.py` suite has an existing diagnostic-text expectation
failure at line 46, reproduced against an isolated HEAD compiler using the same
unchanged generated bindings. It is not an extends regression and remains outside
this slice. No execution-speed or compilation-speed claim is made.

Review fixes verified before commit:

- `this` now subscribes its prepared body to the declaring record's stable symbol.
  The existing layout chain propagates inherited field type/access changes to
  ordinary methods; no inherited-member index or separate invalidation engine
  was introduced.
- Allocation factories for effective authored constructor contracts are emitted
  once in each payload's source, with complete signature types collected there.
  Locally authored and inherited constructors share that publication path. The
  inline inherited-factory special case was removed; empty shared/weak consumers
  and resource-bearing record headers require only declarations.
- `extends_regressions.py`, invoked by `extends_native.py`, covers a three-level
  hierarchy, field type/access/removal errors, actual base-file deletion, repeated
  errors, recovery, changing the selected base and retiring the old subscription.
  Retained output/diagnostics match fresh compilation. A no-op prepares/emits
  nothing; an unrelated function remains reused and its source is not emitted.
- Constructor publication tests cover empty shared/weak handles, handles stored
  in another record, inherited record/shared-handle parameters, actual calls,
  parameter-type edits and restoration. Each derived factory has one `.cpp`
  definition. Native compilation/execution passes on Clang and GCC in all three
  flavors, including each successful incremental recovery.

Verification after the fixes: the full extends suite with `--memcheck` passed
cpp/php/js on Clang and GCC (including ASan/UBSan and Valgrind cleanup checks).
The expanded factory-signature edit matrix passed separately for all six
flavor/compiler combinations. Existing constructor and weak-owner native suites
passed all three flavors; compiler writing checks and `git diff --check` passed.
The previously recorded baseline CLI diagnostic-text failure remains unrelated.
Virtual overrides and shared-handle upcasts remain deferred.

Deferred: virtual/abstract methods and overrides; base/interface shared-handle
upcasts and cross-type identity operations; interface declarations and multiple
capabilities; base-value slicing conversions; downcasts/type tests; parent method
calls beyond base construction; multiple class inheritance; polymorphic child
collections; non-owning parent/generation lifetimes; write-once attachment; traits;
reflection; explicit deletion; custom dispatch; ownership-layout redesign and
optimization campaigns. Plan those against the agreed native-C++/runtime division
above, but do not implement them as part of this first extends slice.


### Next slice — class defaults and object nullability boundaries

Doc Status: planning. Agreed direction after the first extends slice; not an
implementation claim. Establish these contracts before generalizing shared base
views. Object borrowing is deferred and is not a prerequisite for this slice.

#### Agreed defaults and temporary property rule

- A bare class type `Node` uses shared object storage by default. Copies share
  object identity; they do not copy the payload. A bare struct type uses inline
  value storage and the existing memberwise copy rules. The keywords do not adopt
  C++'s merely visibility-based distinction.
- Keep nullability explicit at function/method parameter and return boundaries:
  `Node` requires an object; `?Node` accepts absence. Known null rejects for a
  required boundary; a potentially empty value requires a presence check before
  the callee body or return boundary completes. Optional boundaries accept null
  without inventing an object. Required-to-optional adaptation preserves identity.
- Temporarily relax **shared-object instance properties**, including properties
  declared inside either classes or structs. `public Node $left;` starts empty,
  accepts `= null`, and accepts later assignments of `null`, despite having no `?`.
  This is not restricted to construction or initialization.
- Do not introduce property initialization/definite-assignment analysis, additional
  presence checks on property reads/copies/writes, or constructor-completion
  validation for those properties. Preserve ordinary member type/access checks
  and the runtime's existing empty-handle dereference guard; this relaxation does
  not authorize undefined native dereferences.
- Passing such a property to a required parameter or returning it as required
  still uses the function-boundary presence check. Its written `Node` annotation
  is not proof that its current value is present. `?Node` remains meaningful for
  explicitly nullable interfaces even though these properties temporarily share
  the same empty-storage permission.

Examples of the agreed property policy (proposed class support, not current
compiler coverage):

```php
class Addition {
    public Node $left;
}

struct Node_holder {
    public Node $left;
}
```

Both `left` properties may remain empty and may later be reset to null. This rule
concerns shared-object storage, not nullable inline struct payloads, scalars or
containers. Static-property policy is not expanded by this instance-property
slice. Do not broaden required local/value contracts by implication.

This decision deliberately supersedes the **initialization-only** restriction in
[type-use modifiers](../../../specs/type_use_modifiers.md#temporary-required-field-storage-exception--2026-10-25)
for the planned instance-property slice. The owning normative contract must be
updated alongside implementation; this planning note is not semantic authority.
Do not silently restore the old later-null-assignment rejection or stronger
property validation while implementing the agreed exception.

#### Bounded implementation scope and owners

The next slice admits class declarations with shared-by-default type uses and
exact-concrete construction, assignment, instance properties and callable
parameters/returns. Reuse existing field/method/constructor machinery and the
current exact-type ownership mechanism. Preserve existing struct behavior and
explicit shared struct handles. Class inheritance/upcasts are not prerequisites
for this proof; no control-block/view redesign or two-pointer representation is
selected here.

| Stage / owner | Work in this slice |
| --- | --- |
| Parsing / collection | Retain class versus struct declaration kind and written nullability in the common model. Reuse nominal publication and member ownership; do not create a separate class-only compiler pipeline. |
| Type/signature preparation | Resolve nominal payload identity separately from default storage and required/optional value contracts. Keep required and optional class uses distinct even if their C++ storage is identical. Reuse existing conversion/binding owners. |
| Property preparation | Record the relaxed property contract locally. Property reads remain potentially empty; do not mark all occurrences of the canonical class type nullable or infer presence from its spelling. No property initialization dataflow analysis is required. |
| Boundary preparation | Decide required versus optional parameter/default/return admission and prepare any necessary presence conversion. A null default requires an optional parameter. A reference to handle storage remains distinct from borrowing the object. |
| Lowering / ownership | Consume prepared storage/conversion facts, reuse exact-type allocation/destruction and native payload layouts, and emit checks only at required function boundaries or existing guarded dereferences. No emitter-owned nullability inference or new shared ownership ABI. |

A plain class parameter receives a shared handle by value; object mutation is
visible through aliases, while rebinding the parameter does not replace the
caller's handle. Existing explicit reference-parameter rules concern caller
storage and must not become implicit borrowed-object parameters. General object
reference-parameter extensions are outside this slice.

#### Incremental contract and verification checklist

Use stable nominal/signature symbols and the existing dependency owner. Consumers
must observe declaration kind/default storage, property contract, and callable
nullability/default changes. A cached body must not retain an old requiredness or
presence-conversion decision after a signature edit. Keep `this` record watches
and original member ownership from the extends review fixes. Semantic
re-preparation, source re-emission and native rebuilding are separate concerns;
unchanged emitted text does not prove cached semantic facts are current.

- [x] Run equivalent proofs in cpp/php/js on Clang and GCC.
- [x] Prove bare class copies/arguments preserve object identity and mutation;
  parameter rebinding leaves the caller unchanged. Struct copies remain values.
- [x] Exercise an uninitialized and explicitly null shared-object property in
  both a class and a struct; allow null reset after a non-null assignment.
  Confirm no extra property presence checks or initialization analysis are emitted.
- [x] Required parameters/returns reject known null; optional parameters/returns
  and optional null defaults accept it. Empty property values fail required
  function boundaries before the callee body executes; populated values pass
  without payload copies or duplicate evaluation.
- [x] Empty property dereference retains the existing runtime failure behavior;
  resetting a property releases its ownership normally. Verify alias lifetime,
  exact-type weak expiry and cleanup without introducing owning parent cycles.
- [x] Edit class/struct declaration kind, callable required/optional annotations,
  defaults and property types with unchanged consumers. Compare diagnostics and
  generated output with fresh compilation through errors, retries and recovery.
- [x] Verify watch replacement after a property target/signature changes. No-op
  compilation prepares/emits nothing; unrelated bodies and sources remain reused.
- [x] Check cross-unit declarations/includes and native rebuilds after storage
  or signature changes; retain one factory definition per concrete payload.
- [x] Update the owning nullability contract and implementation documentation,
  explicitly retaining this temporary relaxed property policy.

#### Implementation and proof

Implemented the common `ast_record_declaration` / `ast_instance_field` model with
one immutable declaration-kind enum. The nominal payload owns the optional and
required class identities; structs reuse their existing shared identity. There
are no new per-expression flags, field initialization indexes or preparation
traversals. Existing member ownership, layout preparation and signature watches
remain authoritative. Failed nullable signature lookups register their watch owner
before resolution so recovery/retirement also works after failure.

`classes_native.py --memcheck` exercises cpp/php/js with Clang and GCC: shared
mutation versus parameter rebinding, struct value copies, constructors/methods,
all four empty property forms, null reset, optional defaults, required argument
and return failures, single evaluation, weak expiry and Valgrind cleanup. It
compares fresh and retained output/diagnostics after kind, property target,
nullability/default and missing-type edits, including retries. Generated checks
are asserted at boundaries and absent from ordinary field assignment/reset bodies.
No-op and unrelated-source reuse are asserted. CTest registers the repeatable proof.

The existing extends, constructor, weak, enum-method and failure-cleanup proofs
also pass. The general retained/fresh suite covers 100 revisions and 24 fresh
checkpoints per flavor. This establishes functional reuse, not a measured compiler
speedup or native parallel preparation proof.

Review follow-up closes the remaining gaps: the repeatable class suite now recovers
by adding/deleting/restoring the missing provider with signatures and callers
unchanged. Successful return-contract edits (`?Class` ↔ `Class`) assert that the
unchanged caller adds/removes its presence conversion, matches fresh output and
runs correctly. Source `weak()`/`lock()` exercises optional typing, live identity,
retention, expiry and a required argument failure before callee execution. The
nullable resolver reuses its resolved element in the common specialization lookup;
it no longer repeats runtime element resolution. Existing carrier type/value
proofs cover that path. No new caches or preparation passes were introduced.

Coverage limits remain explicit: required class locals need an initializer until
definite-assignment support lands; obtaining an owning `$this` value is deferred
(field/method receiver use works). Static-property policy is not broadened by this
instance-property exception. See [implementation details](../../compiler/docs/classes.md).

#### Follow-up boundaries

The follow-up below implements ownership-preserving class base views. Native
virtual/abstract behavior remains the next separate discussion. Explicit shared
struct handles retain exact-type conversion admission for this bounded slice.

Defer `ref Node`, `Node*`, borrowed object parameters, stored/returned borrows,
new unique/value overrides, interfaces, virtual overrides, shared-struct upcasts,
downcasts, parent/generation lifetime rules and cycle management. Ordinary PHP-like
object passing does not require borrows: shared identity already supports object
mutation. Explicit PHP-style reference parameters alias variable/handle storage;
they are not a reason to introduce object borrowing now.

### Implemented slice — class inheritance and shared base views

Doc Status: planning. Execution record for the agreed follow-up; the owning
inheritance specification remains semantic authority. Virtual methods and
override enforcement are not implemented by this slice.

Proof target:

```php
class Node { public int $start = 0; }
class IntegerLiteral extends Node { public int $value = 0; }
function start_of(Node $node): int { return $node->start; }
$literal = new IntegerLiteral();
echo start_of($literal);
```

Implemented representation: a typed view pointer plus a pointer
to the shared control block. A single allocation owns the control block and concrete
payload; derived/base handles retain that allocation and may have different typed
view pointers. Concrete destruction belongs to the allocation's retained destroy
operation, not the static type of the view. This costs a second pointer per handle;
do not assume an exact-type allocation pointer can be reinterpreted as a base
allocation. The runtime owns the control block and count operations; generated
allocation callbacks destroy the concrete payload and release its storage. A
concrete inline adapter transfers each demanded source/target view pair. The
application owner backend supplies that pattern; the reusable engine owns
deduplication and emission, without C++ templates. No
ABI framework or new expression facts were introduced.

Weak views must retain the same control block and only expose their typed pointer
after successfully obtaining strong ownership. An expired cached view pointer
must never be dereferenced. Identity compares the allocation/control block, not
an adjusted view address. Preserve the current thread-confined ownership contract;
this slice does not add thread-safe counters.

| Owner | Bounded change |
| --- | --- |
| Parser / record AST | Reuse the single written base and existing declaration kind; no new member inventories. |
| Declaration preparation | Admit single class-to-class inheritance, settle ancestry before consumers and preserve original field/constructor owners. Reject cycles and cross-kind bases. |
| Conversion preparation | Prepare derived-to-base shared conversion using settled ancestry; compose with existing required/optional presence conversion. Keep downcasts and unrelated conversions rejected. |
| Runtime ownership / C++ lowering | Preserve the control block and concrete destruction while C++ adjusts the typed view. Emit settled conversions without repeating ancestry searches. |
| Incremental | Watch nominal ancestry and callable contracts through existing stable symbols. Base edits invalidate dependent conversions and cached callers, including when native spelling stays unchanged. |

Verification:

- [x] Base field access and construction through a derived class in cpp/php/js.
- [x] Required/optional derived-to-base parameters, returns and assignments;
  preserve relaxed shared fields, known-null rejection and runtime presence checks.
- [x] One allocation identity across views, alias mutation and exactly-once
  concrete destruction after the final strong reference, regardless of view type.
- [x] Weak locking before/after expiry; copies and reassignment release the right
  allocation; failure paths clean up on Clang/GCC with memory checking.
- [x] Reject unrelated conversion, downcasts, inheritance cycles and cross-kind
  bases; no borrowed-object or implicit owning-this capability is introduced.
- [x] Edit/remove/restore base edges with unchanged callers; compare retained/fresh
  diagnostics and C++, check no-op/unrelated reuse and rebuild native consumers.
- [x] Record handle size/allocation impact. Do not claim execution or compilation
  speed improvements without measurements.

The repeatable `shared_upcasts_native.py --memcheck` proof runs cpp/php/js with
Clang and GCC: three-level ancestry, explicit/inherited base constructors,
field mutation through views, parameter/return/property conversions, null checks,
related-view identity, weak expiry and concrete destruction. A derived-only
owned field proves cleanup through the last base view; a throwing derived
constructor proves partial-construction cleanup. Successful and failing ancestry
edits compare retained/fresh output and diagnostics, with native rebuilding,
no-op and unrelated reuse checks. Unsupported method replacement stays rejected.

On the tested 64-bit Clang/GCC targets, strong and weak handles are each 16 bytes
(previously 8); the common control is 24 bytes (previous counters were 8). The
fixture's 24-byte Leaf payload occupies one 48-byte allocation. Upcasting creates
no allocation. These are storage measurements, not timing/speedup claims.

The existing class-defaults, extends, weak, shared-reference and constructor
proofs protect previous behavior. The runtime header is independently compiled
on both compilers; generator publication/input checks and generated freshness
also cover the publication change. Generator contract capture passes 130 accepted
and 247 rejected runtime cases. Isolated committed-HEAD comparison across all 130
accepted cases confirms that only `public/scpp/owner_access.hpp` changes. The two
frozen-baseline CTests remain red on pre-existing arithmetic-header/runtime-source
and input-hash drift, reproduced with committed HEAD; only the ownership-header
hashes were updated here. Do not describe those frozen comparisons as passing.
See [implementation details](../../compiler/docs/shared_upcasts.md).

### Concrete inline adapter engine — implemented

The first pattern handles admitted shared class upcasts. Preparation still owns
ancestry and nullability; lowering consumes its conversion and requests a concrete
native signature/body. The engine knows parameter modes, result type, explicit
provider headers and exception contract. It does not infer ownership semantics.
Required/optional occurrences share a native adapter; required-presence checks
remain at the use. No new prepared expression facts or AST traversal were added.

Source-private requests are merged as immutable text during bundle publication.
Per-source demand survives incremental reuse; the final demand union determines
which fragments remain. Generated fragments follow complete type dependencies;
they are internal includes, not standalone public headers. Single-file output
embeds the same definitions. See [engine ownership](../../compiler/docs/inline_adapters.md).

Verification (`inline_adapters_native.py`, plus `shared_upcasts_native.py --memcheck`):

- [x] cpp/php/js native correctness on Clang and GCC, including constructor arguments.
- [x] One adapter per native signature despite multiple uses and presence contracts.
- [x] No adapter for unused pairs and no C++ application-view template.
- [x] Retained/fresh equivalence after demand removal, final-use removal, restoration,
  error/retry/recovery and root-namespace edits; obsolete fragments are deleted.
- [x] No-op preparation/emission reuse and unchanged adapter-using source reuse.
- [x] Persistent Ninja builds on Clang/GCC for cpp/php/js: no-op preserves object
  and executable timestamps; a single-consumer edit rebuilds only changed C++;
  touching an adapter header rebuilds exactly its consumers through native depfiles.
- [x] Successful source deletion/restoration: removing one consumer preserves the
  surviving cached consumer and adapter; removing the last use retires the fragment
  from disk and the manifest. Restoration recreates demand. Every revision matches
  fresh output, preserves timestamps for unchanged artifacts and runs natively.
  Source removal relinks without recompiling unrelated translation units.
- [x] Global/custom namespaces, complete include ordering and standalone emission.
- [x] Shared identity, null checks, concrete destruction and weak lifetime retained.

Deferred: unique ownership conversion semantics, borrowing, downcasts, interfaces,
virtual dispatch, arbitrary template parsing and new adapter patterns. Native
reference/move parameter spelling is engine vocabulary, not source feature admission.
No compile-time or execution-speed improvement is claimed without measurements.

### Concrete virtual methods and mandatory override intent — implemented

Add public concrete instance virtual methods, inherited method lookup and exact-contract
explicit overrides for the small class AST family. A base method may supply a default
implementation; descendants may inherit it unchanged or explicitly override it.
Include final-method protection. Ordinary unmarked methods remain nonvirtual, and
nonvirtual hiding stays rejected. This records the completed bounded slice; the normative inheritance contract
remains the semantic authority.

The [owning inheritance spec](../../../specs/inheritance_v0_2.md#abstract-types-and-overriding)
requires explicit override intent in every flavor. Reject missing/spurious markers,
final-method replacement and incompatible contracts in common preparation; emit native
C++ `override` as an additional check. C++ itself makes its marker optional; Simple C++
does not. Inheriting an implementation unchanged needs no declaration or marker.

Agreed PHP-flavor spelling additions: support both zero-argument attributes
`#[\Virtual]` / `#[\Override]` and declaration modifiers `virtual function` /
`override function`. For example, these pairs express the same intent:

```php
#[\Virtual]
public function evaluate(): int { return 0; }

public virtual function evaluate(): int { return 0; }

#[\Override]
public function evaluate(): int { return $this->value; }

public override function evaluate(): int { return $this->value; }
```

These are alternative declarations, not four methods in one class. The modifiers
are Simple C++ PHP-flavor syntax, not a claim of standard PHP compatibility. Normalize
attributes and modifiers to one common written intent, with source locations for
errors. Reject duplicate intent (including attribute plus equivalent modifier),
conflicting Virtual/Override intent, and attribute arguments. The owning inheritance contract records the modifier aliases. No second semantic
path or general attribute engine was introduced.

| Flavor | New virtual contract | Replacement of inherited virtual contract |
| --- | --- | --- |
| cpp | `virtual int evaluate() { ... }` | `int evaluate() override { ... }` |
| php | `#[\Virtual] public function evaluate(): int { ... }` or `public virtual function evaluate(): int { ... }` | `#[\Override] public function evaluate(): int { ... }` or `public override function evaluate(): int { ... }` |
| js | `virtual evaluate(): int { ... }` | `override evaluate(): int { ... }` |

Virtual establishes a new contract; Override replaces an inherited one. Repeating
Virtual on an inherited contract is invalid. Constructors, static/private methods
and lifecycle declarations are not admitted as virtual/override methods here.
Final implementations reject further overrides. Keep parameter and return types,
passing modes, nullability and optional-parameter positions exact in this slice;
parameter names and default values may differ. Defaults and conversions come from
the statically selected declaration, never the runtime implementation. Reject
unsupported variance/reference-return cases explicitly until their later slices.

Example proof: Node.evaluate returns 0; IntegerLiteral.evaluate overrides it and
returns its value. A function taking bare Node receives an IntegerLiteral through
the existing shared-view adapter and dispatches evaluate to the derived body.
An intermediate descendant without a declaration inherits the implementation.

| Owner / stage | Bounded responsibility |
| --- | --- |
| Tokens / parsers | Recognize the flavor spellings and retain virtual/override/final intent on the common method declaration. No flavor-specific semantic models. |
| Declaration collection | Keep only locally authored methods in their declaring inventory/index; retain existing duplicate handling. No flattened inherited-method copies. |
| Declaration preparation | Settle base relationships and callable contracts before dependent bodies. Resolve inherited lookup with original declaring ownership; validate override compatibility, markers and finality. |
| Prepared relationships | Cache method-contract completion on the declaration and retain existing symbol watches. Resolve inherited contracts during validation without caching foreign AST pointers; invalidation clears completion. |
| C++ lowering | Consume settled contracts and emit native inheritance, virtual/override/final declarations and ordinary virtual calls. No repeated ancestry inference or handwritten vtable mechanism. |
| Runtime ownership | Preserve the existing control block, typed view, concrete destroy callback, strong/weak identity and presence checks. No new handle or allocation for dispatch. |

Native polymorphic payloads acquire native virtual dispatch state; handles retain
their existing representation. The inheritance contract also requires virtual
destruction for virtual hierarchies, including an implicit public nonthrowing
virtual destructor when none is authored. Preserve existing copy/move behavior:
adding a destructor must not silently suppress moves. The allocation's concrete
callback still owns destruction; virtual dispatch does not transfer that ownership.
Preserve receiver lifetime across argument evaluation, single evaluation, required
presence checks and the existing source evaluation contract.

Incremental contract: separate implementation bodies from callable/layout contracts.
Calls observe their statically selected declaration, not every possible derived body.
Lookup must observe traversed ancestry and missing nearer declarations so adding an
override invalidates prior inherited lookup. Use existing stable symbol/dependency
owners; no parallel registry of implementations on every call site.

| Edit | Required invalidation / reuse |
| --- | --- |
| Override body only | Reprepare/reemit the owning implementation as needed; unchanged caller C++ and native objects remain reusable. |
| Base signature, access, virtual/final status | Revalidate affected overrides and calls; republish changed native contracts. |
| Method insertion/removal or base-edge change | Revisit affected inherited lookup, including previously absent methods and selected inherited defaults. |
| Override removal/restoration | Fall back to inherited implementation, then restore derived dispatch. Update declarations and rebuild dependent native objects through header dependencies. |
| Invalid edit, unchanged retry, recovery | Preserve pending watches/work and last successful output; recovery must match a fresh compilation without stale method links. |

Verification checklist (cpp/php/js, native Clang and GCC):

- [x] Base-typed dispatch, unchanged inherited implementation and three-level overriding.
- [x] Both PHP attribute and modifier forms normalize equivalently, including edits
  switching spellings; verify missing/spurious, duplicate and conflicting markers.
- [x] Reject signature/mode/nullability mismatches, final replacement, nonvirtual
  hiding and unsupported method categories. Check statically selected defaults.
- [x] Shared allocation identity, alias mutation, weak expiry and exactly-once
  concrete cleanup remain correct; null checks and receiver lifetime survive dispatch.
- [x] Verify implicit virtual destruction and preserved copy/move behavior; record
  payload/handle size effects without claiming an unmeasured performance improvement.
- [x] Cross-file edits to base contracts, ancestry, derived markers and method
  insertion/deletion/restoration; repeat errors and compare recovery diagnostics,
  generated artifacts and native behavior with fresh compilation.
- [x] Persistent Ninja builds prove no-op reuse and body-only caller-object reuse;
  contract/layout changes rebuild required native consumers via depfiles even when
  their emitted C++ text is unchanged. Include an unrelated source that stays reused.
- [x] Review regression: a class named `scpp` with virtual/override integer-returning
  methods compiles and runs in project and standalone output. Member return spelling
  reuses the existing signature qualification rule (`::scpp::int64`).
- [x] Review regression: add/remove an unused base field while caller C++ bytes and
  timestamps remain unchanged. Changed native headers rebuild both the base-typed
  caller and allocating caller through depfiles; the unrelated object stays reused.
  Compare every revision with fresh emission and run both native builds.
- [x] Retain concrete-adapter demand/reuse/removal coverage through method edits;
  successful deletion must retire obsolete artifacts and restoration recreate demand.

Deferred: abstract methods/types, interfaces, unique ownership, borrows, callable
variance, reference-return virtual methods, protected virtual methods, explicit
parent-method calls, authored destructors, downcasts and struct virtual support.
Do not broaden static redeclaration or final-type support as a side effect.

Implementation evidence: [virtual-method ownership and verification](../../compiler/docs/virtual_methods.md).
`virtual_methods_native.py --memcheck` covers persistent native builds and
retained/fresh recovery in all three flavors on Clang/GCC. The existing class,
extends and shared-upcast proofs also pass. Shared/weak handles remain 16 bytes,
control blocks 24 bytes; the tested allocation grows from 48 to 56 bytes with a
polymorphic payload. These are storage observations, not timing claims.

Incremental limitation: existing nominal AST replacement can still invalidate
caller preparation/emission after an implementation-body edit. The tests prove
unchanged caller C++ bytes, file timestamps and native object reuse, not zero
caller reanalysis. No-op builds reuse all these stages. A broader nominal-identity
refactor remains outside this slice.

### Checked shared-class casts and instanceof — implemented

Implemented explicit checked class access and nonthrowing type tests together, following
[checked shared-object access](../../../specs/inheritance_v0_2.md#checked-shared-object-access-and-type-tests).
Uses native C++ RTTI and the existing concrete inline adapter engine. This planning
note records delivery and verification; the linked semantic/frontend contracts own
language meaning. Deferred object features remain outside this slice.

PHP-flavor example:

```php
function aaaa(Node $node): void {
    $literal = object_cast($node, IntegerLiteral::class);
    bbb($literal);
}

if ($node instanceof IntegerLiteral) {
    bbb(object_cast($node, IntegerLiteral::class));
}
```

The cast target is a statically named class resolved through normal namespace/import
lookup, never a runtime string or an ownership-wrapper expression. The
[frontend contract](../compiler_frontends.md#checked-shared-class-access) records
`Target::class` for cpp/php and `Target.class` for js. All flavors use `instanceof`
and normalize to one common operation and target reference.

Bounded semantics:

- `object_cast` accepts required/optional shared class operands and returns a required
  target-class handle. A compatible present object succeeds; absence or runtime mismatch
  throws the runtime error code `invalid_object_cast`. It never implicitly narrows at an
  ordinary assignment, argument or return boundary.
- `instanceof` evaluates its operand once and returns false for absence or mismatch,
  true for a compatible object. It does not acquire a lasting owning result, change the
  operand's static type, authorize reference rebinding or eliminate a later cast check.
  Preserve the owning specification's precedence, including negated tests.
- Admit same-type and proven upward views plus related base-to-derived class casts.
  Reject unrelated classes, bare-null operands, inline values and dynamic targets during
  preparation. A valid runtime mismatch is not a preparation error.
- Downward casts/tests require a polymorphic source contract that already exists.
  Do not add virtual members/destructors solely to enable a cast. Same-type/upward
  operations need only the appropriate presence check and prepared pointer conversion.

Native lowering and ownership:

- Demand-emitted concrete functions use built-in `dynamic_cast<Target*>(source.view)`
  for downward operations. This is a C++ operator, not template instantiation. A null
  native result means absence or mismatch; the cast pattern calls a runtime-owned error
  helper, while the test pattern returns false. No custom vtable/type-tag registry.
- Reuse concrete adapter identity, dependency ordering, per-source demand, bundle merging
  and obsolete-fragment retirement. Request only used source/target/operation pairs;
  required/optional source identities with identical native storage may share a pattern.
  Keep the throwing cast and boolean test distinct contracts.
- For an owning result, take the source handle by value. An lvalue copy retains its
  existing control block; a temporary can transfer its existing hold. After a successful
  pointer check, transfer that hold and the adjusted view to the result and clear the
  adapter parameter. On failure, ordinary parameter destruction releases the temporary
  hold. Never consume or clear the caller's named handle.
- Preserve control-block/allocation identity, aliasing, weak observers and concrete
  cleanup. No payload copying/slicing, new object allocation or new control block.
  Each operand is evaluated once, with its ownership retained through the native check.
  The test uses the existing concrete adapter `const_reference` mode: its sole argument
  lives through the call, and the check neither calls user code nor escapes the handle.
  This avoids a temporary retain without exposing a source borrow feature.
- Runtime owns `invalid_object_cast` failure construction/publication; the application
  ownership backend owns concrete view-transfer patterns. Preparation owns admission;
  emission consumes settled facts rather than repeating ancestry/polymorphism searches.
- Downward operations require native RTTI on Clang/GCC. Validate the effective build
  configuration and diagnose an incompatible RTTI-disabled build explicitly. Include
  relevant compiler flags in existing native-build/cache identity; never silently force
  RTTI on against an explicitly incompatible configuration.

Incremental ownership and dependencies:

- Retain target syntax on the expression and use existing stable symbol/type resolution.
  Observe source/target contracts, traversed ancestry and source polymorphism, including
  unresolved target names needed for recovery. Do not cache a foreign method body or
  scan every possible descendant implementation at each call site.
- Base-edge edits, target deletion/recreation and removal/restoration of the last virtual
  contract invalidate dependent cast/test admission, even if native type spelling stays
  unchanged. Rebuild occurrence facts through their existing preparation owner.
- Preserve pending watches and last successful output through invalid edits and repeated
  retries. Recovery must match fresh diagnostics, generated artifacts and native behavior.
- Reused sources retain adapter demand. Editing/deleting one consumer must preserve an
  adapter used by another cached source; removal of the last use retires it from disk
  and the manifest. Restoration recreates demand without stale generation links.
- Separate source preparation/emission reuse from native object reuse. Preserve the
  documented nominal-AST replacement limitation; do not claim zero caller reanalysis
  merely because generated bytes or native objects remain unchanged.

Verification checklist (cpp/php/js, Clang and GCC):

- [x] Same-type/upward success, checked downcast success, mismatching sibling object,
  empty optional handle and a deeper descendant; reject unrelated/nonpolymorphic casts.
- [x] Required cast result, false type tests on absence/mismatch, boolean precedence,
  namespace/import target lookup and explicit rejection of unsupported target forms.
- [x] Effectful operands execute exactly once on success/failure. Test a temporary
  owning operand and verify checks are not skipped or reordered across required effects.
- [x] Cast success preserves allocation identity and alias mutation; named input remains
  valid. Cast failure releases only temporary holds. Weak expiry and exactly-once concrete
  cleanup remain correct, including the last-owner path; run native memory checks.
- [x] No payload copy/new object allocation from either operation; concrete generated
  adapter signatures/bodies contain no C++ template machinery. Verify include dependencies,
  runtime error-provider selection, and project/standalone output.
- [x] Edit/remove/restore ancestry, target declarations and the final virtual contract;
  repeat failed compilation and compare recovery with fresh output in every flavor.
- [x] Deduplicate demanded adapters, preserve them for cached surviving consumers, retire
  last-use/source-deletion artifacts, and restore them correctly.
- [x] Persistent Ninja builds verify no-op and unrelated-object reuse. Include a real
  header-only contract/layout edit with unchanged caller C++ bytes/timestamps that still
  rebuilds dependent native objects through depfiles.
- [x] Verify RTTI-enabled builds and the explicit failure for incompatible RTTI-disabled
  configuration. Keep build-cache flag changes distinct from source semantic invalidation.

Deferred: interfaces, interface cross-casts, struct casts/virtuals, unique ownership,
borrows, weak-handle casts, abstract methods/types, automatic flow narrowing, redundant
check elimination and a custom RTTI implementation. No execution/compile-time performance
claim without separate measurements.

Implementation evidence: [checked shared-class access](../../compiler/docs/object_access.md).
`object_access_native.py --memcheck` verifies all three flavors on Clang/GCC, including
native Valgrind checks, project/standalone emission and retained/fresh equivalence.
Two body-only type-test targets produce distinct concrete adapters; required/optional
sources with the same native representation share demand. Inherited source polymorphism
is observed through the ancestry chain. Removing the last consumer retires its fragment;
a cached surviving consumer keeps it live.

Persistent native builds prove no-op reuse, body-edit caller-object reuse and real
base-layout edits that rebuild dependent objects without rewriting caller C++ bytes or
timestamps. Changing the effective Ninja compile command to `-fno-rtti` rejects downward
operations explicitly; restoring flags rebuilds successfully. Same/upward operations
also pass with RTTI disabled. This verifies native-command cache invalidation, not a
new source-level RTTI configuration mechanism.

Existing concrete-adapter and virtual-method native suites, compiler style/failure
cleanup, generator input/cleanup and runtime freshness pass. Generator capture verifies
17 accepted/37 rejected base cases and 130 accepted/247 rejected runtime cases. Only
ownership-header frozen hashes changed; the already documented unrelated arithmetic
header/runtime-source/input-hash baseline drift remains, so frozen parity comparisons
are not reported green.

The nominal AST replacement limitation described above remains: unchanged native caller
objects do not imply zero caller preparation after every declaration edit. No broader
incremental identity refactor, interfaces, unique or borrow implementation was added.

### Explicit ancestor instance calls — implemented

Continue the small AST family with concrete instance parent calls. PHP uses
`parent::method(...)`, JS uses `super.method(...)`, and C++ uses the actual ancestor
name, `Base::method(...)`, including namespace/import lookup. `Parent` is not a
special C++ alias. PHP/JS start at the immediate lexical base; C++ can explicitly
select a more distant strict ancestor. Ordinary inherited lookup selects the
original declaration owner and bypasses virtual redispatch for this call only.

The existing call AST owns written parent intent and a resettable prepared fixed-
dispatch flag. Preparation owns relationship/access/instance checks and stable-symbol
watches. Ordinary method binding owns arguments, defaults and conversions; the C++
invocation emitter only changes receiver/target selection. No RTTI, shared-handle
adapter, payload copy, new ownership allocation or alternate argument-lowering path.
Constructor initialization remains a separate operation; a parent method at the
start of a constructor body executes after implicit base construction.

Verification checklist (cpp/php/js; Clang and GCC):

- [x] Concrete virtual and ordinary ancestor calls, inherited owner selection through
  three levels, parent defaults and virtual calls made inside the parent body.
- [x] Same-object field mutation, void calls and writable-reference argument forwarding.
  Outer receiver retention survives argument-driven release of its source owner; weak
  expiry and concrete cleanup pass Valgrind checks.
- [x] Single argument evaluation and checked conversion failures before the parent body;
  namespaced/imported C++ ancestor names and standalone emission.
- [x] Reject private/missing methods, free/static contexts, absent/unrelated bases,
  wrong arity and current-instance use during base initialization. Repeat failures and
  recover to fresh output; distinguish parent methods from constructor syntax.
- [x] Base-body/default edits, nearer override insertion/removal, valid reparenting and
  base declaration deletion/restoration match fresh diagnostics/emission/native results.
- [x] No-op preparation/emission/native reuse; body-edit caller-object reuse; real
  header-only changes rebuild dependent objects while caller C++ bytes/timestamps stay
  unchanged and unrelated objects remain reused.
- [x] C++ explicit grandparent selection bypasses a nearer override.

Evidence: [ancestor-call implementation and checks](../../compiler/docs/parent_methods.md),
`parent_methods_native.py --memcheck`. Preserve the earlier nominal AST invalidation
limitation; native reuse is not a claim of zero caller reanalysis. Existing method,
class, extends, namespace/import and failure-cleanup regressions protect earlier work.

Deferred: parent constants/properties/static-method spellings, protected-method
admission, broader reference-return support, abstract bodies, interfaces, unique and
borrows. Existing type-qualified static calls remain supported. No performance claim
without separate measurement.

### Abstract class and method slice — implemented

Supports explicit abstract classes and bodyless public instance methods in cpp/php/js,
mandatory overrides, inherited implementations and abstract intermediates. Native C++
owns pure virtual dispatch; the compiler rejects abstract construction and incomplete
concrete descendants. Fixed parent calls to abstract bodies reject.

Keep structures light: written declaration flags, a nullable body, declaration-owned
source dependency identity and one record readiness bit. Obligations use a transient
nearest-method scan over existing ancestry, with no persistent flattened inventory.
Existing record invalidation clears readiness. No synthetic body or preparation job
is allocated for abstract declarations.

- [x] Verify native dispatch, inherited concrete implementations and re-abstraction.
- [x] Verify constructor inheritance, ownership identity, alias mutation, checked
  downcasts, weak expiry and cleanup on Clang/GCC, including Valgrind.
- [x] Verify invalid syntax/contracts, mandatory overrides, exact signatures,
  abstract construction and abstract parent calls, including failed-build retries.
- [x] Compare retained/fresh output and diagnostics across cpp/php/js after body,
  default, signature and header edits, added obligations and deletion/restoration.
- [x] Verify no-op reuse, isolated body rebuilds and header-only dependent rebuilds;
  unrelated native objects and callers remain reused when their artifacts are unchanged.
- [x] Run existing class, virtual, parent, object-access and lifecycle regressions.

Evidence: [abstract-method implementation](../../compiler/docs/abstract_methods.md)
and `abstract_methods_native.py --memcheck`. Earlier slice deferrals above describe
those historical scopes; abstract classes/methods are now implemented in this slice.
Interfaces, abstract structs, protected-method expansion, callable variance, broader
reference returns, unique and borrows remain deferred. No preparation-speed benchmark
or broader incremental invalidation redesign is claimed.

### First interface slice — implemented

Implemented and verified 2026-11-18. This advances 3.4 with class implementations
and shared interface views; interface-capability tests from 3.3 follow separately. The
[inheritance contract](../../../specs/inheritance_v0_2.md#interface-implementation)
owns semantics. This note bounds implementation coverage, not the full language.

#### Inherited implementations and mandatory override

An interface establishes a callable obligation. A compatible public instance method
already inherited from a base may satisfy it without a new source declaration or
marker. Preserve that method's original declaring owner, body and dispatch policy.
The base does not retroactively implement the interface or become virtual merely
because a descendant adds a capability.

```php
interface HasStart {
    public function start(): int;
}

class Node {
    public function start(): int { return 0; }
}

class IntegerLiteral extends Node implements HasStart {
    // Inherited start() satisfies HasStart; no redeclaration is required.
}
```

Where native C++ requires it, emit a public interface-overriding forwarding member
in the implementing descendant, targeting the selected inherited implementation.
Preparation owns this binding. Do not synthesize a source AST method, copy the
foreign body or mutate the base declaration. Native C++ multiple inheritance alone
does not make a method from an unrelated base override an interface's pure method.

| Source situation | Required behavior |
| --- | --- |
| Interface's own bodyless method | Establishes a contract; no Virtual/Override marker required. |
| Newly authored implementation of an interface method | Explicit Override is mandatory, even without a matching base-class method. |
| Compatible inherited implementation used unchanged | No new declaration and no new marker; prepare an interface binding. |
| One local method satisfies multiple compatible interface contracts | One Override marker; validate against every requirement. |
| Descendant replaces an existing non-final interface implementation | Override remains mandatory. |
| Override with no matching virtual/interface obligation | Reject. |
| Virtual used instead of Override for an implementation | Reject; this fulfills an existing contract. |

Reuse both existing attribute and modifier spellings and their duplicate/conflict
diagnostics. The current exact-contract checks apply: types, passing modes,
nullability and optional-parameter positions match. Parameter names and default
values may differ; the statically selected callable supplies defaults. A private,
protected, static or incompatible method does not silently satisfy a public
interface obligation. Existing nonvirtual hiding restrictions remain in force.
An interface forwarding binding must not be used to authorize otherwise forbidden
source redeclarations.

The full contract permits inherited final implementations, including an existing
final virtual slot. That case needs a different binding strategy: an ordinary
same-name forwarding override can violate C++ final. Defer inherited-final
satisfaction explicitly in this first slice, with an unsupported-case diagnostic
rather than claiming it is a language error or silently emitting invalid C++.

#### Bounded scope and owners

- Public bodyless instance methods; classes implement one or multiple independent
  interfaces. Abstract implementers may retain obligations; concrete classes must
  discharge them. Reuse common abstract/override signature validation.
- Admit compatible same-name requirements using the exact-contract subset; reject
  conflicting contracts. Preserve each interface declaration's identity and defaults.
- Interface-typed shared storage, parameters and returns use class-like handle
  semantics. Function nullability remains explicit; shared properties retain the
  agreed relaxed empty-storage rule. Interfaces cannot be constructed.
- Include class-to-interface conversion and passing/returning an existing interface
  view. Reuse the concrete inline adapter engine, parameter binding and runtime
  ownership. No source templates, new control block or payload copy.
- Frontends retain written interface/implements intent using the common nominal
  model. Multiple implemented interfaces are valid multiplicity; retain the existing
  single-base representation. Do not allocate body scopes/jobs for prototypes.
- Declaration preparation resolves interface edges and settles requirements before
  body work. Keep original declarations; store only needed prepared bindings with
  the implementing owner. Avoid a second flattened method inventory, repeated
  per-call ancestry scans or emitter-owned semantic resolution.
- Lower to native abstract interface payloads and public virtual interface edges,
  as specified, plus prepared forwarding members where required. Preserve public
  virtual nonthrowing interface destruction and existing copy/move behavior.
- Handle views may have adjusted pointers but retain the original allocation,
  control block and concrete destruction callback. Preserve allocation identity,
  strong/weak lifetime and receiver retention through calls.
- Existing declaration watches own invalidation; source-owned adapter demand owns
  publication/removal. Lowering reads settled contracts and owns its output. No
  foreign-body preparation, shared registry mutation or new threading machinery.

#### Incremental contract and verification checklist

Completed semantic, emitted-C++ and native proofs across cpp/php/js on Clang and
GCC, comparing each successful incremental recovery with a fresh build.

- [x] Small AST proof: Node plus two concrete expressions, a shared child link and
      an interface-typed operation. Verify virtual dispatch reaches the original
      object and distinguish alias mutation from copying.
- [x] Local marked implementation, inherited ordinary and virtual implementations,
      abstract intermediates and a later explicit override. Check calls through
      concrete, base and interface views; adding the interface must not change
      pre-existing base-view dispatch semantics.
- [x] Multiple independent interfaces, including compatible same-name requirements
      and conflicts. Verify both adjusted interface views share allocation identity.
- [x] Missing/spurious/duplicate/conflicting markers in all admitted spellings;
      missing methods, wrong signatures/modes/nullability, invalid visibility/static
      methods, interface construction and explicitly deferred forms reject cleanly.
- [x] Calls through interface and concrete types select their own declared defaults;
      conversions retain checks and evaluate each argument once under the existing
      evaluation contract. Preserve writable-reference behavior where already admitted.
- [x] Interface parameters/returns, nullable function boundaries and empty shared
      properties; null dereference guards, weak expiry and exactly-once concrete
      cleanup. Release other owners during argument evaluation and verify receiver
      lifetime. Run native memory/lifetime checks.
- [x] Interface signature/default edits; add/remove an obligation or implements edge;
      change base ancestry or inherited implementation visibility/signature; insert,
      remove and restore a nearer override. Watch missing candidates as well as
      selected declarations; never retain stale forwarding bindings.
- [x] Delete/restore interface and implementation sources, repeat invalid builds and
      recover. Preserve pending work and compare diagnostics, generated artifacts and
      native behavior with fresh compilation.
- [x] No-op preparation/emission/native reuse; implementation-body-only edits preserve
      unchanged caller emission and objects. Do not claim zero caller preparation
      where current nominal replacement invalidates contracts more broadly.
- [x] Interface/header changes rebuild required native consumers through depfiles,
      including consumers whose generated C++ stays byte-identical. Verify unrelated
      units remain reused and qualification does not hide artifact invalidation.
- [x] Retain adapter demands from reused sources; remove the last use/implements edge
      and retire obsolete adapters/headers; restore and compare with fresh publication.
- [x] Keep declaration/body/lowering ownership explicit and run existing inheritance,
      abstract/virtual/parent-call, adapter, failure-cleanup and style regressions.
      Sequential PHP checks do not establish native thread safety.

Deferred: interface inheritance/diamonds; interface instanceof/downcasts/cross-casts;
struct implementers; inherited-final interface satisfaction; callable variance and
broader reference returns; interface fields/constants/static methods/default bodies;
traits; unique; borrows/non-owning AST links; cross-thread ownership and scheduling.
Review broader ownership or dispatch changes before expanding this slice. No native
performance or preparation-speed claim follows from the proposed design.

Implementation evidence: [class interfaces](../../compiler/docs/interfaces.md) and
`interfaces_native.py --memcheck`. The common record kind and original declarations
own interfaces; the existing record readiness flag now covers all method obligations.
Only written/resolved interface edges and required forwarding targets are retained.
No copied method inventories, synthetic AST bodies, runtime ownership changes or
new preparation framework were introduced. C++ constructor syntax with only an
interface edge settles omitted base initialization as a void no-op.

Verification includes multiple interface views sharing one control block, adjusted
pointers, weak expiry, receiver retention when an argument releases its owner,
reference mutation and by-value handle forwarding. Effect traces prove single
argument evaluation and checked byte conversion failure before the method body.
Record-name shadowing (`scpp`) and standalone emission compile on both toolchains.
Retained tests cover body-only edits, defaults/signatures, reparenting, adding/removing
obligations, invalid retries, source deletion/restoration, actual native header rebuilds
with unchanged caller C++, and last-use adapter retirement/restoration. Existing
extends, classes, shared-upcast, inline-adapter, virtual, abstract, parent-call,
frontend/AST/preparation and failure-cleanup checks pass. The extends fixture now
expects semantic rejection of unsupported comma-separated struct base edges, since
C++ base lists are parsed for interface support. Source method-contract validation
precedes outstanding-obligation diagnostics, preserving existing error precedence.

Review fixes keep the same owners and retained structures. Method preparation validates
an inherited abstract method's exact signature before allowing its body to remain
outstanding. The emitter registers forwarding targets with the ordinary function
key, avoiding duplicate standalone prototypes and signature inventory entries.

- [x] Reject an incompatible inherited abstract signature in all three flavors;
      compare repeated retained diagnostics and recovery with fresh builds, and
      compile the compatible abstract obligation on Clang and GCC.
- [x] Successfully remove and restore the last implements edge on an inherited
      ordinary-method implementer. Check that both the published bridge declaration
      and out-of-line definition disappear/reappear, with fresh-output equivalence.
- [x] Preserve main and unrelated source emission and native objects through that
      transition. The implementer, its layout consumer, base method and interface
      owners rebuild as the last bridge changes exported header demand.
      The consumer keeps byte-identical emitted C++. No-op builds
      after removal and restoration prepare/emit/rebuild nothing.
- [x] Assert exactly one standalone declaration for the inherited forwarding target
      and compile/run it on both native toolchains in cpp/php/js.

These checks prove the sequential implementation and generated native behavior;
they do not claim cross-thread ownership safety or a preparation-speed improvement.
The earlier nominal replacement limitation remains: unchanged emitted callers/native
objects do not imply zero caller preparation after every declaration edit.

### Interface capability tests and checked casts — implemented

Implemented and verified 2026-11-18. This remains a planning-status record of scope
and evidence, not semantic authority.
This advances 3.3 using the first interface slice. The
[checked shared-object contract](../../../specs/inheritance_v0_2.md#checked-shared-object-access-and-type-tests)
owns semantics; this section bounds implementation and verification.

PHP-flavor example:

```php
function inspect(Node $node): int {
    if ($node instanceof HasStart) {
        $capability = object_cast($node, HasStart::class);
        return $capability->start();
    }
    return 0;
}
```

The example requires an already polymorphic Node contract. A descendant can implement
HasStart without making Node itself implement that interface. The test does not change
Node's static type; the explicit cast supplies the required interface view.

#### Bounded behavior and existing owners

- Extend the existing instanceof and object_cast operations to class-to-interface,
  interface-to-class and interface-to-interface relationships. Retain same-type and
  proven upward paths. Use existing static target spellings and namespace/import lookup
  in cpp/php/js; introduce no new syntax or implicit narrowing at typed boundaries.
- Follow semantic relationship admission: two interfaces need no known common
  implementer; a class can overlap an interface through a descendant. Class finality
  is not implemented in this checkout; by agreement it remains deferred and final-class
  syntax stays rejected. Finality-aware overlap admission will follow that prerequisite.
  Preserve rejection of
  unrelated class pairs and require existing source polymorphism for runtime checks.
  Never add virtual members solely to enable a test/cast.
- instanceof evaluates once and returns false for absence or mismatch. object_cast
  evaluates once and returns a required target handle, or raises invalid_object_cast
  for absence/mismatch. Testing does not remove the subsequent checked cast or authorize
  reference rebinding. Keep source evaluation and short-circuit contracts unchanged.
- Preparation owns admission and dependencies; lowering consumes settled facts. Reuse
  existing expression nodes, type resolution, ancestry queries and prepared cast facts.
  Do not add another relationship registry, flattened implementer inventory, synthetic
  method/body, or whole-program descendant scan. Reuse readiness and invalidation rather
  than repeating relationship analysis per emission or native adapter invocation.
- Reuse the concrete inline adapter engine, demand identities and existing native RTTI
  patterns. Emit only demanded source/target/operation pairs. Proven views use existing
  pointer adjustment/presence paths; runtime narrowing/cross-casts use native dynamic_cast.
  Keep throwing casts and boolean tests distinct. No general ABI, source templates,
  custom type tags or vtable implementation.

#### Ownership and incremental boundaries

- Every successful view retains the original allocation identity, control block and
  concrete destruction callback, even when interface pointer addresses differ. No
  payload copy, new object allocation or second control block. Preserve alias mutation.
- Reuse the existing by-value cast adapter: copies retain a named source, temporaries
  can transfer their hold, success transfers that hold into the adjusted target view,
  and failure releases the temporary hold. Do not consume a named source handle.
- Reuse the boolean test adapter's const-reference mode; retain operand lifetime through
  the call without a lasting owning result. This native adapter mode does not introduce
  a source borrowing feature. Preserve weak observation and final concrete cleanup.
- Watch source/target declarations and the ancestry, implements edges and
  polymorphism consulted for admission, including unresolved targets needed for recovery.
  Runtime-only implementer changes need correct layout/header publication; they must not
  require a global implementer index or observing every possible descendant at a call site.
- Keep watches and pending work through failure; acknowledge only successful preparation
  and publication. Reused sources retain adapter demand. Retire a fragment only after
  its last consumer disappears, using existing generation cleanup after readers finish.
- Distinguish prepared-body reuse, emitted-unit reuse and native object reuse. Preserve
  unchanged caller output where applicable, but do not promise zero reanalysis after
  nominal declaration replacement. Native depfiles must rebuild affected layout consumers.
  Keep RTTI flags within existing qualification/build-cache identity.

#### Verification checklist — cpp/php/js on Clang and GCC

- [x] Class/interface casts and tests in both directions; interface cross-cast success
      and runtime mismatch, including two interfaces with no known common implementer.
      Exercise inherited implementations and two adjusted views of one concrete object.
- [x] Same-type/proven upward paths, deeper descendants, required/optional operands,
      false tests on absence, invalid_object_cast on absence/mismatch, and required
      result typing. Verify target lookup, negation precedence and short-circuit behavior.
- [x] Reject unsupported final-class syntax, unrelated class pairs and
      nonpolymorphic runtime checks. Preserve diagnostics for inline values, bare null,
      weak/unique operands, dynamic target names and deferred struct targets.
- [x] Effectful and temporary operands execute once. Named handles remain usable;
      allocation identity, adjusted views, alias mutation and weak locks stay correct.
      Verify failed-cast cleanup, last-owner release and exactly-once concrete destruction
      with native memory/lifetime checks.
- [x] Inspect concrete generated adapters for no payload copy/new object allocation,
      correct signatures, include/error-provider selection and deduplicated demand.
      Compile/run both project and standalone output; check incompatible RTTI-disabled
      configurations through existing toolchain validation.
- [x] Edit/remove/restore implements edges, ancestry, target declarations and
      source polymorphism. Repeat invalid builds, preserve last successful publication,
      and compare recovered diagnostics, artifacts and native behavior with fresh builds.
- [x] Change a runtime implementer's capability without editing the caller; verify the
      new runtime result and required native header rebuilds with unchanged caller C++.
      Exercise a header-only layout edit affecting adjusted interface views.
- [x] Two sources demand one adapter: retain it when one consumer changes/disappears,
      retire it after the last use, and recreate it on restoration. Verify manifests,
      on-disk fragments, cached surviving consumers and source deletion/re-addition.
- [x] No-op preparation/emission/native builds do no work. Unrelated units and native
      objects stay reused through affecting edits. Run existing class-cast, interface,
      adapter and failure-recovery regressions; review retained fields and traversal
      ownership to keep structures light and preparation bounded.

Deferred: class finality and finality-aware overlap checks; interface inheritance/diamonds; struct implementers and struct casts;
inherited-final interface satisfaction; unique, weak-handle casts and source borrows;
automatic flow narrowing, cast-check elimination, non-owning parent-link contracts,
traits, broader callable variance and native threading. No runtime ownership redesign
or preparation-speed claim is authorized. If existing owners cannot support the bounded
slice without a wider refactor, report that boundary before expanding implementation.

Implementation evidence: Object_Access_Preparation now admits class/interface and
interface/interface overlap using the existing record kind and ancestry queries.
No AST fields, retained indexes, preparation passes or runtime ownership machinery
were added. The concrete adapter bodies are unchanged; native RTTI already supplies
the required cross-cast pointer adjustment and existing adapters transfer the hold.

Verification: interface_access_native.py covers all three flavors on Clang and GCC,
including Valgrind, project/standalone output, RTTI rejection, interface namespace/import
targets, absent/mismatching objects and no implicit narrowing. Persistent builds prove
capability changes with unchanged caller output, layout-driven native rebuilding,
invalid retries/recovery, source deletion/restoration and last-use adapter retirement.
The class object-access, interface, inline-adapter and failure-cleanup regressions also
pass. Their existing ancestry and qualification-flag cases complement the new tests.
Class-finality checks were explicitly deferred after inspection showed that record
finality is not represented or parsed; unsupported final-class syntax stays rejected.
No timing or native threading claim is made from these functional checks.

Review verification closes the remaining adapter-publication gaps:

- [x] With unchanged source/target names, edit Node's implements edge to switch both
      object_cast and instanceof between runtime-checked and proven-upcast adapters,
      then restore the runtime path. Compare every successful publication with fresh
      output and execute the cast result through its interface on Clang and GCC.
- [x] Two source units share both adapter patterns. Remove one unit's demands while
      reusing the surviving consumer, delete the final consumer, and restore both.
      Assert exact cast/test adapter counts and native-object reuse for cached consumers;
      no-op runs after retirement and restoration prepare/emit/rebuild nothing.
- [x] At each successful incremental publication, manifest headers are unique and
      exactly match live .hpp files and the fresh build's header inventory. Retired
      adapter fragments are absent from both disk and manifest; restoration republishes
      them. Apply these checks to the original capability-edit suite as well.

These review fixes add tests and planning evidence only. The compiler, ownership
implementation and deferred boundaries are unchanged.

### Weak parent links and retained source-tree lifetimes — first proof implemented

Implemented and verified 2026-11-18. This remains a planning-status record of
bounded scope and evidence.
This is the first bounded proof for 3.5, using existing shared/weak handles and
explicit root retention. It does not complete staged initialization from 3.6 or
translate the full incremental compiler. The current
[AST ownership model](../../compiler/docs/ast_model.md),
[generation cleanup](../../compiler/docs/generation_cleanup.md) and
[failure cleanup](../../compiler/docs/failure_cleanup.md) remain the implementation
owners; this planning note does not introduce new language semantics.

#### Small executable family and scope

Use a Node base, Literal and Addition children, and one source root per tree.
Addition has fixed owning child slots; do not require application-defined vectors,
a new arena or a general node registry for this proof. Each node has one weak parent
slot. Attach children explicitly after constructing the owning handles, using an
external construction/attachment function rather than adding an owning-this feature.
Use an existing class-to-Node shared upcast before weak(Node) construction where
needed; do not introduce weak-handle covariance as a hidden prerequisite.

Model two source slots and a retained reader using ordinary owning root handles.
A new simulated generation reuses an unchanged source root and replaces an edited
source root. This demonstrates native lifetime behavior; it is distinct from the
PHP compiler's real incremental preparation/emission tests below. Do not create a
second production generation manager merely to represent the test sequence.

#### Ownership and validity contract

| Link / holder | Ownership and validity |
| --- | --- |
| Source root and parent to children | Strong ownership of the tree. No owning child-to-parent backlink. |
| Child to parent | Existing weak<Node> observation. Lock returns an optional owning Node; empty/expired links cannot be dereferenced. |
| Active compilation or reader to source root | Strong root hold for the complete operation that needs ancestry/source context. Hold only the relevant roots, not every historical session. |
| Diagnostic to source snapshot | Retain the source data required for reporting; do not pin the entire AST merely to preserve diagnostic text. |
| Cross-source semantic lookup | Preserve existing stable-symbol identities and watches; do not replace them with owning links to old declarations. |

- The root's parent is empty. The fixture attaches each non-root once to its owning
  parent and forms no strong ownership cycle. Compiler-enforced write-once attachment,
  general cycle validation and delayed-attachment syntax remain separate 3.6 work;
  this slice must not claim that an ordinary writable field enforces those rules.
- A child kept alive independently does not keep its ancestors alive. After the last
  root/parent owner is released, locking that child's parent may return empty even
  though the child itself is still usable. Ancestor/source queries require a retained
  root or explicit handling of expiry; they must never follow a saved expired pointer.
- A successful lock keeps that parent alive for the locked handle's lifetime. It does
  not promise that all ancestors remain alive. Reader-owned root retention supplies
  the stronger guarantee required for a complete parent walk.
- Reuse unchanged source trees intact: preserve node identity, parent links and source
  snapshot identity. Parent links describe syntax ownership, not the compilation
  session; never reparent reused nodes to the latest session.
- An edited source creates a distinct tree. Old and new trees coexist while an old
  reader retains its root. Releasing the old reader permits old-tree destruction after
  other explicit owners finish; current roots and reused trees stay alive.
- Object lifetime is not semantic freshness. Holding an old tree does not freeze stable
  symbol entries, validate stale prepared facts or make an old emission handle current.
  Preserve existing invalidation and stale-handle rules; historical semantic snapshots
  are not introduced by this proof.
- Existing weak observers retain allocation storage after payload destruction until
  the last observer is released. Verify payload lifetime separately from allocation
  release. Do not claim that weak links are zero-cost, that expiry immediately frees
  storage, or that this establishes the eventual cheapest native AST representation.

#### Owners and preparation budget

Use existing weak field storage, weak()/lock(), shared views, parameter binding and
optional-result checking. First verify their admission in this exact fixture. If a
missing capability is discovered, report the bounded prerequisite rather than silently
adding a new ownership family or a broad type/lowering refactor.

Runtime ownership remains responsible for counts, locking, expiry and concrete
cleanup. AST construction owns the syntax links; the operation/coordinator owns root
retention. Existing Generation_Cleanup remains the production retirement gate after
readers finish, including failure paths. Do not scatter cleanup into AST traversal or
acknowledge pending preparation/emission work merely to release old roots.

No new per-node generation ID, strong root backlink, flattened ancestry index,
copy of an entire tree on reuse, descendant scan or prepared lifetime registry.
Do not add a preparation pass to implement runtime lifetime behavior. No new borrow
checker, scheduler, locking policy or native threading machinery is authorized.
The current PHP compiler's ordinary parent references and GC policy are not migrated
as part of the native source-language proof.

#### Verification checklist — cpp/php/js on Clang and GCC

Native AST lifetime proof:

- [x] Construct the small tree, attach weak parent links, lock and walk parents while
      holding the root. Verify root absence, original allocation identity, inherited
      field access and a base-typed operation. No payload copy on weak construction/lock.
- [x] Keep a child after releasing its root. Verify the parent payload expires, the
      child survives, and locking the expired link returns empty without invalid access.
- [x] Retain a successful parent lock across release of other owners; it keeps that
      parent alive until released. Separately prove root retention keeps the full walk
      valid, including source context.
- [x] Reuse one source root across simulated generations while replacing another.
      Check unchanged node/parent identity, distinct edited trees and a retained old
      reader's valid parent walk. Release that reader and verify old-tree cleanup.
- [x] Drop a partially constructed candidate without publishing it; the retained
      current roots remain usable. Exercise success and failure cleanup through existing
      supported mechanisms/native harnesses without introducing source exception syntax.
- [x] Verify weak expiry, final payload destruction and eventual allocation release
      after weak observers disappear. Run repeated replacement/release cycles with native
      memory checks; no leaks, double destruction or use-after-free.

Actual compiler incremental verification for the fixture:

- [x] No-op builds reuse preparation, emitted source and native objects. An unrelated
      source stays reused through affecting edits; preserve the documented limitation
      that unchanged C++ does not imply zero reanalysis after nominal replacement.
- [x] Change a method body and a node layout separately. Verify body-only reuse where
      supported and native depfile rebuilds for layout changes, including unchanged
      emitted consumers. Compare generated artifacts and native behavior with fresh builds.
- [x] Delete/restore a required node/source declaration, repeat invalid compilation,
      and recover. Preserve pending work, current valid caches and existing publication
      guarantees; compare diagnostics and recovered output with fresh compilation.
- [x] Run existing weak/shared ownership, generation-retirement and failure-cleanup
      regressions. Where the fixture demands existing shared-view adapters, check cached
      demand, retirement/restoration and manifest inventory through existing test helpers.
- [x] Review retained fields and ownership boundaries before declaring completion.
      Functional/native checks prove the bounded lifetime contract, not native parallel
      safety, full compiler translation or a preparation-speed improvement.

Deferred: raw/non-owning borrows, stored or returned borrow semantics, unique ownership,
weak upcasts/downcasts, arenas and generation-wide allocators, per-node lifetime tags,
write-once attachment enforcement, settling ranges, traits/general composition, historical
semantic snapshots, native concurrency and performance-driven replacement of weak links.
Interface inheritance, struct implementers and class finality remain outside this slice.

Evidence: [weak AST parent lifetime proof](../../compiler/docs/ast_parent_lifetimes.md)
and ast_parent_lifetime_native.py --memcheck. Existing weak fields and locking admitted
the fixture without compiler/runtime changes, new AST facts or preparation passes.
The source fixture owns construction/attachment and base-typed operations; a native
harness models retained readers, source-slot replacement and candidate failure. These
simulated generations are explicitly separate from the resident compiler's real
incremental source-edit checks. Neither is a full native compiler translation.

All three flavors pass Clang/GCC execution and Valgrind. Real incremental checks cover
no-op reuse, body-only edits, layout-dependent native rebuilding with unchanged emitted
callers, required-source deletion, repeated invalid builds and fresh-equivalent recovery.
Existing weak/shared, inline-adapter, retained-generation and failure-cleanup suites
cover the reused ownership, adapter retirement and cleanup boundaries. Production PHP
parent references and collection policy remain unchanged. The older weak regression
now expects the current related-shared-types diagnostic for an invalid comparison;
retained and fresh compilation still reject it identically. Milestone 3.6 staged attachment
and range settling remain pending; this proof does not enforce single attachment.

Review verification closes both lifetime coverage gaps:

- [x] Recompile and execute the complete parent-locking, expiry and retained-root probe
      after adding/removing a node layout field and after required-source deletion and
      recovery. Run these gates across cpp/php/js on Clang/GCC, including Valgrind.
- [x] Attach children to an unpublished candidate using the source attachment helper.
      Prove that a retained child locks the live candidate before failure and sees an
      expired parent afterward, while the child survives and both current/reused root
      identities and behavior stay unchanged. Release the child and verify its expiry.

Only the test fixture and documentation change for these review fixes; no compiler,
runtime ownership, retained structures or deferred features are added.

### Small AST integration proof — ordinary application policies

The follow-up decision keeps ordinary weak-reference semantics: weak fields are
assignable observers, and expiry does not introduce attachment state. Any attach-once,
cycle or range validation required by the compiler's own AST belongs in that AST's
application methods. Do not introduce specialized language semantics, hidden readiness
flags or a new preparation pass for this example. Earlier deferred attachment/range
items do not authorize such a compiler feature.

The existing lifetime fixture now combines these capabilities in one executable family:

- Construct 1+2+3 as nested Addition/Literal nodes, with owning children and weak parents.
- Keep numerical values separate from source offsets; use ordinary inherited setRange
  calls to assign [start,end) spans for literals, the inner expression and the root.
- Evaluate through a Node handle and through Evaluable after instanceof/object_cast;
  verify a literal does not expose that capability.
- Reuse an unchanged tree, replace another source tree and retain an old reader.
  Check original parent identity, old ranges and separate source-snapshot identity/data.
- Repeat lifetime checks after layout edits and deletion/recovery; verify real compiler
  no-op reuse, body-only rebuilds and native layout dependencies against fresh output.

Verification uses ast_parent_lifetime_native.py --memcheck across cpp/php/js on Clang
and GCC. This is an integration test extension, not a compiler/runtime implementation.
It adds no AST fields to the compiler, generation manager or ownership feature.

Milestone 3 assessment: the bounded small-family integration proof exercises the
implemented inheritance, shared views, virtual dispatch, interfaces, checked capability
access and weak-parent/root-lifetime behavior together. Basic staged construction and
range assignment work as ordinary application code. No additional language feature was
required by this example. Actual compiler-AST translation and any declaration-specific
composition policy remain separate work, not a claim of full bootstrap completion.
Exact-type queries, protected static methods, interface inheritance/diamonds,
struct implementers, inherited-final satisfaction, unique, borrows and
native threading remain deferred; none is required to complete this bounded example.

## 4. Failures and cleanup — failed preparation and retry

| Order | Group | Initial scope |
| --- | --- | --- |
| 4.1 | Source-authored exceptions | Throw, typed catch and rethrow, diagnostic payloads and ordinary value cleanup. |
| 4.2 | Guaranteed cleanup | Finally, or an explicitly agreed equally direct construct. Cover return, throw and nested cleanup. |
| 4.3 | Failure expressions | Throwing fallback/default arms through the established expression and exception models. |
| 4.4 | Host failures | Typed bindings for expected misses and operational failures without importing PHP warnings. |

Proof: fail midway through preparation, restore its context and retry successfully. Separately fail output publication and verify staging cleanup.

Basic exception infrastructure may move earlier if required by preceding proofs. Complete recovery behavior before translating substantial analyzer code, to avoid spreading a temporary error protocol throughout it.

## 5. Parser, preparation and emission — one complete translated path

| Order | Group | Initial scope |
| --- | --- | --- |
| 5.1 | AST collections and scopes | Base-typed node collections, declaration indexes and duplicate chains. |
| 5.2 | Canonical type instances | Type-owned static handles and lazy initialization preserving singleton identity. |
| 5.3 | Generated definitions | Existing metadata generator emits target-language definitions and registration for required types. |
| 5.4 | Remaining signatures | Review actual unions/intersections and nonliteral defaults individually. Preserve guarantees without automatically implementing all PHP type syntax. |
| 5.5 | Traversal callbacks | Named callable references first. Add captures where they materially simplify the translation. |

Proof: parse a real expression, collect its declaration, prepare through the common AST, emit C++, compile and execute. Include a diagnostic and successful retry. Review readability and ownership as well as results.

## 6. Incremental identity and retention — multiple generations

| Order | Group | Initial scope |
| --- | --- | --- |
| 6.1 | Identity-keyed storage | Symbol/watcher keys and non-owning references with explicit lifetime boundaries. |
| 6.2 | Collection snapshots | Copy membership while retaining referenced objects. Distinguish snapshots from draining a pending queue. |
| 6.3 | Watch dispatch and invalidation | Reuse interfaces and stable symbol identities. |
| 6.4 | Retirement and recovery | Removed/replaced declarations, failed generations, pending work and re-addition. |

Proof: edit a file, retain an unchanged AST, invalidate appropriate bodies, recover from failure and compare with a fresh compilation. Repeat sessions to reveal retention problems. Translate the existing incremental model instead of redesigning it in this milestone.

## 7. Host integration and bootstrap — a native compiler

| Order | Group | Initial scope |
| --- | --- | --- |
| 7.1 | Host data | Manifest/JSON shapes, validated records and inspection output. |
| 7.2 | Runtime facilities | Files, paths, regex if retained, process invocation and other bindings from the early inventory. |
| 7.3 | CLI compiler | Compile a project through the native compiler and compare artifacts and diagnostics. |
| 7.4 | Resident service | Socket protocol, retained sessions, idle shutdown and repeated requests. |
| 7.5 | Bootstrap verification | Build the native compiler using PHP, then assess compiling its source with the resulting native compiler. |

Host dependencies must be inventoried early, not discovered only at milestone 7. Their implementation can proceed separately from language work.

## Features outside the demonstrated required path

- General generators and yield.
- Arbitrary closure captures and recursive closures.
- General-purpose source templates.
- Full PHP union/intersection syntax.
- General cloning and PHP array copy-on-write.
- Every expression-valued assignment/update form.
- Named arguments everywhere.
- Full dynamic-object semantics.
- Language-level threading and cross-thread ownership.

These are not rejected. Review the actual use before deciding whether a bounded feature or a clearer translation is preferable.

## Next discussion and verification policy

Group 1.3 now has application-defined vector/hash storage and by-value signatures through shared concrete publication; empty instance fields and traversal are implemented; writable collection parameters are implemented; bounded 1.5 readonly initialization and the valid-data token/source-snapshot proof are implemented; review the tokenizer-path prerequisites next. Groups 1.1 and 1.2 have reached their agreed bounded scope. Coordinate collection ownership before implementation. Continue AST relationships against the updated milestone-3 status rather than repeating landed slices. Tokenizer work can proceed independently where its prerequisites are available.

Open decision: confirm a readable native-oriented translation with deliberate local adaptations, rather than near-verbatim PHP. This determines which traits, unions, closures and collection behaviors are mandatory. Writing this plan does not settle that question.

For each implemented slice, test the real caller and failure/lifetime behavior, not only syntax acceptance or C++ emission. Apply relevant all-flavor and incremental recovery checks. Native proofs must compile and execute. Report compiler work, qualification, native wall, end-to-end and Clang frontend/backend/link timings when benchmarking, plus change counts. No performance or completion claims follow from this proposed ordering alone.

### 1.5 stored-value readonly — bounded implementation

One syntax flag on `ast_instance_field`; common access checks enforce stored-value
immutability. The type-level reference-mutation query defaults to false, is true for
shared record owners, and is generated from boxed family copy semantics for dynamic.
No new AST node family, retained occurrence facts or C++ const layout is introduced.
Temporary constructor state tracks single initialization, reads and conditional joins;
it is released after success or failure. Loops cannot initialize readonly fields.

All three flavors pass native construction/copy/reference-boundary tests, enum fields,
27 incremental revisions against fresh artifacts, readonly-contract edits and rejection/
recovery checks. Ordinary struct, constructor, method and enum-default regressions pass.
Two old 8-byte-owner assertions in the struct suite were corrected to its existing
control-pointer plus view-pointer layout; no owner ABI changed in this work.
Compiler writing checks pass. The subsequent valid-data token/source-location proof is recorded in section 1.

Initial readonly fixture timings (ms; individual observations):

| Flavor | PHP compiler | Native wall | Clang frontend | LLVM | Link |
| --- | ---: | ---: | ---: | ---: | ---: |
| php | 7.84 | 242.67 | 73.61 | 66.42 | 68.00 |
| cpp | 6.85 | 234.87 | 73.77 | 74.32 | 57.00 |
| js | 7.75 | 254.24 | 80.50 | 83.83 | 61.00 |

O2/g0, Ninja8, mold, no PCH/LTO, prebuilt libc++ runtime; direct Ninja qualification
included in native wall. PHP is separate; native phase work overlaps and is not summed
into wall time. These are fixture observations, not a controlled performance comparison.
Raw proof: `/tmp/scpp-readonly-verified`; merge proof: `/tmp/readonly-merge-tests.log`;
record regressions: `/tmp/readonly-record-regressions.log` and
`/tmp/readonly-struct-regression-final.log` (corrected stale assertions).

### Recursive collection values — implemented

[Recursive specialization](../../compiler/docs/nested_collections.md) reuses vector/hash
recipes and canonical element-owned caches, including three-level homogeneous and mixed
family nesting. All three flavors pass copying, mutation, fields, calls, traversal and
36 incremental checkpoints against fresh output. No AST facts or preparation passes were
added. Shared-owner hash keys now use the [common key contract](../../compiler/docs/hash_keys.md).
Other key families and self-referential collection fields remain separate.

### Milestone 3.1–3.3 bounded follow-up

[Protected instance methods and final classes](class_contract_completion.md) reuse
existing access, declaration and cast/test owners. Required-field policy remains
unchanged. Exact-type uses were inventoried; no new query syntax is introduced.
Protected static methods subsequently gained [declaring-type calls and lexical self
resolution](../../compiler/docs/protected_static_methods.md), reusing the same access
and call owners without retained structure changes. Descendant-name and lexical self calls now reuse the inherited method lookup.
Explicit ancestor static calls now use the ordinary binder. Compatible static redeclarations and final static methods reuse declaration checks and fixed lookup. Interface/composition work remains separate.

#### Inheritance follow-ups after ancestor static methods

| Order | Small work and status | Owner and verification |
| --- | --- | --- |
| 1 | Implemented. Protected static properties and constants through declaring types, descendants and lexical self. | The declaring symbol owns storage or the constant value. |
| 2 | Implemented. Explicit ancestor property reads/writes and constant reads in all three flavors. | PHP `parent::` and JS `super.` select the lexical base. C++ names the type. Reuse inherited lookup and verify both base edges. |
| 3 | Verified. Inherited name collisions during editing. | Existing record layout validator rejects repeated static properties/constants and instance/static property collisions. Exercise introduction, failed retry and removal in a separate intermediate source. |
| 4 | Implemented. Shared incremental dependencies for protected access. | The record access check observes the lexical layout and, for instance access, the receiver layout. Their base dependencies already propagate. Verify factory access when the lexical base changes and instance field/method access when a receiver becomes a sibling and returns. |
| 5 | Implemented. Expression-qualified static calls, property reads/plain writes and constant reads. | The [three-flavor expression qualifier](../compiler_frontends.md#static-calls-qualified-by-a-value) preserves evaluation and temporary lifetime. Reads and writes bind the declaring symbol. |

The [static data and method suites](../../compiler/docs/protected_static_methods.md)
exercise these slices against fresh compilations and native output. Static bodies now also support
called-type forwarding, late-selected members and shared factories. Inherited public final
class methods can satisfy a new interface through the [existing forwarding path](../../compiler/docs/interfaces.md).

**Separate design or implementation slices**

| Candidate | Prerequisite or decision |
| --- | --- |
| Interface inheritance, variance and abstract structs | Separate proofs for interface layout/dispatch, signature bridges, and inline construction/copy admission, respectively. |
| Interface-qualified static dispatch and exact-type query syntax | Source behavior remains a discussion item. Subtype and interface-capability tests already exist. |

Required-field policy stays as agreed. Trait composition and ownership families remain separate workstreams.
