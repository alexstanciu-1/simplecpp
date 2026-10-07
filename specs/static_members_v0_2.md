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
(values, fixed writable/read-only references and typed trailing packs) and value/void returns.
Explicit arguments evaluate once in source order. Instance methods require `->`;
static methods require `::` in this slice.

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
decrement remain gated. Instance-qualified `$object::method()` is a separate CH10
follow-up, not inheritance work. Inherited lookup/access, `parent::`, late-bound
`static::`, protected/override/abstract/final semantics and inherited construction
belong in CH11 after relationship and dispatch prerequisites. These gates do not
claim permanent language prohibitions.
