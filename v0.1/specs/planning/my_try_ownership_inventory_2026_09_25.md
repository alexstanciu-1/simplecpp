# my-try responsibility and file ownership inventory

Doc Status: planning

Reviewed 2026-09-25 against the current workspace, following the first integer
S2S slice. This is an inventory and proposed ownership map, not an implemented
refactor or a change to language semantics. Scope: `compiler/my-try` production
pipeline, its composition root, and supporting ownership documentation. This is
not a line-by-line correctness or performance audit of the experimental backend.

## Assessment

The coordinator has accumulated concrete algorithms that deserve named owners.
The strongest example is `Compiler::compare_declarations`: declaration matching,
token comparison, change classification and retained deletion evidence are all
implemented inside the stage coordinator. Recent additions also have ownership
problems; the integer slice is included in this inventory.

The correction is not to remove every method from a data structure. AST linking,
scope access and private index maintenance legitimately protect representation
invariants. Keep those encapsulated. Processing policy, source-language adaptation
and backend interpretation should have explicit owners outside those operations.

Proposed paths below are relative to `compiler/my-try`; they identify concrete
possible homes, not a requirement to create every file immediately. Locations
refer to the workspace at review time; method names remain the stable anchors.

## Clear extraction or ownership candidates

| ID | Current location | Responsibility found there | Proposed owner and reason |
| --- | --- | --- | --- |
| O01 | `compile/compile.php:187`, `spelling`, `declaration_text`, `declaration_key`, `compare_declarations` | Significant-token comparison; declaration identity matching; header/body changes; unmatched old declarations marked deleted | A `Declaration_Changes` worker in `compile/sync/declarations.php`. It consumes the previous/candidate syntax and collection records. The coordinator invokes it; the parser and collector should not acquire update-history responsibilities. Move this existing behavior without expanding invalidation. |
| O02 | `compile/compile.php:75`, first half of `sync` | Clear notification flags, deduplicate paths, determine module membership, construct source candidates, distinguish deletion from reading | A source synchronization worker in `compile/sync/sources.php`. The compiler chooses when to synchronize; this owner defines how notifications become work items. Do not put this policy into the file record or tokenizer. |
| O03 | `compile/compile.php:163`, `find_source`, `find_syntax`, `publish_update`, `order_roots`, `publish_parsed`, `publish_scope` | Find retained live roots, replace one source, publish scopes, preserve deletion evidence, rebuild ordered parallel roots | A publication worker in `compile/publication.php`. These operations maintain one retained graph and should be grouped, rather than scattered among model records. The coordinator retains scheduling and the serialized publication boundary. |
| O04 | `03_parse/structures.php:195`, `scope`; `04_analyze/prepare.php:117`, `Scope_Lookup` | Shared scope storage/API and lookup, used by parsing, collection, synchronization and preparation | A shared scope owner, for example `scopes/structures.php` plus `scopes/lookup.php`. The current split makes scope look parser-owned and puts common lookup beside experimental name preparation. Move the existing concept; do not introduce another registry or change parent lookup. |
| O05 | `03_parse/structures.php:255`, struct branch of `scope::register` | Construct a source `type_definition` and choose its category/origin while inserting a scope entry | Source-definition construction under `04_analyze/types/source.php`, called from collection's declaration registration. Scope accepts an already constructed definition through its private-storage API. Construct once and preserve identity when publishing. This issue was introduced by the recent slice. |
| O06 | `04_analyze/collect/collect.php:21`, `Symbol_Collector::record` | Read source spelling and remove a leading PHP `$` to manufacture the collected name | Frontend name normalization, initially in `03_parse/parser.php` or a local parser helper. The shared collector should receive the normalized name while retaining the token index for provenance. This matters directly to the agreed PHS-shaped canonical model: other frontends should not depend on a collector knowing PHP spelling. No new frontend is required for this cleanup. |
| O07 | `04_analyze/bindings.php:128`, `Integer_Literals::decimal` | Decimal literal acceptance, exact value preservation, signed-64 representability | Shared literal preparation in `04_analyze/literals.php`. Binding preparation consumes a prepared value/type; it does not own numeric spelling rules. Keep the current bounded integer contract, not a speculative general numeric framework. This is another recent-slice placement issue. |
| O08 | `01_prepare_inputs/structures.php:12`, `SYNC_*` | Change protocol shared by source files, declarations, synchronization and lookup | Synchronization data definitions, e.g. `compile/sync/structures.php`. These are not input-loader-specific constants. This is a placement correction; changing flag representation is a separate decision. |

O01–O03 belong to compiler lifecycle ownership, even after extraction. Moving them
to a dedicated owner does not mean every operation belongs in a numbered frontend
stage. Conversely, `Model` should not become the destination for all these algorithms.

## Boundaries to clarify, without forcing extra abstractions

| ID | Current location | Assessment | Bounded proposal |
| --- | --- | --- | --- |
| B01 | `compile/compile.php:441`, `frontend`, and worker callbacks inside `sync` | Per-file work, queue transitions, barriers and publication are repeated. However, standalone tokenize/reparse and candidate replacement have different contracts. | Separate the per-file operation from publication when extracting the lifecycle owners. Preserve standalone reparse without rereading and candidate isolation during sync. Do not merge them through a growing collection of boolean switches. |
| B02 | `03_parse/structures.php:359`, `scope::replace_source` / `retain_other` | Private index maintenance is rightly encapsulated, but the operation also encodes the synchronization rule “remove superseded live entries, retain deleted evidence.” | Let the publication owner decide when replacement happens and own the documented policy; keep actual index mutation inside scope. An explicit scope replacement operation can remain valid. Do not expose arrays merely to make scope look passive. |
| B03 | `compile/model.php`, `Model::reset_syntax` | Root construction and resetting belong here under the current model. Calling `Language_Types::install` also makes a data-root reset perform language bootstrap. | Clarify this as session/bootstrap responsibility if extracting initialization. Preserve the contract that every syntax reset leaves a populated language parent and a usable global scope. Moving only the installer to `Compiler::init` would break other reset callers. No urgent new bootstrap framework is needed. |
| B04 | `04_analyze/bindings.php`, `Binding_Preparation::prepare` / `expression` | The worker currently prepares the complete supported straight-line file, including return expressions, rather than only bindings. | A clearer file-preparation name is reasonable. Keep the small expression routine local for now; extract a separate expression worker when another real caller or added behavior needs it. Extracting the already named literal concept is independently justified by O07. |
| B05 | `05_cpp/generate.php:6`, `CPP_Types` | Correct subsystem, separate concern: canonical type to C++ representation mapping sits in the renderer file. | `05_cpp/types.php` is a clearer home. This is file organization, not a wrong-stage semantic rule. Keep native C++ spelling and required headers in this backend. |
| B06 | `02_tokenize/tokens.php:25`, `Tokenizer::tokenize` calls `File_Loader::init` | Tokenization currently also obtains disk bytes. This is consistent with the existing per-file read/tokenize/parse chain but couples direct tokenizer use to I/O. | If separating it, let the frontend worker read before tokenizing. Preserve in-memory sources and direct-call contracts. Do not introduce a global read barrier or call this an existing concurrency defect. |
| B07 | `compile/host_report.php`, `Host_Report::run_native` | Host presentation and running the experimental executable share a class. It is already separate from portable stage coordination. | Host-driver placement/naming cleanup only; not a priority for the generation slice. The native tool invocation itself already has its own `Native_Runner`. |

## Existing debt outside this S2S cleanup

| ID | Evidence | Ownership issue | Treatment |
| --- | --- | --- | --- |
| D01 | `04_analyze/templates.php:13` accepts `Storage<llvm_prepared_file>` and `llvm_policy`; `template_check_context` in `04_analyze/structures.php` stores those backend records | A backend-coupled checker and its invocation data appear to be generic analysis infrastructure | Record as experimental LLVM/checking ownership debt. If later isolated, keep its data with its checker/backend contract. Do not port it into shared S2S preparation or implement validation now. |
| D02 | `04_analyze/prepare.php:9`, `Name_Preparation`; production caller in `05_llvm/prepare.php:51` | Current name preparation is the older bounded experiment, despite the broad file/class naming. Shared `Scope_Lookup` now lives in that same file | Extract common lookup under O04. Keep the experimental preparation behavior available to regression callers. Its current implementation is not the canonical resolver specification. |
| D03 | `03_parse/structures.php:314`, `source_types_named`; callers in `Name_Preparation` and `05_llvm/structs.php` | Source-only type projection supports the older declaration-based type model | Consider a compatibility adapter near those consumers when isolating the experiment. The query itself is not inherently invalid on a scope; this is a consumer-driven API distinction, not a reason to remove it now. |
| D04 | `03_parse/parser.php:187`, template-parameter/function-name conflict rejection | A name restriction is enforced during parsing, beyond recognizing syntax | Record for the second validation pass. Keep syntax/representation integrity checks distinct from language validation; do not remove existing checks as part of file moves. |

The LLVM integer policy and its emitters remain experimental. They do not supply
the Simple C++ integer contract or the new literal owner. Existing checks and
synchronization are inventoried because they exist, not because this review
authorizes developing validation or selective invalidation.

## Owners that should stay

- `Compiler::exec_cpp`, `update_cpp`, and high-level stage sequencing belong in the
  coordinator. Calling preparation and emission is its job. The current one-file
  restriction does not by itself justify a new program-planning subsystem.
- `Model` owns retained roots and their reset invariants. The new prepared S2S
  records belong to shared preparation; C++ output records belong to `05_cpp`.
- `ast_node` navigation/linking and `Syntax_Nodes` construction/access protect
  AST representation. Methods in a structures file are not automatically misplaced.
- Scope methods should continue to hide maps, type storage, parent/publication
  links and mutation. Preserve ordinary parent lookup and the LANGUAGE+RUNTIME
  parent of global scope.
- `Symbol_Collector` records occurrences during parsing and registers declarations
  after the file succeeds. That timing is deliberate; do not add another AST walk
  just to separate files.
- `Language_Types` owns hardcoded language definitions. `CPP_Types` owns C++
  representation. Runtime/library JSON loading remains future work.
- `Source_Work_Queue` owns work-state transitions. `File_Loader`, `Module_Loader`
  and `Native_Runner` already have named input/workflow responsibilities.

## Documentation drift found

- `MODEL.md:57` says preparation records remain transient; later v0.2 text and
  `Model::$prepared_files` describe retained S2S preparation. Qualify the earlier
  statement as referring to the experiment.
- `MODEL.md:109` still names `scope.parent`; the field is now `scope.enclosing`.
- `04_analyze/README.md` and the header of its `structures.php` describe the older
  preparation only. They should include the new shared S2S records and distinguish
  them from experimental LLVM/checker data.
- `Symbol_Collector`'s “publish declaration pools” wording should distinguish local
  registration from serialized publication into the retained global model.

## Suggested order and proof for a later refactor

1. Extract declaration comparison (O01), preserving its matching and tombstone
   behavior. Then group synchronization planning/publication (O02–O03). Reuse the
   incremental, publication and queue regression tests, including duplicate names,
   body-only edits, deletion and re-addition. This is preservation, not new
   invalidation work.
2. Consolidate shared scope/lookup ownership and source-definition construction
   (O04–O05). Preserve private storage, type/declaration identity, parent versus
   publication links, ordinary shadowing lookup and successful-file publication.
3. Put frontend spelling normalization and shared literal preparation in their
   owners (O06–O07); relocate protocol definitions with their lifecycle owner (O08).
   Verify collected names/provenance and unchanged S2S output, including integer
   boundaries and declaration-on-first-assignment behavior.
4. Apply justified small file/naming cleanups and reconcile ownership docs. Leave
   deferred checker/backend work deferred.

Moves must update `boot.php` and any explicit portability source lists/native
driver composition. PHP tests alone would not prove that the portable compiler
still exports/builds. Use existing PHP/native parity and C++ S2S proofs for a code
refactor; no fresh performance study is needed for this inventory.

At inventory creation, no production files had been changed and no runtime tests had been rerun.

## Resolution — current ownership refactor

The original locations above are retained as review evidence. The requested
implementation now addresses the inventory as follows:

| Findings | Resolution |
| --- | --- |
| O01 | `Declaration_Changes` in `compile/sync/declarations.php` owns comparison. |
| O02 | `Source_Synchronization` in `compile/sync/sources.php` owns candidate planning. |
| O03 | `Source_Publication` in `compile/publication.php` owns root lookup, publication/replacement and ordering. `Compiler::publish_parsed` remains a delegating compatibility entry for existing callers. |
| O04 | Scope and lookup moved to `scopes/structures.php` and `scopes/lookup.php`. |
| O05 | `Source_Types` in `04_analyze/types/source.php` constructs definitions; the collector registers them through scope's encapsulated API. |
| O06 | `Parser_Run::record_name` normalizes PHS spelling; `Symbol_Collector::record` accepts the canonical name. A test deliberately supplies a name different from the source token. |
| O07 | `Integer_Literals` moved to `04_analyze/literals.php`. |
| O08 | Shared flags moved to `compile/sync/structures.php`. |
| B01 | `Source_Frontend` handles private per-file operations and queue failure transitions. `frontend_operation` replaces boolean mode combinations. Publication remains separate and serialized by the scheduler. |
| B02 | Publication owns replacement timing/policy; scope retains encapsulated mutation. This existing API is intentionally retained. |
| B03 | Retained and documented: every Model syntax reset must establish the populated language/global graph. A separate bootstrap framework is unnecessary for this slice. |
| B04 | Renamed `Binding_Preparation` to `File_Preparation`, in `04_analyze/file.php`; its small expression helper remains local. |
| B05 | `CPP_Types` moved to `05_cpp/types.php`. |
| B06 | Retained and documented: tokenizer's disk/in-memory public contract and the per-file read/scan/parse chain already have regression coverage. Moving disk reads would change that API without a current requirement. |
| B07 | Retained host adapter; execution already delegates to Native_Runner. Further host naming cleanup does not affect S2S ownership. |
| D01–D04 | Still deferred as specified. Shared lookup was extracted from the experimental preparation file; no new LLVM or validation behavior was added. |

The Model and analysis documentation drift is corrected. Host composition and both
native source-discovery lists include the new owners. Production changes are local;
this task does not create a PR or push these changes.

Verification passed: full PHP/C++ regression and all 142 native comparisons. See
[ownership refactor evidence](results/my_try_ownership_01/README.md) for commands,
source fingerprints, diagnostic limits and attempt history.
