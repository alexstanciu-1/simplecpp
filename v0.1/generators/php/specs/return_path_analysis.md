# Required return-path analysis
Doc Status: supporting

The frontend summary's `returns_on_all_paths` flag means a function cannot complete
normally without a result: both return and throw terminate a path. STAN retains
separate return-value compatibility checks, including bare returns in typed functions.

The structural analysis composes sequence exits and exhaustive if/switch branches.
Switch cases are analyzed backwards to account for fallthrough and grouped labels.
Missing default branches and breaks can still complete normally. Unreachable returns
after a break do not establish completion. Multi-level jumps conservatively prevent
a proof; loop nontermination and catch/finally completion are not inferred.

`php tests/tools/test_scpp_return_paths.php` exercises accepted and rejected source
through the public frontend extraction/summary path. The full strict-discipline test
also retains its negative missing-return check.
