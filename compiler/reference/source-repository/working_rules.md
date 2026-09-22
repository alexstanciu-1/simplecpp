# Repository working rules

## Project objective

Build a Simple C++ compiler with first-class STAN (static analysis) and a useful
direct LLVM compilation path targeting `-O0` and `-O1`; optimization is delegated
to LLVM. Independence from Clang, LLVM or the Simple C++ runtime is not a goal.
Clang-assisted type, ABI and implementation preparation is explicitly allowed;
evaluate it against direct compiler implementation for total complexity, reuse
and source-analysis quality. The existing S2S path can serve broader compilation
needs. These are objectives, not claims that all modes or routing are implemented.
Follow [the shared objective](docs/README.md#project-objective); keep unresolved
Clang/lifecycle choices in discussion until implementation is authorized.

Ownership follows origin: runtime implementations use Clang-prepared metadata and
LLVM; our compiler owns source-defined behavior, including complete field lifecycle
composition and custom bodies. Mixed types retain both owners through explicit
bridges. Clang may measure layout/ABI or compile native adaptations, but must not
silently acquire or duplicate source lifecycle implementation. See the
[agreed boundary](docs/details/clang_lifecycle_composition.md#ownership-decision).

The future S2S generator belongs in this compiler and should consume shared
resolved and checked semantic information, including lifetime/ownership facts.
Preserve agreed source semantics across LLVM and C++ output; investigate legacy
S2S limitations without treating them as permanent language restrictions. Record
explicitly agreed contract changes separately from current implementation support.
The language intent is natural, predictable behavior from locally declared contracts.
Do not turn missing legacy symbol/type analysis, or limitations of our current
analysis representation, into new language restrictions. Discuss semantic gaps;
report unsupported implementation coverage explicitly.

## Design and scope

Follow Simple C++ semantic contracts. Investigate or compare generated C++ only
where a contract or its application is unclear; do not routinely re-prove settled
behavior. Explicitly agreed prototype extensions, including the bounded source
template work, must be documented as extensions, not assumed to change existing
Simple C++ contracts. This does not waive the explicit native-port probes below.

Legacy Simple C++ STAN is not semantic authority. Use authoritative contracts;
where unclear, use executable behavior plus discussion. Legacy STAN diagnostics
do not gate this compiler's work.

Keep it simple and well structured. When complexity grows, identify the concept
and its owner, then simplify and refactor locally before extending behavior.
Avoid accumulating exceptions or hiding complexity in generic frameworks.
Discuss unresolved design choices with the user. Before a wide refactor across
ownership areas, explain why the current model cannot accept the requirement,
the scope, what it unlocks, risks, validation cost, and smallest reasonable
options; obtain user confirmation. Consolidate temporary code, stale docs,
misleading names, and dead expectations before committing.

Before each feature, assess whether it exposes limitations in existing data
contracts or processing. Generalize where needed before adding the feature,
moving existing behavior onto the revised model and proving it still works.
First ask whether the new feature can faithfully use an existing implementation;
otherwise, whether both are particular cases of a shared underlying concept.
If so, reshape the owner around that concept and migrate both features to it,
keeping their semantic differences explicit. This does not require inheritance.
Generalize properties only if they have the same meaning, and code flow only
if it has the same role/meaning. Keep distinct concepts separate; do not force
uniformity or build speculative frameworks. Clarity takes priority. Follow the
[feature evaluation workflow](docs/code_organization.md#evaluate-and-generalize-before-extending).

Keep the shared mental model in [docs/README.md](docs/README.md) minimal. Use
relative documentation links and put longer investigations in `docs/details/`.
Follow the [type model](docs/type_model.md) for types and
[code organization](docs/code_organization.md) for boundaries and verification.

## Active implementation and representation

Work in the [PHP prototype](prototype/README.md) through small, verifiable goals.
Keep `src/` as the original reference; parallel implementation is not required.
Port back to Simple C++ / PHP++ (`.phs`) only on explicit user request, never
merely because a feature milestone is reached.

Use lowercase `snake_case` for inline records, structs, enums, and unions;
use `Capitalized_Snake_Case` for allocated/stateful and processing classes,
for example `Token_Buffer`. In the PHP prototype, former structs are lowercase
record classes with typed properties; datasets and indexes remain PHP arrays.
Create new records or explicit clones before editing retained records, cloning
owned nested records where needed. The eventual native target uses typed linear
containers for high-count rows, without a class allocation per token or AST
node; the PHP prototype intentionally allocates record objects.

## PHP++ authoring and porting gate

Before authoring or debugging PHP++/PHS, read the
[Simple C++ strict-authoring skill](../simple_cpp_compiler/.codex/skills/simple-cpp-php-strict/SKILL.md)
and its referenced quick-learn for language/API and diagnostics guidance.
This repository owns compiler architecture and slice scope. Use the custom
toolchain configured in `tools/toolchain.json`; never silently fall back to
globally installed `scpp`.

Before an authorized port, use that toolchain for focused compile/run probes of
struct support for `string`, `vector<T>`, `hash<T>`, `hash<T, K>`, and
class-declared properties, including snapshot copy/reference behavior.
Documentation or release announcements alone are insufficient. If support is
missing or unverifiable, stop and report the exact type/operation and toolchain
version. Do not work around it with path/buffer ID tables, wrapper classes, or
changes to the agreed data model.

## Code style and navigation

Before writing or modifying `.php` or `.phs` files, read and follow the
[PHP and PHP++ coding guide](docs/code_formatting.md). Its explicit formatting
rules override existing conventions and apply to PHP/PHP++ only. Follow its
method-ordering and shallow call-map conventions, including the adjacent group
and process navigation documents, within the guide's stated scope.

## Organization by process and role

Organize by compiler process, with one owner for shared data and consumers using
its contracts. In the prototype, keep processes within the numbered folder groups
shown in [prototype/README.md](prototype/README.md); namespaces do not include
these groups. Use the following files within each process, with `.php`
counterparts in the prototype:

| File | Responsibility |
|---|---|
| `structures.phs` | Inline records and related value types |
| Clearly named processing files | Process classes, processing and join methods |
| Local helper file, when needed | Folder-specific helpers |
| `store.phs` | Dataset owners |
| `result.phs` | Stage output containers |
| `compile/compile.phs` | `Compiler_Session`, retained state, compile/publication/cleanup methods |
| `compile/state.phs` | Update-control records |

Split files for concrete responsibilities. Keep representation TODOs with the
process until records justify a structures file. Do not create empty helper
files or central catch-all structures/utilities directories.

Group all processing and helpers in classes qualified by namespaces. Use
instance methods for stateful-owner operations, including compilation, lookup,
and index maintenance; use static methods in process/utility classes for
stateless work. No namespace-level free functions or static mutable compiler
state. Collection/resolution decisions belong in process classes, not storage
classes. Preserve this organization when porting; the free-function layout in
retained `src/` is historical.

## Compilation, parallel work, and incremental updates

**Current prototype proof scope: one full rebuild, then one incremental attempt.**
The increment may succeed or report failure. Repeated increments, recovery after
failure and rollback are deferred; do not add them as requirements for the current
slice unless the user explicitly expands its scope. Preserve reusable identities,
ownership, fixed worker inputs and joins for future updates. This is a development
scope limit, not a runtime one-increment limit: keep existing broader behavior and
tests, without expanding recovery machinery merely to anticipate future needs.
See the [planning scope](prototype/docs/planning/compiler_foundations.md#current-required-flow).

Consider all three goals in every implementation slice:

- Prove real source-to-output behavior through the common compiler stages.
- Execute compiler stages serially using explicit work units, unchanged phase
  inputs, separate outputs, and joins. Future workers must execute these same
  units. Prevent races through read/write partitioning and dependency ordering;
  do not add actual threading or a separate sequential compiler path.
- Retain session results with clear identities, dependencies, and replacement
  boundaries. Select work before computing it. Unsupported changes rebuild in
  the same process through the same stages. Add incremental categories one at
  a time, beginning with function-body edits under unchanged contracts. Added
  code/functions/properties/classes need their own future impact rules and proof.

Full rebuild uses the same incremental algorithm with all current work selected:
`update.full_rebuild || element.needs_recompile`. Use one update-context
`full_rebuild` flag, set by the coordinator at phase boundaries and fixed for
tasks. Never mark every record merely to request a rebuild or create separate
full/incremental paths. Remove deleted contributions independently of selection.

For each result, review its producer, reads/writes, and invalidation conditions.
Use small concrete contracts; avoid speculative frameworks, threading machinery,
or unsupported language features to claim readiness.

## Entity identity must not depend on hash uniqueness

Use allocated IDs or exact, unambiguously composed keys for compiler and
preparation-tool entities. Reversible encodings of the complete key are allowed.
Do not use SHA, other hashes, truncated digests, or random values as supposedly unique type,
symbol, operation, or record identities. A low collision probability is not a
correctness argument. ID allocation must have a named owner and an explicit scope
and lifetime; retain mappings when identities must survive updates, and do not
recycle IDs while retained references can exist. Validate imported ID uniqueness.

Keep exact names/keys for lookup and metadata joins. Hash tables are allowed when
they compare full keys and resolve collisions. Content fingerprints/checksums for
change detection and artifact verification are separate from entity IDs and must
not be repurposed as identities or proof that distinct entities are the same.
Generated link names must encode allocated IDs within an unambiguous namespace
or reversibly encode the full qualified identity. Prefer readable escaping for
review: preserve ASCII letters/digits, encode `_` as `__`, encode other bytes as
`_xHH_` with uppercase hexadecimal digits, and separate components with `_X_`.
Decode escapes left to right; raw substring splitting does not respect escaping.

## No committed shortcuts

Never hard-code compilation cases to make a sample or feature pass. This covers
types, source shapes, runtime helpers, target assumptions, paths, and results.

- Put facts in authoritative language/provider/target/configuration definitions
  and consume them through owned contracts. Language rules and backend primitives
  still require explicit implementations in their proper owners.
- Implement reusable algorithms for real concepts. Do not enumerate concrete
  type/feature combinations or special generic instances in consumers.
- Sample-specific tables remain shortcuts, even in configuration. Metadata must
  express a real contract; advertised capabilities require implementations.
- Temporary hardcoding is only for short local experiments: remove it before
  committing or claiming completion. Transition exceptions do not justify it.
- Tests may use concrete inputs and expected outputs; compiler behavior must
  never depend on fixture identities or expected answers.
- If the general path cannot handle a case, report the precise unsupported
  operation or blocker. Never substitute fake success or a special route.
