# Handoff: begin v0.2 catalog development
Doc Status: planning

Prepared 2026-09-25. This records the user's latest decisions, not new language
semantics or blanket implementation authorization. Read current files before editing.

## Next action

The first `LIT-INT-001` slice is implemented; read
[the integer S2S slice](s2s_integer_slice.md) and the completion evidence below.
Continue the catalog discussion one item at a time. Do not infer broader literal,
function, composite-type or multi-file support from this bounded implementation.

Follow the existing chapter order. Pick one item, discuss it, split combined
requirements, implement the agreed slice, record proof, and move on. If an item is
already covered, verify and record that evidence rather than reimplementing it.
Individual rows may depend on later chapters; split/defer those rows without
reorganizing the catalog wholesale. Consult chapter 14 from the start for the
minimal program entry, runtime integration and output-artifact requirements.

## Workspace and reading order

Repository: `/home/alexv/__AI/simple_cpp/simple_cpp_01`.
Implementation: `compiler/my-try`, namespace `scpp\compiler`.
Branch at handoff: `feature/scpp-native-portability-fixes`.
Committed implementation tip: `44bcba86` (this handoff is committed afterward).
The user also edits this folder in their IDE. Preserve unrelated modifications.

Follow repository AGENTS.md: read specs/spec_map.md, docs/ai_onboarding/README.md,
docs/ai_onboarding/coding_style.md, specs/simple_cpp_php_strict_quick_learn.md,
then the owning specs. Also read:

- [Local AGENTS.md](../AGENTS.md), [code style](code_style.md).
- [Catalog README](../catalog/README.md), then the selected card.
- [Model](../MODEL.md), [ownership](ownership.md) when touching retained data.
- [Incremental contract/debt](incremental.md), [work queue](work_queue.md).
- [Native adaptations](native_adaptations.md) and saved evidence for portability.
- [v0.2 inventory](../../../specs/planning/v0_2_compiler_s2s_requirements.md) and
  [latency handoff](../../../specs/planning/s2s_next_generator_technical_handoff.md).
  The v0.2 inventory predates the generation/validation split below; its requirement
  for a fully checked program before first generation is superseded for stage 1.

Use the simple-cpp-portable-php skill for compiler implementation. The compiler is
written in executable convertible PHP, with supported adjacent conversion types.
Catalog examples are the language being compiled: strict-mode PHS, using source
types such as `$x string = "test";`. Do not confuse these two authoring surfaces.
Use the strict skill and current language/runtime specs for target-source decisions.

The catalog and v0.2 inventory were present but UNTRACKED at handoff. Local AGENTS.md
and README.md also had existing edits. They are deliberate local context: do not
remove, regenerate or silently include them in an unrelated commit. A new chat in
this same workspace can read them; a clean remote checkout will not contain the
untracked catalog until it is deliberately committed. Parked compiler/src changes,
its lifetime tests/specs, and Python cache folders were also outside this task.

## Agreed v0.2 scope

Two development stages:

1. Make the semantically informed C++ S2S work.
2. Add comprehensive STAN/validation and selective semantic invalidation.

Stage 1 resolves whatever facts are necessary to choose a known lowering. It need
not validate every source rule before emission; Clang can reject remaining invalid
C++. Unknown/unsupported forms or missing facts required for lowering must be
reported, never guessed. An unresolved type is not implicitly mixed. Valid emitted
C++ must not silently mean something different from the source.

Output shape is open to discussion. Imported C++ snippets, casts, wrappers and
v1 type-blind-generator constraints are historical references, not required v0.2
output. Current owning language specs remain authoritative. The catalog records
frontend and C++ S2S evidence separately; do not mark a whole feature complete just
because its AST parses or the old S2S handled it. Existing diagnostic cards remain
in the catalog, but comprehensive validation is the second milestone.

Direct LLVM backend development is deferred unless the user explicitly requests
it. Existing LLVM output/tests are regression infrastructure, not the new roadmap.
Do not resume the parked compiler migration or copy its architecture wholesale.

For compilation performance, prioritize narrow headers, stable implementation
units/names, accurate dependencies and writing only changed final artifacts. The
latency study's main gain was less native recompilation. Do not benchmark every
literal spelling or assume shorter C++ is faster. The study's 1.5-second frontend
allowance was assumed, not measured with this frontend.

## Agreed multi-language direction

Agreed 2026-09-25: PHS/PHP++ is the reference surface for the full canonical
Simple C++ language model. Other source languages shape their ASTs toward that
model and may expose subsets of it. This is a design direction, not a claim that
every PHS feature is already implemented.

Pipeline: source language -> canonical PHS-shaped AST -> shared preparation ->
C++ emission. Develop one canonical model led by PHS/PHP++; no separate universal
language abstraction is planned. The canonical AST represents language concepts,
not incidental PHP token spelling. Frontends own source syntax and its mapping
to those concepts; shared preparation and emission own the common generation path.

Mappings must preserve the intended source behavior. A differing source operation
must be expressed through supported canonical operations; if that is impossible,
discuss extending the canonical model or leave the feature unsupported rather than
silently changing its meaning.

Current work develops this path through strict PHP++ / PHS examples, accepting
straightforward legacy forms that fit the same model. Other frontend implementations
are later work. Generation remains the first pass; comprehensive validation and
semantic invalidation remain the second. This decision does not authorize a broad
AST refactor or settle the first literal card's target C++.

## Agreed type and scope direction

Agreed 2026-09-25 during the first literal discussion:

- Follow the Simple C++ type contract. LLVM was an experiment and does not define
  canonical types, numeric defaults or the new S2S data model.
- Hard-code language-defined types; runtime/library definitions can be loaded from
  JSON using existing project conventions where applicable.
- Keep native data compact, using supported fixed-width fields such as uint32 and
  enums for categories, origins and strategies. Use strings for actual names and
  spellings. Expand the model for future features only when they affect current work.
- Encapsulate scope access behind methods so callers do not depend on its storage.
  Keep scope lookup as the common name-resolution path; do not add a competing
  type-name registry. The global scope has a LANGUAGE+RUNTIME scope as parent.
  Preserve the distinction between lexical parents and file publication links.
- For this generation pass, use ordinary lookup from the current scope through
  its parents. Do not add special reserved-name or shadowing rejection to this slice.
  Restrictions on declarations conflicting with defined/reserved language names
  belong to the later validation/STAN pass; this is deferred enforcement, not a
  permanent language guarantee that those declarations are legal.
- Existing AST structures suffice for `$a = 10;`. Add shared resolved expression
  types and binding results during preparation; keep C++ representation mapping
  with generation. Constructed-type machinery is outside this slice.
- Defer spelling-level compile-time optimizations to a final pass. The one-off
  Clang comparison is evidence only, not a selected lowering policy.

## First integer S2S completion

Implemented the agreed compact type/scope model and `LIT-INT-001`. See
[the slice notes](s2s_integer_slice.md) and
[saved PHP/native evidence](../../../specs/planning/results/s2s_integer_01/README.md).
Scope storage is private; global scope has a language/runtime parent. Built-in `int`
is canonical signed 64-bit with a uint32 width field and enum categories. Source
and built-in definitions have truthful provenance. Preparation retains resolved
expression types and binding identities. The 2026-09-26 follow-up attaches those
facts directly to specialized AST nodes, preserving source syntax and adding
per-node cleanup on reset, reuse and failure. Native compiler builds are now opt-in
under local AGENTS.md; the earlier native results below do not cover this follow-up.

`Compiler::exec_cpp` / `update_cpp` and host `s2s.php` select the new path. The old
`exec` / `update` LLVM entrypoints remain regression infrastructure. Current C++
coverage is one file of straight-line integer locals/reads/assignments and optional
entry returns. Output uses typed literals with `auto`; no spelling optimization was
adopted. Other literal forms and JSON import are not included.

Proof: nine generated-C++ executions (including two independent int64 value/type
probes), PHP source-purity/scope/update tests, native compiler S2S byte parity and
execution, and all 142 existing native comparisons passed. Normal STAN-enabled
native build: zero blocking compile errors, 294 advisory errors, 117 warnings.
The candidate is the fingerprinted unversioned `/tmp/scpp-native-244` overlay;
no new verified release pin is claimed. Final artifacts remain in memory/stdout;
physical publication and build caching are outside this first slice.

## Current retained model and execution

Model owns static module, token-list, parsed-file, collected-file, language/global
scopes, prepared-file and C++/LLVM-output roots. Storage is a numeric shared-object list; Keyed_Storage is the
string-keyed counterpart. No Storage_View layer remains. AST nodes are concrete
subclasses of abstract ast_node, with attached node_structure data and private
traversal links. Native weak scope/backlinks have explicit conversion support;
ordinary documentary weak annotations are not automatic lifetime enforcement.

Compiler owns the bounded work queue. A worker reads, tokenizes and immediately
parses one file, without a global tokenization barrier. Parsing uses private
file-local scopes; global publication is locked. PHP runs the same flow sequentially;
native executes parallel work. DEFAULT_COMPILER_JOBS is 12. Discovery remains
synchronous, and concurrent independent Compiler sessions are unsupported because
Model is static. Cross-file preparation starts after all workers join.

## Minimal incremental implementation: preserve this design

- `init(module_paths)` resets/discovers an empty session.
- `exec()` synchronizes every live file, then runs existing full preparation/output.
- `sync(changed_paths)` reads/parses only notified files and publishes replacements.
- `update(changed_paths)` performs sync, then full live-program preparation/output.
- Module membership/configuration changes use init + exec for a full compilation.
- There is one initial/update sync path; the first run is all additions.
- Workers build private candidate file/tokens/AST/scopes. Comparison precedes
  replacement under the publication lock. Unchanged files retain their syntax.
- Existing file and collected_name records have a single `changes` field:
  ADDED=1, CHANGED=2, BODY_CHANGED=4, DELETED=8; 0 is unchanged. Declaration/body
  changes may combine; added/deleted are exclusive. No AST/token flags.
- Match by declaration kind/name/enclosing declaration in the current supported
  subset. No persistent identity records, declaration_change structures, extra
  bookkeeping properties or multi-version history were approved. Only the previous
  live state is compared. Matched entries receive new syntax and occurrence objects;
  declaration object identity is not stable across updates.
- Compare function header/body independently. Already-supported struct fields are
  included in the declaration inventory. Top-level statements stay ordered in the
  file AST. Namespace/class/method/constant coverage is not implied.
- Keep duplicate declaration candidates. Global indexes map names to lists.
  Retain deletions with flags; Scope_Lookup.live removes them from resolution and
  ambiguity counting. Consumers must check deletion before following old references.
- Clear transient flags at the next update. Physical reclamation remains deferred.
- Full type/use/call/return preparation reruns; bidirectional dependency tracking
  and selective re-resolution are later work. Do not add them to a literal slice.
- Failed candidates keep the previous complete file and clear generated output.
  Earlier successful files may already be published; batches are not transactions.
  No watcher/service loop was added: a caller keeps the session and sends notifications.

Deferred debt is documented in incremental.md and REVIEW.md: physical deletion and
old-reference reclamation, remaining lookup/comparison scans, dependency tracking,
and multi-error validation. Root ordering now uses a temporary source identity
index; flag reset visits declaration inventory rows rather than all occurrences.
Do not reintroduce history tracking to handle a name reappearing after deletion.

## Validation and proof boundaries

For ongoing development the user wants focused checks, not a full native rebuild
for every edit. Run the incremental smoke when the change could affect sync:

```sh
php compiler/my-try/tests/incremental_smoke.php
```

This is one full PHP-hosted compilation plus one incremental update, restoring and
removing temporary source files afterward. It does not invoke the native compiler.
Optional `--restore` also compiles the restored file and compares its output with
both the initial output and a fresh full compilation. Broader sync cases are in
`tests/incremental.php`; run when touching matching, deletion, publication or flags.
Run PHP lint and `python3 compiler/my-try/tools/style_check.py` for relevant edits.
Use focused conversion and native proofs at meaningful new-feature boundaries.
Do not claim native support based only on successful PHP or conversion.

Earlier full native evidence (superseded by the integer S2S proof above):
[results/file-sync-native-01](../../../specs/planning/compiler_migration/results/file-sync-native-01/README.md).
Normal STAN-enabled build passed 142 PHP/native comparisons (48 valid, 94 rejected),
with all 48 valid LLVM outputs compiled/executed. Native preflight includes repeated
sync, body flags, unchanged caller reuse, duplicates and deletion. STAN had zero
blocking compile errors but 260 advisory errors and 104 warnings.

Candidate checkout was `/tmp/scpp-native-244`, revision
`d8ddde93b04d0e23d295e30f662c3a81b0d50fd1` plus previously applied portability/task
fixes. It is not an immutable clean release checkout; verify it before reuse and do
not rely on /tmp surviving. The verified release pin was not changed. Native harness:
`compiler/my-try/tools/native_validate.py` (see saved evidence for invocation).

Subsequent `44bcba86` changes passed PHP smoke/broader sync/pipeline tests, conversion
and style; they have NOT had another native rebuild. Do not describe that exact tip
as natively revalidated. `eec8846f` added the on-demand smoke/restoration test.
`e5ba49a9` is the main sync implementation commit. All three were pushed.

## Collaboration constraints

Discuss the selected catalog item before coding. Prefer a concept-owned local slice;
report a required wide cross-owner refactor before undertaking it. Do not add new
model structures/properties merely for convenience—the user explicitly constrained
the incremental implementation to flags on existing records. Explain any new model
need during the selected feature discussion. Preserve short project type names and
convertible-PHP discipline, including explicit typed locals for nested collections.
Commit and push completed scpp fixes so they are not lost. Stage only task-owned
files; leave the user's simultaneous edits intact. Do not create a new chat via tools
unless explicitly asked; this document is ready for the user to supply to one.
