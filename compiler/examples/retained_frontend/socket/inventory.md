# Native source inventory digests

The Linux native frontend links OpenSSL libcrypto through its private adapter.
With the normal project build complete, pass an application directory or an explicit module manifest.

```sh
build/clang/native-frontend/native/program inventory /path/to/application
```

The command reads every selected source in 64 KiB chunks and returns one JSON
object keyed by the compiler's module-qualified source key. Each row contains
the selected flavor, the lowercase SHA-256 of the exact file bytes, and the
generated C++ unit name derived from the SHA-256 of the source key. Output is
published after the complete scan succeeds. A missing selected file fails the
scan. Each invocation selects sources directly from the manifest and filesystem.
An explicit `sources` list or path-to-flavor map replaces recursive discovery.
Discovery skips hidden entries, symlinks, nested modules, activated dependency
roots and the standard build, generated, vendor and node_modules directories.
Pass `--output-directory PATH` to exclude a custom output directory as well.
Keys are ordered by relative path using bytewise comparison.

This command inventories source files. The PHP project build still owns full
module graph validation, runtime provider selection, and artifact linking.

Pass `--from-bytes` to exercise hashing an in-memory source snapshot instead.
Both paths use OpenSSL EVP and preserve embedded NULs. Neither command persists
a digest cache. Run `compiler_native_source_inventory` to compare both modes
with PHP project digests and the backend's unit naming.
