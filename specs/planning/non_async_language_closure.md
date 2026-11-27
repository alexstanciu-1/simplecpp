# Non-async bootstrap language closure

Reviewed at `7c8560abc` in simple_cpp_03. Scope: G01–G17 of the [language-first plan](remaining_language_gaps_and_native_tokenizer.md#language-closure-before-tokenizer-integration). Async, tokenizer integration, helpers and libraries have separate workstreams.

## Result

The inventory pass is complete. Direct translation still has language gaps. The 177 historical Missing/Partial records comprise 99 with bounded evidence, 50 with a migration, and 28 deferred. These categories describe the **bootstrap disposition of each record**. For composite donor records the notes state which parts remain deferred. The appendix in the earlier plan lists 164 IDs; its G05 and G13 evidence sections account for the other ten collection and three exception/destructor records. Companion families and additional findings in current compiler source are outside those counts.

### Decisions before moving on

1. **Result and storage types (companion TYPE-UNION, REC-INIT).** [Constant dispatch](../../compiler/types/definition.php) now extracts integer values, enum case names and literal bytes through typed methods. Preparation checks exact subject type before detecting duplicates within that domain. The [integer and enum proof](../../compiler/tests/typed_label_extraction_native.py) and [string proof](../../compiler/tests/string_label_extraction_native.py) cover admission, collisions and same-instance repair against fresh builds. This closes the combined label-key question for bootstrap. The [translated constant fact](../../compiler/examples/constant_evaluation/constant_fact.php.scpp) models unavailable, known null, boolean, integer and floating values by value. The [native proof](../../compiler/tests/constant_fact_native.py) checks propagation/reset and sign-bit preservation. A local Clang -O2 run measured 24 bytes with 8-byte alignment, 2.31 ns per indirect evaluator call (median of 11 × 240,000), and 1.444 ms to prepare this 2,650-byte fixture (median of 21 fresh preparations in a warmed PHP process, excluding lexing and parsing). A no-edit retry prepared zero bodies. These measurements cover the proof only. The [first translated conversion](../../compiler/examples/constant_evaluation/integer_conversion.php.scpp) consumes the production-selected bool-to-int cast and integer identity initialization operations. The [differential proof](../../compiler/tests/constant_fact_cast_native.py) replays the PHP compiler’s dirty cast sites and compares cast and local facts. Fresh preparation of the three-body input made four evaluator calls; changing the literal from false to true prepared one body and made two calls; a no-edit pass made none. PHP and native counts agreed. In one warm run the median PHP preparation times were 0.3504, 0.1730 and 0.0324 ms respectively. The native cast/local work alone took 20.65 ns for two sites and 13.81 ns for the edited site (Clang -O2, median of 21 batches). These timings describe different scopes. The PHP compiler still owns parsing, scheduling and its production evaluator. Separately, [resolved calls](../../compiler/03_parse/structures/functions.php) store several declaration categories or absence. Their result and storage representations remain open. The PHP return-target contract in the same file uses interface getter properties; the target rejects interface fields. A getter-method control dispatched through the interface and printed 1. Prefer methods and private storage for this port; confirm who may write each staged field.
2. **Expression results (G01).** The parser's child-collector assignments and the preparation resets now use separate writes. A local holding the RHS result preserves single evaluation, write order and identity for these ordinary destinations. The accepted discarded-expression migration uses a standalone call for effects and an explicit local assignment for a checked result. Omit an expression only when it is proven pure and total. PHP, C++ and JavaScript now support chained assignment statements to existing locals. General assignment expressions and effectful destinations remain open.
3. **Collections and references (G05/G07).** Choose concrete records or typed nested containers for each heterogeneous table, with explicit write-back on copied reads. For integer counts, test for a missing key before comparing the stored count with zero. Whole-variable PHP-flavor unset covers nullable values, optional shared owners and weak handles. Whole-variable clean writes the defined default through locals, aliases and reference parameters. Synchronous PHP-flavor free functions can explicitly borrow a vector or hash by read-only reference. The scoped writable-loop exception is recorded in row 3 below.

### Collections and borrowing backlog

Status at the read-only constructor input slice. This tracks PHP collection traversal and
borrowing. The rows group language decisions by the proof they need. They are
different sizes and should not be used to calculate a percentage complete.

| Delivered | Current boundary |
| --- | --- |
| Whole-collection references | Exact-type named vector and hash slots support writable calls and forwarding. Two reference parameters can denote the same caller slot. Synchronous PHP free functions and class, struct and interface methods accept explicit read-only collection references. [Whole-slot contract](../native_reference_safety.md) · [Read-only tests](../../compiler/tests/readonly_parameters_native.py) |
| Writable traversal of owned storage | Keyed and unkeyed PHP loops work for typed vector and hash locals, ordinary by-value parameters, and owned variadic packs in synchronous code. A write is visible before break, return or throw. A visible key assignment cannot redirect the write. Read-only packs reject. [Traversal contract](../collection_traversal.md#php-writable-vector-and-hash-foreach) · [Native and recovery tests](../../compiler/tests/writable_foreach_native.py) |
| 1. Writable traversal through a mutable reference parameter | Vector and hash writes reach the caller. Preparation protects same-type reference parameters and borrowed captures that may alias the source, including through mutable calls. [Alias and recovery tests](../../compiler/tests/writable_foreach_native.py) |
| 2. Explicit read-only collection parameters | Direct, virtual and interface methods, closures, typed callbacks, owned callables and PHP class and struct constructors can borrow exact-type named collection slots. [Constructor tests](../../compiler/tests/readonly_constructors_native.py) · [Callable tests](../../compiler/tests/readonly_callable_inputs_native.py) |
| Protection during traversal | Preparation guards structural mutation through the source, known lexical aliases and mutable calls. Vector writes to existing indexes are visible. Separate hash writes are rejected because assignment can insert. The loop binding ends at loop exit. [Preparation](../../compiler/04_analyze/prepare/prepare.php) |

| Remaining work | Status | Decision or acceptance case |
| --- | --- | --- |
| 3. Scoped borrows of a selected element | Direct mutable and read-only helpers implemented | A synchronous source free function with a value or void result can borrow the selected vector or hash slot in either admitted parameter mode. Plain assignment to an immediate field of a selected inline record also writes through. An inferred local closure may also borrow the selection for direct calls during its iteration. Nested inferred locals can recapture the selected slot by reference for direct calls. Direct synchronous consumers may invoke or forward a `callback_view<void>` during the iteration. A leading required by-value bool may guard invocation or forwarding in either branch, and a consumer can forward its flag alongside its view. Broader consumer bodies and signatures remain open. [Scoped borrow tests](../../compiler/tests/scoped_element_borrows_native.py) |
| 4. Durable aliases and reference returns into elements | Deferred lifetime design | Covers `$item =& $v[$i]`, escaping captures, and returning an interior slot by reference. Specify what happens after vector relocation, hash removal and reinsertion of the same key, or whole-container replacement. A stable reference to the container variable alone does not identify a surviving element. [Reference safety](../native_reference_safety.md) |
| 5. Automatic read-only borrow inference | Cardinality, checked-index and key-presence readers implemented | Direct calls on named locals, ordinary by-value parameters, mutable or read-only variadic packs, or explicit read-only or mutable-reference collection parameters may borrow a vector or hash whose stored elements and keys have proven effect-free copy and destruction for a count, emptiness check, checked indexed read or canonical hash key-presence query. Indexed reads and presence checks take an inert literal or the exact key parameter. The function can return the result directly or initialize an ordinary scalar local and immediately return that same binding. Both initialization and return require identity conversion. An explicit key must be an exact-type inert literal, plain local, or ordinary by-value parameter, with identity conversion. Positional and named calls may also omit a key with an exact-type inert literal default. The caller uses the selected declaration’s prepared default. Named calls use the prepared parameter slots in either label order. An effectful key retains by-value evaluation in written order. Broader by-value parameters remain open. Local snapshots from exact-type named parameters may borrow through one or two consecutive scalar reads at the end of the function. The first read may initialize a scalar local that is added to the second in the return. Longer local borrow intervals remain open. Preserve snapshots across argument evaluation, other aliases, forwarding and escapes. Measure eliminated copies and preparation cost, and invalidate the proof after a relevant caller or callee edit. [Inference debt](#compiler-debt-for-inferred-read-only-borrows) |

**Further boundaries requiring a use case.** Writable loops on fields, static or
global storage need owner-lifetime and alternate-access rules. A temporary also
needs a decision about where its writes would be observable. Async and generator
loops need a rule for mutation while suspended. Boxed traversal needs separate
decisions for mixed copy-on-write and dynamic shared identity. Existing-value hash
updates through a second name would need an operation or proof that excludes
insertion. These are possible extensions, not prerequisites for rows 3–5.

Fixed arrays are tracked separately. Expressions and access now covers [nested writes to local and instance-field vectors and hashes](../vector_slice.md#nested-collection-writes). Nested append follows the same paths into vectors and integer-keyed hashes. [Two-key assignment on boxed locals](../boxed_slice.md#plain-two-key-assignment-on-boxed-locals) supports plain writes, nested append, compounds and standalone updates. All four operations support deeper boxed paths on locals and instance fields. Typed-to-boxed conversion belongs to structured conversion, and
additional library helpers to the libraries backlog. Those areas may eventually
consume an element-borrow contract.

### G01 indexed shared owners in PHP write targets

Doc Status: planning

The grouping-dependent diagnostic observed at `9b9626589` and `aef175d70` is resolved.
PHP admits both `$owners[0]->values[0] = 7` and `($owners[0])->values[0] = 7`
when the typed vector contains shared class handles. Both receiver spellings also accept
field append, retaining the selected owner across RHS replacement. The [native regression](../../compiler/tests/indexed_owner_target.py)
covers single evaluation, replacement of the owner slot, the last owner's destructor,
readonly rejection, and incremental recovery. Indexed inline struct elements remain deferred.
See the [vector contract](../vector_slice.md#nested-collection-writes).

**Writable-path classification.** For nested keyed stores, append, compounds and updates,
[preparation](../../compiler/04_analyze/prepare/prepare.php) uses `nested_collection_root`
and `boxed_path_root` to identify writable storage and check access. All four boxed mutations retain the checked root in statement preparation and clear it on reset. [Lowering](../../compiler/05_backend/cpp/emit.php)
uses that root when staging keys and emitting boxed write-back. Typed slot mutations still consult the path helpers during lowering.

### Compiler debt for inferred read-only borrows

Doc Status: planning

Further borrowing optimizations are deferred. The parallel optimizer will own all existing compiler optimizations and evaluate additional optimization families using prepared facts. Its migration inventory, effects analysis and acceptance criteria are tracked in the [optimizer planning note](prepared_operation_effects.md).

A read-only callee body alone is insufficient. In `snapshot($items, $items)`, if a second parameter clears the caller’s collection before the first parameter is read, the by-value first parameter must retain its original contents. Prove that the source remains alive and that argument evaluation, other aliases, forwarded calls and escapes cannot make the replacement observable. Keep the copy when the proof is unavailable. Track inferred facts across body and caller edits. Verify the optimization with a copy-count or allocation probe on an eligible call and the aliasing counterexample in the [reference tests](../../compiler/tests/readonly_parameters_native.py).

The first multi-statement proof inspects at most two statements during emission. Preparation already records the initializer conversion, the returned name’s local binding and the canonical operation. A checked read still executes at its original statement and can throw. A converted initializer, a different returned binding or an initializer calling another source function uses the ordinary by-value implementation. Branches, additional statements, aliases and indirect dispatch would require evidence about intervening effects or lifetime. The [local snapshot contract](../native_reference_safety.md#private-local-collection-snapshots) covers one or two consecutive scalar reads after initialization. [Copy and recovery tests](../../compiler/tests/count_reader_native.py)

For the three-reader expansion, dev002 body re-preparation showed paired median changes of −0.08% for vector emptiness, +0.30% for hash presence, +0.78% for a checked hash read into a local, and +0.36% for an unrelated scalar return. Each case used 12 alternating baseline/candidate pairs pinned to CPU 23, 60 warm-ups and 6,000 preparations per sample, with Xdebug off. These timings exclude lowering and native compilation.

The [count-reader feasibility study](benchmarks/inferred_collection_borrows/report.md) measures both collection families and records the allocation-failure experiment and the observable destructor counterexample.

### Structured conversion design debt

Doc Status: planning

Typed keyed literals now use the [named constructor of a data record](../data_initialization.md#named-construction-of-data-records). Field-derived records also support [typed JSON encoding and decoding](../data_initialization.md#native-codec-adapters-for-field-derived-signatures). General in-memory structure import and export remain open.

Explore an opt-in interface or capability for converting between a typed value and an in-memory structure. The working names are `::to_structure` for exposing ordered elements or key/value entries, and `::from_structure` for constructing a typed value from structured input. The two directions could be supported independently. The motivating case is a typed vector used as a child of a `mixed` or `dynamic` literal (ARR-VALUE-001).

Explore sharing the part of [initialization](../data_initialization.md#construction-targets) that consumes ordered inputs. For example, a vector could expose its integers in order once, and the selected family's list initializer could box them and construct the child. In the reverse direction, a typed target could adapt structured input to its existing list or keyed initializer, reusing entry conversion, key validation and construction failure handling. The literal parser would still own source notation.

Before adopting a contract, decide how a type publishes each capability, when a known destination may select it implicitly, and how entries are passed with their order and lifetime preserved. Check the design against both a positional collection and a keyed record, including invalid input and the existing mixed/dynamic family boundary. The names, signatures and selection rules remain open.

**Native list export probe.** A [focused Clang probe](probes/boxed_vector_export.cpp) sends `begin_array`, typed `write` and `end_array` through the existing `Encoder&`. The destination constructs a fresh packed container, boxes each value in its own family and appends it. Both `mixed` and `dynamic` preserved the order and contents of integer and string vectors after replacement, clearing and destruction of the source, including a heap-backed string. The shared test traversal uses only vector size and checked element reads; a string read currently returns a value.

The default runtime now publishes the structural Encoder with JSON disabled. Run the `boxed_vector_export` CTest to exercise the native probe against that runtime. The JSON writer separately selects its published encoders. Explicit casts now export typed vectors and [field-derived data structs](../data_initialization.md#explicit-boxed-export) with scalar leaves into the selected boxed family. Implicit export and shared boxed subtrees remain open.

**Native boxed import probe.** A native consumer can request the generated
`Packet::decode(Decoder&)` field loop and pass it a mixed or dynamic cursor.
The [round-trip test](../../compiler/tests/fixtures/boxed_record_decode.cpp)
checks named fields in reversed order, omitted defaults, an independently
owned vector, and rejection at `$.data[1]` before root construction. It links
only the application runtime. Source-level import syntax and error delivery
remain open.

### Call-target storage and conversion follow-up

Defer byte-to-int widening in the translated constant evaluator until a bootstrap consumer needs a known byte to remain known. Decimal token decoding already exercises the runtime conversion.

In a resident PHP compiler trace, the caller contained both `answer()` and `strlen($text)`. Editing only the body of `answer` prepared one body and reused two. Both call nodes survived. The source call retained the same symbol entry and resolved its replacement declaration. Changing its return type from int to bool prepared two bodies and reused one. The caller saw the new return type. The runtime descriptor kept its identity throughout and supplied `scpp::str::byte_length` and `scpp/string.hpp`. A separate source-shadowing check added a declaration named `strlen` and removed it again. Preparation rebound the same spelling to the source declaration, then to the original runtime descriptor. Preserve the source-name watch even when lookup falls back to runtime. [Resolution](../../compiler/04_analyze/prepare/prepare.php) · [Prepared call](../../compiler/03_parse/structures/functions.php)

Additional resolved call targets in the same PHP field:

| Example | Lifetime and edit behavior | Consumer |
| --- | --- | --- |
| Inherited static `Right::read()` | The demand graph interns the method-entry/called-type-entry pair. An edit retaining the same demand reused the private implementation. After successful preparation, removing the last reachable demand released its instance and private body while a sibling demand survived. | Signature queries follow the method entry. Emission selects the instance implementation prepared for the called type. [Lifetime proof](../../compiler/tests/called_method_lifetime.php) |
| Callable local `$g()` | Preparation retains the lexical binding. A no-op reused the exact call and declaration. Editing the enclosing function replaced both; the new call bound to the new declaration. Moving an invocation outside the block fails lookup. | The emitter obtains the signature from the binding type and invokes the local value by its generated name. [Callable scope proof](../../compiler/tests/capture_free_lambdas_native.py) |
| Python-flavor `Counter(7)` | The call holds the stable type entry. Editing the constructor body preserved call and entry identity, replaced the record declaration and prepared both bodies. Adding a required parameter failed at the caller; repair reused the same entry. | Preparation watches the type and selected constructor and binds argument conversions. The emitter uses the shared result type and current constructor signature to generate the owner factory invocation. [Construction lowering](../../compiler/05_backend/cpp/emit.php) |

These traces establish lookup and ownership requirements. The [weak-handle comparison](benchmarks/call_targets/report.md) measures both candidate layouts, fresh and edited binding, allocation and consumption. The [production-consumer pilot](benchmarks/call_targets/report.md#pilot-with-production-consumers) matched diagnostics and emitted C++ across edits. PHP whole-program serialization failed on the weak slot in the existing lowering-purity check. Keep production storage pending a weak-aware purity oracle.

### Companion topics beyond the donor rows

| Group | Current evidence and bootstrap disposition |
| --- | --- |
| G14 · Compile-time types/layout | Namespace integer constants fold arithmetic and named dependencies during preparation. Cross-file edit, cycle and repair proofs compare resident and fresh compilation. Fixed and nested byte arrays have native proofs; the int-element probe rejects. Keep literal values/metadata specializations for bootstrap. Source templates, restricted payload unions, packing and general layout queries require a concrete source/layout contract before implementation. |
| G15 · Native interop | Source `extern function` rejects. The supported integration route uses registered metadata. No inline native syntax or new library dependency is needed for this pass. Recommend keeping raw extern/inline native source outside the bootstrap surface. |
| G16 · Policy | The [frontend contract](../compiler_frontends.md) defines one language model across flavors. Existing preparation rejects invalid source; checked runtime failures remain observable. Keep diagnostic severity/profile design separate from language validity. No alternative strict/legacy language mode is needed. |
| G17 · Synchronous producers | The [yield proof](../../compiler/docs/yield.md) covers free producers, suspended locals/loops/foreach and cleanup. Source delegation rejects. The production declaration and adapter walkers use member producers and delegation; the visitor alternative relies on exhaustive traversal and preserved order. Use a visitor for those walks; reopen producer methods, nested cursors or delegation if a consumer needs pull/lazy/early-abandonment behavior. |

Other compound companion probes isolate important spelling limits. Parenthesized xor printed 0 for two truthy integers. Constant-label match supports grouped labels with one shared result; condition-form arms retain one condition. The [G08 inventory](remaining_language_gaps_and_native_tokenizer.md#g08--function-and-method-signatures) tracks Named Arguments and the [G05 inventory](remaining_language_gaps_and_native_tokenizer.md#g05--collection-construction-and-access) tracks the proposed missing-key test for mixed and dynamic. General nested indexed writes, asymmetric setters and nullsafe chaining still have separate bounds. A class/interface parameter intersection is already supported; it does not solve heterogeneous scalar results. The compound static-cache specimen failed type resolution at `Node`; this alone does not establish a cache-lifetime blocker. These are scoped observations, not production-wide equivalence proofs.

### Verification

Replayed all 30 runnable donor probes among the original 177 and all 21 non-async executable companion probes in the archived 2026-11-22 ledger. Successful emission alone is not evidence of correct native semantics. Contextual controls supplied native output or specific diagnostics where the donor fragment lacked declarations. The [retained G01 and field controls](../../compiler/tests/bootstrap_donor_verification_native.py) compare the resident compiler with fresh diagnostics/artifacts and run Clang executables, including the checked failure. The six-batch [results and linked fixtures](remaining_language_gaps_and_native_tokenizer.md#verification-results-at-76c8e86ea) remain the evidence for namespace lookup, member selection, enum lookup, and foreach. The [reference fixture](../../compiler/tests/reference_parameters_native.py) distinguishes same-origin returns from invalid element aliases.

### Companion probe replay

The archived snippets are multi-feature inputs. The table records only the first result for each input; a failure does not establish that later expressions were analyzed.

| Probe | Current PHP-flavor result |
| --- | --- |
| TYPE-GENERIC | `main.php:1:1: Expected variable name` |
| TYPE-LAYOUT | `main.php:1:4: Inline fixed-array storage requires byte elements` |
| ANN-NATIVE | `main.php:1:1: Expected variable name` |
| OP-ASSIGN | Emitted C++ |
| OP-SELECT | `main.php:1:60: Expected variable name` |
| FLOW-MATCH | `main.php:1:20: Expected =>` |
| FN-SIGNATURE | `main.php:1:68: Expected )` |
| FN-CLOSURE | `main.php:1:35: Unknown or not-yet-initialized local/global: base` |
| REF-MUT | Emitted C++ |
| TYPE-UNION | `main.php:1:15: Union requires two distinct required classes with a common class base` |
| REC-INIT | `main.php:1:21: Expected identifier` |
| VAR-GLOBAL | `main.php:1:29: Unknown or unsupported type: Node` |
| VAR-CONST | `main.php:1:16: Constant initializer requires a literal` |
| COLL-INDEX | `main.php:1:33: Nested indexed write requires inline fixed-array storage` |
| FLOW-ITER | `main.php:1:38: Expected variable name` |
| FLOW-YIELD | `main.php:1:86: Expected ;` |
| INTR-PRESENCE | Emitted C++ |
| ANN-TYPE | Emitted C++ |
| ERR-TRY | Emitted C++ |
| CTRL-CALLBACK | Emitted C++ |
| CTRL-LITERAL | Emitted C++ |

## Donor ledger

Each original ID occurs once in this table. **Bounded evidence** includes supported behavior and intentional rejection; **migration** records the available target spelling or narrower equivalent; **deferred** preserves an explicit boundary; **decision** needs scope or representation agreement. A migration here does not authorize new language behavior.

| Group | Donor records | Disposition | Evidence and limits |
| --- | --- | --- | --- |
| G01 | `VAR-CHAIN-001` | Migration | The parser stores each new child once and installs the same child as the lexical scope. Preparation resets write literal values to each field in right-to-left order. The native factory control printed M9 after a mutation through the shared handle. Destinations with side effects need their own ordering proof. |
|  | `EXPR-PREINC-001`, `EXPR-POSTINC-001`, `EXPR-PREDEC-001`, `EXPR-POSTDEC-001` | Bounded evidence | Initialized int64 locals in the full assignment RHS or initializer. All four donor sources emit. Existing native update proofs distinguish returned value from the final store. |
|  | `STMT-EXPR-001` | Migration | Keep a standalone call when its result is unused. Assign a checked expression to a local so evaluation and failure remain observable. Omit only proven pure, total expressions. The [native proof](../../compiler/tests/bootstrap_donor_verification_native.py) checks bare arithmetic rejection, one printed C from the call, and division_by_zero from the unused assignment. |
| G02 | `EXPR-ANDWORD-001`, `EXPR-ORWORD-001` | Deferred | Historical low-precedence spellings remain deferred. Preserve grouping and lazy evaluation when using symbolic operators. |
|  | `EXPR-XORWORD-001` | Migration | Bare RHS diagnoses the required parentheses. Grouped integer operands 2 and 3 printed 0; eager evaluation is covered by logical.php. |
|  | `STR-INTERP-001`, `STR-INTERP-002`, `STR-INDEX-001`, `CAST-INT-001`, `CAST-FLOAT-001`, `CAST-BOOL-001`, `CAST-STRING-001`, `CAST-OBJECT-001`, `CAST-ARRAY-001` | Bounded evidence | Use the exact operand pairs, byte semantics and two unsupported-cast diagnostics in the G02 verification table. |
| G03 | `LIT-NULL-001`, `VAR-ISSET-001`, `VAR-EMPTY-001`, `EXPR-COALESCE-001` | Bounded evidence | G03 verifies null-inference rejection, carrier presence, collection emptiness and lazy fallback with observable side effects. |
|  | `EXPR-ELVIS-001` | Deferred | Short ternary and nullsafe access stay deferred; explicit branches preserve evaluation count. |
| G04 | `TYPE-VAR-001`, `TYPE-VAR-005`, `TYPE-VAR-002`, `TYPE-VAR-006B`, `FUNC-DECL-003B`, `TYPE-PARAM-002`, `CLASS-MEMBER-001B`, `TYPE-VAR-003`, `TYPE-VAR-004`, `TYPE-PROP-001B`, `CLASS-PROP-001B` | Migration | Use inferred initialized locals or explicit scalar, nullable, class and vector types; spell method and field types in the declaration. Comments cannot establish a type or override a written type. The explicit nullable-class control printed 1. Historical spelling support and comment-conflict warnings are separate. |
|  | `TYPE-VAR-006`, `TYPE-VAR-006A`, `TYPE-VAR-007` | Deferred | Historical value/optional-value annotations need an ownership choice per port. Use value structs only when copy behavior is intended. |
|  | `CLASS-MEMBER-007`, `CLASS-INH-011`, `CLASS-MEMBER-010` | Bounded evidence | Override markers use the inherited method contract. Applying the marker to a static redeclaration rejects at the member. |
| G05 | `ARR-INIT-001`, `ARR-INIT-003`, `ARR-INIT-004`, `ARR-READ-001`, `ARR-READ-002`, `ARR-WRITE-001`, `ARR-APPEND-001`, `ARR-EXIST-001`, `ARR-UNSET-001`, `ARR-KEY-SEM-001` | Migration | Explicit vector/hash destinations, typed access and append, hash_contains and remove. One declared key type per hash. The G05 native fixture checks presence independently of zero and distinguishes string keys. |
|  | `ARR-INIT-002`, `ARR-INIT-002A`, `ARR-TYPE-001` | Migration | Give an empty collection an explicit element type. A null initializer needs an appropriate nullable carrier. Use an exact typed writable collection parameter for caller mutation. |
|  | `ARR-EMPTY-001` | Migration | For a typed integer hash and a stable key, use <code>!hash_contains($counts,$key) &#124;&#124; $counts[$key]===0</code>. The [native proof](../../compiler/tests/bootstrap_donor_verification_native.py) returns true for absence and zero and false for a negative count. The absent key stays absent and the hash retains both entries. Scalar operands remain outside the v0.2 `empty` contract. |
|  | `ARR-INIT-005`, `ARR-INIT-006` | Bounded evidence | Heterogeneous bracket-keyed input constructs mixed. A positional child inherits the enclosing family. The [native proof](../../compiler/tests/boxed_literals_native.py) reads back the nested data/id example and checks family separation. |
|  | `ARR-VALUE-001` | Deferred | To store an existing scalar vector in mixed or dynamic, cast it explicitly to the child family. Track the proposed [structured conversion capability](#structured-conversion-design-debt) before adding automatic aggregate conversions. |
| G06 | `CTRL-FOREACH-001`, `CTRL-FOREACH-002` | Bounded evidence | Native vector and hash fixtures assert values and key/value pairing. |
|  | `CTRL-FOREACH-003`, `CTRL-FOREACH-004` | Bounded evidence | PHP supports scoped write-through iteration over owned vectors and hashes and through mutable reference parameters. The [collections and borrowing backlog](#collections-and-borrowing-backlog) tracks sources and lifetimes beyond this boundary. |
| G07 | `FUNC-REF-003`, `CLASS-MEMBER-018C`, `TYPE-PARAM-003`, `TYPE-PARAM-003B`, `CLASS-MEMBER-018D` | Bounded evidence | Writable named slots and same-origin returns are bounded. Functions and final non-overriding methods use exact types; reference_parameters_native.py covers storage identity and recovery. |
|  | `TYPE-VAR-008` | Migration | An explicit lexical alias to initialized local storage uses the reference binder. Unrelated targets and scope escape reject. |
|  | `FUNC-REF-002`, `FUNC-REF-004`, `CLASS-MEMBER-018B`, `CLASS-MEMBER-018E` | Bounded evidence | A reference-return declaration alone supplies no origin. Require explicit types and a writable parameter of the return type; arbitrary local, temporary and field returns do not qualify. Interface forwarding remains deferred. |
|  | `REF-RETURN-001` | Migration | Forwarding the same whole vector through two reference-return functions preserved the caller's storage (native 9). The historical nested-element callee falls under the next row. |
|  | `REF-RETURN-002`, `REF-BIND-001` | Deferred | No durable alias or reference return into a relocatable element. Use bounded reads and explicit write-back; general element borrows need lifetime rules. |
|  | `TYPE-VAR-003A`, `TYPE-VAR-003B`, `TYPE-VAR-003C`, `TYPE-VAR-007A` | Deferred | Defer historical ownership annotation syntax. Existing shared and weak representations do not establish unique construction or implicit conversions. |
|  | `VAR-UNSET-001` | Bounded evidence | PHP-flavor whole-variable reset preserves the slot and its aliases. The [native proof](../../compiler/tests/unset_native.py) covers nullable values, optional owners, weak observers, and incremental recovery. Callable storage is deferred. |
|  | `VAR-CLEAN-001` | Bounded evidence | The [PHP-flavor native test](../../compiler/tests/clean_native.py) covers scalar defaults, empty typed collections, and same-family null for mixed and dynamic. Native aliases observe reset storage and copied dynamic bindings retain the previous object. Callable storage is deferred. |
|  | `TYPE-PARAM-003F` | Bounded evidence | A PHP-flavor synchronous free function can bind an exact-type vector or hash local or parameter as a read-only reference. The [native proof](../../compiler/tests/readonly_parameters_native.py) checks forwarding, alias-visible replacement, independent value copies, rejection of writes, and signature-edit recovery. |
| G08 | `FUNC-DECL-001`, `TYPE-PARAM-003A`, `TYPE-PARAM-003C`, `TYPE-PARAM-003E`, `WARN-TYPE-PARAM-001` | Migration | Use explicit result and collection types. Mutation or whole replacement of a by-value collection stays local. Historical warning is guidance. See G08's individual proofs. |
|  | `FUNC-CALL-004`, `CLASS-MEMBER-017` | Deferred | Variadic declarations deferred; spread call sites require their own ordered translation. |
|  | `FUNC-OVERLOAD-001`, `FUNC-DECL-005`, `FUNC-ARG-001`, `CLASS-MEMBER-015`, `TYPE-PARAM-004`, `TYPE-PARAM-003G` | Bounded evidence | Existing G08 proofs cover overload and untyped rejection, indexed value arguments, optional parameter identity/interface dispatch, and exact override modes within the supported boundary. |
| G09 | `CLOSURE-USE-001`, `CLOSURE-USE-002`, `CLOSURE-CALLABLE-001`, `CLOSURE-CALLABLE-002`, `CLOSURE-RETURN-001`, `ARROW-001`, `SCOPE-VAR-003` | Deferred | Historical captures, implicit callable signatures and separately declared callable storage remain deferred. The populated-vector return donor also captures; capture-free scalar proofs do not cover it. |
|  | `CLOSURE-001`, `CLOSURE-REJECT-002`, `SCOPE-VAR-002` | Bounded evidence | Native block closure, append-placement and lexical invocation proofs. |
|  | `CLOSURE-STAN-001` | Migration | Exact callback result mismatch proved at a typed parameter; historical callable-local spelling deferred. |
|  | `CLOSURE-REJECT-001`, `CLOSURE-CALL-001` | Bounded evidence | Only the explicit result annotation and exact-arity parts are verified. Donor capture and default arguments remain deferred. |
|  | `CLASS-CALL-010` | Migration | Array spelling gets a collection diagnostic. Static callable syntax recovers and prints 8. Diagnostic refinement remains optional. |
| G10 | `VAR-ORDER-001`, `SCOPE-SHADOW-001`, `NS-EXEC-006`, `NS-EXEC-005`, `NS-DECL-002`, `NS-USE-001`, `NS-USE-002`, `NS-USE-004`, `NS-USE-005`, `NS-QUALIFIED-001`, `NS-QUALIFIED-002`, `NS-QUALIFIED-003`, `NS-EXEC-002`, `NS-EXEC-004`, `NS-MULTI-BLOCK-001`, `RESOLVE-001`, `CLASS-NS-001`, `CLASS-NS-002`, `CLASS-NS-003` | Bounded evidence | Recorded imports/namespace and six-batch proofs, including intentional rejection of nested/additional namespace bodies. Supply the required entry result and typed declarations to isolate lookup. |
|  | `SCOPE-GLOBAL-001`, `NS-SYMBOL-PATH-001`, `NS-VAR-001`, `NS-EXEC-001`, `NS-EXEC-003`, `NS-USE-003`, `CLASS-NS-004` | Migration | Use explicit global storage when needed across functions. Namespaced typed constant/function and entry-local controls each printed 1. Supply explicit entry return; type import and current-relative construction follow v0.2 lookup. |
|  | `INC-REQUIRE-001`, `INC-INCLUDE-001`, `INC-ONCE-001` | Deferred | Use module membership for declarations. Effects at the inclusion site require an explicit ordered call; general include execution deferred. |
| G11 | `CLASS-STATIC-NS-003`, `CLASS-STATIC-NS-002`, `CLASS-STATIC-NS-001`, `CLASS-PROP-006B`, `CLASS-PROP-006C`, `CLASS-PROP-007`, `CLASS-PROP-009`, `CLASS-PROP-010`, `CLASS-CALL-003`, `CLASS-CALL-005`, `CLASS-CALL-005A`, `CLASS-CALL-008A`, `CLASS-CALL-009`, `CLASS-CALL-012` | Bounded evidence | Six-batch qualifier/restriction checks and called_methods_native.py cover fixed and late dispatch, called-type construction and negative access paths. |
|  | `CLASS-CONST-001` | Migration | Use an explicitly typed class constant. |
|  | `CLASS-CONST-002`, `CLASS-CONST-003`, `CLASS-CONST-004`, `CLASS-CONST-005`, `CLASS-MEMBER-001`, `CLASS-MEMBER-009`, `CLASS-PROP-002B`, `CLASS-PROP-002C`, `CLASS-PROP-002D`, `CLASS-THIS-001`, `CLASS-NEW-002`, `TYPE-PROP-001`, `CLASS-INH-010`, `CLASS-ABS-008` | Bounded evidence | Typed members, constant and field qualifiers, visibility and base construction have bounded proofs. An abstract-base field read through a child printed 7. |
|  | `TYPE-PROP-002` | Bounded evidence | Nullable class and string instance fields both support absence. The [native string field proof](../../compiler/tests/nullable_string_fields_native.py) distinguishes null, empty and nonempty bytes through construction, writes and reset. After a field-type edit breaks a caller, repairing the declaration restores fresh-build output in the same compiler instance. |
|  | `CLASS-INH-003`, `CLASS-INH-007` | Migration | Use admitted source traits (storage, behavior and hooks have targeted fixtures). PHP-flavor C++-style declaration rejects; use new for a class. Trait conflict/adaptation is a separate contract. |
| G12 | `ENUM-001` | Migration | Use explicit, unique integer case values for internal compiler strategies such as `constant_dispatch`. Keep consumers on named cases. The numbered enum with a method passed natively (79). Revisit if a consumer observes or publishes the backing value. |
|  | `ENUM-003` | Migration | Typed nullable lookup and single evaluation verified. Historical nullsafe chain deferred. |
|  | `ENUM-004` | Deferred | Revisit enum interface implementation when a production caller needs to accept an enum through an interface type. |
| G13 | `ERR-THROW-001`, `CLASS-ABS-003`, `CLASS-ABS-004`, `CLASS-CALL-011` | Bounded evidence | Exception construction/catch/rethrow and cleanup proofs; reject destructor value returns, static destructors and explicit invocation. |
| G14 | `LIT-CONST-001` | Migration | Accepted migration: literal 9223372036854775807. No source PHP_INT_MAX is required for bootstrap. |
