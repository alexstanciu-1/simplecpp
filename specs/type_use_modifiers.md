# Object presence and ownership

Doc Status: normative

## Required and optional objects

A required object or interface and its nullable form have distinct source identities and may share native storage. Known null fails preparation at a required value boundary. Conversion from optional to required checks presence before replacing a destination or entering a callee and preserves object identity. An admitted derived-to-base conversion composes with the check. References are invariant. An override may broaden a by-value parameter or strengthen its result without introducing a failing presence check.

Required class fields must be initialized on every normal constructor exit. A throwing path need not complete initialization. Assignment establishes initialization after successful conversion. A read on the current receiver requires the field to be initialized. An ordinary method call on that receiver requires all tracked fields to be initialized. Base construction initializes the base fields. Direct writes and structured branches can establish initialization. Loops alone cannot establish it. Mutable fields permit reassignment and readonly fields permit a single write.

### Temporary field storage exceptions

A nominal shared-object instance field of a struct may start empty and later be reset to null, even when spelled without a question mark. Reading it through a required boundary checks presence. A required static field may have empty initial storage or a null initializer. Subsequent assignment to it follows the required-value rule.

## Required locals

A required class or interface local can be declared before assignment. Reading it, binding a reference or capturing it requires successful assignment on every reachable incoming path. Returns and throws remove paths from a normal join. A lazy operand or selection arm cannot establish initialization in its sibling. Loop analysis accounts for zero iterations, breaks and continues. Catch and finally entry conservatively use the facts from before the protected body.

## Optional values

An optional inline record stores a genuine optional payload. Absence constructs no payload. Storage requires the payload's storage capability, including for nested applications. Copying requires payload copyability even when the source happens to be absent. Reset destroys a present payload. Presence tests inspect the state without copying. Checked value extraction copies a present payload and fails on absence. A condition additionally requires the appropriate condition capability.

## Owning and observing handles

Explicit shared, unique and weak handles already admit emptiness. An additional nullable layer, immediate ownership-wrapper nesting, and redundant storage overrides are rejected. Shared and unique owning targets must have a value type. A copyable handle parameter can default to null. A required ordinary object parameter and a unique parameter cannot use that default.

Construction uses the declared storage. An admitted local shortcut can infer its payload from a direct constructor initializer. Parameters, returns and fields require complete storage types. Inline construction does not copy an existing shared object into value storage.

An admitted explicit move transfers a named unique handle immediately, leaving its source empty and valid. Aliases observe the empty state. Self-transfer preserves ownership. Consuming calls and returns require explicit transfer from named storage. An owning temporary can pass directly to an admitted consuming boundary. Deriving a weak handle preserves observation without extending the object's lifetime. Explicit locking returns strong ownership on success and an empty handle after expiry.

The [native ownership slice](ownership_slice.md) records its concrete runtime types. Frontend admission is specific to the operation and source flavor.
