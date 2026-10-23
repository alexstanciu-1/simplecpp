# Source object nullability integration checkpoint
Doc Status: supporting

Partial implementation of the approved coordinated NULL-01 design. Source Node and
?Node retain distinct identities; value boundaries compose checked presence and
existing ancestry. Optional member receivers retain existing lifetimes, references
remain invariant and callable adaptations only broaden parameter acceptance/strengthen
results. Weak class/interface locking publishes an optional result. Both C++ modes
reuse the same underlying shared storage/control block.

`/tmp/nullable-objects-06`: 14 tasks pass, including both-mode positive and runtime
failure executions, nine negative boundaries/override cases, and retained signature
rejection/recovery/reset. Positive timings: ordinary 22.29 s, concrete 23.82 s.
PHP/legacy/ordinary/prelowered parity passes in `/tmp/nullable-objects-portable-02`.
Generic optional-value regression passes; 54 upcast/ownership and 32 abstract-dispatch
native programs pass after intentional-null cleanup owners use optional declarations.
No full-suite or native compiler rebuild claim follows.

Bootstrap: conversion 4.67 s / 195,104 KiB RSS, 269 files / 1,968,322 PHS bytes.
Preparation 185.57 s / 457,108 KiB RSS (446.4 MiB), 436.5 MiB PHP heap. The old
ownership-boundary diagnostic disappears. New diagnostics identify nullable object-type
operations, nullable index-key admission and required declarations receiving null.
First blocker: Object type operation requires a shared handle. No fatal/OOM or C++
emission. Faster failure is not a compiler throughput benchmark.

Blocking remainder: object-type/probe/key/other ownership consumers, required-field
constructor exits, required-local definite assignment, runtime API presence guarantees,
source adaptations and broader historical fixture migration. Selection handling is
bounded to supplied optional base types and required coalesce fallbacks; optional
fallback/synthesized nullable common-base behavior still needs review/integration.
The baseline source probe and user-approved design are stored separately under
null01_integration. The saved revision identifies the parent of these measured edits;
the probe directory retains captured production source hashes and worktree diff.
