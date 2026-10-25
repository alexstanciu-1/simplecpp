# Explicit Storage argument boundaries
Doc Status: planning

2026-10-25, baseline c2b8eab54 plus this slice. Source hashes identify the snapshot.
Reuse analysis: generic runtime-call matching requires exact types; ordinary typed
local assignment already supports checked optional extraction and shared upcasts.
Portable PHP provides those boundaries explicitly. No runtime/converter/compiler
semantic change and no new collection or record identities.

Changed nullable body retirement, namespace body insertion, declaration/for/arrow/
interpolation collection insertion, and constant-index removal argument views.
Order of collection mutations stays unchanged; locals retain the original objects.

Production retirement method extracted into a bounded portable fixture passes PHP,
v0.1 and v0.2 ordinary/prelowered native runs. Checks identity, mutation visibility,
repeat retirement without duplication, empty source state, and derived-to-base
insertion identity. Full parser native compilation remains part of bootstrap.
Initial fixture conversion needed an explicit Storage construction annotation;
second passed, third expanded the base-view proof and passed all routes.
Five actual host tests pass: declaration_model, member_owners, namespace_bodies,
closure_model, interpolation. No broad native-compiler claim from these tests.

Bootstrap: 167.41 seconds, 511328 KiB peak RSS (499.34 MiB), 486.5 MiB PHP heap.
24 distinct messages remain, now starting with stable-storage assignment.
Other exact-signature failures remain; aggregate strings cannot count fixed sites.
No fatal/OOM, emission, native compiler or second-generation completion.

The isolated host audit instrumented Preparation_Worker failure propagation without
modifying production files. audit_partial.json contains deduplicated observed work
owners/messages, including propagation (not independent defects). The diagnostic
run was interrupted after sufficient source-location evidence; it is not a completed
bootstrap or a timing benchmark. Current measured bootstrap used uninstrumented host.

Next source audit: parser constant_declaration/callable_declaration assignment
receivers; scope projected collection queries; enum cursor mutation semantics.
Enum defaults and polymorphic prepared-expression casts remain separate issues.
