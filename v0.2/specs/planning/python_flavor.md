# Python syntax flavor

Doc Status: planning

## Concept

Python is a fourth per-file syntax flavor of Simple C++. Borrow typed Python spelling and indented blocks. Shared preparation owns types, binding, conversions and evaluation guarantees. The existing backend lowers the common model to C++. Mixed-flavor projects use the ordinary source and symbol pipeline. See the [frontend contract](../compiler_frontends.md).

Decide the contract before admitting spellings with different observable behavior. Examples include Python division, floor division, operand-valued boolean operators and function-scoped assignment. CPython execution and library compatibility are outside this proposal.

## First tokenizer slice

Keep the shared scanner and add Python-specific layout handling. Track open delimiters and an indentation stack starting at zero.

- Emit `NEWLINE` at the end of a nonempty logical line. Ignore blank and comment-only lines. Support LF, CRLF and `#` line comments outside literals. Suppress layout within open parentheses, brackets and braces.
- Compare leading spaces at the next significant line to the stack. Emit `INDENT` for a greater width and one `DEDENT` per popped level for a smaller width. Diagnose unmatched dedents and tabs in leading indentation. The parser decides where an indentation change is legal.
- At EOF, emit a final `NEWLINE` if a complete logical line is pending, then emit the outstanding `DEDENT` tokens. An unclosed delimiter remains an error.

Lex adjacent plus and minus signs separately in Python (for example, a--b is subtraction of a negated operand). Retain original source snapshots and byte offsets. A physical `NEWLINE` spans its LF or CRLF. Anchor `INDENT` and `DEDENT` at the next significant token with zero-width spans. Suite AST spans end at the final statement, before dedent lookahead and following trivia. Anchor EOF layout tokens at EOF. Point indentation diagnostics to the offending tab or indentation prefix and report line and byte column through the existing source-position model. [Python lexical reference](https://docs.python.org/3/reference/lexical_analysis.html#line-structure)

## First parser slice

Select the Python grammar through the existing per-file flavor mechanism. Parse a multiline suite as colon, `NEWLINE`, `INDENT`, statements and `DEDENT`. Build the existing shared block AST. Associate `elif` and `else` with the matching `if` at the same suite level. Diagnose missing colons, missing suites and unexpected indentation at their original positions.

Support typed top-level `def` declarations with explicit parameter and return types, typed initialized locals using the shared declaration parser, existing-local assignment, positional calls, parentheses, scalar literals including `True` and `False`, and basic arithmetic and comparisons where behavior aligns. Include `if` / `elif` / `else`, `while` and value `return` under the shared function and entry contracts.

Defer single-line suites, semicolon-separated statements, backslash continuation, first-assignment inference, division and remainder spellings, boolean operators, chained comparisons, classes, collections and Python import semantics.

## Verification checklist

- [x] Check exact layout-token sequences for nested and sibling suites, multiple dedents, blank and comment-only lines, multiline calls, LF and CRLF, and EOF with and without a trailing newline.
- [x] Check original byte spans and diagnostics for unexpected indentation, tabs, unmatched dedents, missing colons and missing suites. Include UTF-8 before an error on the same line to distinguish byte columns from character columns. Verify adjacent signs and assert that nested suite and function spans exclude trivia before the next sibling.
- [x] Compare common AST shapes for equivalent Python, cpp, php and js fixtures. Build and run generated C++ with Clang.
- [x] Build a mixed project where a Python-flavor entry calls a typed function in an existing flavor and that function calls a Python-defined helper. Compare stdout, stderr and exit status with the equivalent existing-flavor project.
- [x] Edit only the Python helper body and check reuse of the unchanged caller. Introduce a layout error and check that the last good bundle survives. Restore the source and compare with a fresh build. Run the resident sequence with cpp, php and js bridges. A compatible helper signature edit must rebuild the helper and its bridge while reusing the Python entry. Check native results and fresh artifact equivalence, then exercise incompatible signature failure and recovery.
- [x] Run existing tokenizer and parser regressions for cpp, php and js.

The first slice uses .py for project discovery and py for explicit CLI selection. CTest registers compiler_python_frontend and compiler_python_flavor_native. Existing syntax fixtures explicitly select the flavors they generate source for.

## Second slice: boolean expressions

Admit Python `not`, `and` and `or` with canonical `bool` operands and a `bool` result. Reuse the shared comparison and logical AST nodes. Keep Python spelling and precedence in the frontend. Shared preparation checks both operands of a binary expression, including a right operand that runtime short-circuiting would skip. Require boolean types without applying the wider shared truth conversions. Existing lowering preserves single evaluation and short-circuiting. [Shared logical contract](../../compiler/docs/logical.md)

Comparisons bind tighter than `not`, then `and`, then `or`. Thus `not a < b and ready` means `(not (a < b)) and ready`. Grouping can override precedence. Python returns the selected operand from `and` and `or`; for boolean operands the shared result has the same value. Operand-valued expressions such as `name or "fallback"` are deferred. [Python boolean operations](https://docs.python.org/3/reference/expressions.html#boolean-operations)

Keep `/` and `//` deferred. Python true division of integers produces a floating result and floor division rounds toward negative infinity. The existing shared integer quotient truncates toward zero. Decide precision, rounding, and failure rules in a separate numeric slice. [Shared arithmetic contract](../../compiler/docs/arithmetic.md)

### Verification checklist

- [x] Check the AST and native results for comparison under `not`, `not` under `and`, `and` under `or`, and parentheses that override precedence.
- [x] Run Python callers with a boolean probe supplied by each of the cpp, php and js flavors. Verify that `False and probe()` and `True or probe()` skip the probe, while the complementary forms call it exactly once. Use an effectful left operand to verify it runs once and before any selected right operand.
- [x] Reject non-bool operands of `not` and on either side of `and` or `or`. Include decisive left operands such as `False and 1` and `True or "fallback"`, and check diagnostics point to the offending operand.
- [x] Store a selected false RHS from each existing flavor in a typed Python bool local. Verify the callee runs once and the local retains false.
- [x] Edit a skipped RHS callee from bool to int for cpp, php and js. Check that the unchanged Python caller reports the error at the call operand, the last good manifest and C++ artifacts survive, and restoring bool matches a fresh build and restores native behavior.
- [x] Verify that the Python frontend still rejects both division spellings. Run the existing Python frontend and mixed-flavor regressions and the shared logical-expression tests.

The Python frontend and mixed-flavor native proofs passed along with the existing shared logical and native logical regressions.

## Batch backlog after the boolean slice

The C++ flavor adapter is 3,712 bytes. Most grammar lives in the shared parser, namespace parser and record parser. Judge additions by whether they reuse established AST and semantics. Keep Python layout in the tokenizer, spelling and precedence in parsing, type checks in preparation and native execution in shared lowering.

| Batch | Scope | Readiness |
| --- | --- | --- |
| 1. Everyday bodies | Conditional expression, pass, unlabelled break/continue, and bare return in explicitly void functions | Reuse the shared ternary with Python precedence and right association. Preserve shared branch type agreement and selected-arm evaluation. Represent pass with an empty statement span. Reuse loop exits and void-return checks. Keep typed declarations and block-local scope. |
| 2. Typed updates and traversal | Numeric +=, -= and *= on existing locals, and for name in vector | Implemented using shared assignment and foreach nodes. The later typed hash slice adds key traversal. Range, general iterators and loop else clauses remain deferred. |
| 3. Records and typed failures | Concrete classes, dot fields and methods, construction, raise, typed except and finally | Reuses shared class identity, typed catches, rethrow and cleanup. Dynamic attributes and interpreter exception behavior remain deferred. |

### Decisions before widening semantics

- Division and modulo stay deferred. Agree floating quotient precision, bounded integer floor division, zero divisors and minimum-integer overflow before exposing the Python operators. Consider modulo with floor division because Python couples quotient and remainder.
- Keep typed declarations and shared block scope. Bare first assignments, optional-value spelling, operand-valued and/or and chained comparisons require explicit contracts. Chains need short-circuiting and single evaluation of the middle operand.
- Keep project discovery and ordinary symbol lookup. Decide Python import spelling and module mapping together. If we add print syntax, settle separators, newline, formatting and evaluation before selecting an existing output binding.
- Defer comprehensions, decorators, generators, pattern matching and Python package interoperability until an application needs them.

### Batch 1 ownership notes

- The shared ternary uses parser-owned start and end token positions so its source span includes the true arm and closing parentheses on a grouped false arm. Preparation and emission still evaluate the condition first.
- Pass uses an empty shared block statement. Its scope contains no declarations or effects, and its source span includes the pass token and newline.
- Construct the existing break, continue and return nodes after consuming NEWLINE. Share loop-depth and return-contract diagnostics with preparation. Keep Python exits unlabelled and use the existing void return annotation.

### Batch 1 acceptance

- [x] Check conditional expression precedence with not/and/or, right association, grouping, complete source spans and single evaluation of each selected arm. Reject mismatched branch types.
- [x] Check a pass-only suite and its source span, nested loop exits with an observable skipped iteration, invalid exits outside a loop, and bare return acceptance in void functions and rejection in value-returning functions.
- [x] Run a common native matrix with cpp/php/js peers, including a body edit, preservation of the prior bundle after an invalid edit, and recovery matching a fresh build. Run the affected shared regressions and confirm division remains deferred.
- [x] Review the batch together. Pause for a new language contract or cross-owner refactor that requires agreement.

Batch 1 passed 10 focused CTests with Clang, covering the Python frontend, the native cpp/php/js peer matrix, and shared ternary, logical, loop and transfer behavior. The resident matrix verified unchanged peer reuse after a Python edit, execution of both conditional arms, and publication preservation after a Python branch-type error or a peer signature edit. Both failure paths recovered to fresh-equivalent artifacts and native behavior. The semantic decisions above remain separate from the verified slice.

### Batch 2 contract and verification

The Python parser adds the three numeric compound spellings and the value-only for name in source suite. The common parser constructs the existing assignment and foreach AST nodes. Preparation admits integer and floating scalar operands through a generated query over active metadata providers, then delegates promotions and write-back checks to ordinary compound preparation. Vector admission is checked on the resolved source type before querying its published traversal capability.

A fresh target is local to the loop. An existing local or parameter is assigned on each successful iteration using normal assignment conversion. Empty traversal leaves an existing target unchanged. Break leaves the current value in place, including a body write. The source expression runs once and temporary vector lifetime follows the shared foreach path.

- [x] Verify shared foreach AST and source spans, fresh-name scope, an incompatible destination, undeclared compound destinations, nonnumeric compound operands and continued rejection of division.
- [x] Run numeric integer and mixed floating/integer compound operations, empty and populated vectors, reused locals and parameters, and break and continue in the Clang matrix with cpp/php/js peers. Observe one effect per vector-producing call.
- [x] For each peer flavor, edit the vector provider body and verify reuse of the unchanged Python caller. Change the return signature to a typed hash and confirm that the unchanged caller rejects it, the last bundle survives, and restoring the vector matches fresh output and native behavior.
- [x] Change a peer's return type from vector<int> to vector<string> across cpp/php/js. Verify that the unchanged Python caller is prepared and re-emitted, native traversal sees the new contents, and the bundle matches a fresh build.
- [x] Verify a uint8 compound assignment reaches the generated checked int64-to-uint8 write-back. Storing 255 succeeds and attempting to store 256 raises integer_cast_out_of_range in native execution.
- [x] Run the relevant shared foreach, integer and floating compound, and generator parity checks.

Batch 2 deferred hash iteration until the key-only capability was available. The later typed hash slice adds that form. Range, general iterators and loop else remain deferred.

### Batch 3 contract and verification

- A Python class suite declares fixed typed instance fields and ordinary methods. A method takes self as its first written receiver marker, with no ordinary argument slot. The parser emits the shared receiver, field and call nodes. An unannotated __init__ method supplies the shared constructor signature and body.
- The shared call node retains the written call and its arguments. Preparation resolves the stable function and class entries, diagnoses a callable and class with the same visible name, and selects the ordinary shared constructor contract. Lowering uses the same factory and argument path as explicit shared construction.
- Raise maps to shared throw and bare rethrow. Except Type as name binds the same shared exception object for that clause. Finally uses the shared protected-exit rules, including running during native guard failure without making the failure catchable as a source exception.
- Authored Python inheritance, value structs, super, dynamic attributes, bare and tuple handlers, try else and exception chaining remain outside this batch.

Verification checklist

- [x] Check complete class source spans, fixed field access, receiver exclusion from callable signatures, call/class ambiguity, and rejection of invalid constructors, member writes and exception syntax.
- [x] Across cpp/php/js peers, construct and mutate a Python class and observe the shared alias. Preserve typed diagnostic identity through a bare rethrow after handler-local reassignment. Verify cleanup on value return, rethrow and native guard unwind.
- [x] Verify reuse on a free-function body-only peer edit, refresh of class dependents on a Python method-body edit, last good publication preservation on an incompatible constructor edit and recovery matching a fresh build. Retain the existing class-identity invalidation contract.
- [x] Run the Python frontend and native matrix and the existing shared class, exception and finally suites.

All six focused CTests passed with Clang. Peer cleanup body edits re-emitted only their own unit. Replacing a Python class after a method-body edit refreshed dependent callers because prepared facts retain the canonical type on the record declaration. Fresh-equivalent output and native recovery were verified for all three peer flavors.

The review regressions passed in the Python frontend and native CTests. Across cpp/php/js peers, adding a same-name function invalidated the unchanged constructor caller and preserved the previous native bundle and manifest. Removing the function restored fresh-equivalent output and native behavior. The native matrix also verified Python finally on normal fallthrough, continue and break.

### Batch 4 contract and verification

Python spells a single base as class Child(Base). A derived constructor can begin with super().__init__(args), which supplies the shared base initializer. When that statement is omitted, the ordinary default base-initialization rules apply. Inherited fields and shared handle upcasts use the existing class model.

Standalone @virtual and @override lines mark instance methods at compile time. The shared method contract requires @override when replacing an inherited virtual method, including a method declared in another flavor. The markers take no arguments. General decorators, multiple bases, Python interface declarations and other super forms are deferred.

- [x] Check the shared record, initializer and method flags and their source spans.
- [x] With cpp, php and js base classes on Clang, run Python derived construction, observe one base-constructor effect, read and write inherited storage, and dispatch the Python override through a base handle.
- [x] In each mixed-flavor resident session, edit an inherited method body, observe the new result, and retain the unrelated Python unit. The base declaration replacement still refreshes dependent units under the shared canonical record identity contract.
- [x] Reject a missing or duplicate override marker, multiple bases, a misplaced base initializer and a nonvirtual base contract. Preserve the last good bundle and manifest on failure, including base source deletion. After restoring the base, compare with fresh emitted artifacts and run the recovered native executable.

### Typed hash key traversal

Python for name in source binds an element for a typed vector and a key for a typed hash. JS for (name in source) with a braced body selects the same key-only capability. Both reuse the existing foreach destination rule. A new name is local to the loop, an existing local or parameter receives each key through ordinary assignment conversion, and an empty hash leaves it unchanged. Traversal order has no source guarantee.

The generated typed hash capability publishes only keys and uses the existing physical cursor. Both ordinary and resumable lowering use the selected key type for output storage. Each advance copies only the selected key and keeps no table or entry reference across the body. An indexed read still uses ordinary lookup. General slot reuse, boxed traversal with these spellings, Python items and destructuring are deferred.

- [x] Verify that both spellings lower through iteration_next_key, that temporary sources execute once per loop and that key-only code has no value output slot. Verify empty input preserves an existing destination and a fresh binding stays loop-local.
- [x] With cpp/php/js providers, change the key type from int to string and compare the resident emission with a fresh build. Verify unrelated source reuse, reject a vector in JS for-in, preserve the last good output on failure or provider deletion, and run native code after recovery.
- [x] Run shared foreach and JS resumable traversal, generated provider relocation, generator parity and concrete-template tests. Verify that application-hash key advancement succeeds with a payload that throws on copy, and run the runtime traversal fixture with Clang ASan, UBSan and leak detection.

#### First-statement hash slot read

Lowering inspects only the first statement of a normal key-only loop. The initializer
must be a complete indexed read with identity initialization, on the same plain local
hash and the fresh loop key. It uses existing prepared binding identities and requests
the generated slot read at the original initializer. The cursor still copies only the
key. The value copy can fail at the read, and no table reference escapes.

Python now admits typed hash reads through the shared indexed-read syntax and preparation. The slot proof adds no preparation work, AST fields or incremental watches. A preceding statement,
nonlocal receiver, reused key, conversion, or resumable loop uses ordinary indexing.
The mixed-flavor proof checks native Python and JS reads with cpp/php/js providers,
including a key-type edit, an inserted statement that demotes the fast path,
a post-write lookup, and fresh-equivalent incremental recovery. The concrete hash
probe checks a throwing value copy and exactly one copy when the read succeeds.

Local measurements used Clang 18.1.3 on WSL, pinned to CPU 2. The native input
was one JS unit that populated 8,192 distinct int keys and accumulated their
values over 1,500 passes. Both versions produced the same checksum. The baseline
used the previous emitter. Twenty timed pairs alternated order after four warmup
pairs. Native medians include startup and hash construction.

| Path | Native execution (ms) | C++ bytes |
| --- | ---: | ---: |
| Checked hash lookup | 278.82 | 1,160 |
| Slot read | 60.26 | 1,201 |

Median paired speedup was 4.59x for this fixture. Seven warm build pairs used
Clang O3/g0, libc++, mold, one translation unit, no PCH or LTO, and the same
prebuilt runtime. Compile and link were timed separately. Frontend and backend
durations are from Clang traces and are included in compile wall time.

| Path | Compile wall (ms) | Frontend (ms) | LLVM backend (ms) | Link wall (ms) | Native build wall (ms) |
| --- | ---: | ---: | ---: | ---: | ---: |
| Checked hash lookup | 82.81 | 31.28 | 12.52 | 62.75 | 145.17 |
| Slot read | 86.05 | 38.39 | 12.19 | 63.60 | 155.90 |

The PHP measurement compared the prior preparation and emitter with the current
pair. Each sample prepared a fresh 160-function AST after tokenizing, parsing
and publishing symbols, then measured emission separately. There were 144 warm
samples per variant and workload, with Xdebug disabled and process order
alternated. No preparation increase was observed. The small median differences
are insufficient to attribute a preparation or emission speedup to this slice.

| Workload | Prior prepare (ms) | Current prepare (ms) | Prior emit (ms) | Current emit (ms) |
| --- | ---: | ---: | ---: | ---: |
| 160 eligible hash loops | 18.18 | 17.45 | 7.89 | 6.83 |
| 160 ordinary vector reads | 7.14 | 6.98 | 4.35 | 4.25 |

## v0.2 closeout

The typed Python flavor is complete for this milestone. The shared frontend
contract records the supported syntax and its Simple C++ semantics. The
compiler README provides the build command for the standalone example.
[Frontend contract](../compiler_frontends.md#python-syntax-flavor-in-v02)
[Runnable example](../../compiler/examples/python/main.py)

CLI acceptance passed with Clang at fb77a0487:

- Standalone py. The public tokens, parse and build commands processed a
  program with typed vectors, hashes and key-only traversal. A cold build
  ran successfully. A no-op build reused the emitted bundle and native
  artifacts. A body edit changed the result. An unmatched dedent reported
  main.py:14:1 and preserved the last good manifest and executable. Recovery
  matched the emitted C++ and execution of a fresh build.
- Mixed cpp/php/js/py. A Python entry called C++ and JavaScript, the JavaScript
  function called PHP, and the PHP function called back into Python. A
  body-only C++ edit rebuilt one native object and reused five. A compatible
  return-type edit rebuilt and ran. An incompatible signature and deletion
  each reported a source error and preserved the last good build. Restoring
  the source produced fresh-equivalent C++ and native behavior.

### Deferred work

- Division, floor division and modulo need a common decision on quotient
  type, rounding, remainder sign, zero and bounded overflow.
- Imports and print need explicit module-mapping and output contracts.
- Python-authored interface declarations, super calls beyond base
  construction, general decorators and dynamic attributes remain deferred.
- General truthiness, operand-valued and/or, chained comparisons, first-write
  inference, range, general iterators, items, destructuring, comprehensions
  and generator syntax remain deferred. Broader hash slot reuse belongs to
  the compiler optimization backlog.
- CPython execution and packages are outside the v0.2 flavor scope. Native
  tokenizer cutover still needs Python token and layout parity.
  [Native tokenizer backlog](remaining_language_gaps_and_native_tokenizer.md)

### Typed collection expressions and updates

Python accepts checked reads on typed vectors and hashes and single indexed
assignments on writable local and field storage. A vector assignment replaces
an existing element. A hash assignment replaces or inserts using the strict
key contract. Negative vector indexes retain the shared bounds behavior.

Explicitly typed locals accept list input as [value, ...] and keyed hash input
as {key: value, ...}, including multiline input and trailing commas. An empty
dictionary constructs an empty typed hash. Populated input uses the shared
initialization transport and existing left-to-right evaluation. Equal keys
reject after input materialization. Ordinary reads and writes of application
collections use the same contracts as runtime specializations.

The parser produces the existing collection and indexed-access AST nodes.
Preparation uses the existing contextual type, key and conversion checks,
without another pass or additional retained facts. Indexed compounds, empty
subscript appends, nested writes and general dictionary expressions remain
outside this batch.

- [x] Check the shared AST and CRLF spans for list input, dictionary input, reads and writes.
- [x] Run Python against cpp/php/js peers with Clang. Check ordered literal effects, vector copy independence, field replacement, and application vectors and hashes.
- [x] Check negative reads, out-of-range writes, missing keys, and duplicate rejection after input effects. Reject invalid key types, value conversions and unsupported indexed targets.
- [x] Verify body-only peer edits re-emit only the peer. Preserve the last good bundle and manifest after invalid source edits or peer deletion, and compare the restored artifacts and native behavior with a fresh build.
