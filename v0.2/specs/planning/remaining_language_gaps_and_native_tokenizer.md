Doc Status: planning

# Remaining language work and native tokenizer integration

The short list groups the 23 Missing and 154 Partial records from the donor audit. Use G01–G18 for implementation discussions. Keep the native tokenizer as a parallel integration track and finish the review with the deferred-commit decisions below. The external audit directory `2026-11-22-language-6ff5ff42c` contains `donor-record-audit.md` and `donor-record-audit.json`.

## Evidence and short list

The audit at `6ff5ff42c` covers 330 donor records. Of these, 144 are Covered, 154 Partial, 23 Missing, and 9 Out of scope. Its 71 PHP-flavor probes yielded 44 C++ emissions and 27 rejections. The [short list](#short-list-by-implementation-group) preserves the supplied labels and audit statuses. A record may close through an accepted spelling change, an existing supported equivalent, or stronger proof. Use native execution for observable behavior and precise rejection tests for negative rules.

The two postfix rows were Missing in that audit. Development commit `0a41e4cae` covers postfix `++` and `--` on an initialized int64 local as the full assignment RHS or initializer. G01 starts by recording that boundary against the donor probes, then deciding the remaining forms.

## Implementation groups

| Group | Work to handle together | Example audit IDs |
| --- | --- | --- |
| **G01 · Expression results** | Chained assignment, value-producing increment and decrement, and discarded expressions. Define returned values and when writes occur. | `VAR-CHAIN-001`, `EXPR-PREINC-001`, `EXPR-POSTDEC-001`, `STMT-EXPR-001` |
| **G02 · Scalar and string expressions** | Verify casts, word-form operators, interpolation, and byte indexing. Split differences in conversion or operator behavior into their own changes. | `CAST-*`, `EXPR-ANDWORD-001`, `STR-INTERP-*`, `STR-INDEX-001` |
| **G03 · Absence and conditional access** | Null inference, `isset`, `empty`, `??`, `?:`, and optional access. Settle operand admission and lazy evaluation. | `LIT-NULL-001`, `VAR-ISSET-001`, `EXPR-COALESCE-001`, `EXPR-ELVIS-001` |
| **G04 · Historical annotations** | Decide which comment types and attributes retain their spelling, which migrate to explicit syntax, and which conflicts warrant a diagnostic. | `TYPE-VAR-001`, `TYPE-VAR-005`, `FUNC-DECL-003B`, `CLASS-MEMBER-007` |
| **G05 · Collection construction and access** | Compare typed vector and hash semantics with historical array literals, nested values, indexing, append, removal, and key identity. | `ARR-INIT-*`, `ARR-READ-*`, `ARR-WRITE-001`, `ARR-KEY-SEM-001` |
| **G06 · Collection traversal** | Verify value and keyed foreach. Give writable iteration its own slice covering aliasing and mutation during traversal. | `CTRL-FOREACH-001` through `004` |
| **G07 · References and lifetime** | Define valid referents and lifetimes for local aliases, reference parameters and returns, references into elements, ownership forms, and cleanup. | `FUNC-REF-*`, `REF-RETURN-*`, `REF-BIND-001`, `TYPE-VAR-008` |
| **G08 · Function and method signatures** | Apply shared rules for omitted types, variadics, defaults, overload admission, and override parameter modes. | `FUNC-DECL-001`, `FUNC-CALL-004`, `CLASS-MEMBER-017`, `TYPE-PARAM-003G` |
| **G09 · Closures and callable values** | Build on callbacks and capture-free lambdas to settle block bodies, callable typing, capture modes, escape rules, and invocation. | `CLOSURE-*`, `ARROW-001`, `SCOPE-VAR-002`, `SCOPE-VAR-003` |
| **G10 · Scope, namespaces and imports** | Verify current local/global binding, qualified lookup, imports, namespace execution, and equivalents for historical includes. | `VAR-ORDER-001`, `SCOPE-GLOBAL-001`, `NS-*`, `RESOLVE-001`, `INC-*` |
| **G11 · Records and member access** | Fields, construction, visibility, inheritance, and overrides. Give static qualification and late-static binding a dedicated slice. | `CLASS-PROP-*`, `CLASS-CALL-*`, `CLASS-CONST-*`, `CLASS-INH-*`, `TYPE-PROP-*` |
| **G12 · Enums** | Unbacked spelling, interfaces, conversion from backing values, and case access. | `ENUM-001`, `ENUM-003`, `ENUM-004` |
| **G13 · Exceptions and cleanup** | Verify historical exception construction, destructor restrictions, and cleanup on early exit against existing throw, catch, and finally support. | `ERR-THROW-001`, `CLASS-ABS-003`, `CLASS-ABS-004` |
| **G14 · Compile-time types and layout** | Constant expressions, source generics, fixed-array limits, restricted layout declarations, and type/layout queries. Give generics and layout separate steps. | `LIT-CONST-001`, `VAR-CONST`, `TYPE-GENERIC`, `TYPE-LAYOUT` |
| **G15 · Native interoperability** | Compare source-level external declarations, native binding declarations, and inline native syntax. | `ANN-NATIVE` |
| **G16 · Configurable language policy** | Decide which checks and defaults belong to project configuration and which validity rules always apply in the single v0.2 language model. | `POLICY-CONFIG`, `POLICY-DIAG` |
| **G17 · Generators** | Verify existing yield support, then evaluate member producers, delegation and cleanup across suspension. | `FLOW-YIELD` |
| **G18 · Async/await** | Design async declarations, result typing, valid await contexts, suspension, exception propagation, and cleanup. Require an end-to-end proof. | `ASYNC-DECL`, `ASYNC-AWAIT`, `ASYNC-LIFE`, `ASYNC-VALUE` |

The example column includes companion-audit topics. The short list tracks only the supplied 177 donor rows. A group can therefore have work without a row in that list.

## Implementation order

| Order | Groups | Reason |
| --- | --- | --- |
| 1 | **G01 · Expression results** | Start with a bounded slice. Review all four update forms under one value-and-write contract, building on the postfix RHS already integrated. |
| 2 | **G02–G03 · Scalar expressions and absence** | Verify current operators, casts, interpolation, and fallback behavior before implementing confirmed gaps. |
| 3 | **G04, G10–G13 · Spelling and declarations** | Decide which historical spellings carry forward. Then verify namespaces, members, enums, and exceptions. Several Partial rows may close with existing evidence or a supported spelling. |
| 4 | **G05, G07–G08 · Collections, references, and signatures** | Establish storage, mutation, and call contracts. Start G07 early if a nested collection write needs a decision about which storage can be referenced. |
| 5 | **G06, G09 · Writable traversal and closures** | Build writable foreach and reference captures on G07. Verify value iteration and capture-free cases earlier when useful. |
| 6 | **G17 and G18 · Generators and async/await** | Give each its own implementation group. Async design can start early if prioritized. Implementation can proceed once callable, lifetime, and cleanup contracts are settled, independently of generator delegation. |

Review G14–G16 on a separate design track. Schedule an implementation slice when a concrete consumer or policy decision supplies its scope. The tokenizer can proceed in parallel with language work.

## First discussion · G01

Use one initialized local to compare the intended results and stores. These examples describe cases to review, with nested uses still requiring a separate admission decision.

| Form | Example from an initial value of 10 | Result and write to decide |
| --- | --- | --- |
| Postfix | `$old = $index++;` | Existing bounded form. The result is 10, then the local holds 11. |
| Prefix | `$new = ++$index;` | Candidate to review. The result would be the updated value, 11. |
| Aliasing the destination | `$index = $index++;` | Existing bounded form. The update runs once, then the outer assignment writes 10 back. |
| Chained assignment | `$a = $b = 1;` | Decide the expression result, write order, and where conversions and failures occur. |
| Discarded expression | `$a + 1;` | Decide which expressions are admissible as statements and which checks or effects must run. |

Proposed first discussion: choose whether the prefix slice should mirror the current postfix boundary for initialized int64 locals. Settle the result and mutation contract before taking on chained assignments or broader expression statements.

## G02 verification

Checked against `0faddb982`. The PHP-flavor donor spellings below were prepared and emitted on the current compiler. Accepted examples were compiled with Clang and run. All eight existing PHP and native suites for casts, text casts, interpolation, and string indexing passed. These results describe current coverage. The short-list appendix retains the original audit statuses.

| Donor record | Current result |
| --- | --- |
| `CAST-INT-001` | Covered for the verified pair. `$a = (int)$b;` with float `8.75` yielded integer `8`. |
| `CAST-FLOAT-001` | Covered for the verified pair. `$a = (float)$b;` with integer `8` yielded a printable float value of `8`. |
| `CAST-BOOL-001` | Covered for the verified pair. `$a = (bool)$b;` with integer `2` yielded true. |
| `CAST-STRING-001` | Covered for the verified pair. `$a = (string)$b;` with bool true yielded the bytes `true`. |
| `CAST-OBJECT-001` | `$a = (object)$b;` with an initialized integer was rejected with `Unsupported PHP (object) cast`. The diagnostic identifies the historical cast spelling. |
| `CAST-ARRAY-001` | `$a = (array)$b;` with an initialized integer was rejected with `Unsupported PHP (array) cast`. The diagnostic identifies the historical cast spelling. |
| `STR-INTERP-001` | Covered. With `$name = "é"`, `$a = "hello $name";` produced `hello é` with the original UTF-8 bytes. |
| `STR-INTERP-002` | Braced interpolation is covered. `$a = "sum {$a}";` worked with a string local. With integer `$a` the store rejected string-to-int64 conversion. Assigning the interpolation to a new local produced `sum 42`. Preserve the existing destination type rule. |
| `STR-INDEX-001` | Covered as byte indexing. With `$s = "é"`, `$a = $s[0];` yielded byte `195`. Index `2` is the exclusive byte end and raised `sequence_index_out_of_range`. |

The native cast matrix remains the reference for admitted source and target pairs. Word-form `and` and `or` stay deferred. The existing grouped `xor` contract was outside this verification.

## G03 verification

Verified at `e00c30ea7`. All eight focused CTest suites passed, covering inference, conditions, coalescing, and coalescing assignment in their PHP and native runners. A separate PHP-flavor donor probe emitted C++, compiled with Clang, and checked values and observable fallback effects.

| Donor record | Current result |
| --- | --- |
| `LIT-NULL-001` | Bare `$a = null;` rejects with `Cannot infer a local type from null`. An explicitly typed nullable local can hold absence. |
| `VAR-ISSET-001` | Covered for admitted carriers. The probe returned true for present zero, false, and empty string payloads, and false for absence. Plain `int` rejects with `This isset slice requires a carrier`. |
| `VAR-EMPTY-001` | Covered for published vectors and hashes. Fresh empty collections returned true. Inserting a zero value made each return false. A scalar rejects with `empty requires a published vector or hash`. |
| `EXPR-COALESCE-001` | Covered for the tested nullable carriers and typed fallback. Present zero, false, and empty string skipped the fallback and retained their payloads. Absent `nullable<int>` ran the fallback exactly once and selected `42`. Plain `int` on the left rejects with `Coalescing requires a published nullable carrier`. |
| `EXPR-ELVIS-001` and nullsafe access | Deferred. |

The native trace was `present:1011;empty:1100;zero:0;Fabsent:42;false:0;text:[]`. The only fallback marker, `F`, occurred for the absent integer. The existing native coalescing suite also exercises single evaluation of the left operand, failure ordering, and incremental recovery.

## G04 migration decisions

Checked PHP-flavor preparation and C++ emission at `e00c30ea7`. These decisions cover the verified forms. The short list retains the original donor statuses.

| Donor records or case | Migration decision and observed behavior |
| --- | --- |
| `TYPE-VAR-001` and `TYPE-VAR-005` | Use ordinary inference when the initializer provides the intended type. Both placements of `/** string */` compiled with a string initializer. Use `$x string = "test";` when the type should be explicit. |
| `TYPE-VAR-002` | Use `$x ?string = null;`. A nullable comment with a null initializer still rejects because null cannot infer a local type. |
| `TYPE-VAR-006B` | Migrate to a known explicit type. The unknown comment generic left the null-inference error in place. Explicit `weird<Box>` received a diagnostic that the type does not accept arguments. |
| `FUNC-DECL-003B` | Write an explicit parameter type, such as `function f(vector<int> $items): void {}`. The comment-only parameter rejected. Apply the same migration pattern to method parameters and verify their specific donor cases when needed. |
| `TYPE-PROP-001B` and `CLASS-PROP-001B` | Write the field type explicitly. A comment-only field rejected. A deliberately conflicting comment with a written `int` field still emitted. Treat comment conflict reporting as migration tooling work if needed. |
| `CLASS-MEMBER-007` and `CLASS-INH-011` | Keep `#[\Override]`. It and `override function` both emitted for a valid inherited virtual method and use the same override contract. The existing contract rejects this marker on static redeclarations. |

Ownership annotations are deferred. Review the actual ownership and lifetime contract before migrating historical shared, weak, unique, and value annotations. [Override contract](../../../specs/inheritance_v0_2.md)

## G05 verification

The PHP, C++, and JS native donor fixture covers explicit empty and populated vectors, keyed hashes, indexed reads and vector replacement, append, presence, and removal. String keys `"1"` and `"01"` stay distinct, a zero value is present, removing the same key twice reports success then absence, and a hash rejects keys of the wrong declared type. The fixture also checks a cross-file literal edit, invalid edits, deletion and recovery against fresh output, and zero preparation on a no-op. [Native collection fixture](../../compiler/tests/application_collection_methods_native.py)

The typed migration spells presence `hash_contains(collection, key)` and removal `remove(key)`. The existing nested collection fixture passed across PHP, C++, and JS. It proves copied reads, explicit write-back through three layers, and incremental recovery matching fresh output. Untyped empty, null, heterogeneous and nested array specimens rejected in PHP-flavor probes. Explicit mixed and dynamic destinations also rejected the historical bracket-keyed heterogeneous literal. Keep boxed literal support separate from typed nested value transport. [Nested fixture](../../compiler/tests/nested_collections_native.py)

## G10 verification

The multi-file imports fixture now checks a grouped function import and alias, a constant import, a rooted call, and a relative call into a child namespace. A separate source declares an explicit global initialized to 20. A same-named local inside a function reads as 5, and the subsequent global read still yields 20. Clang-built programs in C++, PHP, and JS produced the expected seven lines `ping`, `pong`, `ping`, `20`, `5`, `20`, and `7`. The namespace, imports, globals, and frontend CTest suites passed. [Import fixture](../../compiler/tests/imports.php)

Nested namespace bodies intentionally reject with `Nested/additional namespaces`. A second body in the same file also rejects. The PHP semicolon case uses that same diagnostic, and the C++ and JS braced cases report `Only one namespace body per file is supported`. Separate files may share a namespace.

Include, require, and require-once execution semantics remain deferred. Module membership is the migration path for declaration-only includes. Revisit execution order if an included body has an observable effect at the call site.

## G11 verification

The native construction fixture uses separate source files for the base class, child class, and entry point. The child forwards 9 to the base constructor, which writes a protected field whose declaration default is 3. A public child method reads the inherited field and prints 9 in PHP, C++, and JS. Entry-point access to that same field rejects with `Cannot access protected field`. Repairing the entry point restores the same result in the same compiler instance, with generated artifacts matching a fresh build. The existing protected-instance suite also checks inherited methods and rejects external protected method calls. [Protected instance proof](../../compiler/tests/protected_methods_native.py)

The historical `declared_qualifier` case in the [my-try late-static suite](../../../compiler/my-try/tests/late_static.php) now has a native proof in PHP, C++, and JS. The same derived class gives different results through the two qualifiers. A base-typed local holding a child selects the base called type and prints 2. Explicit child qualification of the inherited method prints 9 through its late-static call. An unchanged rebuild prepares zero units. [Expression-qualification proof](../../compiler/tests/expression_static_native.py)

## G13 verification

`ERR-THROW-001` is verified with `new Exception(message)` in commit `55155d37f`. It uses the same core factory as `Exception::make(message)`. The native tests cover one evaluation, message bytes through rethrow, arity and type diagnostics, and construction of a source class that shadows the core type. The existing typed diagnostic proof checks identity and original source location. The native exception and finally suites and the frontend suite passed. [Exception contract](../../../specs/exceptions_v0_2.md) · [Native exception proof](../../compiler/tests/exceptions_native.py)

The PHP-flavor destructor probes emitted C++ for an instance destructor reading its field. `CLASS-ABS-003` rejected a value return with `Void function cannot return a value`, and `CLASS-ABS-004` rejected an unused static destructor with `Expected ordinary instance destructor`. Explicit destructor invocation is separately tracked by `CLASS-CALL-011`. [Destructor contract](../../../specs/destructors_v0_2.md)

## Native tokenizer slice

The selected native design is a direct byte scanner. Use the existing PHP bootstrap tokenizer as the oracle for the PHP, C++, and JS parity corpus. The audit branch contains borrowed classification `d3b03bcbf`, a direct-scanner comparison `a2e3c382c`, and archived real-source results plus the design decision `b6189c140`. The comparison harness uses the deferred bulk PCRE2 API. Reuse the relevant cases and the measured baseline when designing the production integration.

Proposed ownership is the immutable source snapshot and its token list. Validate the full snapshot for UTF-8, recognize token spans using a byte cursor, and publish the finished token buffer after a successful scan. Tokens carry byte offset, length, and kind. Spelling comes from the retained source. Keep Python indentation and layout behavior in view. The earlier comparison covered only PHP, C++, and JS. Review the Python layout stage and add its own parity coverage before claiming native support for that flavor.

Acceptance checks:

1. Compare offset, length, kind, and spelling with the oracle on real inputs, including longest punctuation, flavor keywords, comments, quotes, and token-length limits.
2. Match diagnostic class and byte offset for truncated sequences, invalid UTF-8, numeric boundaries, and incomplete tokens. Invalid UTF-8 anywhere in the snapshot keeps its precedence over a lexical error. Reuse the malformed corpus from `d3052abc5`.

   ```text
   Input bytes: 41 C3 A9 80
   Expected: invalid UTF-8 starts at byte 3
   ```

3. Retain a token list across an edit at the same path. Check old spelling and source locations against the old snapshot. A failed scan must leave the published token list intact, and repair must publish a fresh-equivalent list.
4. Measure reading, tokenization, and parsing separately. The archived comparison used resident bytes. Its PCRE2 provider compiled and JITed per scan, so those timings describe the tested providers. Report any differences in scope or configuration in the production benchmark.

Before implementing, identify the scanner entry point, the source owner, and the publication boundary. If the native compiler path lacks one of these owners and adding it would require a wide refactor, document the blocker and review that scope first. [Compiler bootstrap plan](v03_bootstrap_implementation_plan.md) · [Existing tokenizer debt](native_tokenizer_debt.md) · [Python flavor plan](python_flavor.md)

## Final discussion on deferred work

| Area | Preserved commits | Decision to revisit |
| --- | --- | --- |
| Host byte validation | `519951c08` | Choose a byte-set operation when a translated caller needs it. Preserve byte behavior for NUL and high bytes. |
| Numbered loop exits | `4524ddead` | Decide whether parsed depth needs storage when translation or measurements justify it. Retain the leading-zero, overflow, and diagnostic cases as evidence. |
| Bulk PCRE2 scans | `a577511e0` | Look for a consumer of contiguous coverage and output preservation on failure. The direct tokenizer has its own path. |
| Tokenizer proof and benchmarks | `d3b03bcbf`, `a2e3c382c`, `b6189c140`, `d3052abc5` | Reuse the classifier, corpus, and recorded measurements in the production integration. The first three remain on `codex/bootstrap-audit-02`. The malformed UTF-8 proof remains on `codex/bootstrap-audit-01`. |
| Checked mutation-read forwarding | `8f23c8c1d` | Exercise the actual production hook during translation and verify one dispatch and the missing-key contract. |
| Bootstrap translation proofs | `85bd679e7`, `4097767b5`, `981ca1372` | Pair with the real ports of owner allocation emission, previous-source lookup, and constructor preparation. Check byte-exact output, nonmutating misses, and original record identity, respectively. |

## Short list by implementation group

The supplied labels appear once below. The status headings retain the original ledger classification. The donor ledger prefixes these IDs with `DONOR-`.

### G01 · Expression results

**Missing (6)**

- `VAR-CHAIN-001` — Chained assignment
- `EXPR-PREINC-001` — Prefix increment value
- `EXPR-POSTINC-001` — Postfix increment value
- `EXPR-PREDEC-001` — Prefix decrement value
- `EXPR-POSTDEC-001` — Postfix decrement value
- `STMT-EXPR-001` — Discarded arithmetic expression

### G02 · Scalar and string expressions

**Partial (12)**

- `EXPR-ANDWORD-001` — Word-form and
- `EXPR-ORWORD-001` — Word-form or
- `EXPR-XORWORD-001` — Word-form xor
- `STR-INTERP-001` — Bare variable interpolation
- `STR-INTERP-002` — Braced interpolation
- `STR-INDEX-001` — String byte indexing
- `CAST-INT-001` — Integer cast
- `CAST-FLOAT-001` — Float cast
- `CAST-BOOL-001` — Boolean cast
- `CAST-STRING-001` — String cast
- `CAST-OBJECT-001` — Object cast rejection
- `CAST-ARRAY-001` — Array cast rejection

### G03 · Absence and conditional access

**Partial (5)**

- `LIT-NULL-001` — Bare null inference
- `VAR-ISSET-001` — Variable isset
- `VAR-EMPTY-001` — Variable empty
- `EXPR-COALESCE-001` — Null coalescing
- `EXPR-ELVIS-001` — Short ternary ?:

### G04 · Historical annotations

**Partial (17)**

- `TYPE-VAR-001` — Comment-typed string local
- `TYPE-VAR-005` — Misplaced type annotation
- `TYPE-VAR-002` — Comment-typed nullable string
- `TYPE-VAR-006B` — Unknown generic annotation
- `FUNC-DECL-003B` — Comment-typed vector parameter
- `TYPE-PARAM-002` — Historical vector_t parameter
- `TYPE-VAR-006` — Value object annotation
- `TYPE-VAR-006A` — Optional value object annotation
- `TYPE-VAR-007` — Inferred value object annotation
- `CLASS-PROP-001B` — Conflicting property annotation
- `CLASS-MEMBER-001B` — Comment-typed method parameter
- `CLASS-MEMBER-007` — Override attribute
- `CLASS-MEMBER-010` — Static override attribute rejection
- `TYPE-VAR-003` — Comment-typed class local
- `TYPE-VAR-004` — Comment-typed optional class local
- `TYPE-PROP-001B` — Comment-typed property
- `CLASS-INH-011` — Inherited Override attribute

### G05 · Collection construction and access

**Partial (7)**

- `ARR-INIT-002` — Untyped empty array
- `ARR-INIT-002A` — Null array initializer
- `ARR-INIT-005` — Heterogeneous array literal
- `ARR-INIT-006` — Nested array literal
- `ARR-EMPTY-001` — Indexed empty
- `ARR-TYPE-001` — Mutable array parameter
- `ARR-VALUE-001` — Vector inside dynamic array

See [G05 verification](#g05-verification) for the typed migration paths.

### G06 · Collection traversal

**Missing (2)**

- `CTRL-FOREACH-003` — Reference foreach
- `CTRL-FOREACH-004` — Keyed reference foreach

**Partial (2)**

- `CTRL-FOREACH-001` — Value foreach
- `CTRL-FOREACH-002` — Keyed foreach

### G07 · References and lifetime

**Bootstrap decision.** Interface reference returns are donor-proven and deferred.
The compiler inventory found no production caller binding an interface method result
with `=&`. Free functions and final non-overriding instance methods already cover
whole-parameter reference returns in the bootstrap.

If donor parity becomes a priority, port the two cases in
[the donor interface-handle fixture](../../../compiler/my-try/tests/interface_handles.php).
The exact-signature case writes through an interface call and observes the caller’s
original slot. Its incremental companion introduces a cycle by editing only the
implementation, retries without an edit, and repairs it in the same compiler.
Take abstract-base forwarding and variance as separate follow-ups.

**Missing (5)**

- `FUNC-REF-002` — Function reference return
- `FUNC-REF-003` — Return parameter by reference
- `TYPE-VAR-008` — Local reference alias
- `CLASS-MEMBER-018B` — Method reference return
- `CLASS-MEMBER-018C` — Method returns reference parameter

**Partial (15)**

- `FUNC-REF-004` — Untyped reference return rejection
- `VAR-UNSET-001` — Local unset
- `VAR-CLEAN-001` — Explicit clean
- `REF-RETURN-001` — Forwarded array reference return
- `REF-RETURN-002` — Nested array reference return
- `REF-BIND-001` — Alias to array element
- `TYPE-VAR-003A` — Weak ownership annotation
- `TYPE-VAR-003B` — Shared ownership annotation
- `TYPE-VAR-003C` — Unique ownership annotation
- `TYPE-PARAM-003` — Typed reference parameters
- `TYPE-PARAM-003B` — By-reference array parameter
- `TYPE-PARAM-003F` — Const vector reference
- `TYPE-VAR-007A` — Unique construction annotation
- `CLASS-MEMBER-018D` — Explicit method reference syntax
- `CLASS-MEMBER-018E` — Untyped field reference return

### G08 · Function and method signatures

**Omitted return types (`FUNC-DECL-001`).** Use an explicit result type for named
functions. The v0.1 donor defaulted an omitted type to `void`; my-try adopted
[explicit returns](../../../compiler/my-try/docs/catalog/04_functions.md#func-decl-001).
Bootstrap probes rejected both an empty body and a value-returning body at the
missing colon. Writing `: void` and `: int`, respectively, emitted C++.
The [parser regression](../../compiler/tests/functions.php) covers the empty body.
A token scan of compiler implementation PHP, excluding constructors and destructors,
found one omission in the bootstrap and none in my-try. The bootstrap occurrence
is a private [`__clone` guard](../../compiler/05_backend/cpp/bundle.php) for a
single-owner manifest stream. Its port needs to preserve copy prevention.
Classify the historical spelling as an intentional rejection for bootstrap.

**Variadic declarations (`FUNC-CALL-004`, `CLASS-MEMBER-017`).** Defer for
bootstrap. The scan of production PHP found no variadic declarations in the
current compiler or my-try. The four historical PHP generator methods iterate
over line or diagnostic collections. Current uses such as
`array_push($errors, ...$group->diagnostics)` unpack existing collections at
the call site and can use an ordered append loop during translation.
For donor parity, use the [typed trailing pack](../../../compiler/my-try/docs/catalog/04_functions.md#func-call-004)
for functions and methods. Evaluate arguments left to right, convert each
element, and pass an owned `vector<T>`. Call-site unpacking is separate work.

**Override parameter modes (`TYPE-PARAM-003G`).** The bootstrap supports value
and writable-reference source parameters. Method contract preparation compares
the mode at each parameter position for overrides and interface implementations.
The [PHP native proof](../../compiler/tests/reference_parameters_native.py)
exercises both matching modes through an interface and both mismatch directions
across edits. The donor row remains Partial because its interface declares
`const string &$s`, a third source mode. The exact donor and a control with
matching read-only declarations both stop at `const` during parsing
(`Expected identifier`). Replacing the contract with `string &$s` and
using an explicit override reaches the signature-mismatch diagnostic. Matching
by-value strings emit successfully. The [donor regression](../../../compiler/my-try/tests/interfaces.php)
checks incompatibility during preparation. Revisit source read-only borrows
when a bootstrap caller needs them or donor parity takes priority.

**Missing (3)**

- `FUNC-DECL-001` — Omitted return type
- `FUNC-CALL-004` — Variadic call
- `CLASS-MEMBER-017` — Variadic method

**Partial (10)**

- `FUNC-OVERLOAD-001` — Function overload rejection
- `FUNC-DECL-005` — Untyped parameter rejection
- `FUNC-ARG-001` — Indexed argument
- `TYPE-PARAM-003A` — Plain array parameter
- `TYPE-PARAM-003C` — Append to array parameter
- `TYPE-PARAM-003E` — Forward array parameter
- `WARN-TYPE-PARAM-001` — Array reassignment warning
- `CLASS-MEMBER-015` — Method overload rejection
- `TYPE-PARAM-004` — Class, optional, interface parameters
- `TYPE-PARAM-003G` — Override reference-mode mismatch

### G09 · Closures and callable values

**Missing (7)**

- `CLOSURE-USE-001` — Capture by value
- `CLOSURE-USE-002` — Capture by reference
- `CLOSURE-CALLABLE-001` — Context-typed closure
- `CLOSURE-CALLABLE-002` — Inferred capturing closure
- `CLOSURE-RETURN-001` — Closure returns populated vector
- `CLOSURE-001` — Block-bodied closure
- `ARROW-001` — Capturing arrow function

**Partial (7)**

- `CLASS-CALL-010` — Array callable rejection
- `CLOSURE-REJECT-001` — Untyped closure rejection
- `CLOSURE-REJECT-002` — Closure append rejection
- `CLOSURE-STAN-001` — Closure return mismatch
- `CLOSURE-CALL-001` — Closure invocation and defaults
- `SCOPE-VAR-002` — Closure escaping block scope
- `SCOPE-VAR-003` — Annotated closure local

### G10 · Scope, namespaces and imports

**Partial (29)**

- `VAR-ORDER-001` — Read before declaration
- `SCOPE-GLOBAL-001` — Implicit global access
- `SCOPE-SHADOW-001` — Local/global shadowing
- `NS-EXEC-006` — Nested namespace execution rejection
- `NS-EXEC-005` — Nested declaration amid execution
- `NS-DECL-002` — Braced namespace declaration
- `NS-USE-001` — Function import
- `NS-USE-002` — Constant import
- `NS-USE-003` — Bare import rejection
- `NS-USE-004` — Aliased function import
- `NS-USE-005` — Grouped function import
- `NS-QUALIFIED-001` — Absolute function lookup
- `NS-QUALIFIED-002` — Relative qualified lookup
- `NS-QUALIFIED-003` — Unqualified function lookup
- `NS-SYMBOL-PATH-001` — Qualified declaration names
- `NS-VAR-001` — Namespaced top-level variable
- `NS-EXEC-001` — Namespace block execution
- `NS-EXEC-002` — Return from namespace block
- `NS-EXEC-003` — Interleaved declarations and execution
- `NS-EXEC-004` — Multiple unbraced executable namespaces
- `NS-MULTI-BLOCK-001` — Multiple namespace blocks
- `RESOLVE-001` — Constructor and function resolution
- `CLASS-NS-001` — Namespaced class declaration
- `CLASS-NS-002` — Local namespaced construction
- `CLASS-NS-003` — Absolute class construction
- `CLASS-NS-004` — Relative class lookup rejection
- `INC-REQUIRE-001` — require admission
- `INC-INCLUDE-001` — include admission
- `INC-ONCE-001` — Literal require_once

### G11 · Records and member access

**Partial (32)**

- `CLASS-STATIC-NS-003` — Static call via object
- `CLASS-STATIC-NS-002` — Absolute namespaced static call
- `CLASS-STATIC-NS-001` — Local namespaced static call
- `CLASS-PROP-006B` — Write parent::$x
- `CLASS-PROP-006C` — Write static::$x
- `CLASS-PROP-007` — Static field write via object
- `CLASS-PROP-009` — Undeclared field rejection
- `CLASS-PROP-010` — Dynamic field rejection
- `CLASS-CALL-003` — Object-qualified static call
- `CLASS-CALL-005` — Explicit parent method call
- `CLASS-CALL-005A` — Late-static method call
- `CLASS-CALL-008A` — new static(...)
- `CLASS-CALL-009` — Dynamic method rejection
- `CLASS-CALL-012` — Static method declaration
- `CLASS-CONST-001` — Class constant declaration
- `CLASS-CONST-002` — Class-qualified constant
- `CLASS-CONST-003` — self:: constant
- `CLASS-CONST-004` — parent:: constant
- `CLASS-CONST-005` — static:: constant
- `CLASS-MEMBER-001` — Typed instance method
- `CLASS-MEMBER-009` — Abstract static method rejection
- `CLASS-PROP-002B` — Read self::$x
- `CLASS-PROP-002C` — Read parent::$x
- `CLASS-PROP-002D` — Read static::$x
- `CLASS-THIS-001` — Read $this field
- `CLASS-NEW-002` — Qualified new
- `TYPE-PROP-001` — Protected typed fields
- `TYPE-PROP-002` — Nullable properties
- `CLASS-INH-003` — Trait admission changed
- `CLASS-INH-007` — C++-style construction rejection
- `CLASS-INH-010` — Explicit parent constructor
- `CLASS-ABS-008` — Abstract class field access

### G12 · Enums

**Partial (3)**

- `ENUM-001` — Unbacked enum
- `ENUM-003` — Enum tryFrom and nullsafe access
- `ENUM-004` — Enum implements interface

### G13 · Exceptions and cleanup

**Partial (1)**

- `CLASS-CALL-011` — Explicit destructor call rejection

See [G13 verification](#g13-verification) for the exception constructor and destructor declaration results.

### G14 · Compile-time types and layout

**Partial (1)**

- `LIT-CONST-001` — PHP_INT_MAX
