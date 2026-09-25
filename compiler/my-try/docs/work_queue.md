# Compiler parse work queue and publication
Doc Status: supporting

This slice adds no incremental compilation, revisions, cache reuse or replacement
policy. Compiler.init continues to reset Model. Discovery/reading and tokenization
remain their existing stages; the first queue processes parsing work only.

## Work and execution

Compiler.parse owns a transient Parse_Work_Queue of token snapshots and work records.
Membership is sealed before dispatch. Each work record moves from queued to running,
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

Subsequent queue slices can add discovery/read/tokenize orders and dynamically
schedule dependent work. This implementation processes the fixed parse batch only;
it does not claim a complete streaming pipeline or incremental compiler.
