# Runtime metadata
Doc Status: supporting

`modules/*.json` follows the [versioned module contract](../../specs/runtime_module_metadata.md).
The files are runtime-owned, reusable compiler input, not generated C++ or my-try-local
configuration. Current coverage is intentionally bounded to core `strlen` and JSON
free functions. Existing v0.1 symbol maps have not yet been replaced wholesale.

Load selected modules once at startup, decode directly to typed records and retain
them for registry installation/reset. Do not rescan these files during compilation.
