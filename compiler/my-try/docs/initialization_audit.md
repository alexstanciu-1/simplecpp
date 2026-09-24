# Initialization and explicit-nullability audit
Doc Status: supporting

The constructor/per-invocation worker refactor and its native analysis results are
documented in [worker lifetimes](worker_lifetimes.md). The record audit below remains
valid; historical init-before-use worker descriptions are superseded by that note.

Reviewed areas: Model roots, source-loading records, tokenization, AST and parser state. This is
an audit of current PHP construction/publication paths, not native STAN proof.
The [ownership rule](ownership.md#explicit-nullability-and-publication) remains:
required fields are assigned before use/publication; optional absence uses ?T.

| Record/state | Required initialization | Optional state / result |
| --- | --- | --- |
| Model roots | Compiler.init calls Model.reset before any pipeline stage. reset initializes modules before reset_tokens traverses them; downstream resets initialize their roots before rebuilding. | Empty collections are valid; no nullable roots needed. Stage entry before init is outside the lifecycle contract. |
| module | Constructor initializes files; Module_Loader assigns path before discovery. Compiler publishes a module only after loading succeeds. | No optional fields. Reusing the loader directly clears files first and can leave a partial list on failure. |
| file | File_Loader completes metadata/content reads before assigning path, mtime, size and content. Module_Loader publishes each file only after success. | tokens is the sole optional field, initially null. Successful reload clears it; failed load preserves the previous record and backlink. |
| token_list | Constructor initializes tokens; Tokenizer assigns file and captured content before scanning, and returns only on success. | Empty input produces a completed empty list; no optional fields. |
| token | Tokenizer assigns offset, length and text before append. | No optional fields. text remains required until a separately proved native representation replaces it. |
| Tokenizer worker | Constructor requires source; tokenize captures content before any token_end call. Each scan creates a fresh result. | No optional worker fields. init may select another source on an already valid worker; constructor assignment satisfies native initialization analysis. |

Compiler.tokenize clears downstream model roots and file backlinks before scanning.
A lexical failure cannot publish the unfinished token list. Completed earlier files
may remain published; this is not transactional compilation. Retained token lists
keep their own text snapshot when their file is reloaded or edited.

Direct File_Loader use only updates that file and its backlink. It does not reset
Model's downstream collections; use the coordinator's stage lifecycle before
consuming rebuilt results. File size/mtime come from stat, while content is read
separately: concurrent filesystem changes can make them disagree. Resolving that
host-read contract is later source-portability work, not a nullability fix.

## Evidence

Existing model tests cover populated/empty roots, stage reset, lexical/parse failure
and retained results. The added source-initialization test checks required metadata,
exact token spans, failed reload preservation, successful empty reload invalidation,
scanner reuse and old source-snapshot retention.

## AST and parser pass

No missing production initialization or additional nullable fields were found.
`Parser.node` assigns kind, start/end positions and payload before returning a node;
its caller completes each emitted payload before passing it to that boundary.
Constructors initialize every child collection. Empty lists remain valid required
objects, including empty bodies, parameter/argument lists and array literals.

| Optional field | Meaning |
| --- | --- |
| ast_node.specialization | Null for leaf/payload-free kinds; required for the other kinds by Syntax_Nodes validation. |
| scope.parent | Null for a root; function-local scopes receive their parent before parsing their parameters/body. |
| parameter.reference_token_index | Null for value passing; present for reference passing and points to the ampersand. |
| return.expression | Null for a bare return; keyword and semicolon are always initialized. |
| binding.type_syntax | Null for an untyped write. |
| binding.target | Null for a plain named binding; present for indexed/field writes. |
| binding.equals_token_index and value | Both absent for an uninitialized typed declaration, both present for an initializer/write. |
| Parser.target_scope | Null selects a fresh standalone root scope. init without a target resets a previous external target. |

Function return type/body, parameter and field types, block scope, array extent and
index/field base references remain required. parsed_file.tokens/root/collection are
required even though root and collection are assigned at the end of parsing. The
weak convenience collection link is not optional on a completed result.

Parser.init must precede parse. Each parse resets position, result, collector and
current scope. Worker fields are assigned before dependent calls; this PHP audit
does not prove native STAN will follow cross-method initialization. block restores
the enclosing scope in finally on both success and failure. Declaration indexes in
an external scope are updated by collector.finish only after syntactic completion.
Earlier successfully parsed files remain in that scope; repeated successful parsing
into the same external scope adds declarations again, so coordinator rebuilds use a
fresh scope. No rollback on allocation failure or debug-reporting failure is claimed.

The AST fixture now checks declared properties with ReflectionProperty.isInitialized;
get_object_vars alone silently omits uninitialized properties. It checks emitted
nodes/payloads, parsed results, scopes and completed occurrence records. Additional
cases cover optional-field combinations, empty literals, failure after a completed
struct and inside a function, recovery into the same external scope and return to
standalone mode. The unused binary payload family has no parser construction path;
its required fields were inspected but are not claimed as parser-executed coverage.

## Collector and preparation pass

Completed occurrence and preparation records need no additional nullable fields.
Collector construction initializes its source and lists. record assigns entry
references/name/kind/token position, appends, then assigns the returned local_index
before exposing that index to its caller or work lists. That brief incomplete
state is private construction, not external publication; no stored ID is guessed
from count. The collected_file root is assigned by finish before scope indexes
receive declarations. Repeated finish and record-after-finish reject before mutation.
No transactional guarantee is made for allocation failure during index publication.

Name_Preparation creates fresh empty maps and returns only after resolving the
supported uses. Missing map entries are absence, not nullable stored records. Its
scope-walk locals can reach null through scope.parent; explicit local conversion
annotations need review when adapting this worker.

LLVM_Preparation initializes policy, instance registry, pending queue and identity
maps before using them. Struct records receive declaration/name and complete field
records before registration. Prepared files receive source/names before being added
to the private result. Function registration sets declaration, owning file, body,
name, return type and entry status before publishing into private registries. Body
preparation fills its initialized collections later, permitting recursive targets.
Only completion of the entire queue returns the prepared program to the caller.

Locals receive declaration/type/address before insertion. Optional metadata is
populated before that insertion. Parameter mode/local/incoming operand type/text
are assigned before appending. Generator output functions/blocks/operands likewise
have required fields assigned before return/publication; emitted incoming operands
are independent copies. Public mutable records still rely on caller discipline.

| Optional field | Meaning |
| --- | --- |
| llvm_prepared_function.declaration | Null for the synthesized entry only; other instances reference a source declaration. |
| llvm_local.array_type / llvm_place.array_type | Present for whole fixed-array storage; absent for scalar/struct storage and scalar projections. |
| llvm_local.struct_type / llvm_place.struct_type | Present for whole struct storage; absent for scalars/arrays and scalar field projections. |

No optional worker fields are needed for "not initialized yet". Private workers
may retain incomplete scratch state after an exception; retry through their public
preparation entry resets the state before use. This is not a resumable failed run.
The tests exercise failure in struct preparation, name resolution and queued local
preparation, then reuse the same worker with an earlier successful source graph.
Prior prepared graphs remain unchanged. Published model/preparation graph checks
now reject every uninitialized declared field instead of silently skipping it.
Collector tests prove delayed index publication and rejection after finalization.

## Native-runner pass

native_process_result requires exit_code/stdout/stderr. The process is closed before
streams are read; a complete result is returned only when both reads succeed.
Empty strings are valid output, but false from a failed host read must throw instead
of being coerced into an empty string. Toolchain JSON is read through the same checked
boundary and its clang entry is validated before creating temporary artifacts.
Source writes also require the full expected byte count.

native_result.build is required. execution is explicitly nullable: null means the
build failed and the executable was not run. A nonzero executable exit still has a
complete execution result. Launch/capture errors throw; no partial native_result is
returned. proc_close status is retained as reported, including an unknown/error
status; the runner does not turn it into success.

The runner has no mutable per-run fields. Its finally block removes the tracked
LLVM sources, output/capture files and directories after success or failure.
Tests cover invalid LLVM, successful build with executable exit 7, nonempty and
empty streams, missing capture files, process reuse, and cleanup on source-write
failure. Fixtures use host reflection for the private process boundary rather than
adding a production test API. Cleanup under filesystem permission failures, process
timeouts and arbitrary files created by executed programs is not guaranteed by
this bounded runner and remains outside this audit.

## Audit boundary

The current production record families have been reviewed for initialization and
explicit absence. Native STAN proofs and unsupported host APIs remain conversion
work; this PHP audit does not establish native portability. No blanket nullable or
fake-default edits were needed. Test fixtures that deliberately construct partial
records for isolated operations do not make production fields optional.
