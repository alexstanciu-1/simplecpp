# v0.2 static members
Doc Status: normative

Classes and structs share the following bounded member contract. Static is a
modifier on an ordinary property or method, not a separate declaration family.
Properties, methods and constants occupy separate name categories; the same name
may occur once in each. Omitted visibility is public. Private members are accessible
only in the declaring type's context, including its static methods and defaults.
Duplicate/conflicting modifiers and unsupported combinations reject in preparation.

## Qualification and calls

`Type::method(args)` and `self::method(args)` select static methods. `self` denotes
the declaring type and requires that context. Static methods have no `$this`.
They reuse ordinary callable arguments, defaults, conversions and return rules;
the current method slice admits the [shared method parameter contract](instance_methods_v0_2.md#shared-method-parameters--2026-10-07)
(values, fixed writable/read-only references and typed trailing packs), value/void returns
and [same-origin reference returns](instance_methods_v0_2.md#same-origin-method-reference-returns--2026-10-07).
Explicit arguments evaluate once in source order. Instance methods require `->`;
static methods require `::` in this slice.

### Expression-qualified static calls — 2026-10-07

`$object::method(args)` and `produce()::method(args)` select a fixed static method
from the qualifier's declared concrete class/struct type, not its runtime class.
Supported record storage modifiers share this nominal selection, including inline,
shared, unique and weak storage. Read-only storage may qualify a static call.

Evaluate the qualifier exactly once, discard its result, then evaluate arguments
once in source order and invoke the selected static method. Evaluation failure
prevents arguments and invocation. The discarded evaluation is a complete statement:
its temporary result is released before arguments. Qualification itself introduces
no copy, ownership transfer, instance retention, dereference or weak lock. Ordinary
evaluation inside the qualifier still has its normal effects and checks.
Absent typed handles are therefore permitted; there is no instance presence check.

This deliberately replaces v0.1's unevaluated `class_t<decltype(expr)>` lowering.
Use existing callable parameter/default/variadic/reference-return and visibility
rules. No `$this` is supplied to the static method. Non-static targets, unknown
members, inaccessible methods, dynamic member names and qualifiers without a known
concrete record target reject before C++ generation. Interface-based implementation
selection and inherited/late-bound dispatch await CH11; a static interface signature
alone does not identify an implementation.

### Expression-qualified properties and constants — 2026-10-07

The same declared-type qualification supports `$object::$property` reads/plain
statement writes and `$object::CONSTANT` reads, including expression qualifiers
such as `produce()::$property`. Reuse the normal property/constant name categories,
visibility, storage and assignment-conversion rules. Qualification does not create
per-instance static storage.

Evaluate and discard the qualifier once before reading the selected member, including
when a constant's value is substituted into generated code. For a plain write,
evaluate/discard the qualifier first, then evaluate and convert the RHS, then assign.
If either evaluation or conversion fails, that assignment does not occur; earlier
effects are not rolled back. A read-only qualifier can write a mutable static property:
it identifies the type, not the instance storage being modified. Absent typed handles
are valid as with static calls; evaluating the qualifier can still fail normally.

Expression-qualified constants are runtime expressions, not constant expressions.
Reject them in constant declarations, constant-only defaults and switch/match labels;
never discard qualifier effects during folding. Constants cannot be assigned.
Dynamic names, compound updates, increment/decrement, reference escapes and nested
value-producing property assignments are not enabled by this slice. Interface and
inherited qualification remain subject to CH11's pending contracts.

`Type::NAME` and `self::NAME` select member constants. Constants reuse source
constant evaluation: bool/int/float/string literals, constant references and
supported numeric signs. Cycles and invalid initializers reject even if unused.
General arithmetic folding remains outside the existing bounded evaluator.
Member constants are not published as namespace constants. Their values and
visibility participate in incremental dependency invalidation.
Bare constant names inside methods/defaults retain namespace/import lookup;
member constants require type or `self` qualification.

## Type-owned properties

`Type::$property` and `self::$property` read type-owned storage; plain statement
assignment uses the ordinary typed assignment conversion. Static fields are not
part of instance layout, instance copy capability, constructor inputs or synthesized
`data` inputs. `set:` cannot target them.

Omitted initializers use the existing type-default rules. Explicit initializers
use the shared supported constant-expression/default conversion path, including
permitted null and enum defaults. Calls, object creation and data-construction
literals are not enabled as static initializers. Initialization occurs once per
type-owned storage location, not once per instance.

C++ lowering emits a static declaration in the type header and exactly one storage
definition in the declaring source's implementation unit. Static method definitions
are also out of line; `static` appears only on their header declarations. Constants
may be substituted from their prepared immutable values; no runtime storage is required.

## Explicit boundaries

Native-reference escape from static properties, compound updates and increment/
decrement remain gated. Inherited lookup/access, `parent::`, late-bound
`static::`, protected/override/abstract/final semantics and inherited construction
belong in CH11 after relationship and dispatch prerequisites. These gates do not
claim permanent language prohibitions.
