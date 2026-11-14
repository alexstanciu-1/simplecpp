# Proposed implementation order for the v0.3 bootstrap

Doc Status: planning

Date: 2026-11-14. Based on the [language audit](v03_bootstrap_language_audit.md) and the subsequent implementation-order discussion. This is a proposed sequence, not authorization to implement every item or a claim that the features are delivered.

## Approach and scope

The audit is organized by subject. Implementation should follow dependencies and prove small, executable parts of the actual compiler. Preserve behavior, type relationships, ownership, identity and failure recovery. A readable native-oriented translation may use deliberate local adaptations instead of reproducing every PHP mechanism, but that translation policy remains a discussion point.

Use existing metadata, type handlers, argument binding, collection machinery and incremental ownership. Do not implement every PHP construct simply because it occurs in the compiler. Discuss substantial architectural substitutions and wide refactors before proceeding. Coordinate collection changes with the workspace that owns them.

Numbers give the proposed implementation order within each milestone. They are discussion-sized groups, not single batch implementation requests. Reassess the next group after each executable proof. Language readiness and runtime/host readiness are separate tracks. Inventory host dependencies during milestone 1, even though their integration is milestone 7.

## 1. Typed data foundation — tokens and source snapshots

| Order | Group | Initial scope |
| --- | --- | --- |
| 1.1 | Integer representations and literals | Activate the widths actually needed by the compiler through metadata. Add hexadecimal and octal literals with checked bounds. |
| 1.2 | Backed enums | Named cases, integer/string backing, equality, backing-value access and checked construction. Reuse method machinery for enum methods. |
| 1.3 | Application-defined collection elements | Start with vector of token, copying, parameters, returns and field storage. Coordinate with collection work. |
| 1.4 | Collection traversal | Ordered value iteration, then keyed map iteration. Set explicit mutation and element-copy behavior. |
| 1.5 | Construction-time immutability | Readonly fields initialized during construction. Keep delayed AST attachment separate. |

Proof: translate the token representation and source-location lookup. Construct, pass and iterate a token list. Verify spans and line/column results in a native executable.

Do not block this proof on every optional width, populated collection literals or full PHP readonly behavior. Start the host/runtime binding inventory alongside this work.

1.2 status: the [first integer-enum slice](../../compiler/docs/enums.md) supplies
named literal cases, compact/exact storage, same-enum equality, typed calls and
`enum_value`, with incremental recovery. Enum instance fields with explicit case
defaults and function/method/constructor enum parameter defaults are also implemented.
Checked integer reverse construction is also implemented, reusing generated integer conversions.
`enum_name` and byte-exact string backing/helpers are implemented using compact ordinal storage.
Public instance enum methods now reuse the function binder and common method ownership.
The bounded 1.2 scope is implemented; unit enums, uint64 backing, static/private enum
methods, reflection and broader enum integration remain separate follow-ups.

## 2. Readable selection and lookup — a tokenizer path

| Order | Group | Initial scope |
| --- | --- | --- |
| 2.1 | Selection | Match with one subject evaluation, lazy selected arms and a clear result-type rule. Start with enum/scalar subjects, then match on true. |
| 2.2 | Container presence and mutation | Contains/find, removal, missing-key handling and ordered keyed traversal. Distinguish missing from stored null. |
| 2.3 | Lazy fallback | Coalescing, then coalescing assignment through the same presence model. Avoid repeated lookups and eager defaults. |
| 2.4 | Text and table construction | Raw/multiline strings and bounded populated collection forms required by compiler tables. |
| 2.5 | Tokenizer runtime bindings | Byte-scanning or regex facilities needed by the selected translation. These are runtime facilities, not new language constructs. |

Proof: tokenize representative input through a translated path and compare token kinds, positions and diagnostics with PHP.

Keep ternary, switch, assignment expressions and general destructuring outside the critical path unless actual translation demonstrates a need. Isolated uses may have clear alternatives, while extensive enum maps warrant a direct language facility.

## 3. Object relationships — a small AST family

Discuss inheritance, handles and interfaces together before implementing this milestone.

| Order | Group | Initial scope |
| --- | --- | --- |
| 3.1 | Base storage and inheritance | One base, derived fields, inherited access, protected access and construction. No multiple class inheritance. |
| 3.2 | Behavior through base references | Virtual/abstract methods, overrides, parent calls and derived-to-base shared handles. Preserve destruction and allocation identity. |
| 3.3 | Type tests and narrowing | Subtype tests, then interface-capability tests. Keep exact concrete type identity distinct. |
| 3.4 | Interfaces | Interface-typed parameters/references and multiple capabilities using the dispatch model. |
| 3.5 | Non-owning AST links | Define validity of parent links and retained-generation references. Reuse weak handles where suitable; discuss borrow/reference machinery where needed. |
| 3.6 | Staged initialization and composition | Write-once attachment, settling ranges and a declaration trait/interface use. Evaluate bounded traits versus a simpler truthful owner. |

Proof: one base node, two concrete expressions, child attachment and a base-typed operation. Check overrides, original-object identity, destruction and invalid access.

Do not translate every PHP object reference into an owning shared handle. Ownership cycles and generation lifetimes must be addressed explicitly.

## 4. Failures and cleanup — failed preparation and retry

| Order | Group | Initial scope |
| --- | --- | --- |
| 4.1 | Source-authored exceptions | Throw, typed catch and rethrow, diagnostic payloads and ordinary value cleanup. |
| 4.2 | Guaranteed cleanup | Finally, or an explicitly agreed equally direct construct. Cover return, throw and nested cleanup. |
| 4.3 | Failure expressions | Throwing fallback/default arms through the established expression and exception models. |
| 4.4 | Host failures | Typed bindings for expected misses and operational failures without importing PHP warnings. |

Proof: fail midway through preparation, restore its context and retry successfully. Separately fail output publication and verify staging cleanup.

Basic exception infrastructure may move earlier if required by preceding proofs. Complete recovery behavior before translating substantial analyzer code, to avoid spreading a temporary error protocol throughout it.

## 5. Parser, preparation and emission — one complete translated path

| Order | Group | Initial scope |
| --- | --- | --- |
| 5.1 | AST collections and scopes | Base-typed node collections, declaration indexes and duplicate chains. |
| 5.2 | Canonical type instances | Type-owned static handles and lazy initialization preserving singleton identity. |
| 5.3 | Generated definitions | Existing metadata generator emits target-language definitions and registration for required types. |
| 5.4 | Remaining signatures | Review actual unions/intersections and nonliteral defaults individually. Preserve guarantees without automatically implementing all PHP type syntax. |
| 5.5 | Traversal callbacks | Named callable references first. Add captures where they materially simplify the translation. |

Proof: parse a real expression, collect its declaration, prepare through the common AST, emit C++, compile and execute. Include a diagnostic and successful retry. Review readability and ownership as well as results.

## 6. Incremental identity and retention — multiple generations

| Order | Group | Initial scope |
| --- | --- | --- |
| 6.1 | Identity-keyed storage | Symbol/watcher keys and non-owning references with explicit lifetime boundaries. |
| 6.2 | Collection snapshots | Copy membership while retaining referenced objects. Distinguish snapshots from draining a pending queue. |
| 6.3 | Watch dispatch and invalidation | Reuse interfaces and stable symbol identities. |
| 6.4 | Retirement and recovery | Removed/replaced declarations, failed generations, pending work and re-addition. |

Proof: edit a file, retain an unchanged AST, invalidate appropriate bodies, recover from failure and compare with a fresh compilation. Repeat sessions to reveal retention problems. Translate the existing incremental model instead of redesigning it in this milestone.

## 7. Host integration and bootstrap — a native compiler

| Order | Group | Initial scope |
| --- | --- | --- |
| 7.1 | Host data | Manifest/JSON shapes, validated records and inspection output. |
| 7.2 | Runtime facilities | Files, paths, regex if retained, process invocation and other bindings from the early inventory. |
| 7.3 | CLI compiler | Compile a project through the native compiler and compare artifacts and diagnostics. |
| 7.4 | Resident service | Socket protocol, retained sessions, idle shutdown and repeated requests. |
| 7.5 | Bootstrap verification | Build the native compiler using PHP, then assess compiling its source with the resulting native compiler. |

Host dependencies must be inventoried early, not discovered only at milestone 7. Their implementation can proceed separately from language work.

## Features outside the demonstrated required path

- General generators and yield.
- Arbitrary closure captures and recursive closures.
- General-purpose source templates.
- Full PHP union/intersection syntax.
- General cloning and PHP array copy-on-write.
- Every expression-valued assignment/update form.
- Named arguments everywhere.
- Full dynamic-object semantics.
- Language-level threading and cross-thread ownership.

These are not rejected. Review the actual use before deciding whether a bounded feature or a clearer translation is preferable.

## Next discussion and verification policy

Suggested immediate order is 1.1 then 1.2 in this workspace, coordinating collections separately, followed by the token/source-snapshot proof. After the foundation, prioritize AST relationships over peripheral syntax. The tokenizer work can proceed independently where its prerequisites are available.

Open decision: confirm a readable native-oriented translation with deliberate local adaptations, rather than near-verbatim PHP. This determines which traits, unions, closures and collection behaviors are mandatory. Writing this plan does not settle that question.

For each implemented slice, test the real caller and failure/lifetime behavior, not only syntax acceptance or C++ emission. Apply relevant all-flavor and incremental recovery checks. Native proofs must compile and execute. Report compiler work, qualification, native wall, end-to-end and Clang frontend/backend/link timings when benchmarking, plus change counts. No performance or completion claims follow from this proposed ordering alone.
