# Current migration dependency frontier
Doc Status: planning

Active readiness is **94 production files**: input preparation (137 outcomes),
tokenizer (304), parser storage/angle matching (414 plus a reused 5,000-stream PHP
unit), expression grammar (132), and file statements/declarations (82 plus nine host lifecycle assertions). Exact native target remains
`9b4b33f35f053b487e018c94d6a4a7888d77c64a`.

The global helper convention is implemented: no imports, q_ for existing PHP names.
Whole-file grammar is implemented. Syntax access/comparison adds 134 outcomes. Project parser planning/join/reuse adds 66 outcomes plus nine host purity assertions.
Source declaration collection adds 97 PHP/native outcomes plus nine host purity assertions. Source entry selection adds 44 PHP/native outcomes and nine host purity checks. Representation vocabulary adds 98 PHP/native outcomes and retained constructor checks. Lifecycle contracts add 154 PHP/native outcomes. Scalar catalog and entry binding add 116 PHP/native outcomes. Declaration lookup adds 40 PHP/native outcomes. Lexical/body name resolution adds 320 PHP/native outcomes. Project resolution adds 182 PHP/native outcomes plus 25 host checks. Canonical type storage adds 133 PHP/native outcomes plus 20 host checks. Aggregate lifecycle composition adds 36 PHP/native outcomes and eight host checks. Normalized structural definitions are now proved; annotation preparation remains incomplete; provider imports and semantic comparison remain separate dependencies. The complete compiler CLI is not implemented.
See [file parsing](../../portability/file_parser.md). src-runtime-preparation remains
PHP as-is and outside conversion scope. The former 39-file coverage is historical.

The table below records pre-reset dependencies and reusable findings, not active
implementation status or a mandatory task sequence. Previously adapted code is
under `compiler/reference/pre-rewrite/`.

| Selected area | Concrete dependency | Next action |
| --- | --- | --- |
| Structural syntax access and semantic consumers | Three query files now pass cumulative PHP/native coverage on adopted candidate a1a1babd | Continue dependent semantic components; their ten cursor consumers are not yet native-ready as complete files. |
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

The previously selected `2f0d667f` registry exposes fs_realpath, but a search of its PHP generator
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
behavior failure; see `results/explicit-guards-01` and the adaptation record. That historical 39-file validation does not apply to the active rewrite manifest.

Native record layout contracts add 18 PHP/native outcomes and 441 retained-contract
comparisons. Resource-aware definition validation now adds 39 PHP/native checks and 90 retained allocation-effect cases. Record/array materialization adds 27 PHP/native checks. Next: concrete annotation and provider/storage dependencies.

Definition_View adds 14 PHP/native checks for provider/source precedence and accepted
identity. Instance contexts, typed arguments and exact integer literals add 35 PHP/native
outcomes and 200 host range cases. Instance allocation now adds 23 PHP/native outcomes and 60 retained allocator calls.
Symbolic terms and permission-result containers add 40 PHP/native checks and 529
retained symbolic comparisons. Provider integration precedes symbolic declaration
interpretation and the template-checking worker/joins, then registry publication
and instance bindings.
Annotation resolution remains incomplete.

Provider references and semantic signatures add 35 PHP/native checks and two PHP
carrier checks. The user approved provider integration, preserving the PHP
preparation tool and its output contract. Next: generic-family contracts and source
exposure records, then catalog/import and shared symbol integration.

Generic-family contracts and source exposure now add 51 PHP/native outcomes and
33 retained-validator cases. The declaration model is proved; next is real provider
import/catalog integration, followed by symbol origins and name bindings.
