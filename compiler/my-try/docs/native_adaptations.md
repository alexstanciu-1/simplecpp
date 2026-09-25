# Native build adaptations for review
Doc Status: planning

Active goal: build and run the ported compiler with PHP/native parity. These are
source adaptations to the existing v0.1 toolchain, not new compiler features.

- Collection receiver locals make Storage/Keyed_Storage templates explicit across
  files. These wrappers retain shared membership; ordinary value containers are
  not treated as aliases.
- Constructor/per-invocation workers establish real initialized inputs. Public
  reusable facades retain their existing reset and failure-publication behavior.
- Parser `payload_node` accepts a required node_specialization before forwarding it to
  the optional-payload constructor. This separates concrete-to-interface conversion
  from nullable wrapping without copying the payload.
- Parser `error_message` formats a string; call sites construct RuntimeException.
  This avoids returning the framework exception through a native signature whose
  global namespace qualification is lost. Error text and exception family stay the same.
- Enum dispatch uses explicit equality branches because cross-file enum switches
  emitted scalar-wrapper access and unsupported case placeholders. Diagnostic names
  use Node_Kind_Name beside the enum declaration.
- Missing required map entries use isset guards then typed reads. Optional decisions
  use branches instead of native-incompatible `?? null`/mixed ternaries. Declaration
  type and binding-declaration helpers return checked required handles.
- Scope parent traversal checks absence then uses an explicit identity-preserving
  cast to recover the required handle. Unsupported nullable object-local annotations
  were not added to the converter.
- Template transient container resets use typed empty locals before field assignment.
  An absent symbolic local uses an empty string only on the path that must reject it
  as a type mismatch (valid symbolic types are never empty); this merits review.
- Local module/file/token variables were renamed where they shadowed native record
  type names in auto initializers.

- Typed vector/hash emptiness uses count checks instead of comparing with bare `[]`,
  which lowers to a different native container type. Storage keeps `is_empty()`.
- Kind-dependent payload casts occur after a separate kind guard, avoiding eager
  evaluation through native overloaded boolean operators.
- Template formal keys and positions are captured before incrementing the parser
  position. PHP/C++ evaluation order must not change the key being inserted.

Validation completed: a fresh normal STAN-enabled build linked the native compiler.
All 142 PHP/native comparisons passed (48 valid, 94 rejected); every valid emitted
program also compiled with Clang and ran with its expected exit code. Repeated
compilation preserved earlier output handles, and every rejection was followed by
successful compilation in the same process. PHP helper/model suites passed too.

This uses PR #244 revision d8ddde93b04d0e23d295e30f662c3a81b0d50fd1 plus the
focused toolchain fixes committed in 0c28b96e. The verified target pin is unchanged.
STAN reports zero build-blocking diagnostics, but 159 advisory errors and 33
warnings remain; this is not a clean static-analysis report. Most are unresolved
dependencies (120), including native collection types. Other categories still need
individual review rather than blanket dismissal as false positives.

The executable uses a serial test driver reading `build/native-01/request.txt`;
it is not a packaged command-line release. See `tools/native_validate.py` and the
saved `specs/planning/compiler_migration/results/my-try-native-success-01` evidence.

Token representation follow-up: offset and length now use required `uint32` field
annotations (PHP `int`, native unsigned 32-bit). The converter accepts that explicit
field shape. Protected `$_text` is initialized with both spans in the token
constructor and exposed by `text()`. No string-view representation is implemented
in this change. Callers must keep span values within 0..4294967295.

Validation: `build/native-token-uint32-01` passed all 142 PHP/native comparisons and
executed 48 emitted programs. The final private-field rename to `$_text` was then
checked by incremental native rebuild and sample parity. Required-field conversion
regressions and the portability regression suite also passed.

AST contract follow-up: the payload interface is `node_specialization`.
`Syntax_Nodes` now rejects inconsistent binding optional fields/classification and
parameter reference-token presence before parser publication. Checks are local;
children are not traversed again. PHP tests enumerate 48 binding states and four
parameter states.

Three direct scope links opt into native `weak<scope>` fields: scope.parent,
block_specialization.scope and collected_name.scope. Consumers explicitly acquire
through `weakref_get`; PHP's facade preserves ordinary strong references. This does
not convert every documentary weak tag or reclaim every model cycle. STAN now knows
the existing runtime weakref_get primitive. Candidate analysis reports zero blocking
diagnostics, 154 advisory errors and 45 warnings. PHP and conversion/emission tests
passed; no native build/run was performed for this change. See
`specs/portability/weak_fields.md` for the expiration/owner-lifetime contract.
