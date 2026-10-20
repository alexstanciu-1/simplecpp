# Calls, assignments, control flow and ownership review — 2026-10-17
Doc Status: supporting

Continuation of REPORT.md. Review only: no source fixes or repository documentation changes. Same PHP-hosted compiler, current checkout and Clang 18.1.3; separate generated translation units checked with -fsyntax-only, without a native compiler rebuild or runtime execution. Batches 4–6 use the existing shared test runner in CLI mode, 12 task slots.

## Confirmed new gap: captured concrete closures are admitted for reassignment, but lower to nonassignable C++ lambdas

Smallest tested arrow form:

    $n = 1;
    $f = fn(): int => $n;
    $f = $f;
    return $f();

Ordinary replacement, not just self-assignment:

    $n = 1;
    $f = function () use ($n): int { return $n; };
    $g = $f;
    $g = $f;
    return $g();

Borrowed environment variant, with the source still alive:

    $n = 1;
    $f = function () use (&$n): int { return $n; };
    $g = $f;
    $g = $f;
    return $g();

All pass Compiler::prepare() and Compiler::cpp(). Clang rejects the generated assignment:

    object of type '(lambda at ...)' cannot be assigned because its copy assignment operator is implicitly deleted

The generated pattern is:

    auto local_f = [local_n]() mutable -> scpp::int_t<> { return local_n; };
    auto local_g = local_f;
    local_g = local_f;

The reference variant uses [&local_n] and fails identically. Also reproduced inside a nested closure, with a receiver capture ($this), and in chained assignment. These are variants of ONE underlying defect, not separate issues.

Evidence:
- batch4/closure_capture_assign/result.json
- batch4/closure_ref_assign/result.json
- batch5/closure_capture_self/result.json
- batch5/chain_capture/result.json
- batch6/capture_nested_reassign/result.json
- batch6/capture_receiver_reassign/result.json

Each directory includes main.phs, prepared.txt, artifacts.json, cpp/ and result.json. Exact invocations/exit statuses are in the batch logs/commands.json; Clang output is in logs/*-clang-*.stderr.

Controls that pass Clang:
- Copy-initialization of a captured concrete closure without reassignment (batch5/closure_copy_control).
- Reassignment of a capture-free concrete closure (batch4/closure_copy_assign).
- Reassignment through an explicitly typed function<int()> local holding a value-capturing closure (batch5/closure_erased_control).

## Ownership/model diagnosis

compiler/my-try/03_parse/types/model.php:1131 registers closure types as value_storable and copyable_value. 04_analyze/prepare/semantics/capabilities.php:15 returns those declared capabilities for a concrete closure. Body binding uses Value_Boundary_Preparation::decide_value (bodies.php:248), whose copy path checks copyable_value (value_boundaries.php:60).

That admission does not distinguish copy construction from replacement of an existing closure object. In 05_backend/cpp/syntax.php:18 the representation is a native lambda; generate.php:410 emits declarations or assignments, including plain '=' for replacement. For a capturing C++ lambda, copy construction works while copy assignment is deleted. This is an operation/representation guarantee missing from the accepted boundary.

Do not classify these programs as proven language-invalid. They use supported closure syntax and ordinary same-type assignment; the concrete closure replacement policy needs a clear contract. If replacement is supported, the backend needs a representation/operation that implements it. If deliberately unsupported, preparation must reject it with a source-level diagnostic. Blanket removal of copyability would also reject the working copy-initialization control. No repair is implemented or proposed for automatic approval here.

The borrowed example has no escaping storage: n outlives f and g. Its error is not a reproduced lifetime escape. Passing lifetime checks must not be mistaken for proving assignability.

## Other reviewed paths

No additional independent Clang failure was found in these probes:

- Calls: too many/few arguments, void/incompatible arguments, temporary reference arguments, read-only arguments passed to writable references, erased callable arity/type/reference checking, private method access, wrong static/instance call syntax, defaults, variadics and void-return forwarding.
- Assignment: read-only scalar/container/alias mutation, temporary inline field writes, void initialization, incompatible closure identities, unique assignment snapshots and chained unique copies. Invalid cases rejected in parsing/preparation; selected controls compiled.
- Control flow: missing returns after loop breaks and switch breaks, do/switch continue paths, duplicate switch labels, continue without a loop, cross-branch/group locals, invalid calls in unreachable statements and statically unselected branches. Rejected as appropriate. Complete switch/do-return and nested continue controls compiled.
- Ownership/lifetime: returning local references, borrowing heap-backed properties, borrowing-closure erasure, moving read-only ownership, implicit unique copies, unique variadic parameters, inaccessible destruction, and unique upcasts without virtual destruction. These rejected during preparation. Explicit consuming calls/returns and caller-owned reference controls compiled.

This does not establish runtime lifetime safety. Clang accepts many dangling-reference patterns; this review only sought static failures that escape preparation. No runtime sanitizer or incremental recovery claim is made.

Some exploratory syntax was rejected by the parser. Those cases are not counted as evidence of semantic validation. Same-type captured closure assignment failures are retained above; no attempt was made to turn them into multiple independent findings.

## Reproduction

    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases4.json rerun4
    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases5.json rerun5
    MY_TRY_PHP_EXECUTOR=cli python3 specs/planning/results/compiler_diagnostic_review_2026_10_18/run.py cases6.json rerun6

Use fresh output names. See REPORT.md for the harness method and exact Clang flags.
