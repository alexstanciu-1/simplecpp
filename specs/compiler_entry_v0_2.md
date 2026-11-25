# v0.2 compiler integer entry slice

Doc Status: normative
Scope: the bounded PHP compiler rebuild under `compiler/`.

## Executable arguments

When the runtime publication selects an argument reader, generated main accepts native argc and argv and passes them through the entry exception boundary. The authored entry body keeps its parameterless signature. A publication without argument readers uses the original parameterless adapter. The [runtime API summary](runtime_api_summary.md#executable-arguments-and-environment) defines the visible readers and snapshot types.

## Source contract

The first executable slice admits a single file containing explicitly typed integer
local declarations followed by a top-level value return. Its equivalent forms are:

```text
PHP: $ret int = 8; return $ret;
C++: int ret = 8; return ret;
JS:  let ret: int = 8; return ret;
```

`int` currently selects the canonical signed int64 type; `int64` explicitly names
the same type. This fixes the default for this bounded implementation, not a PRJ
profile schema. Integer expressions are nonnegative decimal literals or local name
reads. Leading zeros do not change the numeric base. Literal magnitude is checked
before converting to a host integer; 9223372036854775807 is the largest admitted
literal. Parsing preserves spelling and does not perform that range check.

Locals are visible only after successful initialization, in source order. Duplicate
declarations in this single scope, self-initialization, unresolved names, reads
before declaration and unknown/unsupported type names receive source-located
preparation diagnostics. A reference binds to its collected declaration identity.
Known integer values propagate through local initialization and name reads so
statically failing exit conversions are diagnosed before emission.

The executable slice requires an explicit value return and supports no following
statements. Initializer-based inferred locals are covered by the [inference extension](compiler_scalars_v0_2.md#initializer-based-local-inference).
Plain assignment to existing locals is covered by the [assignment extension](compiler_scalars_v0_2.md#local-assignment-extension). The function extension below admits bare return
inside explicit void functions, but not in the executable entry. Arithmetic, unary
signs, nested blocks, runtime input expressions and incremental compilation remain
outside this implementation. These limits do not prohibit those features in the
broader language.

## Entry exit boundary

A top-level return supplies the executable's process exit value. The example must
compile and run with exit status 8 in all three flavors.

The source integer converts to the target C++ signed `int`, using the target's
numeric limits, not the PHP host's `int` width. If its value is known during
preparation and outside those limits, preparation rejects it. Otherwise preparation
records either a proven conversion or a required runtime check. The runtime check
evaluates the value once, rejects an out-of-range value with `std::out_of_range`,
and only then narrows it. No silent truncation or wrapping is permitted by this
conversion. The operating system may subsequently limit the reported exit status.

The current source subset produces known values. The unknown-value decision and
its native adapter are tested independently; this is not a claim that the source
compiler already supports runtime input or exceptions.

## Stage and publication boundaries

Collection records syntax occurrences and file-local declaration identities without
resolving names, types or visibility. Preparation performs a source-order pass,
retains typed facts, and selects the entry conversion. C++ generation accepts only
completed prepared entry/program facts and does no name resolution or source validation.
Type spellings, safe C++ variable names and native conversion syntax are backend-owned.

The target adapter reads the existing layout probe's signed native-int facts.
Generated code asserts the corresponding native-int signedness and value-bit count,
rejecting compilation against a different integer target. This does not implement
cross-compilation orchestration or a complete runtime ABI packaging protocol.

Emission renders a whole artifact before touching its output path. File publication
stages in the destination directory and replaces the destination by rename. A failed
parse or preparation creates no new output and leaves an existing artifact intact;
the caller must honor failure and must not build or run that older artifact as a
successful result. The compiler never overwrites its source or target-facts input.

The [preparation diagnostic policy](v0_2_preparation_diagnostics.md) remains the
general authority for early rejection versus value-dependent runtime guards.

## Bounded multifile executable

A program explicitly lists its sources with a flavor per file and selects exactly
one entry by canonical root-relative path. The entry must belong to that inventory.
Every source is parsed and collected once per invocation. Only the selected entry
may contain executable statements; secondary files admit whitespace, comments and
the function definitions described below. Calls can resolve functions across the
explicit source inventory.
No execution order or dormant executable body is invented for secondary files.

The backend emits one C++ translation unit per source plus one entry adapter owning
`main`. Source-unit names derive from logical paths, independently of inventory
order and source-root location. The manifest records the source-to-unit mapping and
exact translation-unit list. Native builds consume that list, not a directory glob.

All sources must pass validation before publication starts. A failed source leaves
previous output intact and creates no new output directory. Successful publication
retires only obsolete units listed in the previous manifest, preserving unrelated
files. Individual files are replaced atomically and the manifest is published last;
this is not an atomic bundle transaction. Interrupted publication recovery and
concurrent writers are outside this slice. Callers must honor command failure.

## No-argument void function extension

The initial function slice supported no parameters and an explicit `void` return
type. It remains a subset of the typed-function extension below. Equivalent forms are:

```text
PHP: function ping(): void { return; }
C++: void ping() { return; }
JS:  function ping(): void { return; }
```

All flavors call these functions with `ping();` in statement position. A function
body may be empty or fall through, or end with a bare `return;`. It may forward a void call with
`return ping();`. Returning a non-void value is invalid. Bodies may contain the existing explicit integer
local declarations and no-argument calls; each body has its own local scope and
cannot capture top-level or other functions' locals. Local source-order visibility
rules remain unchanged. Executable statements after a return remain unsupported.

Function definitions are global declarations, not executable statements. They may
appear before or after top-level executable statements, including after the entry's
value return. Definitions may appear in secondary sources. Only the selected entry
owns top-level executable statements and still requires its integer value return.
Nested function definitions are not supported.

Names are case-sensitive and resolve in one global function-name domain over the
explicit source inventory. Function calls use this domain, independently of local
integer bindings; variable-call syntax and function values are not supported.
Forward calls and self/mutual recursion are valid because every callable signature
settles before any executable body is prepared. Duplicate function names are invalid;
this extension does not introduce overloads. Unknown functions receive source-located
preparation diagnostics. Invalid bodies are rejected even if never called.

Typed parameters, arguments, non-void returns and call expressions are specified
below; namespaces and incremental preparation have their own extensions. Inferred
signatures, prototypes without bodies, methods and function values remain deferred.
Unsupported forms diagnose before C++ emission.

The coordinator builds a pending symbol index from parser discovery notifications.
It publishes only after every source parses successfully. Declaration identity,
settled signature and prepared body are distinct: signatures are attached to their
declaration nodes, while body facts are attached to their body owners. Call facts
retain the resolved contract and do not inspect the target's executable body.
Completed prepared-program/entry results gate emission after all bodies succeed.

Each source emits its own function definitions with deterministic backend-owned C++
names and the prototypes needed for its calls. No source embeds a foreign function
body. Cross-file calls link through the existing exact translation-unit manifest.
Stable function names and source-local output boundaries prepare for future
incremental work but do not establish reuse or invalidation today.

## Bounded echo extension

Entry and void function bodies accept `echo value;` in all three flavors.
This implementation subset follows [the output contract](output_v0_2.md): output
has no implicit separator or newline, integer formatting is decimal, and string
output preserves bytes. Each operand is evaluated once.

The current implementation admits one existing int64 literal/local or one quoted
string literal. Single quotes decode only escaped quote/backslash. Double quotes
support `\\`, `\"`, `\$`, `\n`, `\r`, `\t` and isolated `\0`. Unsupported escapes
and interpolation diagnose. General string expressions and multiple operands are
not yet implemented; these restrictions do not redefine the full output contract.
See [implementation and debt](../compiler/docs/output_slice.md).

## Implicit entry representation

Top-level executable statements are represented internally as the body of a
file-private implicit `main` declaration, first in the file AST's keyed declaration
collection. It is excluded from global function lookup. `main` is reserved for
this entry role; explicit source declarations of it remain unsupported. This does
not change the existing selected-entry or exit-status rules and does not admit
new function forms.

## Typed value parameters and returns

Function signatures require explicit parameter and return types. All currently
registered source value types are admitted: int/int64, bool, byte, float/float64,
string and null. Void is return-only. Canonical flavor spellings are:

```text
PHP: function identity(string $value): string { return $value; }
C++: string identity(string value) { return value; }
JS:  function identity(value: string): string { return value; }
```

PHP parameters use type-before-name. Local declaration shorthand does not change
this signature contract. Each function owns its ordered parameter declarations;
its body scope indexes them before collecting local statements. Parameters and
locals cannot duplicate names. PHP bare writes to parameters are assignments,
not inferred declarations. Parameters are initialized on entry, with unknown
caller values during body preparation, and are mutable local storage. Value parameters own copies; explicit references bind caller storage.

Positional calls can omit a trailing suffix with declared defaults. Fixed source signatures
accept named arguments on free functions, instance and static methods, and explicit
construction. A virtual or interface call takes names from the declaration selected
by the receiver's declared type. Dispatch may invoke an override with different names.
An explicit ancestor call uses the selected ancestor declaration. Static lookup
uses the explicit, lexical, called or expression-qualified type as appropriate.
Direct, inline, explicit base and late-bound construction use the effective source
constructor's parameters, including inherited names and defaults.
An instance call evaluates its receiver once and retains it through the call, before evaluating arguments. PHP, C++ and JS
use `difference(right: nextRight(), left: nextLeft())`. Python uses
`difference(right=nextRight(), left=nextLeft())`. Positional arguments can precede
labels. Every required parameter must be supplied once. A label can skip a
defaulted parameter. Unknown labels, duplicate bindings and positional arguments
after a label receive a diagnostic. A parameter rename invalidates callers that
use names on that declaration. Each supplied argument
is evaluated once, left to right, and its destination conversion completes before
the next argument starts. A named writable or read-only reference follows the
same storage and default rules as its positional equivalent. A reference-returning
call retains the origin of the actual bound to the declared return-origin parameter.
Converted values are preserved for invocation. A failure
stops later argument evaluation and callee entry; earlier effects remain. Strings
have owning value semantics: parameter reassignment cannot mutate caller storage,
and returned strings remain valid after the callee exits.

Written parameter and return types authorize the existing metadata-defined typed
destination conversions. This uses the same rules as explicitly typed initialization,
including checked narrowing; it does not add conversions, string/text coercions or
implicit promotion. Unknown-value checks remain at execution time. Returned call
values are not constant-folded by inspecting callee bodies.

Calls can appear in existing value positions, including arguments to nested calls,
initializers, assignments, echo and returns. A non-void result may be discarded in
statement position. A void call can be a statement or the return expression of a void callable. Non-void functions
must return a value; fallthrough and bare returns reject. Void functions may fall
through or return without a value. Following executable statements after a return
remain outside the current straight-line body slice. The executable entry retains
its separate int64-to-native-process-exit contract.

Every signature settles before any body is prepared; callers consume signatures
without reading foreign bodies. Forward and recursive signature binding work, but
terminating recursive execution awaits control-flow support and its own proof.
Incremental caller contracts include ordered parameter types/count, default presence/value and return type;
parameter names do not affect positional callers. Signature changes invalidate
observers, while body-only changes retain them. New signatures never require
copying a foreign AST or creating a second prepared signature model.

A runtime free function accepts named arguments when its binding publishes source
parameter names. Those names are part of its public API. Renaming one breaks calls
that use the old label. The first binding is PHP `str_repeat(string, times)`.
For example, `str_repeat(times: repetitions(), string: fragment())` evaluates
`repetitions()` first and passes its result as the second argument.
Typed callback calls require positional arguments. Variadic signatures remain deferred.
Duplicate same-name functions remain errors, even if their parameter types differ.

### Named callbacks

A free function or static method can declare a required by-value
`callback<Result, Input1, Input2>` parameter. Use `callback<void>` for
a callback with no inputs and no result. Components use ordinary signature
types. Inline record values, nested callbacks and yield producer signatures
are outside this slice.

Pass a source function as `name(...)`. A type-qualified reference such as
`Math::twice(...)` (PHP and C++) or `Math.twice(...)` (JS) can select a
static method using normal name lookup and access checks. The ellipsis forms
a reference without invoking its target. The expected types and arity must
match the target's required signature exactly, including each input's passing mode. Inside the accepting
function, invoke the parameter with ordinary call syntax (`$op(value)` in PHP,
`op(value)` in C++ and JS), or forward it to another matching callback
parameter. A trailing `&` marks a mutable reference input, as in
`callback<void,int&>`. It uses the direct-call rules for exact writable storage,
aliasing and invalidation of caller value facts. Value inputs use ordinary
conversions. A result marker in `callback<R&,Args...>` or `function<R&(Args...)>`
makes the result a writable alias of the one reference input, whose type must be
exactly `R`. The target must declare the same result mode and origin. In PHP,
`$a =& $op($x)` binds to the caller's slot after a successful invocation, while
`$a = $op($x)` copies the value. A stored callable may be reset or replaced while
that alias remains live. The existing capture and ownership rules apply.
Invocation follows the usual evaluation and exception contracts.

The C++ backend passes a typed native function pointer. Instance method
references and persistent callback storage are outside this slice.

### Lambdas

A lambda passed directly to a selected `callback<R,Args...>` parameter may omit
input and result annotations independently. For example, `apply(fn($x) => $x)`
uses the selected callback's first input and result. This applies to PHP block
closures and to expression-bodied arrows in PHP, C++ and JS. Arity and authored
annotations must match the expected signature exactly. A reference input requires a written
`&` on the closure parameter even when its type is omitted. An inferred local closure
still requires all annotations at its initializer. A void arrow such as `fn(): void => ping()` evaluates its void
body once. Functions, methods, and PHP block closures follow the same rule for
`return ping();`. Exceptions propagate through the usual handlers and finalizers.
A void block closure can also use a bare return or reach its closing brace.
A capture-free void closure can supply a matching typed callback such as
`callback<void,int>`.

```text
PHP  $inc = fn(int $x): int => $x + 1;
C++  auto inc = fn(int x): int => x + 1;
JS   let inc = fn(x: int): int => x + 1;
```

Each lambda expression has a distinct concrete type. An inferred local
can invoke its lambda with positional or named arguments. Labels bind to the local
lambda’s written parameter names. Each argument and its conversion run once in
written order. Capture-free arrow lambdas and PHP block closures accept the
same trailing parameter defaults as named functions. Local calls may omit the
trailing suffix or use a label to skip a default. Passing the lambda to a typed
callback exposes its full positional signature. Every invocation through that
callback must supply every input, including those with defaults on the lambda.
Both inline lambdas and inferred locals can supply a callback argument when
the component types match exactly. The C++ backend emits a concrete capture-free lambda and
converts it to a native function pointer at the callback boundary.

A PHP block closure in an inferred local initializer can capture visible copyable
locals or parameters with `function () use ($base, $step): int { ... }`.
Each by-value capture copies its current value when the closure is created. The closure
reads those snapshots even after the outer variables change. Copying a shared handle
retains its allocation. Move-only bindings reject at the capture site. Existing
restrictions on callable storage and fixed-array placement apply. Each snapshot is
read-only. Names must be distinct and cannot repeat a closure parameter name.
In the same list, `&$name` borrows the named storage for the closure's lexical
lifetime. An alias designates its original slot. A nested borrow preserves its
source's writability. Calling the closure invalidates value facts for its writable
borrowed slots. Borrowing supports move-only storage. A writable borrow supports
the original type's `clean` operation. Whole-variable `unset` on a borrow rejects.
Concrete closure copying and escaping environments remain deferred.

PHP arrows capture referenced enclosing locals and parameters automatically by value.
Each name is captured once at creation. Parameters shadow enclosing names. A nested
arrow draws from its immediate callable, so enclosing arrows also capture any
bindings needed to create it. A block closure exposes only its explicit captures
and its own locals and parameters. Global reads retain ordinary lookup.
A PHP block closure in an instance method may borrow its receiver with `use ($this)`.
PHP arrows borrow it when their expression needs an instance, including an instance
call through `parent::`. Each intervening block closure must capture the receiver.
Members operate on the original instance. Closures retain their declaring class for
lexical access to private and protected members and for `self::` and `parent::` lookup.
A static method provides that lexical context alone. A constructor must finish
initializing required and readonly properties before creating a receiver borrow.
A borrowed `$this` is available only as a receiver. Typed callbacks accept only
capture-free callables.

### Owned callables in locals, functions, methods and properties

PHP locals may declare a fixed-arity signature, for example
`$add function<int(int)> = fn($n) => $n + $offset;`. The signature supplies
omitted component types. An exact concrete closure with copyable by-value captures
can initialize or replace the target. Borrowed environments and defaulted
parameters reject at this boundary. Mutable reference inputs such as `function<void(int&)>`
borrow the caller's storage for each invocation under the same rules as typed callbacks. The C++ representation is `std::function`.

Copying the local copies its environment under ordinary value semantics, including
shared-handle identity. A declaration without an initializer starts absent.
`isset`, conditions, `clean`, and `unset` observe or reset that state.
Invocation takes positional arguments at full arity. An argument can replace the
local for subsequent calls. The current invocation retains its selected target.
Calling absent storage throws `std::bad_function_call` after argument evaluation.

A PHP free function, static method or instance method can publish the same fixed signature as its by-value return
type. The return context supplies omitted closure annotations. For example,
`function makeAdder(int $base): function<int(int)> { return fn($n) => $base + $n; }`
returns a callable that owns its snapshot of `$base`. The same ownership and
signature checks apply when returning a concrete closure or an owned callable
local. Returning an absent local preserves absence.

A required by-value parameter on these declarations can also use
`function<R(Args...)>`. The argument receives the selected parameter's signature,
including when the caller supplies a named argument. The callee owns its copy:
resetting or replacing the parameter leaves the caller's callable available.
Invoking the parameter uses the full positional signature. Borrowed environments
and defaulted closure parameters follow the same erasure checks as owned locals.

Static redeclarations, instance overrides and interface implementations must match the
callable representation and its input and result types exactly. Named arguments use
the parameter names from the declaration selected at the call site.

An instance method can copy a field into a local and return a closure that captures
that local by value. Returning a closure that borrows `$this` fails the ownership check.

A PHP class or struct can declare an instance field such as `public function<int(int)> $run;`.
The field starts absent. Assignment supplies the expected signature and applies the same
ownership check as a local. `($object->run)(arg())` evaluates the receiver once and
selects the field’s current callable before evaluating `arg()`. Replacing the field
during argument evaluation affects subsequent calls. Calling an absent target throws
`std::bad_function_call` after argument evaluation. The spelling `$object->run(...)`
with actual arguments continues to select a method.

A static property such as `public static function<int(int)> $run;` starts absent.
Each declaring class or struct owns one slot shared across compilation units.
An assignment through `self::$run` sets the target observed by `(Handler::$run)(4)`.
Static property calls follow the same snapshot, absence and ownership rules as instance
field calls. Install the callable in executable code after declaration.

A typed vector or hash can store an owned callable, for example
`$ops vector<function<int(int)>> = [fn($n) => $n + 1];` or
`$ops hash<function<int(int)>,string> = ["add" => fn($n) => $n + 1];`.
Literals, indexed writes and appends use the element signature and its ownership checks.
An indexed call such as `$ops["add"](arg())` selects the target before evaluating
arguments. Container copies own independent entries. Keyed `isset` requires every
index on the path to exist and the final callable to be present. A missing index
skips the remaining keys and leaves the container untouched.

### Trailing literal parameter defaults

A typed value parameter may declare `= literal` in all three flavors:

```text
PHP: function result(int $value = 8): int { return $value; }
C++: int result(int value = 8) { return value; }
JS:  function result(value: int = 8): int { return value; }
```

Optional parameters must form a trailing suffix. Defaults admit the existing integer,
float, bool, string and null literal syntax, without widening literal spelling rules.
The written parameter type authorizes the same generated checked conversion as a
supplied argument; for example, `byte value = 255` is valid and `= 256` rejects.
Null defaults are admitted only where the existing conversion contract permits null;
they do not make a nonnullable type nullable. Variables, calls, named constants and
general expressions as defaults remain deferred. Types remain explicit.

Declaration preparation checks every default, including uncalled functions and
parameters always supplied by callers. Defaults never make body parameter reads
constant: explicit arguments may replace them. Calls evaluate and convert supplied
arguments left to right, then materialize omitted defaults in parameter order, once
per invocation. Each string default has independent value storage. Supplied arguments
suppress their corresponding defaults. Missing required or excess arguments reject.

Default edits/removal invalidate observing callers. The initial implementation may
conservatively invalidate callers supplying all arguments or after spelling-only
changes. It does not require per-omission dependency watches.

### Explicit mutable reference parameters

Fixed parameters may explicitly borrow whole, stable storage:

```text
PHP: function replace(int &$value): void { $value = 12; }
C++: void replace(int &value) { value = 12; }
JS:  function replace(&value: int): void { value = 12; }
```

The JS spelling is a Simple C++ flavor extension, not JavaScript/TypeScript reference
semantics. Calls use ordinary variable arguments, without call-site `&`.

A writable reference argument must name a local variable or a value/reference
parameter of the exact canonical storage type (`int` and `int64` are aliases).
This rule applies to source functions and runtime functions with writable output
parameters in all three flavors. Explicit globals, constants, properties, indexed
elements and temporaries are ineligible. Reference binding performs no conversion.

A property reference would borrow the slot inside the containing object. For
example, in `change($parent->start, $parent)`, the callee could clear or replace
`$parent` while it still has access to the first argument. Releasing the last
owner would destroy the slot. Silently retaining the old parent would instead
let the callee write into an object that the caller has already replaced. The
same rule covers inline structs, so eligibility never depends on analyzing the
receiver path or the callee. Reading and assigning a property directly remain
valid; use `$parent->start = makePoint();` to replace that field explicitly.

Assignments through the parameter modify its target using ordinary assignment
conversion rules. Forwarding preserves the same storage. Multiple parameters may
alias one target, and aliases remain live during later argument evaluation and the
call. Value arguments retain their left-to-right snapshots. Return types still denote
values; returning a referenced string produces an owning value, not an escaping borrow.

Preparation invalidates known local values after reference calls. Reference parameter
reads are conservatively unknown even after assignment, since another reference parameter may alias them. Earlier value snapshots remain valid. No callee-body analysis,
exclusive borrowing claim or general alias graph is required by this bounded slice.

Passing mode participates in signature-change invalidation. Read-only references,
reference parameter defaults, reference returns, local aliases, interior references,
variadics and escaping captures remain deferred. Value-parameter defaults continue
to work after required reference parameters. Global assignment syntax remains deferred.

### Braced conditional bodies

The active compiler now accepts nested braced `if`/`elseif`/`else if`/`else` in
entry and ordinary functions. See [conditional statements](conditional_statements_v0_2.md#php-first-compiler-delivery--2026-11-04)
for truthiness, lexical scopes and completion. Value-returning functions may return
through an exhaustive chain rather than a direct final return. Statements after a
structurally guaranteed return remain rejected. Value facts no longer cross branch
boundaries, while existing reference-call invalidation remains in effect.

## Parallel block expressions

In the PHP, C++, and JS flavors, `parallel` followed by `{` introduces a
joined batch in expression position. Ordinary calls such as `parallel()`
retain normal name lookup. The body contains one or more entries. A direct worker call ends with a
semicolon. An inline worker ends with its closing brace. The surrounding statement follows its ordinary terminator
rule. For example, with two disjoint ranges in a PHP-flavor vector:

```php
$totals = parallel {
    update(segment($rows, 0, $split));
    update(segment($rows, $split, $remaining));
};
```

This form has the same worker contracts, whole-batch admission, join and
submission-order integer results as the built-in `mt_join(...)`. A block used as
a statement still joins all workers. This slice requires the MT segment runtime
publication and fresh compilation.

An inline worker declares one or more vector grants with `read:` or `write:`. Each binding introduces a distinct worker-local parameter
whose segment element type comes from the supplied vector. The grant expressions
use the caller's scope. A job can interleave these grants with any number of
`value: $name = expression` bindings. Each accepts a numeric or boolean scalar
and creates a worker-local copy of its inferred type. The caller evaluates all
scalar inputs and two-argument segment range expressions once, in job and binding order, before borrowing any vector storage
or admitting the batch. Caller-side effects therefore precede admission even
when a grant is rejected. A suspending caller must await these inputs before the
parallel block. Only grants participate in bounds and overlap checks. The body has its own scope, can use approved constants
and checked helpers, and must return an integer on all completing paths.

```php
$totals = parallel {
    job (write: $prices = segment($items, 0, $count),
         write: $fees = segment($charges, 0, $count),
         value: $adjustment = $daily_adjustment) {
        $total = 0;
        $i = 0;
        while ($i < count($prices)) {
            $prices[$i] = $prices[$i] + $fees[$i] + $adjustment;
            $fees[$i] = 0;
            $total += $prices[$i];
            $i += 1;
        }
        return $total;
    }
};
```

Inline and named entries share batch admission, submission-order results, and
the joined lifetime. Two readers may overlap on the same vector. Any nonempty
overlap involving a writer rejects the whole batch before dispatch, including
overlaps within one job. Empty ranges claim no slots.

A `read:` binding borrows its source slots without copying. Its worker can index
and count the segment and forward it to a helper taking `read_segment<T>`.
The compiler checks this contract in the helper body, including writes to fields
of indexed records. A writable `segment<T>` may also be passed to a reading
helper. A named worker taking `read_segment<T>` requests a read grant when
launched with `segment(vector, offset, length)`. Both modes require isolated
element types. A job consisting entirely of readers is valid. C++ and JS flavors use bare binding names in the same clauses.

### Hash segments

For a local `hash<V,K>`, `segment(table, offset, length)` grants consecutive live
entries in insertion order. The same rule applies to `segment(table, range)`.
Deleting a key removes it from the count, replacing its value preserves its
position, and reinserting a deleted key places it last. For example, deleting
`b` from `[a,b,c,d]` and reinserting it gives `[a,c,d,b]`. Offset 1 and length
2 grant `c,d`. Offset equal to the live count is valid for an empty segment.

Use `foreach ($slice as $key => $value)` to visit the grant. The key is always
read-only. With `write:`, assigning to the loop value or one of its record
fields updates that entry in place. With `read:`, both bindings are read-only.
Named helpers take `hash_segment<V,K>` or `read_hash_segment<V,K>`. Keys may
be integers or strings, and values must satisfy the vector segment isolation
rules. The grant permits forward traversal and counting. The hash structure
stays fixed until join. Admission compares logical intervals on the same
backing hash across the entire batch, including aliases. Readers may overlap.
Any nonempty overlap involving a writer rejects the batch before dispatch.

### Balanced vector partitions

With the MT segment runtime enabled, `balanced_range(length, parts, part)`
returns a `segment_range` value with two read-only integer fields, `offset` and
`length`. All three arguments are integers. The helper requires `length >= 0`,
`parts > 0`, and a zero-based `part` less than `parts`. Invalid inputs raise a
runtime error. The first `length % parts` partitions receive one extra slot.
For 10 slots and 3 partitions the results are (0, 4), (4, 3), and (7, 3).
When partitions outnumber slots, trailing partitions are empty at the end.

A range can be copied, passed to a helper, or returned. It contains no vector
reference. Both named and inline jobs accept `segment(vector, range)`; each
grant is checked against its actual vector at admission. For example:

```php
$range = balanced_range(count($input), $parts, $part);
$results = parallel {
    job (read: $src = segment($input, $range),
         write: $dst = segment($output, $range)) {
        $i = 0;
        while ($i < count($src)) {
            $dst[$i] = $src[$i];
            $i += 1;
        }
        return $i;
    }
};
```

### Runtime-sized parallel batches

In brace flavors, `parallel (workers: limit) { ... }` takes a positive integer
limit evaluated once before any entry. The cap includes the caller. A cap of one
runs the admitted jobs on the caller. Omitting the setting uses available pool
capacity.

An inline job can begin with `each: $part in 0 ..< $parts`. The caller evaluates
the nonnegative integer bound once when it reaches the entry. For each index
in ascending order, it evaluates the bindings once in written order. The index
is a read-only integer visible to those bindings and to that job's body.
A zero bound skips the entry and its bindings. Other entries still run;
a group that expands to zero jobs returns an empty result vector.

```php
$results = parallel (workers: 4) {
    job (each: $part in 0 ..< $parts,
         write: $rows = segment($headers, balanced_range($count, $parts, $part))) {
        return scan_headers($rows);
    }
};
```

The compiler checks the body once. The caller completes all snapshots before
borrowing storage, then admits all grants in the expanded group before dispatch.
A conflict in the final iteration rejects the entire batch even under a cap of
one. Results follow entry order and, within a repeated entry, index order.

A repeated entry may put `workers: limit` immediately after `each:`. The caller
evaluates the bound and then the limit once, before binding any iterations,
including when the bound is zero. The limit must be a positive integer and uses
caller scope. It limits concurrent instances of this written entry. Two entries
calling the same function have independent limits.

For example, with twelve partitions this group can run three scans alongside
both sibling calls when five participants are available:

```php
$results = parallel (workers: 5) {
    job (each: $part in 0 ..< $parts, workers: 3,
         write: $rows = segment($headers, balanced_range($count, $parts, $part))) {
        return scan_headers($rows);
    }
    check_symbols(segment($symbols, 0, $symbol_count));
    check_routes(segment($routes, 0, $route_count));
};
```

The scheduler divides a capped entry into at most its limit of balanced,
contiguous chunks. Each chunk runs its iterations sequentially. Fewer pool
participants can run several chunks in sequence. Batch admission still checks
all grants together, including grants in the same chunk. Results and worker
failures retain their original iteration slots.
