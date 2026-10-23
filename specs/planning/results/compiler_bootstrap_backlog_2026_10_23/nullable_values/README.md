# Generic optional value-boundary checkpoint
Doc Status: supporting

Capability-based nullable value admission reuses value_storable, existing copy/empty
boundaries and optional-value transport. No container-name whitelist or new backend
protocol is added. Unsupported payload truthiness still rejects. Class-handle
contracts, generic take and Result<nullable<T>> transport remain separate work.

- `/tmp/nullable-values-03`: ordinary/prelowered native PASS, 19.28 s / 25.88 s;
  four negative cases PASS. Includes nested unfamiliar provider values, copy
  isolation, exact extraction, lazy fallback, optional defaults, the compiler's
  enum contract-list absence/empty distinction and noncopyable empty state.
- `/tmp/nullable-values-portable-01`: PHP, legacy native, ordinary and prelowered
  native executions agree on absent/present-empty nullable containers.
- Nullable/record/conversion/probe/capability preparation regressions pass. Updated
  nullable-default retained rejection/recovery passes; scalar-default native controls
  pass in both modes. See execution ledger for corrected initial fixture failures.

Bootstrap: conversion 4.72 s / 194,824 KiB RSS, 269 PHS files / 1,962,525 bytes.
PHP-hosted preparation takes 208.90 s / 478,356 KiB RSS (467.1 MiB), 459.0 MiB
PHP heap. The nullable payload-whitelist diagnostic disappears; no diagnostic text
is added. First remaining diagnostic: ownership boundary requires matching handles,
a proven safe same-family upcast, or null storage initialization/assignment.
No fatal/OOM, C++ emission or native compiler rebuild. Input/source hashes and
captured worktree diff remain in the probe directory; saved revision identifies
the parent commit, with the measured production changes supplied by this slice.
