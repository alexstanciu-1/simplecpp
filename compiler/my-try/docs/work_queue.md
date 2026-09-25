# Compiler source work queue and publication
Doc Status: supporting

This slice adds no incremental compilation, revisions, cache reuse or replacement
policy. Compiler.init continues to reset Model. Folder discovery remains synchronous
and records paths without reading source bytes.
The queue processes each file through reading, tokenization and parsing.

## Work and execution

Compiler owns a transient Source_Work_Queue of file work records. exec dispatches
one read/tokenize/parse chain per file: parsing begins immediately after that file
is tokenized, without a project-wide tokenization barrier. A worker holds its slot
for the whole chain, so these stages share one concurrency budget.
Membership is sealed before dispatch. The same source record cannot be queued
twice, preventing concurrent writes to its metadata/content. Parse-only snapshots
must belong to the queued source. Each work record moves from queued to running,
then published (only after publication) or failed. Completion verifies queue
membership and state. The queue never enters the retained Model.

Compiler.jobs is the positive native concurrency limit (default 1):

```php
$compiler = new Compiler();
$compiler->jobs = 4;
```

The task executor bounds concurrent work, serializes publication and joins workers.
PHP implements the same work/publish calls sequentially; native uses actual worker
threads. The compiler's success barrier checks the returned publication count and
all work states AFTER joining. Cross-file LLVM/name preparation starts only after
that barrier. Work/publisher errors join workers and escape, so generation does not
run. Already published files can remain visible until the next reset; no rollback
is promised. Which error wins when several jobs fail is not specified.

## Isolated parsing and locked publication

Compiler invokes Parser without a caller-owned global scope. Each result owns its
file root and nested scopes. Symbol_Collector writes only those private scopes.
The legacy direct Parser API accepting an external scope remains available; it is
not used by compiler workers.

Compiler.publish_parsed installs references to root-scope declarations in
Model.global_scope, retains the completed parse/collection, and records the root
scope's native weak publication link. Function locals are not exported. Declaration
records remain file-owned. Duplicate publication is rejected before index writes.

The executor calls this compiler-owned operation under one dedicated batch-local
native publication mutex, immediately after work completes, without waiting for an
earlier input. Work runs outside the lock. Direct callers of publish_parsed must
serialize themselves. Concurrent compiler sessions remain unsupported because Model
is static; the batch lock is not a global lock between independent compilations.

Scope_Lookup::visible follows the publication link during resolution. A file's
ownership boundary does not introduce a new language-level global scope: global
duplicate candidates remain visible even if the file defines its own matching name.
Unpublished standalone parses still use local maps. AST/occurrence scope identity
is preserved, including the LLVM experiment's same-file variable rules.

After joining, retained syntax/collection roots are reordered by original input
position, preserving generated filenames/output order independently of completion.
Global declaration pools keep every candidate; scheduling cannot select a winner.

## Runtime boundary and validation

`task_run_publish_unordered` is the narrow operation used here. Existing ordered
`task_run_publish` is unchanged. See specs/builtins/tasks/unordered_publication.md.
The tasks runtime module must be enabled for native builds. The compiler native
harness enables it and exercises three jobs per parse pass.

PHP tests exercise reversed publication, cross-file identity, local visibility,
duplicates, sealed membership and failure barriers. A native runtime probe gates a
slow earlier job on publication of a later job, proving completion-order publication;
it also checks exclusion, concurrency bounds and joining after work/publisher errors.
The native compiler harness checks PHP/output parity, repeated runs and recovery.

Compiler.tokenize is an explicit read/tokenize-only batch for callers that want a
stage boundary; Compiler.parse reparses retained snapshots without disk reads.
Compiler.exec uses the combined chain instead of calling these two batch APIs.
Tokens and parsing results are published together after a successful full-chain job.
A parse failure in exec therefore does not publish that job's private token result;
explicit tokenize() followed by parse() retains the already published token batch.

Module discovery sets disk_source=true. Tokenizer then invokes File_Loader before
scanning. In-memory callers retain disk_source=false and supply content directly.
Discovered file records have empty content and zero metadata placeholders until the
worker reads them; those placeholders are not authoritative filesystem observations.
The host report displays source text after execution. Disk contents are reread on
each scan; this is not caching or incremental compilation.

Directory discovery must still complete before dispatch. Dynamic discovery and
independent stage queues/work stealing remain possible later work. This slice uses
a fixed batch of per-file chains and adds no incremental behavior.
