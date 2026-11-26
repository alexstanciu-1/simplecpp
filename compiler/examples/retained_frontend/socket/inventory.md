# Native source inventory digests

The Linux native frontend links OpenSSL libcrypto through its private adapter.
With the normal project build complete, run the following against an application
whose module loader has published a current `module.sources.json`.

```sh
build/clang/native-frontend/native/program inventory /path/to/application
```

The command reads every selected source in 64 KiB chunks and returns one JSON
object keyed by the compiler's module-qualified source key. Each row contains
the selected flavor, the lowercase SHA-256 of the exact file bytes, and the
generated C++ unit name derived from the SHA-256 of the source key. Output is
published after the complete scan succeeds. A missing selected file fails the
scan. Run project discovery again to update the selected paths after additions
or removals. The ordinary PHP compiler continues to own discovery.

Pass `--from-bytes` to exercise hashing an in-memory source snapshot instead.
Both paths use OpenSSL EVP and preserve embedded NULs. Neither command persists
a digest cache. Run `compiler_native_source_inventory` to compare both modes
with PHP project digests and the backend's unit naming.
