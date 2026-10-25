# Portable shared receivers and typed key facts — 2026-10-25
Doc Status: planning

Named shared Storage receiver locals in Iterator_Preparation, CPP_Self,
CPP_Runtime_Operations and Runtime_Catalog_Registration preserve membership identity.
Named returned function handle preserves mutation of snapshot_borrow_safe. Async
call target publication uses existing explicit weak(). Record construction prepares
the known string syntax then reads its typed accessor, avoiding a generic fact
narrowing. outlives uses unconditional for (;;) with unchanged behavior.

PHP/v0.1/both v0.2 native proofs pass: shared receiver mutation, weak interface
observation, and the actual record-key preparation/accessor statements with call count.
First receiver fixture lacked the required annotation on new Storage assigned to a
field; fixing the fixture annotation required no converter change. Host data_construction,
storage_lifetimes, runtime_payload_calls and incremental_preparation pass. The
initialization suite's expected rejection fails identically using unchanged parent
sources; current/baseline logs retained.

Measured bootstrap with host-only deduplicated failure-owner logging: 1:30.05 wall,
628908 KiB RSS, 594.5 MiB PHP heap. Categories 11 -> 7. Logging lives only in /tmp;
audit_recipe.py documents the setup. This timing includes observer overhead, and is
not a performance claim. owners.json contains all observed failing owners from this
completed run, not necessarily independent root causes. No native compiler build yet.

Remaining groups: required-null JSON output binding, nonpolymorphic narrowing,
late-exposed typed foreach list, static indexed storage, optional enum comparisons,
more generic argument boundaries, and nullable selection. Continue safe PHP fixes;
leave binding/retained-protocol changes for discussion.
