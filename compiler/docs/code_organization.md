# Code organization
Doc Status: supporting

Active development is in the [PHP prototype](../README.md), which keeps
the process/owner model below with `.php` files and lowercase record classes
standing in for structs. Datasets and indexes remain arrays. Native
layout descriptions and `.phs` links describe the retained PHP++ implementation
and eventual port target.

PHP++ reference status: valid manifest reading, source discovery, and a two-refresh simulation
work, including malformed-JSON recovery. Complete manifest type validation is
unfinished. Compact token/AST storage and
an inactive update skeleton extend through symbol
resolution; later stages remain explicit placeholders. See the
[skeleton walkthrough](details/source_skeleton.md) for current code and limits.

## Process and role

The final implementation targets **PHP++ (`.phs`) using SimpleC++**; prototype
the early behavior in PHP first. Organize folders by compiler process and put
all processing and helpers in classes. Stateful owners use instance methods,
such as `$session->compile($manifest_path)`; stateless work uses static methods
in process/utility classes. Namespaces qualify classes, such as `parse\Parser`.
Do not use namespace-level free functions or static mutable compiler state.
Preserve this rule in the later Simple C++ / PHP++ port. Use structs for native
inline records. Stateful and processing classes use
`Capitalized_Snake_Case`; structs, enums, and unions use lowercase `snake_case`.

| File role | Contents |
|---|---|
| Named process file | Selection, worker entry points and coordination. |
| `join.php` or a named `*_join.php` | Accept selected work results and assemble current stage output. |
| `structures.phs` | Inline records and related value types. |
| `store.phs` | Dataset owners and access methods, such as token/AST buffers and symbol indexes. |
| `result.phs` | Stage output containers: configuration, frontend bundles, symbol results, and prepared updates. |
| `compile/compile.phs` | `Compiler_Session` state and its compile/publication/cleanup methods. |
| `compile/state.phs` | Per-update control records, separate from the session. |
| Local helper file | Utility classes with static folder-specific helpers when needed. |

Use `.php` counterparts in the prototype. These are roles, not mandatory files.
Group related methods; split when
concrete responsibilities justify it. Keep representation TODOs with their
process until records exist. Avoid empty helpers and catch-all structures/utilities
folders. Static utility classes are appropriate for shared stateless work.
Shared data has one owning process. Use class imports or fully qualified names
across processes. The retained PHP++ reference predates this method-grouping
rule; its layout and toolchain limitations are in the skeleton walkthrough.

## Evaluate and generalize before extending

Before implementing each feature in the PHP prototype, review the affected
representations and processing for assumptions imposed by the current subset.
Existing contracts may change when the feature reveals a broader version of
the same concept; preserving their current shape is not a goal in itself.

Use the following design questions before choosing an implementation:

1. Can the new feature be expressed faithfully through an existing implementation?
   Check evaluation order, side effects, scope, ownership and exits, not only the
   final result. If it can, reuse that implementation through its proper contract.
2. If it cannot, can the existing and new features both be particular cases of
   a broader underlying concept? Identify that concept and its owner, then reshape
   the representation and processing so **both** features use the common model.
   Update names and contracts to describe the broader responsibility rather than
   attaching the new feature to an owner still defined around the old one.
3. Which semantics remain specific to each feature? Keep those explicit in small
   handlers or concrete variants, sharing only the parts with the same meaning.

A shared underlying concept is not necessarily a parent class. Records, shared
processing methods and composed handlers may express it more clearly than
inheritance. If the common implementation needs many feature-specific exceptions,
reconsider the chosen concept or reduce the shared portion rather than forcing it.

For example, when assessing `switch` alongside `if / elseif / else`, consider
whether both can use common selection-flow construction: arm bodies, destinations
and continuation paths. Successive condition evaluation and matching against a
selector evaluated once remain distinct selection rules. Scope, fallthrough and
transfer rules must also retain their agreed meanings. This is a design question
to evaluate before implementation, not an assertion that these features are all
implemented or a requirement to give them identical syntax or dispatch records.

Generalize a property **only if it has the same meaning** for existing and new
uses. For example, when a single item becomes several items of the same role,
replace the single-value property with a list and migrate its producers and
consumers. Existing behavior becomes the one-item case, using direct access or
a meaningful accessor. Preserve its cardinality rules: a consumer requiring
exactly one item must not silently ignore additional items. Keep element meaning
and ordering explicit, without retaining duplicate single-value/list state.

Generalize code flow **only if it has the same role/meaning**. When existing
processing handles one occurrence and the new feature needs several, consider
a shared iteration or traversal that serves both. Preserve evaluation order,
scope, ownership and exit semantics. Similar-looking code or fields alone do
not justify sharing; keep distinct concepts and feature-specific rules separate
when combining them would obscure their meaning. Clarity takes priority over
uniformity, and a feature does not automatically require a refactor.

Use this workflow in small, verifiable slices:

1. Assess which assumptions the feature invalidates, identify their owners,
   and state the bounded scope and non-goals.
2. Refactor the affected representation and processing where shared meaning
   justifies generalization. Keep existing behavior on the revised common path.
3. Prove that existing behavior and contracts remain correct before extending
   that path with the new behavior.
4. Implement and prove the feature through the revised model, preserving the
   existing work-unit and join boundaries unless the requirement changes them.
5. Consolidate obsolete paths, special cases, tests and documentation; assess
   the resulting structure as well as the passing behavior.

Generalization must answer a concrete feature requirement, without speculative
frameworks or unrelated redesign. Routine local refactors belong to the feature
work. A wide refactor across ownership areas still requires the scope, risks,
validation cost and user agreement described in [the working rules](../reference/source-repository/working_rules.md).

## Controlled feature extension in the PHP prototype

Give each process a small set of named dispatch points for the representations
it consumes, such as parser statements and expressions. Dispatch selects named
handlers; substantial feature implementations belong outside the dispatch body.
A language feature extends only the stages that need new semantics or new
representation support. Later stages should reuse existing operations and flow
when those faithfully express the feature.

Traits may group one processing class's private methods in a process-local
`handlers/` subfolder. Group small handlers by concrete responsibility; give a
substantial feature its own trait when cohesion and navigation justify it.
Use `Capitalized_Snake_Case` trait names. Avoid a catch-all general trait or a
mandatory file per feature. The owner explicitly composes its traits and keeps
worker properties, initialization and the public entry point. Traits introduce
neither independent workers nor shared mutable state.

Use a process-local `utilities/` subfolder for stateless supporting classes
shared by handlers or consumers of that process's results. Keep stage-specific
names on the files and preserve the process namespace. Utilities may expose
cross-stage contracts; the folder name does not make them private. Create the
folder only when concrete candidates exist. Main processing, worker state and
joins retain their process-root roles.
The parser's `utilities/syntax_access.php` and `utilities/syntax_comparer.php`
contain its structural query and logical comparison classes.

A process-local `data/` subfolder may group `structures.php`, `store.php` and
`result.php` to keep the directory listing tidy. Parsing, all five analysis
processes and both code-generation processes use this layout. Backend preparation groups its accepted snapshot in `data/context.php`.
These files retain their distinct roles: records/value types, dataset owners
and completed outputs. Storage access, index maintenance, result validation
and exports stay with their data classes; grammar and work-selection decisions
stay in processing classes. Grouping files changes neither process ownership
nor namespaces and does not introduce a compiler-wide data folder.

[Input preparation](details/input_preparation_organization.md) also groups data
within each process. Language catalog ingestion composes private representation
handlers, while directory scanning and snapshot reading use separate task workers.
Manifest validation stays together; source path operations are local utilities.

Keep composition shallow, methods private and dependencies clear. Document
handler inputs, output, traversal/consumption rules and diagnostics ownership.
Prefer uniquely named methods over trait conflict resolution or overrides.
An operation with independent state, invariants or consumers remains a separate
class. A file split must not conceal a missing ownership boundary.

The parser applies this to statements, control statements, declarations and
expressions, preserving grammar, node identities/order, spans, diagnostics,
input purity and frontend reuse. `File_Parser` retains worker state, file assembly
and shared token/tree helpers. The [parser handler contracts](details/parsing.md#statement-handler-contract)
and [extension map](details/parsing.md#feature-extension-map) describe the current
boundaries. The [parser scaling plan](details/parser_scaling_plan.md) records
the implementation slices and preservation proofs. `switch`, other stages and
PHP++ porting remain separate work; wider adoption requires its own local review.

[Symbol collection](details/symbol_collection.md#organization-and-extension-points)
uses a declaration-handler trait and a declaration-syntax utility while keeping
identity reconciliation and change comparison in its processing classes. Its
handlers are private static methods because file extraction is stateless;
traits do not require inventing a stateful worker.

[Name resolution](details/symbol_resolution.md#organization-and-extension-points)
uses declaration, name, statement and expression traits on its source-owner
worker. Scope/local state stays with that worker; stateless declaration/function
lookup utilities are shared by binding and reuse checks. `Symbol_Resolver` selects work;
`Resolution_Join` accepts results, sharing `Resolution_Validity` with selection.

[Type resolution, body checking and lifetime analysis](details/analysis_handlers.md)
use the same role boundaries. Type resolution follows accepted annotation bindings
through a utility and keeps materialization dispatch in its processing owners.
Body checking composes statement, control-statement and expression traits;
lifetime analysis composes checked-statement, value and local-lifetime traits.
State and result assembly stay with their workers. `Flow_Builder` and
`Local_Flow` remain independent processing classes, while stateless `Flow_Graph`
queries belong to checking's utilities. Trait groups follow each stage's input
representation rather than requiring a source-feature handler in every stage.

[Lowering and LLVM emission](details/code_generation_handlers.md) compose
handlers for their checked operations and prepared instructions. Lowering keeps
value/slot identity and lifetime cursors in its existing worker. Emission uses
one private worker per function for text, operands and coverage counters.
Module assembly has its own phase owner and a stateless worker with named import
and text-assembly methods. Backend preparation owns `prepare_backend/`; its retained toolchain lives in
`prepare_backend/tools/`. The independent process runner lives in `tool_process/`.

## Step lifecycle in the PHP prototype

Every numbered prototype stage has its lifecycle on the meaningful process owner
in `main_<process>.php`. One object executes one phase: `init()` fixes preparation
and task selection, `run()` completes processing, and `finalize()` accepts results.
Concrete outputs implement the result marker; actual stores implement the store
marker. No forwarding step wrapper, base class or inner-loop state checks are added.

See the [entry inventory](../src/compile/steps.md) and
[coding guide](code_formatting.md#step-lifecycle) for the shared contract.
Separately scheduled phases keep distinct owners within their process folder:
discovery/read, collection/comparison, language entry/types, backend/lowering/native
entry, and function emission/module assembly. Workers and joins remain separate.
The session and toolchain are retained services rather than one-update steps.

Selection is private preparation when the phase owns it. Type task helpers share
selection with their workers. Type_Resolver accepts ordinary records, runs instance
preparation, then fixes signature/local batches before their materialization.
Instance preparation alternates fixed application and record batches; its joins
own canonical mutations, while consumers retain original ASTs and concrete contexts.
Tests may inspect private selection through test-only support to exercise independent
worker ordering and joins; the production API has no extra scheduling facade.

## Join organization in the PHP prototype

A stage join accepts results for explicit work units, checks their relationship
to fixed inputs, and assembles current output with any valid retained results.
Keep this processing responsibility in its own process-root file: `join.php`
for the main join, or a descriptive `*_join.php` when a folder contains several
distinct joins. Existing stage entry methods may delegate to these classes.
Shared input/validity checks belong in process-local `utilities/`; join-only
validation and reconciliation stay private to the join class.

All task-result join owners implement [compile/Join](../src/compile/join.php).
Their constructors capture concrete context and selected tasks through assignments;
instance `join(array $results)` validates and returns a concrete output. Workers
and steps call the actual join owner, without forwarding join methods on workers.
`Frontend_Join` also retains its segmented merge()/finish() accumulator. Its task
validation is deferred from construction to first acceptance/completion.

`compile\Join` is a marker; each concrete owner declares its typed inputs and
outputs. Array boundaries receive explicit container annotations as they migrate.
The marker does not expose a callable polymorphic acceptance operation. There is no
shared join algorithm or additional step lifecycle. Preserve each process's ordering,
identity, invalidation, failure and private-candidate mutation rules.

The [join inventory](details/join_organization.md) maps every implemented batch
acceptance boundary to its file. Path concatenation and control-flow joins are
different concepts. Manifest reading, runtime loading and final publication do
not gain artificial task joins. This organization changes no work scheduling,
language semantics, data representation or publication behavior.

## Navigation and ownership

The [coding guide](code_formatting.md#quick-navigation-maps) defines the shallow
group, process and file call maps, together with method declaration ordering.
Start at the [plain-text compiler map](../src/compile/calls.md), then
follow group order lists and process `calls.md` files beside the implementation.

Start at [Compiler_Session](../src/compile/compile.php): its `compile()`
method coordinates work, with stateless selected units in
[Phases](../src/compile/phases.php). `Compiler_Snapshot`, beside the
session, groups each retained generation. The session replaces `observed` and
`published` as whole snapshots; stage owners still own their datasets.
The [prototype README](../README.md) shows the numbered folder groups.
The prefixes order navigation; namespaces and process ownership remain unchanged.
The retained [PHP++ source README](../reference/original-phpp/src/README.md) describes its original layout.

| Folder under `src/` | Responsibility |
|---|---|
| `compile/` | Coordinate updates; retain session/control state; collect candidate and phase outputs. |
| `01_prepare_inputs/read_manifest/` | Read/validate configuration and produce its snapshot. |
| `01_prepare_inputs/load_runtime/` | Validate/import provider metadata into shared contracts; own package leases and artifacts. |
| `01_prepare_inputs/read_sources/` | Discover participants, read snapshots, and join replacements; own source identities and spans. |
| `02_tokenize/` | Produce tokens; own token rows and buffers. |
| `03_parse/` | Produce/join parsed files: defined entities and an implicit entry body over flat syntax storage. |
| `04_analyze/collect_symbols/` | Collect/compare declarations; own declaration records, the symbol store, and refresh results. |
| `04_analyze/resolve_symbols/` | Resolve uses and join bindings. |
| `04_analyze/instantiate/` | Prepare literal constants and demanded source instances through fixed batches and joins; retain exact argument identities. |
| `04_analyze/type_model/` | Shared semantic definitions, representation records, normalized callable contracts and canonical storage. |
| `04_analyze/resolve_types/` | Select/resolve source and provider requests; materialize through joins. |
| `04_analyze/check_bodies/` | Check operations/calls/conversions and construct the shared typed body. |
| `04_analyze/analyze_lifetimes/` | Dataflow, storage, ownership, and cleanup analysis. |
| `05_generate_code/prepare_backend/` | Prepare target/layout/ABI contracts; own the retained backend tool service. |
| `05_generate_code/lower/` | Produce explicit body instructions, storage operations and native-entry plans. |
| `05_generate_code/emit_llvm/` | Emit and verify LLVM from completed plans. |
| `06_build_output/build_native/` | Produce/cache objects and link outputs. |
| `diagnostics/` | Shared diagnostics and source anchors. |
| `simulate_increment/` | Optional CLI folder swap, stage journal, and restoration; outside compiler stage semantics. |

This table assigns responsibilities. In the retained PHP++ reference, the folders
after symbol resolution, runtime import, and diagnostics contain placeholders. The resident
driver is not implemented yet; `src/main.phs` inspects manifest/source inputs,
optionally twice through a folder-swap simulation. `compile/inputs.phs` owns the
shared input prefix; simulation does not advance published compiler generations.
The [pipeline](compiler_pipeline.md) owns
processing dependencies and refresh policy; its 22 responsibilities do not
require 22 folders or a whole-project barrier after every step.

The coordinator decides which units run and when. Stages decide language/backend
facts and return separate results; they do not initiate another compilation.
The session retains stage-owned state without taking over semantic decisions.
Producers record their dependencies as supported update rules are introduced;
watch/CLI/IDE transports supply input changes. A general dependency engine is
not a prerequisite for the first slice.

Semantic analysis builds typed bodies; [scalar lifetime analysis](details/lifetime_analysis.md)
produces separate per-value and reachability facts referencing those exact bodies;
lowering consumes both through the [lowering input boundary](details/lowering_inputs.md),
with a separate fixed backend context. [LLVM preparation](details/backend_preparation.md)
owns target probes and project callable linkage bindings under `src/05_generate_code/prepare_backend/`.
[Instruction lowering](details/lowering.md) uses `body.php` for private per-callable
work, `data/result.php` for immutable lowered bodies, `data/store.php` for their indexed set,
and `main_lower.php` for phase execution and selection with `join.php` for plan acceptance.
`05_generate_code/prepare_backend/backend_join.php` accepts prepared callable bindings.
`05_generate_code/lower/main_native_entry.php` owns the hosted startup plan.
`05_generate_code/emit_llvm/main_emit_llvm.php` owns stage entry points and delegates function
emission to `body.php` and its private handler traits, with `join.php`
accepting results; `05_generate_code/emit_llvm/main_assemble_modules.php` owns selection
and independent file assembly, with `module_join.php` accepting its results.
`05_generate_code/emit_llvm/data/structures.php` holds the fixed `module_task`; neither join builds IR.
`build_native`
owns private object/link work and artifact preparation. Session publication owns
adopting the completed candidate. See [native builds](details/native_executable.md).
Lowering makes execution, cleanup, and ABI decisions
explicit. LLVM emission consumes completed plans without recovering semantics
from source syntax. Runtime implementations stay in the runtime toolchain;
compiler consumers use their [provider contracts](type_model.md).

Queries, exports, and debug views consume shared facts without choosing semantic
behavior. Add them near their data owner until a shared facade is needed.
Persisted-cache codecs and optimization passes belong with the representations
they serve when introduced. Running an output program is an explicit tool action.

## Compact storage and identity

`Source_Set` owns one vector of top folders (modules) and one shared vector of
inline file records. Each file carries its `top_folder_index`; private folder
indexes contain live file IDs, not duplicate records. Owner methods hide file-ID
lookup and folder membership. In the prototype, folder/file records store path
strings directly and each file has an optional immutable source-buffer reference.
The retained PHP++ reference still uses the former path/buffer ID tables.
Logical file IDs survive row movement; folder indexes belong to their source
snapshot. Deleted rows use folder
index `-1` and retain their canonical full path for origin tracking. ID lookup
can still retrieve them; live path/folder indexes exclude them.
Discovery also records the manifest entry's file ID; semantic entry resolution
uses `entry_file()` without resolving paths again. `Source_Set::acknowledged()`
prepares publication state, replacing only pending rows and clearing removal
observations. The session adopts it after publication succeeds; shared buffers,
unchanged rows and tombstones remain intact.

Each configured folder is scanned recursively. Relative roots resolve against
the manifest directory; absolute roots are allowed but discouraged. File paths
include a spelling relative to their owning root and a canonical full path for
identity. Whole-second mtime and size are retained on each row; either difference
marks a change under the [current timing assumption](details/incremental_refresh_rules.md#inputs-and-comparison-baseline).
Added, changed, deleted, and unchanged states
describe the update independently of `needs_recompile`. Moved is reserved;
moves currently mean deletion plus addition. In the retained PHP++ reference,
discovery/index operations work while source reading and buffer joins remain
skeleton contracts. Index refresh constructs replacements before adopting them.

In the [prototype discovery](../src/01_prepare_inputs/read_sources/main_read_sources.php), each
directory is an independent task returning direct file metadata and child
directory paths. Serial execution uses breadth-first batches, seeded in manifest
root order; joins follow task order and sorted directory entries, never worker
completion order. Only the coordinator assigns file IDs, compares retained rows,
and builds the replacement dataset. Absence is established only after every
directory scan succeeds. Each result retains its originating immutable task;
the join rejects results from other tasks even when batch-local indexes match.
Workers neither read nor write shared source tables.
Source-read tasks carry a file ID, path and expected metadata; they return
immutable bytes. The read join validates every retained buffer and shares its
metadata row when the buffer stays the same; replacement or clearing clones
that row. `Token_Set` indexes token buffers by file ID; each buffer refers to the
exact source snapshot it describes and derives its file ID from that snapshot.
Selection uses this validity independently
of unfinished downstream work. Full rebuild selects the same tasks for all live
files. Joins exclude removed payloads and preserve earlier snapshots.

Keep high-count records in typed linear containers; never allocate a class per
token or AST node. A `Token_Buffer` owns inline `token` rows; a `Syntax_Tree` owns
inline `syntax_node` rows. Source text stays in retained snapshots. Tokens carry
`start` and `length` directly, as do prototype AST nodes. The retained PHP++ AST
still nests an inline `source_span`; the prototype avoids a second object per node.
Use small unions or separate payload tables only when
actual variants need them; segmentation waits for a demonstrated need.

The prototype's [type storage](details/type_storage.md) belongs to `type_model`:
`data/representations.php` defines value shapes; `data/store.php` owns canonical
identity, interning and flat member ranges. `data/definitions.php`, `data/records.php`
and `data/semantic_calls.php` define shared meaning, normalized structural inputs and
callable contracts. `load_runtime` imports them; `resolve_types` selects and
materializes requests. The model depends on neither producer.
`Type_Cache` remains resolution processing: it checks context and prepares a private
candidate after the coordinator fixes selection. Resolution joins accept worker
outputs and materialize through the model's dataset owner. Completed type results
are shared read-only downstream.
The [signature phase](details/return_type_resolution.md) consumes these contracts:
`01_prepare_inputs/load_runtime/main_load_runtime.php` loads named language definitions and
`04_analyze/resolve_types/signatures.php` selects/resolves explicit return annotations,
with `signature_join.php` accepting and materializing the requests.
`04_analyze/resolve_types/data/result.php` owns typed signature results separately from syntax. Target layout
and ABI planning remain later responsibilities, with no guessed facts on types.

Each parsed file is a `File_Frontend`: a source ID, tokens, flat syntax storage,
`defined_entities` (top-level declaration node IDs), and `entry_body_id`.
Construction binds its exact token buffer and syntax tree; the producer then fills
file/index metadata, and validate() establishes structural readiness.
`Frontend_Set` indexes these results by file ID and shares unchanged ones.
Parsing consumes a single token snapshot and creates private output.
`Frontend_Join` holds coordinator-private replacements: segment merges validate
only their results, and finalization establishes source order, removes deleted
contributions and rejects missing/stale frontends. The
[current grammar](details/parsing.md) preserves functions, returns, locals/blocks,
typed parameters and ordered call arguments. Parameter names bind in root scope;
parameter types, argument checking and scalar passing use their respective stages. Class members will belong to the class rather than the file's
top-level list. Only function declarations are currently supported; other entity kinds
will use this list when introduced.

The entry is an implicit callable owned by the file. Its body is an ordinary
block, just like a named function's body, and is present even for empty or
declaration-only files. Definitions stay in the entity list; the ordered entry
body contains executable statements and references to declaration-initialization
actions. Keep each initializer expression in one place. The prototype's
`Syntax_Tree.root_node_id` identifies one `file_root` node whose children are
the entry block followed by definitions in source order. The entity list and
entry-body ID index those same nodes; there is no copied syntax. Root-child
order describes containment, not execution order.

Project symbols reference declaration and body/list nodes in the owning file
AST snapshot rather than copying them. Top-level declarations, including
constants when supported, contribute named project symbols too. Ordinary
executable statements remain under the implicit entry owner. References must
identify the exact snapshot and local node, and be refreshed when it is replaced.

When the language permits runtime initialization, a declaration such as
`const Xyz = call_me_a_function()` has both a symbol and an entry-body action
equivalent to `initialize(Xyz, call_me_a_function())`. Initialization has its
own rules, distinct from later assignment. Preserve the language-defined order
relative to other statements and initializations. Compile-time constants must
instead satisfy constant-evaluation rules; they cannot silently become runtime
initialization. Runtime calls execute in the compiled program, not on compiler
updates. Project startup policy determines which file entries execute and in
what order. These semantics remain future implementation work.

Child/sibling IDs preserve logical tree and statement order in flat storage.
Node IDs are local to one tree snapshot, not stable identities
across reparses. Retain IDs instead of references into growable vectors. Rows
belong to explicit file/body/session lifetimes; phase inputs stay unchanged and
workers produce separate outputs before a join. Execution is serial for now.

Project declarations are unique by **symbol kind, namespace, and name** under
the language's name rules. File path is an origin, not part of the key. Members
and locals use their containing scopes. Parsing identifies declarations locally;
collection joins them before project-wide reference resolution. Parsers do not
write a shared global symbol index.

Each top-level declaration has a logical identity, source origin, and syntax/body
association. Multiple indexes can reference the same declaration dataset.
`Symbol_Store` owns storage and index consistency; consumers use methods such as
`find_symbol(name, namespace, kind, owner_symbol_id = 0)`, `symbol_by_id(id)`
and `child_symbol_ids(owner_symbol_id)`, not physical row
positions. Collection owns registration/comparison policy and duplicate-key
checks; resolution owns diagnostics for missing uses. Collection and the store
APIs, call-name resolution and the first named return-type resolution slice are
implemented. [Declared body checking](details/body_checking.md) now has separate
per-callable workers and typed results, shared by named functions and the
[manifest entry](details/program_entry.md). Broader type resolution remains unfinished.
Prototype symbol records hold name/namespace strings, a semantic owner ID, and
an exact `File_Frontend` reference for declaration/body node IDs. File identity
and source bytes derive from that frontend; syntax is not copied. A `file_entry`
symbol represents the implicit callable without inventing a source declaration
or name. No name-interning table is implemented. The current grammar uses exact
identifier spelling in the global namespace. See [collection](details/symbol_collection.md).

`Symbol_Refresh.changes` stores flat change records with previous/current symbol
references, own status and a child-change summary. Bodies are child content;
there is no dedicated body-change property or assumption that every element
has children. Resolution selects its own tasks from result validity, independently
of those descriptions. The downstream
eligibility gate belongs to `Input_Selection::supports_increment`, applied by
`Compiler_Session` at the post-resolution boundary; full rebuild selects work without
rewriting change statuses. Collection reports added/removed or uncompared
matched records; [logical comparison](details/symbol_comparison.md) completes
matched classifications and omits wholly unchanged rows. The nullable child
summary distinguishes pending/not-applicable from a known boolean. The current gate admits callable-body edits under unchanged definitions;
broader impact rules remain deferred. See the [catalog contract](details/incremental_refresh_rules.md#catalog-changes-separately-from-reactions).

`parse/Syntax_Access` owns function parts, call targets and statement-expression
roles. Its accessors return existing node IDs. `Declaration_Syntax` owns the
semantic choice of which syntax contributes to a symbol's definition.
`Syntax_Comparer` compares logical subtrees without offsets or
node identities; symbol comparison selects declaration and executable-child
boundaries. The current AST is retained even when its syntax compares equal.

Each `Symbol_Resolution` carries its source owner's symbol ID and exact file AST.
Call bindings use project target IDs; declaration bindings distinguish source and
provider types, template parameter slots and constants. This is a single-AST
result contract, and the resolver/join rejects reuse against a replacement tree.
`Resolution_Set` owns results by symbol ID and retains fixed declaration context.
Selection rechecks target and formal-parameter dependencies, sharing valid results
without copying syntax. See [declaration bindings](details/template_bindings.md)
and [call/local resolution](details/symbol_resolution.md).
A reusable toolchain index is a future option, not a database framework to build
first. Renames and invalidated AST bindings follow the
[incremental rules](details/incremental_refresh_rules.md).

## Performance and memory

Establish a fact once in its owning process, then expose it in a form that
following processes can reuse cheaply. Stage independence includes preparing
efficient consumer inputs. Add stored data only when the saved work justifies
its memory and invalidation costs; prefer shared records and compact indexes.

When touching a process, consider whether it can cheaply prepare facts its
consumers would otherwise rediscover, whether consumers can reuse an existing
fact through its owner's API, and what additional storage would cost. Separate
passes that establish different facts are reasonable; repeated discovery of
the same fact deserves scrutiny.

A process may call a following process's public preparation method when the
required fixed inputs are available. That method owns its logic and returns a
private result; the coordinator retains control of scheduling, joins and
publication. Preserve worker isolation and explicit incremental dependencies.

The [performance watchlist](details/performance_watchlist.md) records four
substantial candidates and links follow-up measurements. Optimization remains
deferred; these observations do not block the next compiler capability.

## Progress and verification

Use small, verifiable end-to-end goals. The [first-slice plan](details/first_slice.md)
owns the three-file example, common lowering flow, and initial gates.
Consider compilation, future parallel work boundaries, and resident incremental
replacement in every slice. Verify real results and a relevant composition;
measure actual work when claiming reuse or performance.

Refactor locally when a representation cannot honestly express the next
requirement. Preserve one reusable path and remove superseded code. Unresolved
design choices or wider ownership changes follow the
[repository rules](../reference/source-repository/working_rules.md). Extract abstractions for shared semantics or
implementation, and avoid accumulating case branches or speculative frameworks.
