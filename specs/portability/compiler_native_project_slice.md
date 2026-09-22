# Native project identity and lexical roots
Doc Status: supporting

`compile/data/native_project.php` now preserves explicit project identity and root
normalization through portable byte operations. The constructor still validates the
project key, then source root, then output root. It never infers identity from a
path or touches the filesystem. Nonempty keys and absolute POSIX roots reject NUL;
roots skip empty/dot segments and reject parent traversal, retaining other bytes.

The root scanner replaces explode/filter/implode with explicit segment boundaries.
UTF-8 validation is intentionally absent: these are filesystem path spellings, and
the original algorithm accepts arbitrary non-NUL bytes. A root with no retained
segments remains `/`. Backslashes remain ordinary bytes in this POSIX contract.

The converter now preserves separately declared readonly scalar fields without
fabricating initial values. They use `public readonly string $source_root;` and
are initialized by the authored constructor. This bounded syntax admits bool/int/
string fields, not nullable/named/container readonly fields or field defaults.
It does not introduce constructor-flow analysis or emulate PHP's uninitialized
property errors. Writers must initialize these fields before observation; the
native production proof checks the actual constructor. Native readonly enforcement
remains advisory, as in earlier promoted-record slices; PHP retains its enforcement.

The frozen original is compared on 2,676 configurations, including validation
precedence, normalized/relative/parent paths, NULs, UTF-8 and invalid UTF-8 bytes.
The cumulative PHP/native witness independently checks normalized roots, exact
identity, byte preservation, root-only paths and constructor diagnostics. The
retained source-export fixture checks exact identities, six-role contracts,
private ABI joins, rebuild binding and body reuse.

This does not migrate Source_Identities, output-path filesystem validation or the
full native export pipeline. The enum/cursor cross-owner decisions remain pending.

Evidence: `specs/planning/compiler_migration/results/native-project-01/summary.json`.
Strict native build/STAN, expected PHP/native behavior and eighteen cumulative
compiler fixtures pass on `2f0d667f38a35ff02ef77e813f409189cba2d032`. The source-export
fixture and updated readonly-form rejection suite passed separately, with results
recorded alongside that evidence. Thirty-three production files are ready.
