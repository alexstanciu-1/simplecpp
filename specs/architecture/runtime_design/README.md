# Runtime Design Rules

Status: active normative architecture support folder.

## Purpose

This folder contains runtime code-organization rules whose job is to prevent semantic drift.

These documents do not replace user-visible language semantics.
They define how runtime code must be organized so that normative behavior is implemented through one authoritative path instead of duplicated logic.

## Scope

This folder is the right place for rules such as:

- operator-family file ownership
- shared-helper reuse requirements
- semantic authority boundaries
- anti-fallback rules
- runtime dispatch centralization rules
- spec traceability requirements for runtime helpers

## Placement rule

Runtime design-rule documents belong here under `specs/architecture/runtime_design/`.

They must not be added ad hoc into the main `runtime/` folder or scattered across unrelated top-level spec files.
This keeps runtime implementation folders focused on code and keeps design-governance material discoverable without flooding the runtime tree.
