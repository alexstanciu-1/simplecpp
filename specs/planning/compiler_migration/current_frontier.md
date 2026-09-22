# Current migration dependency frontier
Doc Status: planning

This is a next-action map, not a completion claim. The ready set is 36 production
files, with latest cumulative evidence in `results/preparation-symbols-01`. Earlier
counts in historical slice documents are checkpoints, not the current count.

| Selected area | Concrete dependency | Next action |
| --- | --- | --- |
| Structural syntax access and semantic consumers | Explicit source guards now pass the focused query/cursor PHP/native proof on a1a1babd | Consolidate cumulative integration and target adoption; no operator fix is required for this component. |
| Semantic records, lifecycle and backend vocabulary | 41 string-backed enums and seven enum methods exceed the selected native subset | Implement the staged typed-tag/codec plan accepted on 2026-09-22; see enum_portability_decision.md. |
| Lexical worker and source diagnostics | Explicit global base class resolves inside the derived namespace | #233 supplies d493525d and combined descendant 361b1e97; validate the diagnostic component before adoption. |
| Manifest parsing and runtime metadata ingestion | Native JSON tables erase object/list identity | Native shape-preserving document API requested in [#240](https://github.com/alexstanciu-1/simplecpp/issues/240); json_document_requirement.md defines the requirement and subsequent PHP adapter work. |
| Source path resolution | Existing host-sensitive absolute-path/separator rules | fs_is_windows is available in 361b1e97; add PHP adapter and prove Source_Paths on the candidate. |
| Verified source snapshot reading | Path/open-handle device/inode, regular-file kind and version checks, bounded read and guaranteed close | Linux fs_read_snapshot is available in 361b1e97; add PHP/native adapter and compiler proofs; other hosts explicitly unsupported. |
| Input_Selection as a whole | supports_increment consumes Symbol_Refresh and its semantic catalog | Follow semantic-record dependencies; do not split a tiny select-only fragment merely to increase ready-file counts. |

## Path resolution finding

Source_Paths::is_absolute passes `DIRECTORY_SEPARATOR === "\\"` to the already
proved pure Source_Path_Syntax helper. Source_Paths::resolve also normalizes native
Windows separators while preserving literal POSIX backslashes. The implementation
therefore needs both canonical path lookup and a target-host platform/separator fact.

The selected `2f0d667f` registry exposes fs_realpath, but a search of its PHP generator
and runtime found no DIRECTORY_SEPARATOR/PHP_OS_FAMILY support or equivalent
registered platform helper. This is a source/API inspection finding, not a native
execution proof that every possible expression is unavailable. Do not substitute a
constant chosen on the converter's host: that can diverge from the compiled target.
Do not infer the host from a filename's spelling.

A small v0.1 host fact such as a filesystem-native-separator or Windows-host query
would allow the existing Source_Paths policy to stay local and unchanged. Native
platform selection belongs to the target implementation, not the converter's symbol
index. No target code was changed by this inspection.

On 2026-09-22, at the user's request, both filesystem snapshot operations and
host-platform information were requested in [issue #233](https://github.com/alexstanciu-1/simplecpp/issues/233#issuecomment-5771113976),
including the read protocol and acceptance coverage. Native lossless JSON is an
accepted direction but is not included in that two-requirement issue comment.

## Work posture

The user accepted the enum, cursor and native lossless JSON recommendations on
2026-09-22. Enum and cursor implementation may proceed within their documented
scope. Continue independently where a coherent component
can be proved; do not weaken validation, discard serialization facts, flatten source
diagnostics, or replace a missing capability with fabricated state to bypass this
frontier. A shared portable JSON parser is possible, but is real implementation and
validation work rather than a trivial function-map addition.

See [the PHP adaptation record](php_adaptation_record.md) for completed changes,
optimization follow-ups and the approved cursor-first resume assessment.

[Candidate a1a1babd proof](../../portability/release_candidate_a1a1babd.md): all ten
established native suites pass. Subsequent explicit source guards clear the query
behavior failure; see `results/explicit-guards-01` and the adaptation record. The
configured pin and 36-file ready manifest remain unchanged.
