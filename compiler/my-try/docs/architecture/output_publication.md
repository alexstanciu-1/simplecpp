# Persistent C++ artifact publication
Doc Status: supporting

`compiler/Output_Publication` is an invocation-local coordinator worker. It consumes
the backend's immutable `Storage<cpp_artifact>`; parsing, collection, preparation
and C++ rendering do not know filesystem policy. Model retains no publisher or lock.
`output_file_ownership` holds scalar before/after digests for the worker, not a
second semantic identity model.

CLI: `--s2s SOURCE --entry main.phs --output ABSOLUTE_OUTPUT_DIRECTORY`. Entry remains
optional for one source. The complete JSON bundle is still printed after success.
Without `--output`, in-memory publication/transport is unchanged. HTTP cannot select
a disk destination.

## Writer protocol

Use the shared [checked file adapter](../../../../specs/portability/output_files.md)
and `Lock_Reservation`. A trusted Linux output directory is required; its parent must
exist. The worker obtains a nonblocking exclusive reservation on `.scpp-output.lock`
before touching publication state. Never remove/replace this lock inode. Contention
fails without changing the existing revision.

Construct the worker **before** initialization, synchronization or preparation.
Validate existing ownership metadata, then mark the attempt pending. Pass it to
`exec_cpp`, `update_cpp` or `cpp` and always `close()` in `finally`. One worker owns
one publish attempt; construct a fresh worker for retry. Parsing/preparation failures
cannot leave an earlier revision ready for this attempt. Corrupt ownership metadata
is refused unchanged, not adopted on retry.

Before artifact mutation, validate the whole candidate set and previously owned
existing files. Reject unowned collisions even with identical bytes, and edited
owned files. Missing owned files can be recreated. Only obsolete owned artifacts
are removed, matching authorized content. Unrelated files and empty directories
remain; no recursive cleanup occurs.

The atomic `.scpp-output.status` has two forms:

- `scpp-output-v1:ready:SHA256\n` checks the exact manifest bytes.
- `scpp-output-v1:pending:SHA256\nJOURNAL` contains and checksums its recovery
  inventory. Journal and authorization are one atomic file, avoiding a failure gap
  between independent metadata renames. Checksums are not hostile-writer authentication.

Journal JSON: `format: scpp-output-journal-v1`, `files` rows with `path`, `before`,
`after` SHA-256 strings; empty digest denotes no corresponding file. Before mutation,
record the union of owned/candidate paths, observed and intended content. Recovery
accepts either authorized content or absence after partial writes/removals. A pending
journal, not the potentially old/new manifest, owns recovery. Retry may publish a
different candidate and retire artifacts introduced by an interrupted attempt.

Typed digest-map reads use explicit `isset` guards before indexing. Legacy S2S
lowers `$map[$key] ?? ""` through a throwing `at()` read for an absent typed key;
that expression cannot implement the journal's empty-digest absence representation.
The [native accounting checkpoint](../planning/native_ninja_accounting_20261016.md)
proves initial publication and native addition/removal with the guarded reads.

Write changed artifacts via same-directory atomic replacement. Exact no-ops retain
inode/mtime. Write `.scpp-output.manifest` last, then the ready status. Manifest JSON:
`format: scpp-output-manifest-v1`, ordered `files` rows containing `path`, `sha256`,
`id`, `kind` (`header` or `translation_unit`). This is the complete artifact set and
translation-unit membership. The manifest also contains `runtime_build` with resolved
`sources`, `libraries` and `definitions` arrays from the selected module closure.
The backend supplies these requirements through the coordinator; publication does
not load catalogs or choose build policy. The ready digest covers both artifacts
and requirements. No-op manifests retain inode/mtime too; the status
file may change each attempt. `Compiler::cpp()` publishes Model output and acknowledges
preparation changes only after disk success. Failure leaves retry obligations intact.

## Readers and limits

Hold a shared reservation on the same lock **for the entire native build**.
`require_ready(root)` checks ready status against manifest and returns its bytes;
it neither acquires a lock nor verifies each artifact. The build consumer must check
content against manifest digests and compile/link exactly listed members. Never scan
the directory for link members or treat an old executable as the failed revision.

This is cooperative process-interruption recovery, not multi-file atomic rename,
a hostile-filesystem sandbox or power-loss durability. SIGKILL can leave unreferenced
adapter temporary files; these are ignored, never adopted. Missing/corrupt ownership
metadata needs explicit operator repair, not automatic adoption. The
[native C++ consumer](native_cpp_build.md) owns objects, depfiles, compatibility keys
and persistent runtime-only PCH reuse; those policies do not enter the publisher.

`tests/output_publication.py` covers PHP/native adapters, persisted crash boundaries,
preservation, ownership refusal/retry, preparation handoffs, CLI restart/failure,
reader/writer contention and native execution directly from published files.
Native adapter/facade proofs are not native compiler rebuilding.
