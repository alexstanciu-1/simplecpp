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

Milestone 1.2 remains partial: string backing, enum methods,
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
and no-op preparation is empty. Static enum storage remains deferred; ordinary
parameter defaults use the following slice.

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

## Function and method parameter defaults

Ordinary functions, instance/static methods and constructors accept trailing exact-enum
case defaults. Reference defaults remain rejected. The existing parser
already retains the default expression; no parser, binder or lowering changes are
needed. No AST classes, properties, indexes or conversion records are added.

`Function_Preparation::declarations` resolves a case through
`Enum_Preparation::reference` with the callable as watcher. It then uses the existing
typed-destination conversion and `settle_default` path. The enum case link lives on
the parameter's existing name expression; the existing conversion is identity.
This happens once per invalidated signature, not per call. Resolution uses the
declaration's file namespace/imports even when the caller has a same-named enum.

Omitted arguments reuse the prepared parameter expression in the common emitter;
explicit arguments retain ordinary binding. Native calls receive the case constant
directly, without runtime lookup or default wrappers. Existing signature watches
propagate enum changes to callable observers. This keeps the current conservative
invalidation policy, including explicit callers, rather than adding omission-specific
watch tables. Unchanged signatures skip preparation and no-op runs prepare no bodies.

`tests/enum_defaults.php` and `tests/enum_defaults_native.py` cover all three flavors:
functions, inline/shared instance receivers, static methods, multiple trailing
defaults, omitted/explicit arguments, declaration-scope imports and rooted case
names, changed defaults and backing values/width, case deletion, repeated failed
retries and recovery against fresh artifacts. Reference defaults,
wrong enum cases, integer/null/computed defaults and required-after-optional
parameters are rejected before native compilation.

Verification passed 15 native fixtures and five regression suites (enum fields,
enum defaults, scalar parameter defaults and their native proof, and struct methods).
An enum-only edit prepared five affected bodies and reused three; a changed callable
source prepared seven and reused the unrelated body. No-op runs prepared none.

2026-11-14 WSL timings for the four-source fixture plus native entry, milliseconds:

| Flavor | Compiler through emission | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: | ---: |
| PHP | 7.79 | 201.23 | 32.91 | 52.93 | 56 |
| C++ | 7.43 | 217.49 | 30.14 | 57.09 | 68 |
| JS | 5.45 | 192.56 | 24.31 | 43.88 | 61 |

One fresh build per flavor: O2/g0, Ninja8, mold, libc++, no PCH/LTO, prebuilt runtime.
Compiler time includes reading and writing source artifacts but excludes PHP startup.
Clang/LLVM times sum work across translation units; native wall includes Ninja and
qualification checks. Artifacts: `/tmp/scpp-enum-defaults-verified/{fixtures,results}.json`.

## Constructor parameter defaults

Constructor admission now uses the same enum-default path without its previous
explicit rejection. No new structures, properties, watches, conversion records or
preparation traversals are introduced. Defaults settle once per invalidated
signature and omitted arguments reuse the existing case expression.

Both inline and shared construction use the ordinary constructor argument binder.
The existing native zero-argument delegating constructor handles all-default
signatures for nested inline fields. Its `declaration_initialization` path already
emits enum cases; no additional lowering strategy or runtime wrapper is needed.
Only the obsolete admission rejection was removed from executable compiler code.

`tests/enum_constructor_defaults.php` and its native runner verify all three flavors:
omitted, partial and explicit argument lists, mixed scalar/enum defaults, inline and
shared construction, two-level nested fields, record copying and constructor-body
overrides of field defaults. Cross-file default edits, backing-width/signedness
changes, missing cases, repeated failures without edits and recovery match fresh
artifacts. Unchanged caller/provider ASTs are reused where applicable; unrelated
bodies remain prepared and no-op runs prepare none. Integer/null/computed defaults,
wrong-enum cases and reference constructor parameters remain rejected.

Verification passed 15 native fixtures, five regression suites and writing checks
for 190 compiler PHP files. Affecting edits prepared the constructor and caller
while reusing the unrelated body; no-op runs prepared zero bodies.

2026-11-14 WSL timings for four source units plus native entry, milliseconds:

| Flavor | Compiler through emission | Native wall | Clang frontend | LLVM backend | Link command |
| --- | ---: | ---: | ---: | ---: | ---: |
| PHP | 5.26 | 244.50 | 30.72 | 45.17 | 59 |
| C++ | 8.36 | 238.26 | 31.17 | 52.07 | 64 |
| JS | 5.62 | 226.46 | 28.96 | 47.55 | 58 |

One fresh build per flavor: O2/g0, Ninja8, mold, libc++, no PCH/LTO, prebuilt runtime.
Compiler time excludes PHP startup; native wall includes Ninja and qualification.
Clang/LLVM figures sum work across units. Artifacts are retained at
`/tmp/scpp-enum-ctor-verified/{fixtures,results}.json` on the measured WSL machine.
