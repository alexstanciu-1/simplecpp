# Ordinary package language exposure
Doc Status: planning

38 PHP/native outcomes agree with the actual retained package importer. Exact
catalog object identity is checked for signed/unsigned integers and void. Other
cases cover new opaque/span contracts, explicit field/resource permission, catalog
collisions, compiler-qualified names, deferred record definitions and rejection of
native imports/source payloads through ordinary exposure. Four cases prevent
resource markers from being ignored outside opaque storage.

Target: clean strict clang++-18 revision
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`. First native build passed without
correction. PHP iteration fixed a field/method access mistake; the oracle needed
one missing include. Initial PHP readiness remains recorded, even though review
subsequently expanded resource validation before native proof.

Run `python3 compiler/tests/type_exposure/run.py --results FRESH` with optional
`--target-checkout /tmp/scpp-json-240-probe`; run the cumulative ready-set command
`python3 tools/php_portability/validate.py --results FRESH`. Summaries record exact
commands, durations and source hashes.

This helper consumes a named binding selected by its caller. Producer name syntax,
whole-package duplicate checks/publication, accepted native/source owner binding,
retention, checksums and leases are separate remaining requirements.
