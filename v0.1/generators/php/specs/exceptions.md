# Exceptions and finally lowering (current pass)
Doc Status: normative
Status: active v1 subset.

## Goals

- keep translated PHP exception flow in-process whenever possible
- avoid `exit()` / abort-style control transfer in normal error handling
- lower `try` / `catch` / `finally` without `goto`

## Runtime model

The current object model already uses handle-like `shared_p<T>` values for PHP objects created with `new`.
The exception pass keeps that model.

- throwable objects derive from runtime `Throwable` / `Exception`
- `throw` wraps a `shared_p<T>` throwable object into `::scpp::php::thrown_object`
- `catch (T $e)` tests the wrapper with `::scpp::php::catch_as<T>(...)`
- the catch variable materializes as a local `shared_p<T>`

This keeps exception transport consistent with the current object runtime and avoids switching user objects to by-value exception transport.

## Finally lowering

`finally` is lowered with one `std::exception_ptr` slot per protected region:

1. execute the protected `try` / `catch` region
2. if an exception escapes, save it into `std::exception_ptr`
3. run `finally` exactly once
4. rethrow the saved exception after `finally` completes

If `finally` itself throws, that new exception escapes and replaces the previously pending one.

## Current restriction

The current pass now supports `return` leaving the protected `try` / `catch` region before `finally`.
The generator rewrites those returns into a pending-return flag/value, runs `finally` exactly once, then performs the delayed return after exception replay.

It still does **not** support `break` or `continue` leaving the protected region, and it does **not** support `return` / `break` / `continue` inside the `finally` block itself. Those shapes are rejected explicitly by the generator to avoid silently wrong code generation.

A nested loop-local `break` / `continue` remains allowed when it stays inside the protected region, and a switch-local `break` also remains allowed when it only exits that switch.

`for (...)` inside a protected region is lowered to an explicit init + `while` form in the delayed-return path so the `loop` clause does not run after a pending `return` has already been captured.

## Outer process boundary rule

Normal library/runtime/generator code should throw typed exceptions.
Only the outermost CLI / process entry point should convert uncaught failures into a non-zero exit status.

## Generator diagnostics rule

When the S2S generator encounters an unsupported or semantically unsafe lowering shape, it must throw a typed generator exception instead of silently emitting placeholder code or returning a best-effort C++ file with fatal generator errors attached.

Current practical rule:

- builder/input failures throw `InputException` / `BuildException`
- generator failures throw `GenerationException` (and may later use narrower subclasses such as unsupported-feature vs lowering failures)
- batch sample tooling may catch those exceptions per file and write `*.errors.txt`, but the generator itself must fail hard at the detection point
