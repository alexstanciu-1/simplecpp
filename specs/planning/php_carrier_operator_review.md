# PHP carrier operator review

Doc Status: planning

The PHP operator catalog projects one layer from each participating operand.
The scalar contract determines which projected types can be used together.
The operation determines when to read the value and what an alternate state means.

| Group | PHP behavior and distinguishing case |
| --- | --- |
| Arithmetic, power, unary signs | Checked payloads, scalar promotion and integer failure rules. A true sentinel cannot serve as the integer 1. |
| Bitwise and shifts | Checked integer payloads. The scalar rules govern widths and shift counts. |
| Ordering and spaceship | Require payloads. A missing integer compared with 2.0 throws. String ordering preserves embedded NUL bytes. |
| Equality and identity | Capture the left operand before a mutating RHS. Compare states first, then invoke an admitted payload comparator. Two nullables containing null compare identical even with different scalar payload types. Successful int and byte values can compare equal under ordinary equality and distinct under identity. |
| Literal guards | A strict comparison of a falseable position with literal false recognizes a miss. An offset of zero passes the success guard. A bool variable participates as a plain payload. |
| Boolean and conditional | Truth observes the carrier state. Logical AND and OR short-circuit, xor evaluates both operands, and a full ternary evaluates one branch. |
| Presence and coalescing | Presence and truth have distinct purposes. A present zero survives nullable coalescing. Coalescing assignment evaluates the fallback only on its admitted missing state. |
| Concatenation | Both projected operands must be strings. Both must be present. Numeric operands require an explicit conversion to text. |
| Compound writes and updates | Capture the writable slot and old payload before RHS effects. Compute through scalar rules and wrap on success. A missing string destination suppresses the RHS of concatenation assignment. |
| Other syntax | Indexed storage, member access, reference binding, boxed dispatch, matching, and helper admission retain their own receiver and storage contracts. The projection rule gives no general permission to mutate an indexed carrier or apply empty() to a carrier. |

The review found two places where an unconditional payload read gives the wrong
answer. Strict heterogeneous equality needs the original states to compare two
absences. Compound writes need the old payload before a call can rebind the
destination. Native fixtures exercise both cases and compare structured error
fields across result specializations without allocating. The generated checked
update takes its slot as a lambda argument so a temporary receiver lives through
write-back. The fixtures exercise soundness and evaluation order. They do not
measure executable performance.

## Explicit casts and numeric coverage

Scalar numeric and text casts already emitted one checked payload read.
The audit found two missing compositions at the PHP explicit cast boundary:
boxing a carried scalar into mixed/dynamic and casting a carried mixed/dynamic
through its published boxed conversion. Both now use the shared operand family
metadata and the existing payload conversion. Cross-family mixed/dynamic
conversion and typed string-to-bool conversion remain outside the scalar rules.
A failed result raises before boxed tag dispatch. The native fixture also checks
a successful boxed false and one evaluation of a function returning a result.

The carrier operator fixtures exercise unary signs, all six binary arithmetic
operators, bitwise complement/AND/OR/XOR, both shifts, and prefix/postfix updates.
The checked integer compound fixture observes the result after each of
`+= -= *= /= %= **= <<= >>= |= ^= &=`. This covers operator spellings;
specializations and widths still depend on the active publication.

A lowering probe paired 15 published carrier types with seven targets (bool,
byte, int, float, string, mixed and dynamic). Across all 105 pairs, admission
matched the corresponding plain payload cast: 87 accepted and 18 rejected.
Each accepted carrier expression emitted a checked value read. The comparison
covered the local publication including JSON result types. The native regression
selects boxed-result cases when the build publishes those specializations.
