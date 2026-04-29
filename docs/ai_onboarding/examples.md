# Examples
Doc Status: supporting

Purpose: route AI assistants to the best example sources in the repo.

## 1. Canonical Semantic Examples

Start here for preferred PHP authoring patterns:

- `specs/php/canonical_examples.md`
- `docs/examples/php/README.md`
- `docs/examples/php/strict/README.md`

Use `docs/examples/php/` when you want tested, project-mode PHP examples for agents rather than abstract style guidance alone.

These examples are especially useful for:

- strict equality
- explicit false/null handling
- typed function boundaries
- avoiding ambiguous truthiness
- strict-vs-legacy profile selection

## 2. Generator Sample Suites

Use when you need real lowering-oriented source examples:

- `generators/php/samples/stage_01/`
- `generators/php/samples/stage_02/`
- `generators/php/samples/stage_03/`

Use these to answer:

- is this shape already supported?
- what lowering style is already established?
- is there an existing nearby sample to extend instead of inventing a new one?

Treat `stage_01` to `stage_03` as the main compatibility-oriented sample progression, and treat `10_negative_cases.php` or explicit rejection fixtures as rejection anchors.

## 3. `know_how` Reconnaissance Fixtures

Use when the real problem is exporter or AST reality:

- `generators/php/samples/know_how/`
- `generators/php/samples/know_how/README.md`

These are the best examples for:

- `echo`
- `isset`
- `unset`
- namespace/use forms
- foreach variants
- slot-reference edge cases
- complex conditions and control-flow shapes
- exporter-specific surprises that should be proven before changing lowering

If a syntax form is AST-uncertain, prefer adding or consulting a focused `know_how` fixture first.

## 4. Runtime / User-Facing Docs

Use these when examples are tied to specific runtime modules:

- `docs/json_builtins.md`
- `docs/filesystem_builtins.md`
- `docs/fastcgi.md`

For normative builtin behavior, prefer the owning spec under `specs/builtins/`.

## 5. How To Use Examples Well

Preferred AI behavior:

- find the nearest existing example before inventing a new pattern
- prefer examples that already align with current normative specs
- use examples to confirm style and workflow, not to override higher-level semantic authority
- when examples and specs disagree, follow the authority order from `specs/spec_map.md`

## 6. Good Example Selection Rules

Choose examples based on the task:

- semantic intent example -> `specs/php/canonical_examples.md`
- strict profile example -> `docs/examples/php/strict/`
- legacy profile example -> `docs/examples/php/legacy/`
- lowering shape example -> `generators/php/samples/stage_*`
- AST/exporter reality example -> `generators/php/samples/know_how/`
- project workflow example -> `docs/getting_started.md` and usability harness docs
- runtime module example -> `docs/*_builtins.md` plus the owning builtin spec
