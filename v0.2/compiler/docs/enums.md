# Integer-backed enums

Doc Status: supporting

The [compact-layout contract](../../../specs/compact_layout_types.md#30-php-bootstrap-compiler-integer-enums)
owns source behavior. PHP uses `case Name = literal;`; C++ and JS use comma-separated
`Name = literal` entries. C++ emits `enum class` with an explicit underlying alias.

## Ownership and preparation

`ast_enum_declaration` owns its optional backing reference, one case-name index
and one `enum_type_definition`. `ast_enum_case` owns its integer literal; the
literal holds the checked value. Duplicate names use `duplicate_element` on the
declaring enum's chain. There is no second retained case/value table.

The new `ast_type_declaration` interface is shared with structs. Publication and
type lookup use the existing `class:qualified_name` symbol keys. Struct/enum
duplicates share that namespace and produce ordinary diagnostics. Plain type
imports reuse the existing file import index rather than adding a third index.

`Enum_Preparation` runs at the declaration gate, before signatures and bodies.
It checks literals, uniqueness and range in one pass. Its temporary duplicate-value
set and min/max accumulators are discarded. Compact storage selection uses the
canonical generated integer definitions' limits. The ready definition retains
source backing and storage references, plus one immutable `enum_value` callable
binding shared by all calls. That binding is constructed before body readers,
not lazily mutated from a foreign body. No per-call enum preparation record exists.

A failed declaration remains unready. A retry resets each literal before checking
it again, so partial validation does not cause double-settlement errors.
Successfully prepared unchanged declarations skip the case walk.

Case expressions use the existing name-expression binding slot, extended to
reference `ast_enum_case`. Their type is the nominal enum, not its underlying
integer. They do not copy a numeric constant fact. `enum_value` uses the existing
runtime-call binding and lowers to `static_cast<backing>(value)`; no runtime helper,
table lookup or generated scalar conversion matrix is added.

## Incremental behavior and emission

Case references and signatures watch the stable enum symbol. Replacement, deletion
or a missing case invalidates affected bodies/signatures through existing watches.
Recovered builds reuse unchanged caller tokens/AST and match fresh native artifacts.
No-op runs prepare zero bodies. Enum preparation adds no cross-unit traversal.

Private enums remain in their source unit's C++. Foreign observers and callable
signatures use one published enum header. Existing header visibility tracking now
covers both enums and structs. Constructor prototypes use fixed-backed opaque enum
declarations, while ordinary methods/functions share the existing signature path.

## Verification

```sh
XDEBUG_MODE=off php v0.2/compiler/tests/enums.php
XDEBUG_MODE=off python3 -B v0.2/compiler/tests/enums_native.py --output /tmp/scpp-enums-proof
python3 -B v0.2/compiler/tools/check_style.py
```

The proofs cover all three flavors, extrema-based `sizeof`, exact fixed backings,
module gates, radix literals, nominal copying/equality, callable arguments/returns,
constructor arguments, helper shadowing, duplicate names/values, imports and
incremental value/width changes, case deletion, whole-type deletion, repeated
failure without edits, recovery and fresh-output equivalence. Native fixtures
check output and exit status and retain Clang stage timing JSON.

2026-11-14 WSL proof: 27 native fixtures passed; optional-integer, struct,
constructor, method, namespace and import regression suites also passed.
Header visibility removal/re-addition matches fresh emission without reparsing
the unchanged enum provider. Writing checks passed for 187 compiler PHP files.

Main range/copy/call fixture, one measured build per flavor, milliseconds:

| Flavor | Compiler frontend/prepare/emission | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: | ---: |
| PHP | 5.14 | 186.32 | 11.16 | 9.46 | 58 |
| C++ | 5.22 | 183.41 | 11.00 | 7.66 | 64 |
| JS | 3.70 | 212.37 | 12.11 | 8.73 | 72 |

O2/g0, libc++, mold, Ninja8, no PCH/LTO, prebuilt runtime, fresh instrumented
application objects. Compiler time uses in-memory source and excludes PHP startup
and test assertions. Native wall includes the Ninja/toolchain-check path; LLVM
backend is a cumulative trace interval, not an additional wall-time component.
These are fixture timings, not an old/new performance comparison. Full artifacts:
`/tmp/scpp-enums-complete/{fixtures,results}.json` on the measured WSL machine.

Milestone 1.2 remains partial: string backing, enum methods, parameter defaults,
reverse construction and other broader enum contracts are subsequent slices.

## Instance fields with explicit case defaults

Enum fields reuse `ast_struct_field.initializer` and `ast_name_expression`'s
resolved declaration link. No AST classes, retained properties or separate default
tables were added. The field's existing conversion code is identity. The canonical
enum definition now advertises instance-field support.

`Enum_Preparation::reference` shares case lookup between body expressions and field
defaults. During layout preparation, a struct definition watches the enum symbol
through the existing `Dependency_Watches` owner. Watching both the written type and
case is deduplicated for that watcher. Successful unchanged layouts skip the walk;
enum replacement invalidates the record and its dependent bodies through existing
layout notification. Missing cases retain watches for retry and recovery.

Preparation resolves the default once per changed layout, without copying its
integer value or scanning cases at each construction. Lowering emits the resolved
case name directly. The existing type-prototype traversal and record header planner
now include enum field dependencies before the containing record. Generated record
copying and shared ownership need no new runtime machinery.

Verification uses `tests/enum_fields.php` and `tests/enum_fields_native.py`: all
three flavors, private single-unit emission, cross-unit headers, inline copies,
shared aliases, nested fields, changed defaults, compact width/signedness changes,
missing-case failures and retries, wrong-type defaults/writes, and fresh-output
equivalence. Unchanged caller/record ASTs are retained, unrelated bodies are reused,
and no-op preparation is empty. Static enum storage and enum parameter defaults
remain deferred.

The field slice passed 21 native fixtures plus the existing enum and property
regressions. Constructor proofs observe the default before the body and verify
that copies do not rerun construction. Enum deletion/restoration and repeated
failures recover to fresh artifacts. Affecting edits prepared two bodies and
reused the unrelated third; no-op runs prepared none.

2026-11-14 WSL field fixture timings, milliseconds (four sources plus native entry):

| Flavor | Compiler through emission | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: | ---: |
| PHP | 4.19 | 197.43 | 20.81 | 29.82 | 55 |
| C++ | 4.77 | 239.67 | 26.70 | 33.02 | 56 |
| JS | 4.30 | 183.76 | 18.77 | 26.97 | 56 |

One fresh build per flavor, O2/g0, Ninja8, mold, libc++, no PCH/LTO, prebuilt runtime.
Compiler timing includes source loading and bundle writing, excluding PHP startup.
Clang/LLVM figures are summed work across units; native wall includes Ninja and
qualification checks. Artifacts: `/tmp/scpp-enum-fields-verified/{fixtures,results}.json`.
