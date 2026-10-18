# Diagnostic review evidence — 2026-10-18
Doc Status: supporting

Start with [the main report](REPORT.md) and the topic-specific REPORT files.
This is historical evidence from the recorded working tree, not a fresh test run.

Recovered text evidence is recorded with SHA-256 hashes in
[evidence_manifest.json](evidence_manifest.json). Sources, emitted C++, command
journals, diagnostics, result records and preparation markers are preserved.
Executables, objects, PCH files, dependency files, locks and ownership markers
are excluded. Absolute paths inside raw records preserve original provenance.

Report reproduction commands are relative to the repository root. The historical
probe scripts still contain the original checkout path; adjust that path before
replaying on another machine. Use fresh output directories; do not overwrite this
archive. Native-cache investigations require rebuilding excluded binary/cache
state. No compiler tests were rerun while preparing this archive.
