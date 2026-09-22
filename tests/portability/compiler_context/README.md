# Adopted compiler component proofs
Doc Status: supporting

`main.php` is a shared PHP/PHP++ behavioral witness for the actual
`compiler/src/compile/state.php` component. There is no maintained copy of that
component here. The cumulative witness also loads the real tokenizer
`src/02_tokenize/structures.php`; see the [tokenizer slice](../../../specs/portability/compiler_token_slice.md). See the [slice contract](../../../specs/portability/compiler_context_slice.md).

`prologues.py` checks namespace/declare placement, scoped imports, local scalar
fields and diagnostic attribution without native compilation.

`run.py --target-checkout <clean-v0.1.76-checkout> --results <fresh-directory>`
loads the real component in PHP, stages the explicitly ready file set for local
conversion, checks incremental reuse/rejections, runs sixteen existing compiler
fixtures, and executes the generated strict PHP++ project. Results retain logs
and the generated component for inspection; scratch build outputs remain in /tmp.

The cumulative runner also loads `compiler/src/compile/step.php`, checks its exact
PHP interface surface, and compiles the lifecycle contracts in the native project.
The shared witness checks unit enum state assignment/comparison. See the
[step slice](../../../specs/portability/compiler_step_slice.md).

The source-path slice adds pure spelling methods and byte-slice proofs, including
both host spelling policies and non-ASCII inputs. The PHP host loads the central
framework before the ready production files.

The [parser node slice](../../../specs/portability/compiler_nodes_slice.md) adds
node defaults, links, spans and shared mutation to the cumulative witness.

The [role-view slice](../../../specs/portability/compiler_role_views_slice.md)
adds real promoted constructors, defaults, nullable enum extraction and a separate
PHP readonly enforcement check. Native readonly enforcement is not claimed.

The [cursor slice](../../../specs/portability/compiler_cursor_slice.md) adds typed
list fields, mutable promotion, append/index/count, copy independence and reset
through an explicitly typed local. This completes the parser data declarations,
not migration of the parser algorithm or its storage.

The [storage slice](../../../specs/portability/compiler_storage_slice.md) adds
source snapshot identity/bytes and list membership copying with shared node objects.

The [decimal slice](../../../specs/portability/compiler_decimal_slice.md) adds
large integer boundaries and the integer-conversion integration fixture. The
separate `../decimal_range.py` oracle expands PHP coverage independently.

The [exception slice](../../../specs/portability/compiler_exception_slice.md) adds
backend-configuration validation, native framework assembly and handled-error
proofs. `../native_runtime.py` independently checks assembly ownership and reuse.
