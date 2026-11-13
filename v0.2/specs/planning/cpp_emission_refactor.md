# C++ emission audit and refactor plan

Doc Status: planning

Audited 2026-11-09 against `2148ee34a` plus the working-tree null alias change. Scope covers application C++ emission for the shared cpp/php/js compiler, from prepared expressions through generated type handlers, unit headers and bundle publication. The PHP probes below use the current LP64 publication. The native runtime implementation, build driver and historical S2S generators are outside the refactor.

## Decision

Start with the simplest C++ operation implementing the approved source operation. For each cast, retained value, sequencing statement or lambda, identify the requirement at that use site. A checked helper call can itself be the simplest operation. When C++ statements already provide the required order, use their sequencing. Keep temporary storage in the smallest scope that preserves lifetime, conditional evaluation and execution frequency. An immediately invoked lambda is appropriate when an expression genuinely needs local statements there.

A **staged refactor of the C++ backend and its generated handlers is warranted**. The current compiler already emits many operations directly. The structural problem is that operand evaluation, result spelling and placement are decided by different routes for closely related expressions. The existing lowering inventory and catalog remain useful for tracking individual forms. This note proposes the migration order for the broader work. [Earlier inventory](native_cpp_lowering.md) · [Catalog](../../compiler/docs/php_lowering_catalog.md) · [Evaluation contract](../../../specs/expression_evaluation.md)

## Evidence from current emission

- At the time of the audit, `$a + $b` emitted an addition for plain integer locals, while `$a + $a` emitted a lambda and two snapshots. The same-spelling guard protects self-comparisons from a Clang warning and also catches addition. Warning handling belongs to the operation that can trigger the warning.
- At the time of the audit, for `function greet(string $s = "hi"): void`, `greet()` passes the constructed string directly. `greet("hi")` emits a lambda, a named string and an rvalue cast. Preparation knows the destination and passing mode for both. The call emitter also builds temporary-binding text while iterating general arguments, even when it ultimately returns the direct call. Emitted bytes and PHP lowering cost are separate measurements.
- The integer three-way formula reads each operand twice and returns C++ `int` before its explicit result cast. Two independent decisions follow. A call operand needs one evaluation. A typed `scpp::int64` initializer can supply the final widening. An enclosing overload or arithmetic operation may require the exact result type earlier.
- A small PHP input measured 145 source bytes, 1,048 bytes for its implementation unit, 122 bytes for the separate entry adapter, no application header and two immediately invoked lambdas. Input was the following single line, without a trailing newline. Counts include generated includes, prototypes and formatting. This is a reproducible shape probe; compile-time impact remains unmeasured.

```php
function greet(string $s = "hi"): void { echo $s; } $a int = 6; $b int = 2; $x = $a + $b; $y = $a + $a; $a += $b; greet(); greet("hi"); return 8;
```

### Correctness findings from the audit

Both fixes are now implemented. Compound initializers use statement-local snapshots and compound updates keep their own destination type. Global definitions request their declared type’s provider directly. The reproducers below describe the original failures. [Loop regression](../../compiler/tests/mixed_compounds.php) · [Global provider regression](../../tools/api_generator/tests/submodules.py)

1. **One loop temporary serves two different bindings.** When both the initializer and update of a `for` are compound assignments, the emitter declares one `compound_previous` using the update destination's type. In the example below the initializer copies `0.5` into an integer before adding `0.5`. Clang compiled the emitted program and it returned 9. The source requires 8, even though the loop body never executes. Each operation needs storage of its own required type if it needs storage at all. Verify the retained-snapshot path as well as a direct spelling. [Loop emission](../../compiler/05_backend/cpp/emit.php)
2. **An unobserved global definition can omit its provider include.** Use two PHP units, `main` containing `return 8;` and `lib` containing only `global $n null = null;`. The secondary implementation emitted `constinit scpp::null_t var_n = nullptr;` without an include. Isolated Clang compilation failed with an undeclared `scpp`. The definition collects conversion headers and can receive a project header through an observer, but its own declared type also needs its provider. Fix that use site and test with and without an external observer and with a relocated provider. The include-selection graph itself already resolves the provider. [Unit emission](../../compiler/05_backend/cpp/emit.php) · [Header planner](../../compiler/05_backend/cpp/headers.php)

```php
$a int = 500;
$f float = 0.5;
for ($f += 0.5; $a < 500; $a += 1) {}
if ($f === 1.0) { return 8; }
return 9;
```

## Audit by emission path

| Path | Current decision | Refactor or constraint |
| --- | --- | --- |
| Numeric, keyword and byte-string literals; constant and storage reads | Generated handlers preserve literal bits/bytes. Constants inline their prepared initializer. Typed integer declarations have a separate plain-literal path. | Reuse literal spelling by destination. Protect signed minimum, negative zero, embedded NUL and exact-overload suffixes. A known result alone cannot justify dropping evaluation. |
| Local/global initialization, assignment, explicit casts, function and entry returns | A prepared conversion selects identity, native conversion, checked helper, extraction, boxing or construction. Entry has its own exit-range policy. A child expression usually renders before it knows its destination. | Supply the use at the producer/consumer boundary. Drop only conversions whose native behavior, range and type match there. Preserve global constant initialization and entry checks. |
| Unary, promoted binary, bitwise, shifts, power, equality and three-way | Execution type and conversions are settled. Generated bindings choose native operators, formulas or helpers. The emitter combines subtree reorderability, text equality and a separate generated direct-spelling predicate. | Separate single evaluation, repeatability, actual native result and observable effects. Scope warning workarounds to affected operators. Keep runtime checks and grouping. A call to a throwing helper may accept direct operands. |
| Conditions, negation, lazy logicals, eager XOR | Conditions use type truth handlers; some strip outer parentheses by slicing emitted text using the safety flag. Lazy operands use native short circuiting; eager XOR binds both conditions inside a lambda. | Give grouping its own owner. Keep skipped RHS expressions skipped. Evaluate eager operands once and avoid unsequenced conflicting accesses under native C++ rules. |
| Compound writes, standalone updates, branches, loops and transfers | Updates and control statements mostly emit directly. Compounds always save the old destination, perform binary lowering and write back. Statement and loop-header callers arrange storage differently. | First common decision for native compound spelling. Preserve a prior read or conversion when RHS mutation or failure can expose it. Loop tests and updates must execute on their original iterations, including after continue. |
| Source calls, defaults and references; runtime calls and collection observations | Runtime functions and count/empty have direct bindings. A source call chooses between direct arguments and a sequence of bindings. Single reference and omitted string literal have special cases. | Apply the same parameter requirement to explicit/default argument expressions. Preserve reference identity, required value copies, moves/lifetimes, argument conversions and call-specific order. |
| String construction, concatenation, comparison and echo | Literal echo emits bytes and length. Echo emits a separate statement per item. Computed string output retains the operand for data and size. Generated string rules admit selected direct binary forms. | Preserve complete output of one item before evaluating the next. Reuse a stable string when both observations are safe; evaluate a producer once and keep its storage alive through the write. |
| Typed vector/hash construction, read, append, replacement and cardinality | Vector set consumes exact-argument lowering. Vector append/read and hash bindings have fixed casts. Generic keyed writes snapshot key and value. Observations already emit a single member call. | Use published parameter identities at each member boundary. Retain bounds errors, ownership, exact overload selection and the applicable key/value/store contract. Test optional native adapters and relocated providers. |
| Carriers and boxed values | Presence can directly call a member. Truthiness can need presence and payload from the same live object. Conversions carry extraction/wrap/sentinel facts. Boxed operations dispatch through checked runtime helpers; selected dynamic expressions have their own syntax-shaped direct rule. | Keep state distinctions, tag checks and once-only observation. Encapsulate family-specific runtime semantics in generated/type handlers; share placement and operand-use rules with ordinary expressions. |
| Declarations, signatures, includes, naming, units and publication | Per-unit dependency collection, external observers, sorted includes, deterministic names, reused bodies, and the entry adapter already have owners. The global definition omission above is a missing type use. | Keep these boundaries. Check implementation and published header separately. Changing a spelling can affect includes and cache reuse even when source admission stays the same. |

Implementation references: [statement and expression emitter](../../compiler/05_backend/cpp/emit.php), [output](../../compiler/05_backend/cpp/output.php), [literal bytes](../../compiler/05_backend/cpp/strings.php), [conversion transport](../../compiler/types/definition.php), [operator and conversion generation](../../tools/api_generator/php/render_compiler.php), [boxed generation](../../tools/api_generator/php/boxed_dispatch.php), [vector](../../tools/api_generator/templates/vector_type.php.tpl), [hash](../../tools/api_generator/templates/hash_type.php.tpl), [carrier](../../tools/api_generator/templates/carrier_type.php.tpl), [unit assembly](../../compiler/05_backend/cpp/program.php), [bundle reuse](../../compiler/05_backend/cpp/bundle.php).

## What preparation needs to know

| Question | Existing owner and proposed action |
| --- | --- |
| Which operation is legal? What are its source result, execution type, operand conversions and write-back? Which declaration does a name reference? | Shared preparation already settles these questions, including compound assignment. Consume the existing facts. Keep source diagnostics, range proofs, reference binding and incremental watches here. [Preparation](../../compiler/04_analyze/prepare/prepare.php) · [Prepared compound](../../compiler/03_parse/structures/common.php) |
| Which native type implements this export on this target? What are the helper signature, parameter modes and provider? | Runtime metadata, target probe and API generator. Generate the answers once. The same source identity can use different native integer aliases on different targets. [Generator](../../tools/api_generator/php/render_compiler.php) · [Signed integer binding](../../tools/api_generator/templates/signed_integer.php.tpl) |
| What C++ type does this expression actually produce before normalization? Does the consumer need exact identity, ordinary initialization, an lvalue, or a discarded value? | C++ backend and generated binding. Source result type alone cannot answer this for promoted arithmetic, comparison differences or suffixed literals. Pass the use down or return a small transient native-result description where two real consumers need it. Preserve intermediate types in nested arithmetic. |
| Must an operand run once, be retained, be ordered, or remain conditional? | Combine the operation's operand uses with the enclosing source contract and existing AST/declaration facts during emission. A plain local read is repeatable only across an interval that cannot change it. Unknown calls may mutate or throw. Prefer a local snapshot to new interprocedural analysis. |

**Preparation decision:** the audit found no missing retained preparation fact for the two defects or the first migration. Avoid new AST fields, target-specific cast flags, a second traversal, runtime JSON queries, or a general effects pass. Reopen preparation only with a valid pair of programs that require different source behavior and cannot be distinguished by settled declarations, types, conversions, passing modes and existing constant facts. Document the owner, invalidation rule and fresh/incremental equivalence test before retaining a new fact.

## Proposed implementation sequence

1. **Correctness and baseline.** Add regressions for the two reproducers and fix them independently. Before changing emitted shapes, record representative source bytes, implementation/header/adapter bytes, emitted lambda/temporary counts and compilation measurements. Keep the small probe above alongside a larger existing [capability workload](../../samples/capability-benchmarks/benchmark.py). Run catalog comparison against fresh emission.
2. **Compound assignments as the first vertical slice.** Generate native compound eligibility from the admitted operation and representation. Share the decision between statement and loop-header emission. Verify local plus local/literal, aliased reference RHS that mutates the destination, checked division by zero, mixed write-back and loop continue. Unknown interactions keep their valid sequencing. Extend to another admitted native operation using the same rule before generalizing it.
3. **Native results and destinations.** Start with three-way results in typed initialization versus exact arguments, then use vector set as the reference for vector read/append and typed hash read/write in the collection slice below. Verify values and overload choice on long/long-long target facts, small-integer promotion, literal boundaries, nested arithmetic and checked conversion failure. Remove displaced special cases as each consumer moves.
4. **Operands, calls and placement.** Separate the need for a snapshot from the form used to express it. Compare self-addition with self-comparison, explicit with default string arguments, and single with multiple reference/value arguments. Verify that calls in three-way operands run exactly once, that lazy RHS effects stay conditional and that ordered echo can make partial progress before a later failure. Review boxed collection arguments together after the typed collection slice. Carrier placement remains separate.
5. **Consolidate and measure.** Retire obsolete direct-spelling branches and stale shape assertions after their replacements pass. Distinguish source-contract tests from tests that deliberately pin current backend behavior, including suppression of a later call on an eager failure. Review any change to those expectations explicitly. Check fresh versus incremental emit/build output after source, signature and provider edits. Keep the catalog and lowering guide aligned with the generated sources.

For each slice, run focused emission and native behavior tests across cpp/php/js, compile warning-sensitive cases with the supported flags, and rerun the catalog's two-column check. Measure PHP preparation and emission separately from native compilation. Compare matched clean builds per toolchain with fixed input, publication, flags, cache/PCH settings and parallelism; report repeated timings and dispersion for Clang and GCC where available. Keep link time separate. Historical 3–10× expansion motivates this work; the ratio for a tiny program includes fixed scaffolding and cannot predict build time. [Catalog check](../../compiler/tests/lowering_catalog.php) · [Direct-lowering tests](../../compiler/tests/direct_lowering.php) · [Incremental native checks](../../compiler/tests/incremental_executable_native.py)

The first compound slice now emits native `+=`, `-=`, `*=`, `&=`, `|=` and `^=` for plain signed 64-bit locals with a local or integer literal RHS and three identity conversions. Statements and both loop clauses share the decision. The all-flavor compound fixtures check the native spelling, `continue` reaching the update, and a reference-mutating call producing 13 for addition, 7 for subtraction and 30 for multiplication from the original 10. The bitwise fixtures exercise both local and literal operands and verify that a mutating call returning 3 produces 2, 11 and 9 for AND, OR and XOR respectively from the saved 10. Checked compound operators and broader RHS analysis remain deferred.

## Collection argument-lowering audit

The working-tree vector `set` binding already passes each argument’s prepared expression, conversion and emitted operand to the exact-argument renderer. For integer `<=>`, the producer can widen a comparison result once at an exact parameter boundary. For ordinary integer arithmetic, the emitted expression can still have a different C++ type from the language result. Use vector `set` as the reference for the next two slices. [Vector handler](../../tools/api_generator/templates/vector_type.php.tpl) · [Exact integer arguments](../../tools/api_generator/templates/signed_integer.php.tpl)

| Receiver and published binding | Emission at the time of the audit and obligations |
| --- | --- |
| `vector<T>` with `at(int64)`, `append(T)` and `set(int64, T)` for integer or string elements | Reads and append still wrap operands in fixed casts. The emitter already converts the appended value. Select the canonical overload even when native conveniences such as `int` indices are disabled. The runtime checks read/replacement bounds and returns an owned element on read. |
| `hash<V,K>` with `at(const K&)` and `set(K, V)` for all four integer/string pairs | Keys must have the declared type. String keys retain all bytes, including numeric-looking text and embedded NUL. A read currently casts even a string local that could bind directly to `const K&`. A write snapshots its key, converts and snapshots its value, then casts both at the call. Reads return copies and throw on absence. Writes insert or replace. |
| `mixed` and `dynamic` with `source_read` and `source_set` | Both admit integer or string keys at compile time. `boxed_key` checks the nonnegative 31-bit integer range and classifies string bytes. The result selects the integer or boxed-text-key overload. Writes take a same-family box by const reference. Admitted scalars go through that family’s boxing factory. Some factories currently cast an already-exact native input. |

Publication supplies the member, parameter types, passing modes and provider. Preparation owns source admission and the value conversion. The emitter should supply the original prepared operand to the generated binding, apply the conversion once and add native normalization only when the canonical parameter requires it. Exactness can remove a cast while a runtime adapter, an owned copy or a snapshot is still necessary. Use existing prepared facts and generated type handlers. [Vector publication](../../tools/api_generator/php/render_compiler_vectors.php) · [Hash publication](../../tools/api_generator/php/render_compiler_hashes.php) · [Preparation](../../compiler/04_analyze/prepare/prepare.php) · [Ordered keyed stores](../../compiler/types/definition.php)

### First collection slice — vector read/append and typed hash read/write

- Give the vector read and append handlers access to the prepared operand and its emitted C++ at the parameter boundary. For append, route the existing element conversion through that boundary exactly once. Preserve conversion-provider collection. For a read, apply the admitted index identity to the published count parameter.
- Use the hash specialization’s key and value types independently. Bind an exact key directly on read. On write, pass both exact arguments to `set` in native argument order.
- Limit the slice to existing source operations. Vector replacement is the regression baseline. String indexing and boxed indexing share compiler interfaces and retain their existing behavior. New collection methods, hash append, traversal and general sequencing changes belong to other work. [Typed collection feature plan](typed_collections.md)

**Verification checklist for the first slice**

- [x] Across cpp/php/js, inspect emitted C++ and run native cases for both vector specializations and all four `hash<V,K>` pairs. Cover exact local arguments, a target-native integer literal, an integer `<=>` result and arithmetic whose native type still needs a cast. Verify canonical overload selection with optional vector adapters disabled and with `long` and `long long` target facts.
- [x] Verify valid vector reads and owned string results, negative and past-end read failures, and append conversions. A failed checked conversion or carrier extraction must leave the vector’s size and prior elements intact.
- [x] Verify hash insert, replace, copied reads and a missing-key read that throws without inserting. Exercise strict key diagnostics, embedded NUL and distinct numeric-looking string keys. Verify that an RHS call changing the key local writes under a key allowed by native argument evaluation order, and that a failed RHS conversion neither inserts nor replaces. Check single evaluation with observable calls.
- [x] Compile emitted units with relocated vector/hash providers and required conversion headers. Check fresh versus incremental output after an operand type change and recovery. Rerun vector replacement, string and boxed indexing regressions, then update the lowering catalog and verify both C++ columns against fresh emission. [Vector tests](../../compiler/tests/vector_operations.php) · [Hash tests](../../compiler/tests/hash_operations.php) · [Catalog check](../../compiler/tests/lowering_catalog.php)

The typed slice is implemented. Preparation checks the append capability and the emitter
supplies each original argument and its prepared conversion to the binding. Typed hash
writes normalize the key and value at the two by-value parameter boundaries. No new retained analysis facts
were needed.

Native Clang fixtures passed for cpp/php/js on the host LP64 publication, including failed
append overflow and empty-carrier extraction against a populated vector, a missing hash read,
and failed hash insertion and replacement. The relocation proof exercises reads and writes
with the collection and conversion providers moved and the optional vector adapters disabled.
The repeatable `collection_argument_targets` CTest compiles emitted units for all three
flavors against canonical-only headers for Linux x86-64 (`int64 = long`) and Darwin
x86-64 (`int64 = long long`). It models the two native identities, checks each against
Clang, and checks the literal suffix and normalization of an arithmetic key on each target.
The host native tests pass checked division and remainder directly into collection
arguments using their validated return types. Both catalog columns matched fresh emission.
Vector and hash provider-export fixtures check the configure-time inventory against
the prepared provider graph and string adapters, including reduced publications.
[Target test](../../tools/api_generator/tests/collection_arguments.py) · [Relocation proof](../../tools/api_generator/tests/submodules.py)

### Second collection slice — mixed and dynamic

Both generated family bindings pass the prepared scalar expression to the shared exact-argument
renderer for the factory parameter. It gives integer literals the target suffix, passes an
exact local or an already-normalized comparison directly, and widens a byte for the integer
factory. A same-family value uses the existing identity conversion. Boxed writes pass the
converted RHS and validated key directly to `source_set` in native C++ argument order.

The cpp/php/js fixtures exercise both copy policies, null and missing-key reads, writes to
null, scalar-receiver errors and rejected cross-family stores. A successful effectful write
evaluates both arguments once. Invalid keys prevent the store, and the RHS may have run.
Explicit conversions that already produce int64 pass straight to the factory, including
checked float conversion and boxed extraction. An identity cast around an ordinary literal
still needs the target's overload rules. The target test compiles these argument boundaries
for both native integer layouts.
[Boxed regressions](../../compiler/tests/boxed_scalars.php) · [Factory generation](../../tools/api_generator/php/boxed_dispatch.php) · [Target test](../../tools/api_generator/tests/collection_arguments.py)

## Single by-value source parameter

A source call with one by-value parameter now applies the supplied argument's prepared
conversion, or the declaration's prepared default conversion, directly inside the call.
The callee's parameter owns its copy. The cpp/php/js fixtures cover explicit and default
strings, caller-visible copy independence, and a checked byte conversion whose producer
runs once and whose failure suppresses the callee. A byte parameter with default 7
exercises both conversions on the same declaration. After a library default changes
from 8 to 123, and again after removal and restoration to 8, the published incremental
caller matches fresh emission byte for byte. Typed-initialization spelling and
multiple-parameter calls remain deferred. [Direct-call regressions](../../compiler/tests/direct_lowering.php) · [Defaults](../../compiler/tests/parameter_defaults.php)

## Repeated integer operands

The generated operation uses its native operator spelling to decide whether equal operands
need warning protection. Safe nested sums compose directly. Self-comparisons and equal
XOR operands keep their warning-safe snapshots. The cpp/php/js fixtures cover repeated
nested sums, byte narrowing before widening and two calls returning successive counter values.

## Native operand order audit at `f2221fa6a`

The November eager-order amendment allows either operand to run first, including when one throws. It does not change the contract of a source call, compound update, lazy expression or output statement. The exact-build probe exercises integer divide and remainder call arguments. Its LR/RL trace cannot select a globally valid ordering for other expressions. [Evaluation contract](../../../specs/expression_evaluation.md) · [Agent guidance](../../../AGENTS.md)

### Reproduced through the PHP-flavor frontend and C++ emitter

| Source fragment | Current emission, abbreviated | Simplification and owner |
| --- | --- | --- |
| `$x = (8 / 2) + 1;` | Lambda with two snapshots around `int64_divide(8LL, 2LL) + 1LL` | The right literal has no effects and the parent consumes each value once. Emit the addition directly. Keep the divide classified as potentially throwing in an ordered context. The binary emitter currently uses the child reorderability flag to select the parent spelling. |
| `$a / $b` with two float locals | Lambda, two scalar copies, then `float64_divide(binary_left, binary_right)` | Pass the two locals directly. The published helper takes two values and owns the zero check. |
| `f() ** f()` with an int return | Lambda with one call per snapshot feeding `int64_power` | Emit `scpp::int64_power(f_(), f_())`. Each argument initializes a separate scalar parameter once. Either call can supply the first observed effect. The same reasoning applies to checked shifts with scalar value bindings. |
| `($a xor $b)` with distinct bool locals | Lambda with two bool snapshots and a final inequality | Emit `(a != b)` for these operands. The eager logical branch owns the combine and the type handler owns truth conversion. Preserve grouping of numeric truth tests, single evaluation and warning-safe self-comparisons. |
| `$a /= $b;` with two int locals | Block containing `compound_previous = a` then `a = int64_divide(compound_previous, b)` | Emit `a = scpp::int64_divide(a, b);`. The compound emitter already bypasses the temporary for a simple field and a simple RHS. Apply the old-value requirement to local storage as well. Preserve the snapshot when the RHS can change the destination. |
| Forwarding a single exact reference parameter to `f($b)` | Lambda binds another C++ reference to the parameter before calling `f_` | Emit `f_(b)`. Preparation already admits exact named parameters and locals as stable reference arguments. The direct call shortcut tests the concrete local-declaration class. |

Owners are [the emitter](../../compiler/05_backend/cpp/emit.php), [scalar operation generation](../../tools/api_generator/php/render_compiler.php), [boxed operation generation](../../tools/api_generator/php/boxed_dispatch.php) and [reference binding in preparation](../../compiler/04_analyze/prepare/prepare.php).

### Boundaries inspected

- For scalar binaries the generator distinguishes zero-checked integer division/remainder from shift/power and float division even though these helper parameters all receive values. The emitter also gates its direct-operation route on identity conversions. A prepared widening can stay attached to its argument without requiring a lambda. Preserve the actual parameter/result types, conversions, helper include and operator checks. Do not extend the scalar rule to borrowed references without examining value and lifetime semantics.
- Native infix operators need their own check that the emitted C++ has defined evaluation. For the first pass a literal beside a checked scalar subtree is enough to demonstrate the distinction. Identical C++ spellings can require warning-safe grouping or names. Three-way formulas repeat their operands and need single-evaluation protection for calls.
- String and boxed generators still recognize particular operand shapes. Two stable string locals and a boxed local plus a side-effect-free scalar are candidates for direct forms after checking the published signature. Aliasing a borrowed object across a sibling mutation can change what the helper observes. Shared-owner equality with an effectful null expression also has explicit sequencing in the owner-first branch. Preserve the null expression's effects and single evaluation when reviewing that case. [Struct type handler](../../compiler/types/struct.php)
- Inline instance calls always construct a receiver binding and lambda, even for a zero-argument method on an inline local. That case can pass the local to the generated function directly. Shared receivers require closer inspection, including a method body that can release the last external owner. Effectful source function, constructor and runtime arguments retain their ordered boundaries. The current all-argument snapshot fallback may be narrowed when only one argument needs stabilizing. Treat copies, checked conversions and reference binding as separate requirements. [Instance method contract](../../../specs/instance_methods_v0_2.md)
- Vector replacement, typed hash writes and mixed/dynamic keyed writes already put the key and value in the native call. Preserve exact argument conversion, boxing and runtime validation. A string byte write has a different contract requiring the key before the RHS. A primitive key and value could avoid temporary storage when the order cannot be observed. [String byte indexing](../../../specs/index_operator.md)
- Ordered echo items, lazy RHS evaluation, ordered string appends, once-only carrier presence checks, and compound destination retention each have an independent reason for their current structure. The probe supplies no basis to remove them. Plain field assignment emits native assignment, whose RHS precedes its LHS. Recheck destination selection and failure timing before extending this shape to indexed shared receivers; preparation currently rejects such write roots.

### Suggested implementation and verification order

1. Batch scalar helpers whose binding consumes both operands by value once. Generate the direct-call decision from the validated lowering/binding strategy. Start with float division, shifts, power and proven scalar widenings. Keep the existing prepared types and conversions. Confirm each operand runs once on success, invalid divisors/counts retain their errors, and an argument failure skips the helper. Accept either permitted sibling trace.
2. Separate permission to emit a direct eager expression from whether that expression may move in an ordered parent. Start with the checked subtree plus literal and distinct boolean locals above. Exercise nested precedence, warnings-as-errors, repeated calls and both orders allowed by the source. Keep short-circuit tests strict.
3. Simplify stable compound destinations and exact reference forwarding. Cover reference parameters as well as locals, checked write-back, a mutating RHS and loop updates. Handle inline receivers separately with a retained-owner control case.
4. Update expectations alongside each change. The direct-lowering tests pin snapshots for nested division, promotion and float division. Arithmetic tests require a failing left subtree of addition to suppress a sibling call; logical tests require one eager xor trace. Split combined fixtures so that source-call ordering and eager-operator ordering have independent oracles. Where both eager operands can fail, accept either permitted failure. Keep exact once-only and failure-before-mutation assertions where the language requires them. [Direct lowering tests](../../compiler/tests/direct_lowering.php) · [Arithmetic fixtures](../../compiler/tests/arithmetic.php) · [Logical fixtures](../../compiler/tests/logical.php)

Verification should compare emitted C++ bytes, temporary declarations and lambda counts for fixed representative inputs across cpp/php/js. Measure native compile time separately on identical units, toolchain and flags before claiming a speedup.

At the audited revision the first five table rows produced four immediately invoked lambdas and nine operand/old-value temporaries in each of cpp, php and js. The reference-forwarding example was separately emitted in PHP flavor. The ten selected CTests passed (direct lowering, logic, integer compounds, their native tests, argument order and the three native struct suites). Running `python3 -B v0.2/compiler/tests/arithmetic_argument_order.py --cxx clang++ --cxx g++` also passed. At `-O2`, Clang 18.1.3 observed LR and GCC 13.3.0 observed RL for integer divide/remainder on success and zero-divisor failure, across all three flavors.

### Execution backlog

- [x] Generate direct scalar value-helper calls for float division, shifts, power and admitted promotions. Check helpers, exact evaluation count, permitted failure traces and published includes.
- [x] Emit checked scalar expressions beside integer literals directly where the native operation permits it. Simplify eager xor on distinct boolean locals. Check grouping, warnings and lazy parents.
- [x] Remove redundant stable-local compound snapshots and forward stable exact reference parameters directly. Exercise checked write-back, loops, destination-changing calls and updates through forwarded references.
- [x] Verify cpp/php/js emission and native behavior on Clang and GCC, refresh the lowering catalog, and record before/after bytes, lambdas and temporaries for the same inputs.

Shared receivers, broader multi-argument calls and borrowed boxed/string bindings remain separate work.

### Measured emission and native verification

For each flavor, the five audit inputs used an entry target of 31, a trailing
`return 8;` and the default LP64 publication. The nested addition uses 8, 2
and 1. The xor locals are true and false, the compound and float division use
6 and 2, and both power operands call the same function returning 2. Totals
include the full five strings returned by `Cpp_Emitter::emit`.

| Per flavor (cpp, php and js) | Audited baseline | Implemented |
| --- | ---: | ---: |
| Emitted C++ bytes | 2,562 | 2,051 |
| Immediately invoked sequencing lambdas | 4 | 0 |
| Named operand or previous-value temporaries | 9 | 0 |

The dual-compiler regression compiled 13 generated cases per flavor on Clang
18.1.3 and GCC 13.3.0 with `-O2 -Wall -Wextra -Werror`, using the existing
Clang/libc++ runtime archive. The helper cases produced LR on Clang and RL on
GCC. A remainder failure inside a shift argument preserved its error on both;
the sibling ran on Clang and was skipped on GCC. Both also passed the grouped
xor, checked subtree with literal, checked compound update and forwarded
reference cases. This measures generated source size. A native compile-time
comparison remains separate.

All 26 focused lowerer and native CTests passed in the parallel rerun. Additional checks passed for generator publication, the 53 catalog rows, struct properties, boxed scalars, numeric families, integer promotion and the compiler argument-order runner. The expanded native direct-lowering test took 67 seconds under parallel load and 48 seconds alone; its CTest timeout is now 120 seconds.

### Follow-up batch — zero-parameter methods on inline storage

The existing receiver walk identifies whether a field path ends at an inline
local, parameter or borrowed `this`. For a method declaring zero parameters,
the C++ emitter passes that place directly to the method's record-reference
parameter. A call with omitted defaults still follows argument lowering.
Crossing a shared allocation still pins its owner. A method on a temporary
inline record still binds the result once so it can pass a non-const lvalue.

The method regression updates a local through `this`, an inline field and a
field inside a shared owner, then calls a mutator on a temporary. All three
source flavors must print `211`. The fixtures check the direct and retained C++
spellings and compare incremental output with fresh compilation. A separate
Clang/GCC check compiled and ran local, nested-inline and shared-field updates
from each flavor at `-O2 -Wall -Wextra -Werror` and observed `111` in all six runs.

### Follow-up batch — stable string operands

Generated string handlers select direct C++ for the published concatenation
and single-comparison strategies when each operand is a string literal or a
named string local or parameter. This includes same-string strict comparisons.
The RHS of concatenation is borrowed and the left parameter owns its copy.
Comparison borrows both operands. Aliased parameters can therefore read the
same storage directly. A call operand can change that storage, so it continues
through the existing snapshot path. The three-way comparison formula and
cross-type identity keep their existing lowering.

The source-shape checks cover concatenation and all eight single comparisons
in cpp/php/js. Native fixtures cover embedded NUL, equal storage, two references
to one heap-backed string, and a sibling call that replaces the source string.
Clang and GCC ran the string literal, alias and mutation cases in all three
flavors with warnings as errors. The lowering catalog includes a verified
two-local concatenation and ordering example.

### Follow-up batch — stable mixed and dynamic operands

Both generated boxed handlers use the prepared pair's published helper directly
when each operand is a named local, parameter or literal. The same operand test
serves string lowering. Boxed addition and the four ordering operators retain
their runtime kind dispatch, including when the same box appears twice or its
current payload is a container. A nested expression or call stays on the
snapshot path. The emitter adds no preparation facts.

All three flavors exercise box/box, box/scalar and scalar/box combinations.
A sibling that replaces the box is called once. With the box on the left the
addition uses its saved value; with the call on the left the addition reads
the updated box. The dual-compiler check ran stable, mutating and invalid-kind
fixtures for both families in all flavors (36 runs total). The catalog checks
both C++ columns for 56 examples. The four original lots remain complete;
their five-input size baseline is separate from this follow-up.

### Follow-up batch — one order-sensitive argument in a free function call

For free functions with by-value parameters, the ordinary argument loop counts
arguments whose evaluation could matter to a sibling. A scalar literal with a
trivial destination and a nonthrowing prepared scalar conversion needs no
sequencing. The full conversion code is checked, so a checked narrowing or a
carrier extraction cannot qualify as such a literal. At most one remaining
argument can go directly into the call alongside those literals. A local read
is counted because an effectful sibling may change its storage. The previous
direct path still covers multiple independent, trivial identity operands.

The three source flavors cover literal peers on either side, a call between
a proven byte literal and an integer literal, and an omitted literal default.
The controls exercise a mutating sibling, two calls with ordered output, and
checked narrowing with both a literal peer and an effectful peer. On Clang
18.1.3 and GCC 13.3.0 all four fixture groups produced the expected effects
and failures. The catalog now checks 57 examples. Selectively removing individual
temporaries in calls that still need sequencing remains a separate batch.

### Follow-up batch — final scalar in an ordered free call

When a free call with by-value parameters requires an ordered block, the final
trivial scalar initializes its parameter directly from its prepared expression
and conversion. Earlier arguments keep their typed captures. In
`sum($x, change($x))` the first argument captures the original value and the
second call appears once inside `sum_`. An earlier conversion failure skips
that final argument; a final conversion failure skips the callee.

The lifetime of a temporary within the final expression can matter even if its
result is scalar. The existing emission walk detects record and handle operands
and conservatively retains their statement boundary on the ordered path.
A regression transfers the last owner, reads its integer field and checks a
weak reference inside the callee. The callee observes that the owner expired.

The three flavors cover the mutating and ordered-call cases, both checked
conversion failure positions and the weak-reference lifetime control. The
catalog verifies 58 generated implementation and header excerpts.

### Follow-up batch — literal positions in ordered free calls

In a by-value free call, scalar literals with nonthrowing prepared conversions
stay inline in any position. The emitter lowers every argument once, records
the order-sensitive positions and renders capture statements only if the call
needs sequencing. The last order-sensitive argument can initialize its parameter
inside the call when its result is trivial and no record lifetime boundary is
needed. Trailing literal defaults follow the same rule as explicit literals.

Three-flavor fixtures check first and middle literal positions and an omitted
proven byte default. Each example has two effectful arguments and emits one
scalar capture. The native output checks argument order, values and call counts.
Controls cover a late reference parameter, checked conversion failures and
release of the last shared owner before the callee observes a weak reference
even when a literal default follows the field read. The catalog checks 59
examples against fresh compiler output.

### Follow-up batch — exact reference arguments in ordered free calls

Preparation already restricts mutable reference arguments to named locals and
parameters of the exact storage type. The emitter passes those lvalues directly.
By-value operands use the existing capture rule. For
`write_sum($x,$x,change($x))`, only the first argument captures the original
value. Two reference parameters can both name the same local without a wrapper.

Controls cover reference positions at the start, middle and end, two aliases,
mutation through the callee, and a scalar field read that must release the last
owner before the callee inspects a weak reference passed by reference. The
generator, preparation and method receiver contracts are unchanged.

### Follow-up batch — type-qualified static method calls

Free functions and type-qualified static methods now share argument selection
through the prepared call's receiver status. Construction expressions stay on
their construction path. For `Counter::advance($x,2)` the emitted static method
receives the original local and the literal directly. For
`Counter::accept(mark(4),mark(5))` with a byte first parameter, the ordered
block evaluates and converts the first result before calling the second producer.
The failure variant uses 300 and checks the range exception, the first
producer's output, and the absence of second-producer and callee output.

### Follow-up batch — inline and shared constructor arguments

The existing argument selection handles construction of an inline record and
the call to a shared record's allocation factory. Both use the prepared
constructor parameter types and conversions. With two effectful scalar inputs,
the first is evaluated and converted in a statement and the second is passed
directly. A proven scalar literal needs no capture. The ordered path keeps
nontrivial values and record lifetime boundaries. Allocation remains inside
the shared factory.

Three-flavor fixtures cover both forms with a proven byte literal, two calls
that print their arguments, and a first conversion that fails before the second
producer or constructor body can run. Existing constructor regressions exercise
cross-unit edits, recovery, cleanup on exceptions and private constructor access.
