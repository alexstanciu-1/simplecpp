# Compiler source work queue and publication
Doc Status: supporting

Both `sync()` and standalone entrypoints use the same per-phase work queue.
Scanning selects source records in place. Tokenization joins before parsing starts;
parsing joins before cleanup and preparation. No private whole-file replacement
pipeline or second declaration comparison remains.

## Work and execution

`Source_Work_Queue` is invocation-local. Membership is sealed before dispatch; each
source has at most one work item. Items move queued -> running -> published or failed.
Completion checks ownership and state. Records carry the retained source/previous parse,
a private tokenization input, and the current phase result. Model never owns workers.

`Compiler.jobs` is the positive concurrency limit, defaulting to
`DEFAULT_COMPILER_JOBS` (12). PHP runs the same callbacks sequentially; the native task
executor bounds work, serializes publication and joins the batch. Native compiler
validation of this refactor remains on demand.

## Parsing and publication

Parser invokes collector methods as declarations are recognized. Private scope updates
need no lock. Exported declaration registration calls `task_synchronize`, using the
batch publication mutex. `Source_Publication::publish_stage` installs tokens or the
mutable parse result, including incomplete results retained for retry.

After joining, successful-file revision sweeps mark deleted symbols. Parse errors are
reported after other files finish. Earlier read/tokenization errors prevent parsing.
No rollback or transactional batch guarantee is implied. Concurrent compiler sessions
remain unsupported because Model is static.

`publish_parsed` remains a standalone helper for completed isolated parses; it is not
used by the incremental compiler worker. It exports declarations once and marks their
membership so shared deletion cleanup can remove both local and global indexes.
Scope publication links make all global duplicate candidates visible during lookup.
File executable variables stay private to their separate scope.

Module/source indexes determine traversal order independently of worker completion.
No retained root reordering or path-based publication join is required.

## Runtime boundary and validation

`task_run_publish_unordered` is the narrow operation used here. Existing ordered
`task_run_publish` is unchanged. See specs/builtins/tasks/unordered_publication.md.
The tasks runtime module must be enabled for native builds. The compiler native
harness enables it and uses the default worker limit, except for the explicitly
single-worker pipeline-order check.

PHP tests exercise reversed publication, cross-file identity, local visibility,
duplicates, sealed membership and failure barriers. A native runtime probe gates a
slow earlier job on publication of a later job, proving completion-order publication;
it also checks exclusion, concurrency bounds and joining after work/publisher errors.
The native compiler harness checks PHP/output parity, repeated runs and recovery.

Compiler.tokenize is an explicit read/tokenize-only batch for callers that want a
stage boundary; Compiler.parse reparses retained snapshots without disk reads.
Compiler.exec_llvm uses sync, the combined update chain, instead of these two batch APIs.
Tokens and parsing results are published together after a successful full-chain job.
A parse failure in exec therefore does not publish that job's private token result;
explicit tokenize() followed by parse() retains the already published token batch.

Module discovery sets disk_source=true. Tokenizer then invokes File_Loader before
scanning. In-memory callers retain disk_source=false and supply content directly.
Discovered file records have empty content and zero metadata placeholders until the
worker reads them; those placeholders are not authoritative filesystem observations.
The host report displays source text after execution. Disk contents are reread for each notified file; unchanged files retain their parse.

Directory discovery must still complete before dispatch. Dynamic discovery and
independent stage queues/work stealing remain possible later work. This slice uses
a fixed batch of per-file chains per update.
