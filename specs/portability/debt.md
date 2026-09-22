# Portability consolidation and adoption debt
Doc Status: planning

Current compiler status: [rewrite reset](../planning/compiler_migration/rewrite_reset.md),
thirty active ready files for input preparation, tokenization and file grammar; see
[its bounded JSON adapter contract](project_manifest_reading.md). Capability support below remains available; prior compiler
slice statements describe archived implementation/proofs, not active rewrite coverage.

Date: 2026-09-21. This records the converter consolidation checkpoint. The compiler
has since been [adopted](../planning/compiler_migration/adoption.md); the
[first component slice](compiler_context_slice.md) now passes PHP/native proofs. The native escape hatch remains documented only. No `SCPP_NATIVE`
constant or native-comment conversion is implemented.

## Current constraint consolidation

This section consolidates the subsequent slices and review decisions. The older
tables and checkpoint notes below retain their historical context; their initial
unsupported-feature lists are not the current converter matrix. Thirty-six production
files are in the ready set, not the whole compiler. [Semantic type-reference records](compiler_type_references_slice.md) now prove
heterogeneous interface-vector membership and identity. Explicit literal named local
types are supported; reference resolution/downcasts and variant consumers remain work. The user resumed bounded
portability slices with the [fixed tool-service requests](compiler_tool_contracts_slice.md).
The next [source read-task slice](compiler_read_tasks_slice.md) preserves fixed
worker observations/requests using existing conversion capabilities.
The [discovery-record slice](compiler_discovery_records_slice.md) adds nullable
list/named fields. The [recursive annotation slice](container_annotations.md) now expresses its typed
maps and nested vectors directly. [Source_Set](compiler_source_set_slice.md) now
passes index, acknowledgment, JSON and identity proofs. [Keyed probes and by-value
iteration](map_iteration.md) are now proved too. [Container method returns and
nonpublic scalar fields](container_returns.md) cover the next local declaration needs;
container interface returns hit a verified pinned-target syntax limitation. The selected candidate now fixes direct nested-vector field reads (#232), as
proved in the [collection integration](collection_helpers.md).
That target lowering bug is tracked in [issue #232](https://github.com/alexstanciu-1/simplecpp/issues/232);
the earlier explicit-local workaround remains valid on the older release.

The [source-scan join](compiler_scan_join_slice.md) now proves concrete typed
batches with a marker Join contract, explicit record copies and task-order acceptance.
[Snapshot acceptance](compiler_snapshot_join_slice.md) now proves buffer replacement,
retention and rejection with explicit Source_Set copies. Its selected-target compound
null-guard failure is avoided by a separate guard; this target issue remains documented.

The [fixed read-selection owner](compiler_read_selection_slice.md) now passes
PHP/native membership, ordering and task-version proofs. Full filesystem reading
requires an exposed path/open-handle stat contract: selected-target filesystem
headers and strict symbol catalog expose no lstat/fstat or device/inode observations.
Internal fstat calls in lock/process implementations do not provide that API.
Preserve the existing race checks when this dependency is addressed on v0.1.

[Token storage](compiler_token_store_slice.md) adds ordinary explicit constructor
inputs, concrete nullable returns and enum-name export. Empty container defaults
remain rejected; selected-target enum-name lookup requires the operation beside
the enum with a local enum-typed parameter. Token runtime name collisions require
qualified compiler container types. [Token acceptance](compiler_token_join_slice.md) now passes PHP/native proofs.
[Token selection](compiler_token_selection_slice.md) now preserves full/incremental
identity selection in PHP/native proofs. Lexical algorithms and their source-diagnostic
dependency remain migration work.

[Source diagnostic preflight](../planning/compiler_migration/source_diagnostic_preflight.md)
reproduces a qualified-base inheritance blocker on the selected target, filed as
[issue #233](https://github.com/alexstanciu-1/simplecpp/issues/233). The native control
proves a typed catch strategy; converter subclass support remains unimplemented.

[File_Frontend](compiler_frontend_record_slice.md) now binds token/tree references
at construction and validates indexes before native access. Uninitialized named
fields remain unsupported; no converter relaxation was needed. [Frontend storage
and segmented acceptance](compiler_frontend_join_slice.md) now pass PHP/native
proofs; parser workers and structural query owners remain migration work.

[Binary syntax](compiler_binary_syntax_slice.md) now uses explicit typed scratch
stacks and passes PHP/native/operator and frozen-prototype angle-pairing proofs.
The [struct-member cursor decision](../planning/compiler_migration/struct_member_cursor_decision.md)
now has a PHP implementation and ten adapted consumers, with 39 retained fixtures
passing. Real query dependencies now convert and pass PHP oracles; native construction is
fixed by candidate a1a1babd. Explicit source guards now clear its eager-evaluation
constraint and the 39-file cumulative query/cursor native proof passes (see the
[adaptation record](../planning/compiler_migration/php_adaptation_record.md#explicit-control-flow-for-dependent-guards)); no
generic generator conversion is implemented.

[Parser selection](compiler_parser_selection_slice.md) now delegates to a pure
owner with PHP/native full/incremental, identity and failure proofs. This is not
full Parser phase coverage.

[Logical syntax comparison](compiler_syntax_comparer_slice.md) now preserves
subtree boundaries and byte spelling with typed reusable frames. While loops have
a production proof; generic generator conversion remains outside the profile.

### 1. Coder guidelines: review and behavioral tests own these

- Prefer structures with named typed fields. Ordinary PHP arrays are for non-hot
  setup followed by read-only use, not the default mutable compiler state.
- Preserve behavior the algorithm relies on. Make mutation, sharing, copying,
  snapshot independence and ownership visible; do not depend on incidental PHP
  copy-on-write behavior. The converter does not judge these design choices.
- Keep references under careful review; gradually move appropriate data toward
  owned records accessed by index. Do not confuse logical IDs with storage offsets.
- Stabilize boundary data into explicit structures. Use explicit JSON schemas and
  serialization/parsing operations; avoid object-to-array JSON roundtrips as an
  internal representation or equality mechanism.
- Distinguish UTF-8 text/code-point operations from byte operations. Lexer offsets
  and binary inputs need explicit byte helpers. Neither code points nor bytes mean
  user-perceived graphemes; normalization is not implicit.
- Use explicit types at meaningful boundaries and distinguish null, false and
  errors. Prefer fixed members, direct calls and explicit resource owners over
  dynamic PHP behavior or destructor-timing assumptions.
- Adapt source when its shape is unsuitable. Do not expand the converter merely
  to accept arbitrary prototype PHP. Preserve existing functionality and tests;
  add no compiler functionality before full migration.

### 2. Converter restrictions: fail with source diagnostics

These are structural checks, not proof of whole-program correctness. Existing
rejection paths cover the following categories; some diagnostics are generic
unsupported-syntax errors rather than dedicated explanations.

| Boundary | Current restriction |
| --- | --- |
| Namespace/import policy | No namespace or one leading lowercase semicolon namespace, optionally preceded by strict declaration/comments. Reject bracketed, repeated or late namespaces, manual import aliases, modified managed imports and framework-name bypasses. |
| Declaration index/traits | Reject duplicate declarations, missing or cross-namespace traits, trait composition, adaptation blocks (`as`/`insteadof`), repeated uses and method collisions. Current traits are method-only; fields, constants and magic methods are rejected. Indexing a declaration does not imply its body is convertible. |
| Local conversion facts | Require supported explicit type/annotation shapes; reject unsupported annotations, dynamic calls/member names and unmapped operations. No receiver-type inference, remote signature/default lookup, inheritance analysis or general symbol resolution. |
| Syntax coverage | Forms outside the implemented parser subset must fail, including named/unpacked call arguments and currently unsupported functions, closures, reference parameters, inheritance, constructor forms outside the explicit supported grammar and `finally`. These parser gaps are not blanket Simple C++ language limitations. Supported scalar/named method signatures with void returns, promoted constructors, bounded loops, vectors and fixed handled exceptions remain available. |
| Literal representation | Reject NUL/invalid-UTF-8 string literals and unsupported Unicode escape spelling. Runtime binary input is a separate contract. |
| Output/cache integrity | Reject invalid manifests/indexes, conflicting or modified owned support artifacts and unsafe symlink paths. A failed conversion does not make retained previous output current. |

Every extension needs accepted and rejected examples plus a meaningful native
proof. Do not silently pass unsupported syntax through or erase required behavior.
The direct same-namespace trait index is the agreed narrow exception to file-local
conversion, not permission to build a semantic compiler. Wrong inferred types,
callee compatibility and general program semantics belong to target analysis and
compilation, not these local checks.

The [method signature slice](method_signatures.md) removes the temporary scalar-only
method restriction using one local parser for classes, traits and interfaces.

### 3. Contracts still requiring decisions or proof

The [owned-collection snapshot proof](collection_snapshots.md) establishes explicit
replacement copying for class rows containing scalar/string/vector-of-int fields,
with shared unchanged rows. It also demonstrates that shallow copies do not enforce
immutability. Broader graph/container/value representations remain open below.

- Typed containers, value records, owner-specific copying and snapshots: preserve
  mutation/identity, including nested data; do not assume PHP arrays prove native
  value or aliasing semantics.
- Heterogeneous variants, interfaces, class unions and narrowing: prove the chosen
  target representation before changing compiler type-model owners.
- Custom diagnostics/exceptions, native-originated failures and guaranteed cleanup:
  current fixed exception-family proofs do not cover arbitrary inheritance or
  uncaught diagnostics. `finally` exists in the target but not yet in this converter.
- Enum names/values, byte scanning/search helpers and schema-based JSON adapters:
  define the selected operations as real slices need them, without receiver lookup.
- Numeric boundaries and readonly/lifetime behavior: PHP execution is an
  approximation. Existing native readonly limitations are not enforced immutability.

These are not all requests for new Simple C++ features. First inspect existing
target contracts, then choose source adaptation, a PHP adapter, converter coverage
or a demonstrated target fix. Wide model changes require a separate discussion.

### 4. Target requirements and native-only evidence

[Issue #231](https://github.com/alexstanciu-1/simplecpp/issues/231) tracks the agreed
[three-item batch](../planning/compiler_migration/simple_cpp_batch.md): typed
map/filter helpers, managed child processes and cross-process file locks.
Native contracts are implemented on the selected unreleased candidate `08c8206a`.
[Collection integration](collection_helpers.md) is proved; PHP process/lock parity
remains outstanding. The [OS candidate checkpoint](os_helpers.md) records both
implemented PHP backends, passing host lifecycle tests and the v0.1 alias-signature
blocker, subsequently fixed by `2f0d667f`; the prepared native facade proof now passes.

PHP sequential MT approximations prove algorithms, not threading, synchronization,
suspension or native lifetime behavior. Process cancellation/reaping and file-lock
contention need real separate-process tests. Run target-specific proofs after
conversion. Historical evidence retains its original pins. Candidate `08c8206a` passed the
collection and cumulative compiler proofs before selection; it also fixes #232.

### 5. Tooling and workflow debt

- The [authoring guide](authoring_guide.md) and project-local
  [portable-PHP skill](../../.agents/skills/simple-cpp-portable-php/SKILL.md) now cover
  implemented forms and boundaries. Keep them aligned as capabilities grow;
  strict PHP++ guidance alone is not executable portable PHP guidance.
- Read-only `check.php` now shares discovery/imports, declaration/trait indexing
  and conversion rules, and adds PHP lint. Optional existing-cache reuse writes
  nothing; validation runs for every file and reports the first failure. The
  [ready-set test orchestration](validation_workflow.md) is now available separately;
  broader project discovery, native diagnostic mapping, policy validation
  and coherent IO failure reporting remain incremental tool work.
- Conversion remains one PHP file to one PHP++ file, with direct trait dependency
  invalidation. Warm caching still polls metadata; it is not a filesystem watcher.
- Publication is atomic per file, not per project, and concurrent writers are not
  supported. Consume generated output only after successful conversion.
- Keep PHP bootstrap/test infrastructure outside the conversion source set;
  generated PHP++ is derived output, never a separately maintained fork.
- The `SCPP_NATIVE` escape hatch stays documentation-only. GUI/WebView and full PHP
  compatibility stay out of scope. v0.2 naming migration and v0.3 removal of legacy
  Prism file/folder acceptance remain separate product work.

The earlier consolidation did not resume migration. The subsequent explicit resume
authorizes bounded slices using proved capabilities; grow each owner when a real
migration slice needs it. The target batch remains separate work.

## Readiness assessment

Target work deferred for joint resolution is tracked in the
[Simple C++ batch list](../planning/compiler_migration/simple_cpp_batch.md).
Typed map/filter helpers, managed child processes and cross-process file locks are
queued there; implementation is deferred while portability constraints are reviewed.

The [UTF-8 checkpoint](utf8_text_contract.md) establishes managed text helpers and
explicit byte operations. Code-point semantics and malformed-input rejection are
proved in PHP/native execution; older byte-oriented default `strlen` guidance is
superseded. Other string functions, grapheme behavior and source-literal binary
limitations remain outside this slice.

Later agreed boundary: [direct traits and incremental index](traits_and_incremental_index.md)
now implements declaration-location lookup, same-namespace method expansion and
persistent PHP token arrays. Earlier "no symbol resolution" statements exclude
general semantic resolution; this narrow structural lookup is explicitly allowed.
Trait bodies still require supported portable method syntax; the index does not
make all prototype code convertible. File membership and direct-trait invalidation
are incremental; metadata polling and conservative recent-write checks remain.

The loop is proved for the small scalar script fixture: executable annotated PHP,
managed imports, local conversion, incremental publication, PHP++ build/run and
comparison against expected results. This is a usable foundation for extending the
converter. It now also converts the actual `compile\Update_Context` component;
this establishes a shared-reference class path, not whole-compiler portability.

The planned working loop is accepted:

1. When mining is authorized, select one bounded prototype component and record
   its provenance, dependencies and intended behavior.
2. Prepare readable portable PHP; make target intent explicit where needed.
3. Check imports, PHP syntax, local portability and PHP behavior.
4. Add only the syntax/library capability the selected component actually needs.
5. Convert file-to-file, compile with strict Simple C++, and run the same behavioral
   witnesses plus required native-only tests.
6. Compare expected behavior, record intentional differences, consolidate the shared
   path and repeat with a slightly larger component.

Do not implement the 241-symbol library inventory before starting this loop.
The first component should expose a useful small gap, not demand the whole runtime.
The initial review selected no component; the subsequent update-context slice is
now complete. Subsequent planning adopted
a complete-codebase preservation strategy: see the [inventory/baseline plan](../planning/compiler_migration/README.md).
First adopt and reproduce the whole PHP baseline, then adapt incrementally; no new
compiler functionality until full migration is complete.

## Consolidation completed

| Finding | Resolution | Evidence |
| --- | --- | --- |
| Import synchronization could discard arbitrary executable content between managed markers | Replace only the expected generated import-declaration shape; reject unexpected content and preserve source | Runner inserts an executable statement into the block and proves rejection with unchanged bytes |
| Conversion manifest was used without version/fingerprint-shape validation | Validate supported version, file collection, relative paths and hash fields before publication | Invalid/null/version-mismatched/corrupt-hash states fail without changing generated source |
| Output symlink checks could be bypassed by the reuse fast path | Check manifest and output paths before reading/reusing them | Hash-matching symlink output is rejected; external target remains unchanged |
| Catalog rows could look like implementation commitments | Keep target inventory separate from converter scope; explicitly mark GUI/WebView out of scope | Catalog/first-slice/debt entry points |

These are local corrections inside existing owners, not new language features.

## Tokenizer checkpoint

The [tokenizer vocabulary slice](compiler_token_slice.md) adds literal int-backed
enums, literal named constants and initialized named-type fields. The ready set
now includes `src/02_tokenize/structures.php`. PHP/native row behavior and existing
tokenizer fixtures pass. Packed token layout remains debt: this slice intentionally
retains the adopted class representation. Methods, containers and enum reflection
remain unsupported; there is no general enum or class portability claim.

## Remaining capability debt

Historical foundation table: read together with the current consolidation above
and the later checkpoint notes, which supersede completed portions of these rows.

Update-context checkpoint: P01 now covers one optional strict declaration and one
lowercase semicolon namespace, comments and LF/CRLF input. P02/P03 cover ordinary
classes with explicit initialized scalar properties, literal construction and named
property access. P05 is settled for shared-reference classes; value records and
containers remain open. P10 now has an explicit compiler migration file list with
host-only staging/loading. The remaining breadth in the original rows below is
still debt, not a reason to widen this completed slice.

| ID | Debt / current limitation | Owner | Smallest useful proof / exit condition |
| --- | --- | --- | --- |
| P01 | Single strict/namespace prologues are supported; block/multiple namespaces and class/type imports remain unsupported | Import_Policy + synchronizer | One real file prologue executes in PHP, converts correctly and cannot rebind reserved function names; define supported namespace shape and CRLF/leading-comment policy |
| P02 | Scalar-field classes are supported; functions, methods, constants, inheritance and user-defined calls remain unsupported, as do most operators, arrays, indexing, loops and return | Converter structural AST | Add coherent declaration/expression nodes needed by the selected component; real cross-file call and native execution without symbol lookup |
| P03 | Local annotation grammar covers four scalars and three simple wrappers; class properties cover bool/int/string literals. Generic containers, named local types and typed callable slots remain open | Converter local annotation owner | One parameter/property/return/container example with explicit local intent and native compile witness; no remote type discovery |
| P04 | Runtime contains only scalar `take_*`; `is_bool` is the sole ordinary-PHP mapped builtin; no shipped compat count/JSON adapters | PHP framework + function policy | Add only selected dependencies; common names have one policy in every file; actual adapter output/error behavior matches the intended contract |
| P05 | Shared class identity is proved for update context; value-record copying and container representation remain design choices | Portability contracts + PHP library | First component states which values share or copy; tests check relevant mutation/identity, not merely printed output |
| P06 | Completed: portable-PHP authoring guide and project-local skill | Guide + skill + owning contracts | Maintain supported examples and restrictions as proved slices grow; direct PHP++ guidance is not executable PHP guidance |

P01–P05 are capability gates only when a component needs them. Do not solve every
variant before the first useful slice. P06 improves repetition; it is not a reason
to delay a manually reviewed first proof.

## Tool and workflow debt to grow with use

P14's original single-script evidence has been superseded by the compiler component,
traits and UTF-8 proofs. Representative whole-compiler native coverage remains open.

| ID | Debt | Current boundary / next trigger |
| --- | --- | --- |
| P07 | Call nodes retain token/group children rather than explicit argument nodes; arity is a top-level comma count | Fixed positional calls only. Trailing commas are currently rejected; named/unpacked arguments are unsupported. Introduce argument nodes when adding defaults/optional arities, never receiver-type lookup |
| P08 | No native-diagnostic source map back to authored PHP | Local parser diagnostics have PHP file/line; generated-file positions are not a complete original-source mapping. Add maps when a real failure needs them; do not claim blank-line preservation solves all attribution |
| P09 | Check-only CLI and ready-set validation driver completed; general project discovery remains open | `check.php SOURCE [--cache EXISTING_OUTPUT]` shares conversion rules plus PHP lint. `validate.py --results FRESH` checks the ready compiler set and PHP behavior; explicit native selections reuse existing proof runners. |
| P10 | Directory CLI still takes every `.php`; compiler/portability.json now lists ready files for host staging. General source-set/exclusion/autoload tooling remains open | Keep runtime/runner files outside the converted tree. Define a minimal source-set/loader boundary with the first multi-file component |
| P11 | No artifact-set transaction or concurrent invocation lock | Individual replacements are atomic; conversion failures precede publication, but IO/process interruption during publication may leave a partial tree. Single invocation only; on publication failure discard/rebuild the dedicated output tree. Add recovery/locking before concurrent or unattended use |
| P12 | File IO error reporting is basic; manifest and policy are local trusted tool inputs | Improve coherent IO failure reporting and source/output identity validation if build automation requires it. Do not turn this into a cache service |
| P13 | `function_map.php` policy schema is a trusted PHP array; runtime/bootstrap consistency is tested only for current entries | Add policy validation/bootstrap coverage as bindings grow; avoid duplicate implementation maps and silent host-extension fallback |
| P14 | Native proof is one scalar script; runner checks expected output and exit status, not a representative compiler workload | Add real component fixtures, reference/identity assertions and negative target cases only when relevant; native build timings are not a converter performance baseline |
| P15 | Catalog signature/member/constant inventories have gaps and some upstream documentation conflicts | Resolve the owning contract for each selected adapter; do not freeze every library in advance. The 241 global registry names are fully inventoried, not fully implemented |
| P16 | Current project manifest/build outputs still use legacy naming | Broader v0.2 Simple C++/scpp naming migration is separate work; retain legacy-path acceptance through v0.2 as agreed |

## Intentionally deferred or excluded

- GUI/WebView libraries: out of scope for this PHP framework.
- Dynamic PHP behavior: discouraged; unsupported dynamic constructs are rejected.
- Exact PHP compatibility, symbol resolution, whole-program inference and importing
  the prototype into the converter: excluded, not future converter debt.
- MT/async simulation: add useful PHP library approximations when needed; actual
  concurrency/suspension/lifetime properties are native-tested.
- `if (SCPP_NATIVE)` escape hatch: documentation only by explicit user instruction;
  do not implement as part of consolidation.
- Compiler self-hosting and LLVM/native backend: separate milestones, not prerequisites
  for the PHP development loop.

## Existing proof commands

From repository root:

```bash
php tools/php_portability/sync_imports.php tests/portability/fixtures --check
php tools/php_portability/sync_imports.php examples/portability/take/php --check
python3 tests/portability/run.py
python3 tests/portability/run.py --native
```

The native mode requires the configured Simple C++ toolchain/runtime. The test
runner works in disposable directories; it does not mine the compiler prototype.
The sample [README](../../examples/portability/take/README.md) describes direct PHP
execution and native regeneration/build commands.

No commit or branch merge is implied by this review. The v0.2 working changes
remain available for review together.

The [step contract slice](compiler_step_slice.md) adds unit enums and declaration-only
interfaces. Three compiler production files are now ready; methods and containers
remain pending. Conversion still performs no symbol lookup.

The [source-path spelling slice](compiler_path_slice.md) adds real scalar static methods,
literal static calls and an explicit `scpp\string_byte_slice` runtime operation.
Four production files are ready. Filesystem resolution remains outside the ready
set; hex-escaped binary literals require a later conversion/target decision.

[String-byte consolidation](compiler_string_bytes_slice.md) preserves PHP byte lengths via native
`string_byte_len` and rejects unsupported binary literal construction instead of
letting the selected target silently corrupt it. Arbitrary byte construction is
still migration debt.

The [parser node slice](compiler_nodes_slice.md) extracts and ports the existing
syntax vocabulary and flat node row without new conversion syntax. Five files are
ready. Readonly role records, constructors, cursor containers and full parser
execution remain pending.

The [role-view slice](compiler_role_views_slice.md) adds local expansion of readonly promoted
constructors and explicit nullable type annotations. PHP enforces readonly; the
selected native target does not. Six production files are ready. General method
bodies, cursor containers and arbitrary constructor logic remain pending.

The [cursor slice](compiler_cursor_slice.md) adds explicit vector properties/locals, mutable
promotion, enum-case defaults, brackets and one-argument list counting. Seven files
are ready. Scalar/enum list behavior is proved; stack operations, nested containers
and parser execution remain pending.

The [storage slice](compiler_storage_slice.md) proves immutable snapshot identity and vectors
of shared syntax-node objects using existing conversion rules. Nine production
files are ready; lookup sets, serialization and frontend algorithms remain pending.

The [decimal algorithm slice](compiler_decimal_slice.md) adds bounded arithmetic/loops and
`scpp\string_byte_at`, with independent integer-oracle evidence. Ten production
files are ready. General arithmetic edge semantics and the full type-aware literal
checking path are not claimed by this slice.

[Exception-boundary preflight](../planning/compiler_migration/exception_boundary.md)
found that direct SPL exception classes, parent construction and inherited message
methods are not provided by the selected native target. A bounded native hierarchy
probe passed; production framework integration and fuller failure behavior remain
work. Do not pass exception-heavy files through by merely accepting their tokens.

The [handled-exception slice](compiler_exception_slice.md) implements fixed root
exception types, ordered catch dispatch, message/code/cause and an explicit identity
helper. Native framework assembly has a separate owner/manifest. Eleven production
files are ready. Uncaught messages, native-originated failures, user exception
subclasses, finally and subtype-specific typed catch boundaries remain open.

[Quoted-byte decoding](compiler_byte_literals_slice.md) is proved independently of
the remaining body checker. `string_byte_from_int` constructs one arbitrary byte
with an explicit 0..255 contract; native assembly currently uses `hex2bin`. This
adds no general PHP builtin support or Unicode-escape/interpolation functionality.

[Native project identity/roots](compiler_native_project_slice.md) now passes the
byte-oriented root/validation proof. Separate readonly scalar declarations are
supported for constructor initialization; no general uninitialized-state model or
native readonly enforcement is claimed.

[Manifest snapshot export](compiler_manifest_record_slice.md) now preserves JSON
through an explicit schema. The framework owns shared `json_quote` scalar encoding;
this does not add JSON ingestion. Nullable scalar fields accept matching literal
defaults. The manifest reader/stage lifecycle remain migration work.

[JSON ingestion preflight](../planning/compiler_migration/json_ingestion_preflight.md)
proves that the selected decoder collapses `{}`/`[]` and numeric-key objects/lists,
including nested fields. Manifest parsing cannot use a naive mixed-table adapter;
this is distinct from the already-proved scalar JSON output helper.

[Source scanning](compiler_source_scanner_slice.md) now uses proved filesystem
predicates and false-result scan/size/mtime adapters. Scanner and result ownership
are ready; phase scheduling, path resolution and verified source snapshot reading
remain distinct work.

[Runtime-preparation symbols](compiler_preparation_symbols_slice.md) is the first
ready src-runtime-preparation module. It preserves byte-safe reversible names and
PHP input diagnostics. Remaining preparation metadata and orchestration are still
part of the migration, not a separately maintained implementation.

Scalar [value records and explicit local aliases](value_records.md) now have a PHP/native proof.
This adds uint32/bool field records and both `&ref` forms, not general clone, nested
record copying, container-interior references or ownership inference. Existing
compiler classes retain their current representation until individually adapted.
