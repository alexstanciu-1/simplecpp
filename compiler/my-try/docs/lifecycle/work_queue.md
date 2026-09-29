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
`task_run_publish` is unchanged. See the [runtime contract](../../../../specs/builtins/tasks/unordered_publication.md).
The tasks runtime module must be enabled for native builds. PHP executes callbacks
sequentially; concurrency/lifetime claims require native evidence. Historical proof
checkpoints are linked from the [portability review](../portability/conversion_review.md).
