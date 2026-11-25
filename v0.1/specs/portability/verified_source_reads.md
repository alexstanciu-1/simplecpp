# Verified source reads
Doc Status: supporting

`read_sources\Snapshot_Reader::read(Source_File)` checks the discovered path, mtime
and byte size and returns a new `Source_Buffer` owning path, observed mtime and raw
contents. `Source_Reader::read(Source_Listing)` validates the entry position, reads
in listing order and returns `Source_Texts` only after every read succeeds. Failure
throws; no partial result is published and the discovery listing is unchanged.
Buffers are fresh objects and are immutable by usage after publication. They do
not alias discovery rows or buffers from another read. No persistent file IDs are
introduced; the entry index refers only to the returned vector.

Three production files retain prototype ownership: `read_sources/data/buffer.php`,
`read_sources/read.php`, `read_sources/main_read_sources.php`. The prototype's
path/handle/version protocol moves into the shared `scpp\fs_read_snapshot` adapter.
The compiler does not manage OS handles or use a native-comment escape hatch.

## Runtime boundary

Native assembly uses the existing `--filesystem` artifact/module. The adapter checks
the target's `fs_read_snapshot` result and throws on failure, preserving empty string
as successful empty content. The selected native backend is Linux only. It checks
regular-file kind, path/open-handle device and inode, expected mtime/size before and
after a bounded read, and final content length; descriptors close on every exit.
The final path component cannot be a symlink. Intermediate directory links follow
host resolution. Read at most expected size plus one byte to detect growth.

PHP performs the corresponding lstat/fstat/version checks with object-free local
metadata arrays, bounded stream reading and `finally` close. These arrays stay inside
the host framework; they are not compiler storage or converted source. Ordinary PHP
fopen does **not** provide the native no-follow/nonblocking/close-on-exec open flags.
A FIFO raced into place between initial lstat and fopen can block PHP; a temporary
symlink change is not prevented at open. Adversarial open-race behavior belongs to
native tests, not claimed PHP parity. Normal preexisting symlinks/FIFOs are rejected
before opening. The compiler adds no silent retry or partial-content fallback.

This is not an atomic snapshot. Same-inode edits retaining size and whole-second
mtime can pass, and edits after final checks are not excluded. Discovery supplies
mtime/size, not an inode retained from an earlier scan: identity is checked across
this read operation. Do not treat accepted content as proof of unchanged source
since a previous compilation. Locking, hashing, stronger versions, read scheduling,
content reuse and incremental reconciliation remain later work.

## Proof and measured iterations

The stage independently specifies 20 initial and 20 refreshed outcomes. It checks
exact bytes for empty/text/all-byte files, size/mtime mismatch, missing/directory/link/
FIFO paths, invalid arguments, pipeline composition from manifest through discovery,
entry-position bounds, independent buffers and whole-batch failure without input
mutation. The changed-files run demonstrates stale-version rejection and the
same-size/mtime limitation using the unchanged generated executable. It does not
inject concurrent kernel races or independently establish native descriptor cleanup;
those remain target-owned protocol/tests. No native Windows proof is claimed.

```sh
python3 compiler/tests/snapshot/run.py --results FRESH
python3 tools/php_portability/validate.py --results FRESH \
  --native compiler --target-checkout /tmp/scpp-json-240-probe
```

The target remains exact clean candidate `9b4b33f35f053b487e018c94d6a4a7888d77c64a`.
See [timings and native cycles](../planning/compiler_migration/results/snapshot-rewrite-01/README.md).
Next compiler stage: tokenizer, consuming these owned bytes with byte-based spans.
