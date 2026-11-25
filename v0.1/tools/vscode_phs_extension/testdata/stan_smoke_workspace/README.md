# STAN Smoke Workspace

This is a small multi-file workspace for repeatedly smoke-testing the integrated
Simple C++ VS Code extension.

## Files

- `main.phs`
  - entrypoint with one intentional local type morph warning
  - references a helper function and a class method from other files
- `lib/helpers.phs`
  - helper function with arguments and a return type
  - fixture constructor function for a demo user
- `lib/models.phs`
  - small exported class fixtures for class navigation and method navigation

## Expected First Checks

1. Open `main.phs`.
2. Confirm diagnostics show one warning on:
   - `$value = $greeter->greet($label);`
3. Hover `helper_name` in `main()` and confirm hover content appears.
4. Hover `SampleGreeter` after `new` and confirm class hover appears.
5. Run go-to-definition on:
   - `helper_name`
   - `SampleGreeter`
   - `greet`
6. Run find references on:
   - `helper_name`
   - `SampleGreeter`
   - `greet`

## Suggested Hover Targets

- `helper_name`
  - should eventually show a function shape with arguments and return type
- `SampleGreeter`
  - class navigation target
- `greet`
  - method navigation target

## Suggested Completion Checks

In `main.phs`, try completion:

- after `$`
  - visible locals such as `$user`, `$greeter`, `$label`, `$value`
- after `new `
  - declared type names such as `SampleGreeter` and `DemoUser`
- in type positions
  - names such as `string`, `int`, and class names
