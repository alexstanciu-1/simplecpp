# Exception / control-flow spot checks
Doc Status: supporting
- `test_07_finally_return.phs` exercises delayed `return` through `finally`.
- Literal forms such as `break 2;`, `break 3;`, or `continue 2;` may already be rejected by the PHP front-end when the nesting depth is invalid at parse/compile time.
- The generator still now rejects any non-unit `break` / `continue` depth that reaches IR lowering, including non-simple depth expressions.
- `test_08_finally_return_for_loop.phs` checks that delayed `return` does not still execute the `for` post-expression after the return has been captured.
- `test_09_finally_nested_loop_break.phs` keeps a loop-local `break` inside the protected region; this shape should remain accepted because the break does not leave the `try` / `finally` region itself.
