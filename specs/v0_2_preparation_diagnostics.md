# v0.2 preparation diagnostics
Doc Status: normative

## Early rejection of statically invalid operations

In the v0.2 semantic compiler, when resolved types or source structure prove an
operation invalid, semantic preparation must reject it before backend emission.
Do not reproduce a guaranteed runtime failure merely because v0.1's type-blind
generator could not classify that operation. This changes diagnostic timing, not
the set of valid operations defined by the language contracts.

For example, `result_or_bool<T>` is not an admitted `??` operand on either side.
v0.2 rejects that combination during preparation, including an unselected fallback.
The v0.1 helper's runtime rejection remains its compatibility implementation.

Value-dependent failures still need runtime guards: a supported required extraction
from `result<T>` may encounter either a value or an error at runtime. Do not reject
the operation simply because failure is possible, substitute truthiness for presence,
or change its exception behavior. This rule does not require new constant evaluation,
flow narrowing or reachability analysis.

Admission belongs to the existing semantic owner and its prepared facts, not backend
spelling/type guesses. Validate alternatives even when runtime evaluation is lazy;
failed preparation must not publish executable output or reuse stale generated output.
Keep unsupported first-pass features distinct from language-invalid combinations in
documentation and diagnostics. The v0.1 generator and other runtimes are not implicitly
upgraded by this rule.
