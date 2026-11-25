# Retained class mutation receivers
Doc Status: planning

2026-10-25. Baseline eb43bb244 plus this slice. Source hashes identify bootstrap input.
Reuse: shared class locals are addressable storage and retain object identity;
ordinary checked optional extraction and object_cast already exist. Replace writes
through cast/call temporaries and optional field projections with retained local
class receivers. No compiler semantic, converter, runtime or reference-protocol change.

Affected owners: parser constant/method modifiers, preparation settlement/retirement,
and constructor base-initialization facts. Existing worker ownership and algorithms
remain unchanged. Locals extend retention only through their lexical mutation scope.
Prepared-file creation initializes the fresh local before publishing it; no callback
or read occurs between those assignments. No struct or value-container copy rewrite.

Proof: production parser cast/mutation fragment extracted into a bounded portable
fixture plus call-result and guarded nullable receiver writes. PHP, v0.1 native,
v0.2 ordinary and prelowered native agree on mutation visibility and identity.
Seven affected host tests pass: declaration_model, member_owners, source_constants,
incremental_preparation, lifecycle_handoff, preparation_recovery, preparation_work.

Fixture attempts: initial free-function form rejected by converter; renaming did not
change that. Switched to the source's class-method form, then replaced unsupported
annotated nonempty argument literal with an explicitly typed local vector. Fourth
attempt passes all routes. These corrections do not change production semantics.

Full bootstrap measurement and remaining diagnostics: see bootstrap.json/time.
No native-compiler or second-generation acceptance claim from bounded native fixtures.

Measured bootstrap: 142.29 seconds / 511888 KiB peak RSS (499.89 MiB) /
486.5 MiB PHP heap. Still 24 deduplicated diagnostic strings; enum initialization
is now first. Stable-storage assignment also remains at other locations, so the
aggregate does not establish that every site is repaired. No OOM or emitted compiler.
