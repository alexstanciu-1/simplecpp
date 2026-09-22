# Callable contract equality and retention
Doc Status: planning

925 PHP/native comparisons: all pairs among 14 declaration references and 27
callable variations. References cover named/provider/parameter identities, nested
families, ordered arguments and counts. Callable variations cover identity/exposure,
linkage/convention, nested parameter/result types, widths/extensions including span
length, mutable borrow, arity, result transport, optional allocation effects and
positions, nullable binding/conversion tags and literal flags. Each callable pair
also proves reuse identity, untouched input membership and added coverage/order.

The oracle constructs original retained prototype classes and uses their PHP ==
semantics. A discarded oracle based on migrated classes failed at nullable
integer tags (zero versus null); that is not equivalent to the prototype's enum
representation. Failed logs are saved. Production comparison needed no change.
One reserved test-local checker fix preceded first PHP success.

Clean pinned native target 9b4b33f35f053b487e018c94d6a4a7888d77c64a, strict
clang++-18: first native build passes, no correction cycles. Type-definition and
package-context equality and adapter integration remain unfinished.

Run python3 compiler/tests/callable_contracts/run.py --results FRESH
--target-checkout /tmp/scpp-json-240-probe. Cumulative fast validation uses
python3 tools/php_portability/validate.py --results FRESH.

Cumulative fast validation passes with 124 registered production files. Focused
native evidence is recorded separately in proof/summary.json.
