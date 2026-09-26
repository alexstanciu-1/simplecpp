# Historical model conversion checkpoints
Doc Status: historical

Superseded by [the current conversion review](conversion_review.md).
Descriptions of pending work below apply only to their recorded checkpoint.

This file preserves conversion checkpoints from before the object-list simplification.
Current structure and API are in ../MODEL.md and ../docs/storage/STORAGE.md. Storage_View
and parallel AST registries are no longer conversion requirements.

Reviewed 2026-09-24 against the local portability checker and authoring contract.
Scope: Model, Storage/view helpers, retained/preparation record declarations and
Syntax_Nodes. No production behavior, converter or runtime changes in this pass.

## Evidence and limits

[Saved evidence](conversion_review_evidence.json) records source hashes, checker
results, elapsed checker times and complete focused probe inputs. Each of 12 files
was copied unchanged into a separate temporary directory, then checked with
`php tools/php_portability/check.php TEMP_DIRECTORY`. Only
04_analyze/structures.php passed. Each failure is the first diagnostic for that
file, not an exhaustive error inventory. Isolated checking does not resolve named
types or establish dependency closure, lifetime safety or native behavior.
Five additional minimal probes confirmed the declaration/binding gaps below.
No files have been registered as conversion-ready. No native build was attempted.
The configured compiler portability target is 9b4b33f35f053b487e018c94d6a4a7888d77c64a;
this review does not assert that a newer runtime implements these bindings.

## Findings and owning layers

| Finding | Evidence / consequence | Next owner/action |
| --- | --- | --- |
| Static Model fields | Even an initialized static int is rejected as `expected function`. | Converter declaration/access support; retain the agreed static Model. |
| Required fields without defaults | Source, token, AST and collected records reject at their first uninitialized field. | Decide required-field construction, then prove converter support. Do not add fake zero/null values to mandatory references merely to pass checking. |
| Storage template annotations | A minimal nullable Storage property with the adjacent template comment rejects with `expected =`. | Converter/runtime binding for both Storage and Storage_View, at declaration and construction sites. Generic support cannot be assumed from vector/hash support. |
| PHP helper implementation | Mixed-key arrays, abstract/inherited views, interface inheritance, object/mixed methods, generators and finally exceed current portable coverage. | Keep PHP as behavioral reference; use the separately implemented native helper. Do not translate PHP internals mechanically. |
| Nullable parameters | Syntax_Nodes payload parameter rejects; Parser and helper signatures also need review. | Bounded converter signature capability with PHP/native proof; preserve meaningful absence. |
| Token backlink invalidation | `unset($source->tokens)` rejects: only hash-slot unset is supported. | Prefer explicit optional backlink plus null reset when nullable access is proved; distinguish required record links from this optional convenience link. |
| Initialized policy map | LLVM policy types map rejects its nonempty literal initializer. | Source adaptation to supported typed initialization, preserving the explicit policy owner. |
| Value layout and weak links | Ownership tags are documentary. Ordinary classes preserve shared handles; current @scpp-struct grammar is scalar-only. | Native representation/lifetime decision and proof. No automatic value-record promotion or weak-reference claims. |

## Important behavior to preserve

- Storage identities survive growth, replacement/removal for already retained PHP
  handles. Views resolve current owner positions; these are distinct behaviors.
- Native contiguous value rows with bounded borrows cannot silently replace the
  current shared-handle contract. Reconcile that before selecting token/AST layout.
- AST payload objects are registered and then populated through local handles.
  Inline value insertion would change this behavior unless construction/writeback
  is deliberately adapted. Node header links and typed payload stores must agree.
- Scopes and collection indexes form cycles through non-owning relationships.
  Copying PHP strong handles into native shared ownership does not implement the
  documented weak edges or bounded reclamation.
- file.tokens is absent before successful tokenization. It is a genuine optional
  reference; most other uninitialized links are required at publication.
- Source spans are byte offsets. Later tokenizer conversion must use the byte
  helper contract and explicit bounds guards, not UTF-8 text indexing or assumed
  native short-circuit evaluation.

## Recommended conversion sequence

1. Establish native helper binding: typed declarations, constructor specialization,
   indexed access, append, iteration, identity and membership mutability. Preserve
   the empty final Storage_View class at the PHP authoring surface. Keep capacity a
   constructor argument. A runtime helper alone does not establish PHP conversion.
2. Add static Model support and the required-record construction contract, then
   optional token backlinks and stage invalidation. Prove reset and failed-stage
   behavior without changing the model's static ownership design.
3. Convert one source/token component with byte-based scanning; compare independent
   expected tokens, spans and errors in PHP and native. Decide compact token layout
   explicitly rather than using shared classes as a claimed final optimization.
4. Continue file by file into AST/payload references, collection indexes and LLVM
   preparation. Keep host bootstrap/reporting/test runners outside converted input.

No new compiler features, rollback system, broad converter rewrite or speculative
native storage architecture is part of this review. The first implementation slice
should be the helper binding contract, coordinated with the native Storage work.


## Static-field follow-up

Initialized static-field declarations and literal/self access are now implemented
and PHP/native proved; see [static-field contract](../../../../specs/portability/static_properties.md). The original evidence above remains the pre-change
checkpoint. Model's uninitialized required fields, custom Storage bindings and
optional-reference reset are still pending; this does not mark Model convertible.


## Nullable-parameter and backlink follow-up

Explicit nullable scalar/named method parameters, including optional null defaults,
are now supported. file.tokens is explicitly ?token_list, initialized/reset to null;
its absent state no longer depends on object-property unset. The original evidence
remains historical. The wider nullability audit, required-field initialization and
custom Storage bindings remain pending.


## Required-field declaration follow-up

Explicit nonnullable instance/static fields, including annotated vector/hash fields,
now support omitted initializers. Read-before-assignment is not supported behavior;
STAN remains enabled and may require constructor initialization for worker fields.
Custom Storage annotation/binding work remains a distinct blocker.
