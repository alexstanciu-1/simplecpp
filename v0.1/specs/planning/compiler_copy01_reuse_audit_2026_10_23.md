# COPY-01 native-route reuse audit
Doc Status: planning

Source revision 8c105a473. No converter/compiler implementation in this audit.

The existing valueRecord parser in tools/php_portability/src/converter.php owns
@scpp-struct conversion. It accepts only initialized public scalar fields and emits
an inline struct. It can own marker consistency and the one reserved copy method
without enabling arbitrary methods. Existing localBody and method parsing can
validate the admitted PHP method syntax; semantic equivalence/purity remains an
author obligation. Conversion fingerprints already include converter.php, so changes
in this owner invalidate cached outputs. PHP bootstrap can supply the global marker;
it must not become a native base/interface.

## Fresh native evidence

A direct PHS Point with uint32 entry_id and a typed `_struct_copy_` method returning
$this produces independent values (7:9 after mutating the copy) in ordinary and
concrete v0.2: /tmp/struct-copy-reuse-01. The legacy run initially had the wrong
project entrypoint key; that setup failure provides no language evidence.
The corrected /tmp/struct-copy-reuse-02 uses entrypoint=source/main.phs and fails
before native compilation with:

> Struct Point cannot declare methods in the first struct slice at line 1.

Thus emitting the copy method for both routes is not an available implementation.
This does not justify expanding v0.1 struct semantics for the converter. Existing
value_copy is mixed-specific, not an ordinary generic struct-copy operation; it must
not be repurposed. v0.1 function overloading is explicitly forbidden by StrLComp, so
same-name per-record overload generation is not a portable alternative either.

## Proposed bounded bridge, pending user response

Preserve the PHP `_struct_` / `_struct_copy_` spelling, but remove the marker/method
from native record declarations and structurally lower the reserved call to a
converter-owned generic by-value helper on both routes. Its ordinary signature is
T copy(T value), with existing copyability admission and native by-value return.
The helper must retain a real value boundary; do not replace arbitrary calls with
their receivers. Register it through existing generic binding/transport machinery,
not a new Simple C++ keyword, core ownership protocol or implicit class conversion.

The current converter emits flat postfix nodes: a property access has only ->name,
while receiver/call/index tokens are separate. Implementing receiver extraction must
be a bounded structural operation that evaluates it exactly once and preserves
surrounding order; it cannot infer receiver types. Reserve the protocol declaration
spelling, diagnose marker/method inconsistencies and reject unsupported receiver or
reference/escape forms. Do not claim arbitrary PHP method equivalence or unrestricted
expression coverage. Prove the generic source-struct payload on both native routes
before migrating cpp_specialization_ref.

A user question is pending on this replacement of the planned method lowering.
The implementation review already anticipated helper/value lowering for v0.1; this
probe now establishes why that path is necessary. No Simple C++ change is requested.

## Independent initialization triage

source_work is not inherently staged storage: its only construction is in
Compiler_Work_Queue, immediately followed by source/record/position assignments
before queue publication. Those fields should use ordinary promoted required
constructor inputs, not `_uninitialized_`. Nullable tokens/previous/result retain
their lifecycle meaning. Tokenizer similarly assigns its required source directly
from its sole constructor input and can use promotion. token_list is a separate
record whose creation/access sites need inventory; do not blanket-mark all required
fields merely because preparation currently reports initialization errors.
