# STAN LSP Smoke Workspace

This is a tiny repeatable workspace for manually smoke-testing the VS Code STAN extension scaffold.

## What To Expect

- `main.phs` contains:
  - a clean helper symbol for hover/definition/reference checks
  - one intentional local type morph warning in `main()`

## Expected First Checks

1. Open `main.phs`.
2. Confirm diagnostics show one warning on `$value = helper();`.
3. Hover `helper` in `main()` and confirm hover content appears.
4. Run go-to-definition on `helper()` in `main()` and confirm it jumps to the top function.
