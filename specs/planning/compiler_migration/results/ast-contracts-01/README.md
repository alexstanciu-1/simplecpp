# AST contracts and native-only scope observers
Doc Status: planning

PHP suites: storage, tokenizer, ast, ast_invariants, model, llvm_text passed.
The new invariant tests enumerate 48 binding combinations and four parameter modes.
Portability regression runner and weak_fields.php passed, including generated C++
inspection of weak_p fields and weakref_get acquisition. The three actual compiler
scope declarations are also checked for the expected weak conversion.

Converted 30 production inputs plus a minimal Compiler-construction entrypoint.
Ran `php /tmp/scpp-native-244/bin/scpp.php stan` from the converted project.
The candidate uses PR #244 plus the preceding portability fixes and weakref_get
builtin registration. STAN reports zero build-blocking diagnostics; advisory counts
are retained in summary.json. No native compiler/build/run was invoked. Native
expiration and retention semantics still need a future execution proof.

The converted input source hashes are recorded. Reproduce the PHP checks with
`php compiler/my-try/tests/ast_invariants.php`,
`php compiler/my-try/tests/ast.php`, `php compiler/my-try/tests/model.php`, and
`php tests/portability/weak_fields.php`. See specs/portability/weak_fields.md for
representation limits; @reference.weak alone remains documentary.
