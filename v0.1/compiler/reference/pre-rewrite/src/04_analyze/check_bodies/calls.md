# Check bodies call map
Doc Status: supporting

Caller: Phases::run_bodies(). body.php owns Body_Worker with handlers/ for statements, control
and expressions. handlers/writes.php owns local write selection. flow.php builds checked flow; utilities/ owns operation/conversion and graph
queries. join.php accepts callable bodies; data/ owns typed results.

The following are ordered lifecycle calls, not calls between siblings.

```text
Body_Checker                                  main_check_bodies.php
  init() -> [action] select fixed callable tasks
  run() -> Body_Worker::check() [each task]
  finalize() -> Body_Join::join()
  result() => Body_Set
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.

Body selection uses Type_Resolution::body_signatures(). Calls may refer to source
or imported signatures; argument checking and integer conversion share one path.

Local_Write_Checking::check_local_write() (handlers/writes.php) converts the source
then delegates to initialization_write() or assignment_write(). Their independent
capabilities select local_write_kind: zero_initialize, value_copy, direct_construct,
copy_construct or copy_assign. Called assignment borrows an existing source; value
assignment retains a direct read/store even when initialization needs a custom copier.

Place_Checking::finish_place_value() and check_place_value() retain a private
pending_place_value, without selecting copying or borrowing. Each consumer calls
select_place_access(): arguments follow their signature; initialization/assignment
follow their selected operation; binary operands, conversions, indices and conditions
read values; discarded locations borrow without a load. A completed access cannot
be reclassified. Body_Worker::check() requires no pending locations before handoff.
The retained typed_value vocabulary and downstream traversal stay unchanged.

Byte literals: check_byte_literal() uses Byte_Literals::decode(), selects the
metadata-bound constructor through Type_Resolution::language_callable(), and
bound_call() produces an ordinary checked call. check_echo() selects the operand
type's output binding and emits one checked statement per full expression. Both
paths retain normal signature/type dependencies and use existing call evaluation.

Conversions: Body_Worker::convert() creates implicit-boundary requests; finish_call()
creates requests for named provider conversions. Conversion_Resolver::resolve()
selects identity, a primitive or an exact provider callable. Ordinary values/calls
remain the retained execution plan; no conversion-specific lifetime path is added.

check_construction() and typed-local default initialization share default_value().
Place_Checking::check_place() in handlers/places.php resolves ordered field/index
locations through canonical contracts. Reads resume a `place_cursor` through
`check_expression()` alongside call/binary cursors; nested indices do not recurse
into another expression checker. Index values participate in Expression_Order;
assignment targets precede right-hand-side evaluation. begin_call() supplies a
member receiver as the first ordinary borrowed argument.

check_argument() and bound_call() share argument_value(): conversions remain at the
ordinary argument boundary. A const record argument selects a local_borrow of its
existing root or projected place in completed checked output; value consumers
select local_read. Record temporaries and by-value object parameters remain unsupported.
Opaque const local/temporary borrowing continues through the existing path.
Mutable imported calls require existing local storage with permission through the
complete root/field/index path; statically projected owner fields are supported. Checked_Body::allocation_effect_for()
exposes the normalized resource effect from its exact retained callable dependency;
resource state analysis belongs to analyze_lifetimes.

`Body_Worker` construction requires the current `Template_Set` permission result
for source template bodies. [Definition checking](../check_templates/calls.md)
preserves generic permissions before substitution; this stage still owns concrete
operation validity and typed executable bodies.

Method receivers occupy the concrete signature's `receiver_index`. `begin_call()`
checks the local receiver once and reserves its semantic slot; `check_argument()`
fills the other slots in source argument order, skipping that position. Source methods
use slot zero; provider methods preserve their declared position. Body checking does
not branch on family definitions or reorder native ABI parameters. Imported const
integer-address arguments use the same conversion and access selection: a matching
place becomes `local_borrow`; expressions and converted values retain their checked
scalar results. Lowering materializes those results into call storage. Source scalar
reference declarations and mutable scalar borrowing remain unsupported.

`Statement_Checking::check_return()` calls `return_construction()`, which delegates
copy selection to `copy_return()` (handlers/statements.php). `return_kind` selects scalar value, aggregate store,
direct destination construction, copying or expiring-local construction. Bound formal
return types keep the default generic copy permission. Initialization reads the same
`signature_representation::result` for source and imported calls.

`Body_Worker::retain_type()` owns the body's type-dependency collection. Signature,
local, expression and projected-place reads all use it. It retains implicit element
types of dynamic storage and fixed arrays with a visited worklist. A `clear()` body
can therefore pop managed elements without explicitly reading/naming an element value;
lowering and emission still receive the exact element lifecycle contract. Selected
field projections retain their own types. This does not capture unrelated record fields
or enlarge the body with the complete program type store. Existing dependency-based
reuse checks and joins consume the same retained rows.
