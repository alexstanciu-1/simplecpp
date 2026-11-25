# Historical documentation
Doc Status: historical

These documents preserve earlier proposals and proof checkpoints. The active feature roadmap stays in
[docs/catalog](../catalog/README.md), with its examples and rule cards.
They are not current implementation instructions. Dates, “current” headings, paths,
counts and unfinished-work statements describe their original checkpoint and may be
superseded. Start with the [active documentation](../README.md).

- **Completed designs/audits:** specialized AST, data relationships, initialization,
  model/storage/view and ownership proposals record why earlier structures changed.
  Current ownership is documented in `architecture/`; do not restore old payloads,
  sibling links or view registries from these files.
- **Portability evidence:** native adaptations and conversion checkpoints retain
  toolchain/source qualifications. Historical full passes do not certify newer ASTs.
- **Incremental history:** `incremental_strategy_history.md` preserves the discussion
  and superseded alternatives. Current behavior and remaining debt are separate in
  `lifecycle/incremental.md` and `planning/incremental_strategy.md`.
- **Old handoff/slices:** the initial v0.2 handoff and integer slice are historical;
  active feature decisions belong to the catalog and semantic owners.

New historical records should identify their source/checkpoint and active replacement.
Do not append current operational instructions here.
