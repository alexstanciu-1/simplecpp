# Nullable compile experiment archive
Doc Status: supporting

This archive preserves the original README, scripts, generated fixture sources,
input hashes, command logs, timings, and Clang trace. See [README.md](README.md).
The files retain their original bytes and historical path assumptions, recorded
in [provenance.json](provenance.json). The original experiment ran under
`compiler/my-try/temp/compile-probe-20261003/`; its scripts are not relocated
entrypoints. Reproduction requires restoring that layout and the original fixture
input manifest (or adapting the script to the preserved fixtures.json/programs).
Do not overwrite archived evidence during replay.

Executables, object files, PCH and preprocessed output are excluded. These are
rebuildable artifacts, not source evidence. The original temporary directory is
left intact locally and excluded from the proposed commits. No timings were rerun.
