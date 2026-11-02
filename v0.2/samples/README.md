# Reference source corpora
Doc Status: supporting

Copied from `compiler/my-try-cpp/samples/` on 2026-11-02. These are source
snapshots for future compiler coverage and workload tests, not examples that the
current v0.2 frontend can compile end to end.

| Corpus | Source root | PHS files | Source bytes |
| --- | --- | ---: | ---: |
| Small accounting | `accounting-small/` | 15 | 118,906 |
| Large accounting | `accounting-large/` | 93 | 528,088 |
| my-try compiler | `my-try/phpp/` | 257 | 1,943,499 |

The accounting corpora retain their source, legacy project configs, verification
scripts and test fixtures. The compiler corpus is the existing converted PHS
snapshot of `compiler/my-try`, including its framework support; it is not a copy
of the authored PHP compiler. Its converted PHS is intentionally retained as
sample source, not as generated build output.

Provenance files preserve original locations and hashes. Source files are copied
unchanged. Legacy `prism.json` configs and verification scripts have not been
adapted to the v0.2 module loader or build workflow. Paths and commands in copied
documentation refer to the original environments and may need adaptation.

Historical measurement notes are retained in `notes/`; they describe the donor
compiler and are not measurements of the current v0.2 compiler. Their linked raw
benchmark JSON remains in the donor directory. Refresh commands in those notes
update the donor, not these snapshots.

Build products, logs, caches, binaries, raw benchmark results and generated
converter bookkeeping (`.scpp-portability.json`, `.scpp-native-runtime.json`)
are excluded. No automatic generation or build is attached to this directory.

## Supported synthetic workload

[my-try-simple](my-try-simple/README.md) mirrors the compiler corpus's 257 source
paths with supported void functions, integer locals and cross-file calls. Its
repeatable benchmark covers stage timings and incremental frontend reuse.
