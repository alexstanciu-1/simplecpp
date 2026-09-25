# File-by-file review inventory

Current native checkpoint: the normal STAN-enabled build passes 142 PHP/native
comparisons (48 valid programs executed, 94 rejection/recovery cases). See
`compiler/my-try/docs/native_adaptations.md` for changes and limitations. Earlier
pending-build notes below describe historical checkpoints; advisory STAN diagnostics
remain and the verified target pin has not changed.
Doc Status: planning

The full experiment is imported and runs in PHP. Namespace changes, short type
names, data-file separation and container annotations preserve its algorithms.
The old compiler rewrite and the external experiment remain unchanged.

This inventory records review subjects, not new language requirements or a claim
that each PHP form is already supported by the portability converter.

| Owner | Next subjects |
| --- | --- |
| `compile/model.php` | Static fields/access and required-field declarations are proved; Model still needs Storage bindings. Preserve reset and shared-state semantics. |
| `01_prepare_inputs/` | File versus token ownership; metadata/read consistency; PHP filesystem failures; partial module loads. |
| `02_tokenize/` | Token object allocation, duplicated token text/source handles, byte helpers and span bounds. |
| `03_parse/` | Direct AST graph and nested Storage child lists are in place; review shared-handle binding, scope lifetimes and enum representation; separate optional debug formatting from parsing. |
| `04_analyze/collect/` | Occurrence identity versus declarations; keyed name pools and ordered occurrence lists; preserve append-only collection and duplicate handling. |
| `04_analyze/prepare.php` | Explicit lookup contracts and nullable scope traversal; maintain retained declaration identity. |
| `04_analyze/templates.php` | Symbolic contract limits; `SplObjectStorage` keys are collected-file objects and values are prepared files. |
| `05_llvm/prepare.php` | Registry keys and pending instances; object-keyed struct/owner/file-index maps; nullsafe PHP access and retained source purity. |
| `05_llvm/generate.php` and traits | Shared place/value paths; typed-container resets; callbacks, `match`, and specialization narrowing need actual conversion review. |
| `05_llvm/names.php` | Byte escaping and regex-result boundary; preserve reversible names and exact identities. |
| `06_native/` | Host JSON, resource/process APIs and cleanup; keep configured Clang and separate build/execution results. |
| `compile/` and `main.php` | Debug constant, dynamic-property report groups, report escaping/buffering, retained coordinator state and error reporting. |

## v0.2 deferred reserved-name enforcement

Agreed 2026-09-25: the LANGUAGE+RUNTIME parent of global scope participates
in ordinary parent-chain lookup. During the generation pass, do not add special
shadowing/reserved-name rejection. The later validation/STAN pass must apply the
owning language contracts to declarations that conflict with defined/reserved names.
This deferral does not establish unrestricted shadowing as permanent language semantics.
See [the handoff decisions](docs/handoff_catalog_v02.md#agreed-type-and-scope-direction).

## Representation discipline

- Ordered object lists use `Storage<T>`; string-keyed object collections use
  `Keyed_Storage<T>`. Scalar lists use `vector<T>`; sparse indexes and name maps use
  `hash<V, K>`. All current implementation array properties and array parameters/
  returns have explicit container intent. PHP fixtures keep their heterogeneous
  case tables as host test data.
- PHP object identity is preserved. No `@scpp-struct` promotion, numeric-width
  narrowing, ID-table substitution or storage compaction is implied by import.
- `SplObjectStorage`, resource handles and dynamic host results retain truthful
  PHP contracts. Do not invent generic conversion syntax for these boundaries.
- Strings and obvious scalar/object expressions need no redundant annotations.
  Declaration indexes are not automatically dense storage positions.
- Retained records contain data and container initialization only.
  `Syntax_Nodes::category()` supplies the derived AST query. Nested owned record
  lists use Storage; scalar lists and dedicated indexes retain typed arrays.

Optimize high-count data first, with native layout/allocation measurements when
we reach conversion. Avoid claiming native memory savings from PHP measurements.

## Known proof boundaries

The current proof exercises straight-line programs, integer value/reference
parameters, fixed arrays with constant indexes, integer-field structs and explicit
integer template arguments. It does not establish broader language support,
incremental compilation, lifecycle completeness, or native compiler portability.

Existing tests retain source/AST purity checks, unused/reused template checks,
rejection cases, call dependencies and native program execution. Recursive
registration is checked without executing an infinite recursive call.


## Model conversion checkpoint

The [model conversion review](docs/conversion_review.md) records actual checker
current status, ownership-sensitive behavior and the binding-first sequence.
Earlier checker failures are linked separately as historical evidence.
Its [evidence](docs/conversion_review_evidence.json) includes source hashes and
minimal reproductions. This is a review checkpoint, not conversion completion.


## Explicit nullability audit

Status: PHP initialization/nullability passes completed for Model/source/token,
AST/parser, collector, preparation and native-runner results. Native initialization
proofs and host API adaptation remain part of conversion. See the
[initialization audit](docs/initialization_audit.md) for field decisions, lifecycle
preconditions and evidence. Successful source reload now clears file.tokens.

Review retained model records, transient preparation records and worker fields,
then their parameter/return boundaries. Mark every genuinely optional value with
an explicit `?`; keep required values nonnullable. Missing initialization during
construction is not evidence of optionality. See
[required-field contract](docs/ownership.md#explicit-nullability-and-publication).

For each field, inspect creation, assignment, reads, publication, failure and reset:

- Required: populated before read/publication; preserve the build-then-publish rule.
- Optional: absence is meaningful; use `?T`, explicit null initialization/reset,
  and deliberate guards at reads.
- Check existing `isset`/`unset` and absent-property conventions. `file.tokens` is
  now explicitly nullable, initialized/reset to null; its stage behavior is tested.
- Audit existing nullable fields too; do not add `?` to every forward/backward or
  weak link. Empty containers remain valid nonnullable containers.
- Prove initial, successful, failed and restarted stage behavior after adaptation.
  Nullable scalar/named method parameters and explicit optional-reference reset
  now have focused PHP/native proofs. The wider field audit remains pending.

No mass annotation changes or representation choices are made by recording this
agreement. Required-field declarations now have focused conversion proof, not
permission to publish incomplete records or silently accept reads before assignment.


## Current simplification

Storage<T> is a numeric shared object list; Keyed_Storage<T> supplies exact string
keys. Both share Storage_Abstract. LLVM preparation uses both types; sparse indexes
and scalar collections remain typed arrays. AST nodes own payloads and child
lists directly. Storage_View and duplicate node/payload stores are removed. Earlier
native ownership/layout proposals are historical, not current conversion targets.
Issue #242 now carries helpers/STORAGE_NATIVE_TASK.md's simplified replacement
request; wait for the updated implementation and bindings before native integration. Preserve explicit types, nullability and collection identity.


## Host-only declaration proposal

A doc-comment directive such as `@scpp-no-export` could omit an entire class or an
individual method from generated output while keeping it executable in PHP.
This is a proposal, not implemented or accepted annotation syntax. Establish its
exact spelling, declaration scope and structural skip behavior before use. Retained
code must not depend on omitted declarations; the converter must not be assumed to
resolve such dependencies. Whole-class omission includes its members; method
omission must not silently remove call sites or required interface implementations.
Host entry/bootstrap/reporting files can remain outside conversion inputs meanwhile.
No directive implementation is part of the storage reset.


### Real conversion checkpoint

Explicit Storage bindings, host declaration omission and portable source helpers
are implemented and PHP-tested. Real diagnostic acceptance is 27/29 files, including
omitted host declarations/expanded traits. Full atomic conversion still rejects AST
instanceof checks. Payload narrowing and SplObjectStorage identity indexes need the
next representation/native-boundary decision; see docs/conversion_review.md.
No native compilation or target pin update was performed in this pass.


### Complete conversion checkpoint

The normal atomic converter now publishes all 29 implementation files, with 29/29
incremental reuse. Typed Syntax_Nodes payload accessors and real object-key hash
bindings remove the earlier conversion blockers. Native instanceof/cast emission is
implemented and inspected in a focused fixture; native compilation is still deferred.
See docs/conversion_review.md for output location, evidence and remaining native gates.

## Incremental cleanup debt

See [file synchronization](docs/incremental.md). Physical tombstone removal and
release of deleted records' old syntax/source references are deferred. Transient
flags already reset between updates. Bidirectional semantic-use dependencies and
selective re-resolution remain deferred; current preparation reruns completely.
