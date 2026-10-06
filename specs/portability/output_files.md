# Checked output-file adapter
Doc Status: normative

Scope: compiler/tooling publication, not PHP filesystem compatibility. Approved
2026-10-06 for Chapter 08. This adapter supplies mechanisms, never manifest policy.

`fs_output_root(path)` creates one output directory if missing (its parent must
exist), validates an absolute non-root path with no symlink components, and returns
its canonical path. `fs_output_read(root, relative)` returns exact bytes or null
for a missing file. `fs_output_write(root, relative, bytes)` atomically replaces one
regular file and returns whether bytes changed; identical files retain inode/mtime.
It creates missing relative parent directories. `fs_output_remove(root, relative,
expected_bytes)` removes a regular file only if its bytes match, returns false when
absent, and rejects edited files. `content_sha256(bytes)` returns lowercase SHA-256.

Relative names use ASCII letters/digits, underscore, hyphen, dot and slash; empty,
absolute, repeated-separator, dot and parent components reject. Existing symlinks,
directories at file targets, and special files reject. Binary bytes are supported.
Errors throw through the portable facade. Native internal entrypoints use results.
The adapter must never recursively delete, adopt a manifest, or infer file ownership.

Callers own a trusted output tree and hold a stable exclusive file reservation for
all mutation; readers retain a shared reservation while inspecting/using a revision.
These are Linux/local-filesystem primitives. Noncooperating directory replacement
or edits during an operation are outside the contract, especially PHP's path-based
implementation. Symlink checks are not a hostile-filesystem sandbox guarantee.
Temporary files are exclusively created beside the destination and only the exact
temporary created by the invocation is cleaned. A process killed before rename may
leave an unreferenced temporary; it must never be mistaken for a committed artifact.
No power-loss durability or multi-file atomicity is claimed. The coordinator owns
the lock, ready/failed state, recovery journal and manifest-last protocol.

Reference behavior: kept—ordinary exact file bytes and same-directory rename;
modified—checked relative paths, no symlink/special-file targets, explicit errors,
no-op preservation and expected-content deletion; dropped—general PHP coercions,
arbitrary paths, recursive deletion and cross-platform guarantees.

Placement: PHP facade in `tools/php_portability/runtime/output_files.php`, native
filesystem mechanisms in `runtime/include/modules/filesystem/output_files.hpp`,
PHS error bridge installed with `--filesystem`. Internal `scpp_output_*` bindings
are for the portability framework; this does not register new my-try source APIs.
Native code is header-only for this bounded tooling slice, selected through the
filesystem module. No extra link/crypto library is required.

Proof runner: `compiler/my-try/tests/output_publication.py`. It checks independent
SHA-256 expectations across block/padding boundaries, binary and empty IO, PHP/native
path/error parity, and a converted native facade (`--no-stan`). This does not imply
the whole compiler is natively validated; see its Chapter 08 checkpoint.
