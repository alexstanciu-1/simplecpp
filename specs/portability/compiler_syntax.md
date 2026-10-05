# Compiler-driven syntax and host boundaries
Doc Status: supporting

## Host declarations

An exact standalone `/** @scpp-no-export */` before a class (optionally final) or
an explicitly visible concrete class/trait method omits that declaration from PHS.
PHP still parses and executes it normally. Markers on properties or executable
statements are rejected. This is not conditional compilation of callers: exported
code must not call omitted declarations. Import/declaration indexing still runs;
the directive does not relax global prologue or trait-composition restrictions.

The small compiler's `Host_Report` owns presentation and the explicit sample-run
operation. `Native_Runner` remains host-only. `Compiler::exec()` now produces model
results without printing or invoking a toolchain. `main.php` composes compilation,
presentation and sample execution. Tests can inspect presentation without compiling.

## Structural syntax preservation

The portability converter now preserves `match`, `switch`, `do/while`, and `??`.
PHP parsing validates grammar; the native target owns typing and lowering. Use
strict enum cases in compiler switches, not mixed PHP loose-comparison semantics.
Coalescing does not certify every wrapper/container combination: native probes
remain required, and explicit isset guards are used before missing Storage reads.

`try/finally` and `try/catch/finally` preserve cleanup. The existing ordered framework
catch dispatch stays inside the protected region. The target restrictions still
apply: no return/break/continue from finally, and no break/continue escaping the
protected region. See `generators/php/specs/exceptions.md`. The converter does not
perform control-flow analysis to prove those restrictions.

Proof: `php tests/portability/compiler_syntax.php` covers omitted host bodies,
invalid annotation sites, nested cleanup on success and caught failure, enum-like
constant dispatch, coalescing, and rejection of dynamic instanceof targets. Literal targets now have
[checked object support](object_casts.md).
This is PHP behavior plus PHS inspection, not new native parity evidence.

Cross-file enum switches use the target generator's existing project type-kind
catalog, not only declarations in the current unit. For a remote property receiver,
bind the selector to an explicitly enum-typed local before switching; this does not
introduce cross-file property inference. The native regression
`tests/tools/test_scpp_external_enum_switch.py --results FRESH` checks typed
parameters, a typed property-read local, grouped labels and default dispatch against
PHP. The converter continues to preserve the switch structurally.
