# Current migration dependency frontier
Doc Status: planning

This is a next-action map, not a completion claim. The ready set is 36 production
files, with latest cumulative evidence in `results/preparation-symbols-01`. Earlier
counts in historical slice documents are checkpoints, not the current count.

| Selected area | Concrete dependency | Next action |
| --- | --- | --- |
| Structural syntax access and semantic consumers | Typed cursor and ten consumers now pass PHP proofs; existing Syntax_Access/Metaprogramming_Syntax forms still need conversion | Adapt actual query dependencies (first checker rejection: trait ??), then establish native proof; see struct_member_cursor_decision.md. |
| Semantic records, lifecycle and backend vocabulary | 41 string-backed enums and seven enum methods exceed the selected native subset | Implement the staged typed-tag/codec plan accepted on 2026-09-22; see enum_portability_decision.md. |
| Lexical worker and source diagnostics | Explicit global base class resolves inside the derived namespace | #233 fix expected per user update; test an immutable candidate before adoption. |
| Manifest parsing and runtime metadata ingestion | Native JSON tables erase object/list identity | Native lossless document API with a PHP counterpart accepted on 2026-09-22; json_document_requirement.md defines the requirement. |
| Source path resolution | Existing host-sensitive absolute-path/separator rules | Requested in #233; expose a truthful target-host path/platform fact before porting Source_Paths. |
| Verified source snapshot reading | Path/open-handle device/inode, regular-file kind and version checks, bounded read and guaranteed close | Requested in #233; preserve the read.php protocol; current scanner APIs do not supply this information. |
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
