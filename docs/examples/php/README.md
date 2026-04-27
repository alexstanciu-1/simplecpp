# PHP Examples Suite
Doc Status: supporting

Purpose: provide tested, reviewable Prism++ / Simple C++ examples written with the PHP authoring surface.

This suite is for agents and humans who need concrete, valid authoring patterns without falling back to full-PHP assumptions.

## What This Suite Is

- one shared `scpp` project under `project/`
- one dispatcher entrypoint for all examples
- one expected-output artifact per example
- one lightweight validation runner

## What This Suite Is Not

- not a new semantic authority
- not generator guidance
- not proof that full PHP behavior is supported

Primary semantic references:

- `specs/spec_map.md`
- `specs/php/catalog.md`
- `specs/php/canonical_examples.md`
- `docs/ai_onboarding/coding_style.md`

## Layout

- `project/main.php`: dispatcher entrypoint
- `project/lib/`: shared dispatcher and CLI helpers
- `project/examples/`: one example file per scenario
- `project/expected/`: expected stdout for each example
- `project/tests/`: suite runner and ordered manifest
- `project/data/`: static input files used by examples

## Run The Suite

From `docs/examples/php/project/`:

```bash
php /home/alexv/__AI/simple_cpp_repos/hotfix_02/bin/scpp.php run
```

Or build first, then run the built executable:

```bash
./.prism/build/main
```

## Validate The Suite

From `docs/examples/php/project/`:

```bash
./tests/run_examples.sh
```

The runner:

- builds the shared project once
- runs the built executable once
- compares each example section in stdout to the checked-in expected output
- requires exit code `0` for the current example set

## Adding Or Updating An Example

1. Add `project/examples/NN_slug.php`
2. Register it in `project/lib/ExampleRegistry.php`
3. Add `project/expected/NN_slug.stdout`
4. Add the id to `project/tests/examples_manifest.txt`
5. Re-run `./tests/run_examples.sh`

## Hotfix Scope

This suite is intentionally conservative for the pre-release branch.

If a language or library feature does not currently work, do not add a workaround example for it here. Skip it, flag it, and fix the underlying issue separately.

Current pre-release note:

- If a feature still fails in this branch, do not add a workaround example for it here. Fix the owning layer instead.
