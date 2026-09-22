# PHP authoring and conversion decisions
Doc Status: planning

Read [language](language.md), then [libraries](libraries.md). The catalog is a
planning inventory, not authorization to implement every proposal at once.
Only the [first slice](../first_slice.md) is implemented. The converter remains
PHP-written, token-based, local and separate from the compiler.

## Mapping mechanisms

| Mechanism | PHP authoring | Conversion evidence | Test boundary |
| --- | --- | --- | --- |
| Structural preservation | Typed function, class, loop or ordinary expression in the target subset | Current syntax node only | Same algorithm fixtures; target compilation validates types |
| Local type intent | `$n /** uint32 */ = 10;` | Attached annotation | PHP values approximate; test native ranges separately |
| Declaration metadata | Proposed marked class for struct; marked enum backing width | Metadata attached to that declaration | Layout native-only; copy semantics require explicit authoring |
| Reserved call map | `take_nullable($out, $value)` | Exact reserved name and local arguments | PHP helper and target take branch behavior |
| Support object | Proposed Result, Error, buffer, builder or task object | Annotated slot plus explicit support calls | PHP algorithms; native lifetime/thread behavior separately |
| Native-only boundary | Layout queries, real MT, GUI integration | Explicit operation/test marking | No invented PHP equivalence |
| Unsupported shape | Dynamic declaration/ambiguous operation | Local rejection | Clear file/line diagnostic |

There is no mechanism called "look up the receiver type". An operation whose
correct translation needs that must gain explicit syntax/metadata or use a target
operation whose semantics the target compiler can resolve. The portability tool
does not resolve symbols and does not run the prototype semantic compiler.

## 1. Locals and function boundaries

Already implemented:

```php
$count /** uint32 */ = 10;
```

emits:

```text
$count uint32 = 10;
```

Proposed next: preserve native PHP scalar signatures, and use adjacent target
metadata for generic signatures that PHP cannot express:

```php
function total(/** vector<int> */ $items): int {
    $sum /** int */ = 0;
    foreach ($items as $item) {
        $sum += $item;
    }
    return $sum;
}
```

Candidate target:

```text
function total(vector<int> $items): int {
    $sum int = 0;
    foreach ($items as $item) {
        $sum += $item;
    }
    return $sum;
}
```

PHP executes the collection as an array. The converter reads only local metadata.
This is a proposal: functions, generic annotations, foreach and arithmetic are
not yet implemented in the portability parser. Exact target slot syntax should
be locked by a compile witness when adding that slice. Explicit annotations must
not claim PHP enforces vector element types.

## 2. Containers and value records

Authoring policy: [prefer defined structures; reserve ordinary PHP arrays for
non-hot setup followed by read-only use](../README.md#agreed-authoring-discipline-structures-and-arrays).
This is author/reviewer discipline backed by unit tests, not converter enforcement.

Candidate ordinary container authoring:

```php
$ids /** vector<uint32> */ = [1, 2];
$names /** hash<string, int> */ = [1 => "one"];
```

The declaration can be converted mechanically. Dense-index constraints and PHP
numeric-string key coercion remain behavioral questions for the library/tests.
Prefer defined structures and explicit collection owners as described by that
policy. The candidate array spellings above are not a recommendation to use ad-hoc
arrays for mutable algorithm state.

For a struct, a proposed `@scpp-struct` class marker could convert a PHP record
class into a target `struct`; the spelling is not accepted yet. The difficult
part is assignment: PHP object assignment shares identity, while a target struct
assignment copies fields. The converter cannot discover struct-typed expressions
elsewhere. Options to decide are explicit record-copy operations, a deliberately
restricted mutation pattern, or another executable record representation.
PHP `clone` alone is not a general answer: nested value fields require independent
copies while shared object fields must retain shared identity. Do not hide this
inside an untyped generic clone-and-hope helper.

## 3. Wrappers and take

Existing helpers support scalar nullable/falseable/boolean-result approximations:

```php
$position /** result_or_false<int> */ = 0;
$out /** int */ = 9;
if (take_false($out, $position)) {
    echo $out;
}
```

For `result<T>`, use a proposed explicitly tagged PHP result object with value/error
constructors. It must carry successful null and false without treating either as
failure. `take_result($out, $error, $result)` can map mechanically to target
`take($out, $error, $result)`. Exact constructors and Error object API are pending.
For `result_or_false<bool>` / `result_or_bool<bool>`, use explicit tagged states;
our current scalar representation intentionally rejects those annotations.

## 4. Framework namespaces and libraries

All PHP framework/tool namespaces use lowercase. The framework includes whole
libraries needed by portable programs, not just aliases for PHP builtin functions.
It has three sources of executable behavior:

| Owner | Purpose | Examples / direction |
| --- | --- | --- |
| Ordinary PHP | Existing behavior is a sufficient approximation | `is_bool`, `is_int`, `is_float`; other functions reviewed individually |
| `scpp\compat` | PHP-like API with Simple C++-specific behavior/contracts | Proposed `count`, `json_decode`, `json_encode` wrappers |
| `scpp` and lowercase library subnamespaces when useful | Capabilities/libraries absent from ordinary PHP | Implemented `take_*`; proposed Result/Error, typed container/record support, source buffers/spans/locations, builders, task/async support |

These libraries are real PHP implementations used while developing and testing
the algorithm. They may use PHP builtins internally, allocate more, or execute
sequentially. They need not reproduce the target's implementation or performance.
The existence of a PHP primitive that can implement part of a library does not
make that library a compatibility override: a source-buffer/span library still
belongs to `scpp`, even if internally built from PHP strings.

Use ordinary names with explicit function imports where needed, for example:

```php
use function scpp\compat\{count, json_decode};
use function scpp\take_nullable;
```

One central policy selects functions for the whole project. The tool maintains
identical marked imports in every authored file; no per-file API choice is allowed.
The current implementation imports the three `scpp` take helpers and leaves
`is_bool` as ordinary PHP. The example JSON/count imports illustrate the policy
once those adapters exist; they are not installed today.

`sync_imports.php` writes/checks the common block. The converter requires it and
strips it using the local policy; explicit global bypasses of imported names fail.
Global helper aliases have been removed. The current subset supports global script
files, rejecting namespace/declare prologues until correct scoped placement exists.
PHP imports remain lexical: bootstrap loading alone cannot apply them to a file.

For JSON, proposed `scpp\compat\json_decode` must return an explicit PHP Result
carrier supplied by the `scpp` library, separating decoded null/false from errors.
This illustrates the distinction: the compatibility function consumes the new
framework library; it is not itself the whole framework.

Reserved function imports, explicit type annotations and known framework operations
provide all conversion facts locally. No library import authorizes converter type
inference. Native-only MT/layout tests run after porting; missing PHP facilities
need either a useful library approximation or an explicit native-only boundary.

## 5. Probes and short-circuit behavior

A function map cannot implement every syntax construct as an eager function call.
For example, a proposed `compat_isset($row['missing'])` evaluates the missing read
before the function runs. Use a structural path rewrite, explicit container/key
operation, or delayed callback with a defined contract. The information remains
local. The same applies to empty/coalesce and nullable member paths.

Test missing, present-null, invalid intermediate receiver and present values.
Target `empty("0")` is false; PHP's builtin returns true. Avoid directly using the
PHP builtin as a complete approximation for that family.

## 6. Async and MT

Candidate async authoring: an attached `@async` marker plus an explicit reserved
await helper; the converter rewrites those local forms to target async/await.
Ordinary PHP may execute synchronously. Decide whether a task carrier is needed
before supporting stored/forwarded task values; do not guess from a call's return.

Candidate tasks authoring: the same `task_*` names with a sequential PHP worker
implementation and explicit result joins. The PHP run tests work decomposition,
result collection and ordinary error handling. Actual threading, cancellation,
publication synchronization, timings and transfer constraints are native tests.
Async and tasks are separate target capabilities; no implicit bridge is assumed.

## 7. Libraries to build first

1. Finish function/class/namespace and type-slot conversion for one real compiler
   component; extend the managed import policy to namespace/declaration prologues.
2. Add explicit Result/Error support and `take_result`.
3. Choose ordinary-array versus adapter rules for typed vectors/maps and record
   copying; add only needed container operations.
4. Add compiler-oriented bytes, source buffers/locations, builders and filesystem
   adapters. Stable hashes need exact agreed algorithms/bit representation where
   PHP and native cache keys must agree; native uint64 is not automatically PHP int.
5. Add tokenizer contract adapters only if actually needed: PHP `token_get_all`
   does not already tokenize all PHS/JSS extensions.
6. Add sequential tasks once worker contracts are represented. Keep real MT tests
   on the target. Native layout is target-tested; GUI/WebView is out of scope.

Every selected slice should have an executable PHP example, deterministic local
conversion, a compiled PHP++ witness, and intentional difference notes. Do not
turn this inventory into a blanket implementation or compatibility requirement.

## 8. Incremental scope and native escape hatch

Agreed direction: grow the framework from actual compiler needs, solving concrete
situations as they arise. GUI/WebView support is outside this portability effort;
its catalog entries inventory the target, not a framework implementation commitment.
Dynamic PHP behavior is discouraged. Prefer explicit code; the converter continues
to reject unsupported dynamic constructs rather than resolving them.

When shared authoring or a small library adapter is impractical, allow an explicit
native/PHP alternative. Proposed spelling (not implemented yet):

```php
if (SCPP_NATIVE) {
    /** @scpp-native
    $value int = 42;
    echo $value, "\n";
    */
} else {
    $value = 42;
    echo $value, "\n";
}
```

The intended PHP++ payload is directly copy/paste-able source, not C++ and not a
second template language. The marker spelling/comment extraction format still
needs its implementation slice. The agreed processing boundary is:

- The PHP framework defines the reserved global `SCPP_NATIVE` constant as false;
  PHP runs the fallback branch. This constant is not installed by this planning note.
- The converter recognizes the exact standalone `if (SCPP_NATIVE) { ... } else
  { ... }` shape locally. It replaces the entire conditional with the explicitly
  marked PHP++ payload; it does not emit a runtime branch or convert the fallback.
- The native arm contains only the marked payload and permitted trivia. Never
  concatenate arbitrary comments as code or silently discard executable statements.
- No general preprocessor, constant evaluator or symbol resolver is introduced.
  Unsupported condition/elseif/nesting shapes need a diagnostic until specified.
- The authored PHP remains the source of truth; the embedded payload is regenerated,
  not patched into derived output by hand. Copy its text without target rewriting;
  target compilation/STAN checks it. Source attribution should identify the original
  embedded block when the source-map slice is implemented.
- Keep the branch's inputs, outputs and effects explicit. The converter cannot prove
  equivalence or compatible variable visibility. PHP fallback tests and native
  compilation/execution establish the required behavior separately.

Prefer a reusable framework operation when multiple native escape blocks would
repeat the same concept, but do not require a general abstraction to solve a single
bounded case. This is an intentional escape hatch, not a portability failure.
