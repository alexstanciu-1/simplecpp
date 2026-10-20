# Requirements from the converted compiler — 2026-10-08
Doc Status: supporting

This is a source-requirement audit, not a successful self-compilation claim or an
implementation plan. It reads the strict PHP++ project at
`/tmp/my-try-ch11-native-20261008/phpp`, whose `prism.json` selects the strict PHP
profile. No builds or tests were run. Only this chat's documentation was changed.

The [manifest](compiler_phpp_requirements_manifest_2026_10_08.json) records all 204
PHS files and their hashes: 180 compiler/C++-path files, 12 parked LLVM files, nine
portability-framework files, and three validation-driver/proof files, totaling
31,584 lines. A private read snapshot avoided mixing later native-agent changes
into the review. A final hash check found no changes to the original PHS files
since capture. The temporary project and read snapshot are no longer available as of 2026-10-18.
Historical PHS file-and-line citations below identify the audited snapshot, not
files in this checkout. The manifest and excerpts survive; they do not permit
reconstruction or fresh verification of the complete source snapshot.

Whole-project lexical inspection excluded comments and quoted strings, followed
by focused reads of declarations, calls and their surrounding bodies. The compiler
contains many strings describing or emitting features it does not itself use.
For example, the spelling `dynamic` in a registry label, a generated C++ reference,
or a PHS test program stored in a string is not an authored compiler requirement.
This is not a complete call-graph/reachability proof or lifetime audit.

The new table column means:

- **Yes — compiler:** executable source or declarations in the compiler/C++ path.
- **Yes — framework:** supplied PHS support required by this converted project.
- **Driver only:** validation/proof code, not a compiler-language prerequisite.
- **Behavior:** compiler functionality that must survive, rather than a feature
  the compiler needs to accept its own source syntax.
- **No use found:** no such authored use in this snapshot; not a permanent
  language exclusion. A grouped row identifies the subset actually required.
- **Review:** a semantic obligation cannot honestly be inferred from a text scan.

Availability and requirement are independent. Source presence proves demand, not
that my-try or legacy accepts the full program. Existing `tests/php/` evidence stays
separate from newly inspected source bindings. No operator matrix was consulted.

## R01

**scalar code and control flow**

The implementation uses typed/inferred locals, predeclarations, int/bool/string
values, fixed-width `uint32` fields, casts, arithmetic, bit masks/shifts, string
concatenation, assignments, property updates, conditionals, loops and recursion.
The required scalar subset does not establish a need for floating arithmetic.
There are integer/enum switches; no executable `match` expression was found.

- Token records and uint32 fields (historical PHS `02_tokenize/structures.phs:9`): `public $offset uint32;` and int constructor inputs.
- Recursive graph traversal (historical PHS `04_analyze/prepare/semantics/reference_cycles.phs:152`): `visit(int $index)` recursively visits unvisited nodes, uses a vector stack and a do/while loop.
- Integer/enum cursor switches (historical PHS `03_parse/structures/iterators.phs:90`).
- Byte conversion (historical PHS `scpp_framework/strings.phs:85`) uses `>>`, `&`, arithmetic casts and string concatenation.

No keyword `and/or/xor`, numbered break/continue, arrow function, variadic parameter,
native reference return or explicit const-reference parameter was found in executable
compiler code. Bitwise `&` expressions must not be mistaken for reference parameters.

## R02

**typed containers and public Storage**

Required source types include `vector<T>`, `hash<T,K>`, `Storage<T>`,
`Keyed_Storage<T>`, `Key_Storage_List<T>` and `Storage_Cursor<T>`. Storage uses
shared record identity, aliased collection state, stable positions, reads, appends,
removal, named lookups and value iteration; it is not interchangeable with a
copy-by-value vector of records.

- Token membership (historical PHS `02_tokenize/structures.phs:54`): `Storage<token>` and `Storage<retained_token_range>`.
- Module membership (historical PHS `01_prepare_inputs/structures.phs:45`): `Keyed_Storage<source_record>`.
- Duplicate-name indexes (historical PHS `03_parse/scopes/structures.phs:15`): `Key_Storage_List<...>` fields; methods use `named`, `items`, `add`, `remove`.
- Sparse removal (historical PHS `03_parse/collect/collect.phs:390`): `$entries->remove($index)` acts on Storage membership, not vector compaction.

**User direction: typed Storage collections are intended to be available to all
programs.** Their compiler use identifies a required public capability, not a
compiler-private exception. The existing [contract](../../../specs/compiler_storage.md)
and legacy adapter package them in the optional `compiler` runtime module. This
report records the broader intended availability without changing module packaging,
the spec, or implementing registration.

My-try's standard [type installation](../compiler/types/language.php) and
[runtime templates](../03_parse/types/register.php) do not install these families.
The host implementation's own PHP Storage classes do not constitute source-language
support. Legacy [TypeMapper](../../../generators/php/src/Lowering/TypeMapper.php)
maps these runtime families, with [generator bindings](../../../generators/php/src/Generator/Generator.php).
These binding findings supplement, not replace, the restricted test review.

## R03

**object-keyed hashes and empty construction**

Preparation work queues (historical PHS `04_analyze/prepare/worker.phs:11`)
contain `hash<bool, shared<preparation_owner>>` and analogous maps. Reads, writes,
`isset` and `unset` use object identity keys. This is a required capability beyond
the table's original scalar-key hash coverage.

My-try requires hash keys to be hashable/comparable, but its
[capability admission](../04_analyze/prepare/semantics/capabilities.php) grants
nominal handles only copying/storage capabilities. It also explicitly rejects
redundant `shared<Class>` in [type formation](../03_parse/types/model.php).
Removing that spelling alone would not supply object-key hashing.
Legacy [hash type mapping](../../../generators/php/src/Lowering/TypeMapper.php)
explicitly admits shared-handle keys. No targeted `tests/php/` expectation was
claimed for this exact group.

The active compiler files also contain 12 `new vector<...>()` and 32 `new hash<...>()`
expressions. For example, collection reset (historical PHS `03_parse/collect/collect.phs:359`)
assigns `new vector<int>()`; worker construction (historical PHS `04_analyze/prepare/worker.phs:22`)
allocates object-keyed hashes. My-try's [new-expression preparation](../04_analyze/prepare/semantics/object_construction.php)
requires a source class/struct. Typed literal construction existing elsewhere does
not establish acceptance of these exact `new` expressions.

## R04

**iterators and cursors**

The inspection protocol (historical PHS `03_parse/structures/iterators.phs:6`)
declares `interface child_iterator_i extends \Iterator`, with typed `current`,
`key`, `next`, `rewind`, `valid`. Concrete inspection classes use
`new Storage_Cursor<ast_node>($derived_records)` to widen a read cursor without
making mutable Storage covariant.

The compiler declares and constructs these cursors; the direct
foreach over `children()` (historical PHS `main.phs:200`)
is in the validation driver. Thus cursor/protocol declarations are compiler-build
requirements, while this particular object-foreach consumer is driver evidence.
The [legacy cursor/Iterator binding](../../../specs/compiler_storage.md#typed-read-cursors)
is explicit. My-try installs neither the global Iterator protocol nor Storage_Cursor.
Ordinary interface support and vector/hash foreach do not complete this integration.

## R05

**classes, relationships and identity**

The source uses class records, constructors/defaults, access modifiers, static
members, parent constructors, abstract methods, interfaces, shared handles,
nullable references and literal `instanceof`. It has 43 abstract classes, 17
interfaces and 58 enums in the compiler/C++ partition.

- Base syntax protocol (historical PHS `03_parse/collect/structures.phs:60`): `public abstract function syntax(): ast_node;`.
- Refined return (historical PHS `03_parse/collect/structures.phs:280`): `syntax(): property_node` in a descendant. Covariant shared return contracts are actually required, not merely analyzed as a target-language feature. No parameter-contravariance requirement was established.
- Identity comparisons (historical PHS `compiler/callables/instances.phs:23`): `$instance->called_type() === $called`.
- Exception identity (historical PHS `scpp_framework/exceptions.phs:60`): `return $left === $right;`.

Non-null object identity needs its own row: my-try's inspected
[operator dispatch](../04_analyze/operators/preparation.php) handles null presence
but has no non-null shared-object comparison candidate; the remaining nonboxed,
non-enum binary path goes to scalar operators. Supporting null checks is insufficient.
Legacy [strict identity](../../../runtime/include/operators/identity/strict_identity.hpp)
compares compatible shared-handle pointers. This is implementation evidence; exact
object/exception identity was not established by the restricted `tests/php/` review.

No authored `struct`, restricted `union`, trait, unique/value ownership, `new static`
or `static::` use was found in this converted compiler. Classes named after traits,
and code implementing trait semantics, do not make the compiler itself use traits.

## R06

**readonly fields**

Five fields use `readonly`: two in
program entry selection (historical PHS `01_prepare_inputs/structures.phs:98`)
and three in C++ artifacts (historical PHS `05_backend/cpp/structures.phs:39`).
Example: `public readonly string $module_name;`, assigned by its constructor.

My-try tokenizes/parses the modifier, but
[member modifier validation](../04_analyze/prepare/semantics/members.php) rejects
it for ordinary fields. Legacy's [property IR builder](../../../generators/php/src/Builder/IrBuilder.php)
records type/default/static/visibility but no readonly flag. Therefore legacy
syntax admission must not be advertised as proven readonly-write enforcement.

## R07

**spelling and lookup compatibility**

The compiler partition uses `scpp_portability_object_cast(...)` 530 times and
`weakref_get(...)` 162 times. The parked LLVM files and drivers have additional uses.
A representative expression (historical PHS `01_prepare_inputs/structures.phs:79`)
is `scpp_portability_object_cast(weakref_get($this->module_reference), module::class)`.
Weak fields also receive shared handles directly, e.g. `$this->module_reference = $owner`.

My-try's [parser](../03_parse/parser.php) implements `object_cast`, `weak(...)`
and `lock(...)`. It does not install the converter aliases above; matching concepts
do not make this source unchanged-compatible. Legacy has a dedicated portability
object-cast lowering and exposes weakref_get through its runtime helper layer.

Global facade functions are called unqualified from `namespace scpp\compiler`,
for example output reads (historical PHS `06_native/cpp_build.phs:29`)
and global facade declaration (historical PHS `scpp_framework/filesystem.phs:28`).
The v0.2 [namespace contract](../../../specs/namespaces_v0_2.md#name-lookup) deliberately
has no PHP-style fallback to unrelated global user functions. These sites need
qualification/import/conversion adaptation, not a silent semantic relaxation.

## R08

**closures and task callbacks**

Five authored closures occur in compiler code, all with by-value `use` captures:

- Three collection/parser synchronization sites (historical PHS `03_parse/collect/collect.phs:190`), including publication (historical PHS `03_parse/collect/collect.phs:308`) and parser registration (historical PHS `03_parse/parser.phs:274`).
- Worker and publication callbacks (historical PHS `compiler/compile.phs:374`) passed directly to `task_run_publish_unordered($items, $this->jobs, ...)`.

Required callable forms include typed parameters, concrete return types, captured
shared records/enums and passing closure expressions directly as arguments. No
arrow functions, reference captures or boxed callable storage were found.
My-try CH12 remains deferred. Separately, `task_synchronize` and
`task_run_publish_unordered` require runtime registration and callback contracts;
closure syntax alone does not supply the tasks integration. Even a runtime choice
of one worker does not remove source parsing/type-checking requirements.

## R09

**exceptions and finally**

Compiler code contains 20 catches and 28 finally blocks, plus throwing paths
throughout input, parsing, preparation, publication and native build. Framework
adapters convert checked runtime failures into typed exceptions.

- Preparation context restoration (historical PHS `04_analyze/prepare/semantics/expressions.phs:182`).
- Return through finally (historical PHS `05_backend/cpp/syntax.phs:308`).
- Native lock cleanup (historical PHS `06_native/cpp_build.phs:103`).
- Typed exception hierarchy (historical PHS `scpp_framework/exceptions.phs:3`), rooted in runtime `Exception`, with kind-mask matching and retained cause handles.
- Throw an existing control-signal object (historical PHS `04_analyze/prepare/worker.phs:49`).

These are current compiler requirements, not just CH13 test-driver requirements.
Preserving exception identity, rethrow behavior, cleanup and delayed returns matters;
accepting a throw token alone is insufficient. Existing legacy documentation covers
bounded throw/catch/finally; this audit does not invent new test evidence for them.

## R10

**wrappers and error inspection**

Filesystem (historical PHS `scpp_framework/filesystem.phs:6`),
JSON (historical PHS `scpp_framework/json_document.phs:9`),
lock and process facades use helper-form `take($out, $failure, api(...))`, default
`error` locals and `$failure->get_message()`. The one explicit native-reference
parameter found is the framework's
file-lock output handle (historical PHS `scpp_framework/file_locks.phs:6`).

My-try accepts both take spellings through the same parser node, so helper spelling
is **not** an extra gap. The original table is corrected accordingly. Full runtime
type/method registration remains separate: CH13 explicitly defers error getter
integration. The source does not require authored `error -> result<T>` failure
construction; it consumes failures returned by runtime APIs.

## R11

**string and byte APIs**

Tokenizer/parser/emitter code uses `string_byte_len`, `string_byte_at`,
`string_byte_slice`, `str_starts_with`, `str_ends_with` and supporting string helpers.
The portable string facade (historical PHS `scpp_framework/strings.phs:1`)
also uses UTF-8 codepoint APIs, substr/search and checked `hex2bin` to construct bytes.
It quotes JSON text explicitly. These calls are real requirements; ordinary string
arithmetic support is not enough.

Legacy has explicit strict registrations and
[string expectations](legacy_test_expectations_2026_10_08.md#strings). My-try's
standard callable installation currently exposes enum helpers and boxed JSON APIs,
not this string-library surface. The compiler stores source content in `string`
and tokens in its own class/Storage records; no runtime `source_buffer` or
`token_buffer` API requirement was found in this snapshot.

## R12

**filesystem, locks and output publication**

File loading (historical PHS `01_prepare_inputs/file.phs:1`),
output publication (historical PHS `compiler/output_publication.phs:1`),
filesystem facades (historical PHS `scpp_framework/filesystem.phs:1`),
lock reservations (historical PHS `scpp_framework/lock_reservation.phs:1`)
and staging (historical PHS `scpp_framework/output_stage.phs:1`)
require filesystem observations, read/scan/stat wrappers, file_lock_handle,
lock acquisition/release/transfer, safe output reads/writes/removal, SHA-256,
staging/promotion and discarding staged output.

Some extra facade functions are packaged even if a particular call path does not
invoke them. For compiling the project unchanged their bodies still need support;
this is not a claim that every runtime operation executes in every compiler run.
Legacy exposes these registrations; basic filesystem expectations are positive,
but the advanced lock/staging contract is outside that restricted test evidence.
My-try implementing publication as host PHP does not make these APIs available to
programs compiled by my-try.

## R13

**JSON document views**

Json_View (historical PHS `scpp_framework/json_document.phs:3`)
wraps `json_node`, and `json_read` uses `json_document`, `json_parse_error`,
`json_document_parse/root` and typed node queries. It is consumed by compiler
manifest/cache/schema readers. There are **no typed mixed/dynamic declarations**
in the inspected PHS. Existing `json_decode/json_encode` rows therefore do not
describe this compiler's JSON requirement.

Legacy registers these document/node types and functions. My-try's current
JSON source registration (historical implementation path `compiler/my-try/compiler/callables/json.php`, no longer present in this checkout) supplies boxed
mixed/dynamic overloads, not the immutable document/node API.

## R14

**native process and timing**

Native_CPP_Build (historical PHS `06_native/cpp_build.phs:1`)
calls process facades (historical PHS `scpp_framework/processes.phs:1`)
over process_handle/process_output and spawn, poll, result, stop and close; it
also uses `dt_sleep_ms`. This is required for the included native-build consumer,
not for a hypothetical minimal compiler that only returns generated C++ text.
The current project contains the consumer, so it is included in the requirement.
Legacy strict registrations exist; shell_exec tests are not proof of this separate
process-handle API. My-try standard source registration does not expose it.

## R15

**nonrequirements and boundaries**

No executable reference foreach, vector-element unset, typed mixed/dynamic
storage, boxed JSON call, arrow function, named argument, constructor promotion,
trait declaration, struct/union declaration, fixed_array, `data class`, late static
call, unique/value ownership or dynamic property access was found in the active
compiler source. Reference-return and variance analyzer names do not automatically
make every analyzed feature a source requirement; covariant syntax returns are
the concrete variance exception documented in R05.

Scalar/hash/container functionality actually used remains required even when a
larger grouped feature is unnecessary. Whole-storage resets use reassignment/new
construction, not `clean`. There is no direct need to support old source includes:
the converted project is composed through `prism.json`.

Storage record mutation through shared handles is present. Arbitrary structural
mutation during iteration cannot be certified from this review; it needs a separate
alias/lifetime analysis. No reference-foreach requirement should be invented to
solve it. No general safe-mutation guarantee is claimed.

## R16

**build and evidence boundary**

Building all project files also compiles framework and parked LLVM declarations.
Their presence is recorded separately from the active C++ behavior. The three top-level
PHS files are native-validation entry/proof code; output/CLI concerns there are not
automatically core language blockers. The raw source includes LLVM branches in the
driver, including disabled execution; no LLVM execution proof is inferred.

The current project is built through legacy `scpp`; wiring my-try into that CLI is
a workflow requirement if that exact command is to change compiler, not a language
prerequisite for accepting source through another entrypoint. Incremental processing,
publication, native caching and diagnostics are behaviors to preserve, not extra
syntax features inferred from their names.

This review neither changes the native agent's files nor requests another native
run. The native-validation completion status remains owned by that agent. The
source snapshot and earlier tests cannot establish that my-try compiles itself.
