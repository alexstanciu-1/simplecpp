# First LLVM pass
Doc Status: supporting

```text
Compiler::llvm()
    LLVM_Preparation::prepare_program()
        LLVM_Struct_Preparation::prepare() [all struct definitions]
        Name_Preparation::prepare()        [retained file bindings]
        Template_Checker::check()          [symbolic permissions, including unused templates]
        register()                        [ordinary roots and explicit instances]
        prepare_instance()                [each queued instance]
            locals and parameter storage
            LLVM_Struct_Preparation::fields()
            register()                    [new concrete call targets]
    LLVM_Generator::generate()
        LLVM_Function_Generator::generate() -> to_llvm_block()
            expression()/expression_storage() -> loads, calls, checked addresses
            store_value() -> local/element/field stores
        LLVM_Writer::text()
```

`structures.php` owns the explicit experiment policy and output records. Each
`llvm_module` represents a `.ll` file with function definitions, external function
declarations, globals/constants, type definitions and metadata. Functions contain
blocks and blocks contain instructions. The entry role is marked on an ordinary
function. Other module sections currently contain no generated entries; their
string lists can hold prepared LLVM definitions when their producers are added.
Ordered int parameters are supported; general linkage/attributes are not implemented yet.

`prepare.php` selects stack storage and maps source `int` to `i32` using the policy.
Declaration identity is the owning collected file plus local index. Prepared local
indexes are interpreted within their concrete function instance. Generated addresses preserve encoded source names (for example `%a`);
the generator allocates `%_GvN` monotonically within each emitted function.
These namespaces cannot collide and do not depend on source-name uniqueness.

`functions.php`, `expressions.php` and `statements.php` are traits composed into `LLVM_Function_Generator`.
They use prepared declaration targets, emit instructions, and return typed operands
for expressions. The writer serializes the module without looking at source names.
Preparation and generation leave source, AST and collected entries unchanged.

The pass supports one file with top-level executable code plus declaration-only
helper files, explicit `int` declarations with initializers,
nonnegative signed-int32 literals, variable loads and an explicit value return in the entry. Named void or int
functions with zero or more positional int parameters have independent block-owned scopes and emit separate LLVM definitions.
Void body fallthrough or bare return emits `ret void`; int functions require a
value return. Declarations do not emit calls.
It emits stack allocation in the entry block. No conversions are performed.
Multiple files with top-level execution, bindings without an existing explicit declaration, ambiguous/missing reference targets,
other types, uninitialized loads and statements after return report unsupported
coverage. This is not a new Simple C++ language restriction or full semantic checking.

`Compiler::$llvm_files` retains module records and text. Browser debug output displays
the IR. With `dbg = true`, the coordinator then invokes the separate
`06_native/Native_Runner` to compile and run that IR and display its output.

For focused validation, supply an existing empty temporary directory:

```bash
php prototype/my-try/tests/llvm.php /tmp/your-empty-test-directory
```

The check exercises the source pipeline and writes IR cases plus `executions.json`
with expected process exit statuses. Compile each `.ll` with Clang and execute it
to verify behavior. The maximum-int32 case has process exit status 255 on Linux.

Source functions preserve encoded names such as `@helper`. Duplicate names receive
a reserved `_GfFdD` suffix combining file and declaration indexes. Prepared local maps remain source-specific and each
source emits its own LLVM module.

Each source basename produces its corresponding `.ll` output (for example
`helper.phs` becomes `helper.ll`). The native runner compiles/links all outputs
together, using separate temporary subdirectories for same-basename sources.

`names.php` owns source escaping and decoding. Generated block labels use `_Gb0`
to avoid colliding with a source variable named `entry`. Source `main` is reserved
for now. See the decisions document for the complete naming convention.

Direct named calls use `to_llvm_call_expression()`. Instance preparation registers call targets and records unique cross-module
uses; the queue completes all demanded signatures before generation. Only these uses produce external declarations; unused definitions and calls
within the same module do not. `tests/calls.php` exercises this through source files
and native linking/running (pass an existing empty temporary directory).

Value-returning calls return typed operands through the same expression interface
as literals and variable references. Initializers and returns consume those operands
without call-specific handling. Calls retain ordered argument expressions, evaluated
left-to-right for this experiment. Prepared signatures supply parameter types for
both definitions and external declarations; count/type mismatches fail generation.
Incoming value parameters (`i32 %_GargN`) are stored in their own stack slots
before the body executes. Reference parameters (`int &$value`) receive `ptr %value`
directly, with no allocation or copy. `llvm_parameter` retains the passing mode,
local storage and incoming LLVM operand for signatures and calls.

`expression_storage()` retrieves prepared writable storage for reference arguments;
value lowering loads from that same storage. Only initialized int variables can
currently be passed by reference. Assignment bindings resolve to an existing
same-scope declaration in analysis and store through its address in generation.
The collector and retained AST remain unchanged by resolution. Assignment as a
nested expression, reference returns, implicit conversions, defaults, named
arguments and variadics remain outside this slice.


Fixed-array locals use the experimental `int[N]` syntax. `prepare.php` retains
`llvm_array_type` (element type and extent) alongside local storage. Literal
initialization emits a single aggregate store. `expression_storage()` returns an
`llvm_place` for both root variables and index projections. `index_storage()`
checks each constant index before emitting GEP; value loads, stores and reference
arguments share it. Dynamic indexes fail until runtime bounds checking exists.
Whole-array copying/passing and general initializer expressions are unsupported.
The source-pipeline tests include lengths, zero-size storage, element access,
function-local arrays, all three bounds-check consumers and cross-file calls.


Plain structs are a bounded proof: `struct point { public int $x; int $y; }`,
zero-initialized local `$p point;`, and `$p->x` reads/writes/reference arguments.
`structs.php` prepares ordered shapes from collected type declarations before
file-local storage. Name preparation resolves type uses through scope type pools.
Member occurrence lists resolve to prepared field types and ordinals after locals
are prepared. Generation performs no field-name lookup: `field_storage()` emits
GEP and returns the same `llvm_place` used by array and scalar consumers.
Each module emits named LLVM type definitions for its own declarations and used
local types, including source types declared in other files. Struct declarations
do not create an executable entry. LLVM owns field offsets and padding.

This experiment retains its existing int-to-i32 policy for fields; this is not a
claim of complete SC++ compact-layout semantics. Empty structs, field initializers,
methods, nested aggregate fields, custom lifecycle, construction expressions and
whole-struct copying/passing/returning are unsupported. Default initialization is
currently an aggregate `zeroinitializer` store. No runtime helper or allocation
outside the existing function stack is introduced.


Explicit template functions accept `template<T>` or `template<typename T>` with
multiple ordered type parameters. Calls use `identity<int>(value)`. Currently int
is the only admitted concrete template argument; no inference, value arguments,
struct templates, defaults, packs or native provisioning are added.

Prepared files retain source/name bindings, module types, emitted functions and
external dependencies. Each `llvm_prepared_function` retains its original body,
arguments, locals, field targets and call targets. Ordinary functions use the same
path with empty arguments. The registry key is the exact file/definition identity
and ordered arguments. Registration precedes queued body preparation, allowing
recursive uses to refer to an existing instance. Each definition's owning file
emits its demanded instances, and callers import only used cross-file signatures.
Link names append the reversibly escaped `<int,...>` spelling; registry identity
is independent of that spelling.

`04_analyze/prepare/templates.php` performs bounded symbolic signature/body checks before
substitution, including unused templates. It supports value parameters, explicit
copy initialization/assignment, returns and compatible call forwarding. Bare type
parameters do not gain member access, indexing, default construction or mutable
reference permission from an int substitution. Unsupported checks fail explicitly.
The current int-only argument domain needs no unbounded type-expansion mechanism;
future nested/growing template applications will need an explicit instance limit.


## Compiler collections

Preparation returns Storage<llvm_prepared_file>; generation and template checking
consume that same collection. Ordered functions, parameters and pending instances
use Storage. Struct types/fields, external targets and the instance registry use
Keyed_Storage. External targets are keyed by emitted name, preserving first-use
order while avoiding a repeated identity scan. Field insertion rejects duplicates.
Sparse declaration/token maps and LLVM text arrays remain typed arrays.
See [the collection inventory](../../docs/architecture/MODEL.md#collection-choices-during-llvm-preparation)
for ownership and index-maintenance rules.


## Portability preparation

LLVM_Names uses explicit byte helpers for exact reversible encoding, including
non-UTF-8 bytes. LLVM_Writer joins typed string lists while preserving empty-item
separators and section order; LLVM_Functions uses explicit concatenation. These
files pass local conversion syntax checks. Native compilation and Storage consumer
bindings remain unproved; see [conversion status](../../docs/portability/conversion_review.md).
The PHP host boot loads the shared portability framework for these helpers.
