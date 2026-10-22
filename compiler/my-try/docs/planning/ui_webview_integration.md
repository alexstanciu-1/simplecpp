# UI/WebView source integration and compatibility audit
Doc Status: planning

## Scope and decisions — 2026-10-22

V-R08 retains the 24 strict preview APIs in `docs/ui_webview_preview.md`. This
slice adds source/module exposure and default-prelowered provider transport. It
does not add UI features or change runtime semantics. `webview` depends on `ui`;
`ui_event_webview` belongs to the webview module, matching the existing strict
symbol catalog. The other event getters belong to ui. Handles use existing shared
nominal storage; Result payloads use existing extraction and write-back.

The user also approved bounded provider build integration after runtime-first
inspection found that the metadata build contract could not express SDK inputs.
Module JSON names only the existing `ui`/`webview` resolver requirements. The host
adapter reuses `bin/project_services.php` discovery before startup retention.
Resolved, validated SDK options and diagnostics travel in the authenticated build
publication; native consumers do not reopen catalogs or rediscover packages.
Compilation options enter ordinary compile/layout/cache commands, and link options
enter link commands. No executable metadata, extra runtime registry, package
installation or runtime implementation changes are introduced. This integration's
native consumer remains Linux/Clang; other platforms require their own build proof.

## Evidence and remaining acceptance

Implementation is complete within the available host proof boundary. V-R08 remains
**partial** until enabled-provider and GUI acceptance are proved: overall 58/64
resolved (90.625%), one partial and five other open.

On dev002, Xvfb is present, but pkg-config lists neither GTK nor WebKitGTK, and
DISPLAY is unset. The existing `tests/native/webview_compile.cpp` builds and runs
with disabled providers. This proves neither an enabled backend nor GUI behavior.

- `20261022-vr08-final`: all four source cases pass, including independent ui-only
  selection; both contract suites pass. All publications forbid application templates.
- `20261022-vr08-build-regression`: existing provider-source/definition/library
  edits preserve expected cold/warm cache invalidation and execution.
- `20261022-vr08-source-03`: all 24 calls compile/run through default prelowering;
  Result failures extract through take. Cold/warm passes, warm compilation/link zero.
- `20261022-vr08-project-01`: the production project configuration path passes the
  same cases; existing build-contract checks and SDK wire/rejection checks pass.
- `20261022-vr08-expanded-01`: adds native-observed left-to-right handle snapshots,
  copy lifetime after clearing the original, failed take preserving a populated
  destination, and failed publication/build rejection followed by recovery. All
  three source cases and both contract suites pass.

Evidence archive on dev002: `/home/alex-ai/curl/vr08-evidence.tar.gz`, SHA-256
`2193cc49b57f1f70c35a99eeffef3eec8a0c83cf6797be0f5c72d53629efe42d`.
All 16 implementation/test/metadata hashes match the verified overlay.

The fixture observer constructs ordinary runtime handles without a GUI backend.
It observes real handle transport but does not claim actual window/event lifecycle
coverage. GUI load/message/reply/shutdown and enabled GTK/WebKitGTK build proofs
remain unavailable on this host. Do not count disabled-provider successes as those
proofs. No full matrix or native compiler rebuild was run.

### Separate finding VR08-H01

`20261022-vr08-source-02/logs/failure-publish.stderr` records that direct `!$error`
conditions reject during default prelowering with `runtime condition requires
concrete transport`. The UI failure probe uses the existing get_message accessor
instead. This general error-condition transport gap is not repaired in V-R08.
The first harness attempt also had a runner-call error, and the second attempt had
a bytes/string assertion mismatch; those were fixture mistakes, not compiler gaps.

## V-D07 / V-D08 compatibility audit

This is a read-only source/catalog audit, not a claim of fresh v0.1 execution.
Baselines are local `release/0.1.78` at
`d2f63e37efe949355c11b137cc4c3520fbfc6368` and `v0.1.77` at
`f3bf694e69ce72205fd3a8c81c8c4d2cb11d482e`.
The frozen original baseline `75de94c71e37060c564b6ade19850b7686bd457a`
also has identical Generator, TypeMapper and rules catalog to release/0.1.78
for this audit. The relevant catalog and TypeMapper are unchanged between the
release branch and v0.1.77. Generator changes
between these refs concern absolute class-name handling, not the audited call/enum
paths. The existing dev002 legacy generator does not hash-match either baseline,
so it was not presented as a pinned-baseline execution proof.

| Candidate | v0.1 evidence | Disposition for further discussion |
| --- | --- | --- |
| Named arguments | `Generator::renderCallArgsForParams` binds by numeric position. No named-argument AST handling was found in the generator/builder. | No established compatibility promise; design extension, pending focused baseline rejection proof. |
| Call-site unpacking | The same call path packs an explicit trailing argument list; no unpack AST handler was found. | Do not confuse supported typed variadics with unpacking. No established compatibility promise. |
| Reference defaults | `renderParams` appends defaults to non-variadic parameters, including reference-shaped signatures. This alone does not prove that omitted arguments compile or have safe reference semantics. | Unproven; require an omitted-argument native baseline probe before labeling a regression. |
| Reference variadics | Variadic parameter lowering selects `const vector_t<T>&` and call lowering constructs an owned vector temporary, independently of the source reference marker. | No demonstrated caller-element write-through contract. Do not inherit accidental acceptance as reference semantics. |
| String-backed enums | `Generator::enumStorageType` explicitly rejects anything except unit/int/fixed-width integer backing. | Extension, not demonstrated lost v0.1 support. |
| Enum cases/from/tryFrom/pseudo-properties | Catalog ENUM-003 explicitly marks helper synthesis unsupported. | Extension. Existing v0.2 enum_name/value/from_value are separate supported helpers. |
| Enum methods/interfaces | Catalog ENUM-004 explicitly marks enriched enum bodies unsupported. | Extension. Shared method/interface foundations do not automatically establish enum semantics. |
| Explicit nullable enum payload | `TypeMapper::mapDeclaredType` recursively maps `nullable<E>` to `nullable<EnumCppType>`; runtime nullable stores a generic optional payload. | Genuine compatibility candidate; require source/native execution before closing or permanently excluding. |
| `?E` shorthand | The same mapper's object/enum branch maps `?E` directly to the enum type, unlike explicit `nullable<E>`. | Spelling-sensitive historical behavior; do not infer absent-state support from syntax acceptance. |
| Result enum payload | The mapper recursively maps Result payloads using the ordinary enum value type. | Genuine compatibility candidate; construction/extraction/state proofs are still required. |

Ordinary value defaults, fixed references, and typed value variadics are supported
controls, not remaining V-D07 gaps. Numeric/unit enum values and explicit helper
paths are likewise supported v0.2 controls. No V-D07/V-D08 issue is closed by this
audit, and S02 remains deliberately deferred. The next enum discussion should
prioritize explicit wrapper payloads rather than importing PHP reflection/methods
as though they were all missing v0.1 functionality.
