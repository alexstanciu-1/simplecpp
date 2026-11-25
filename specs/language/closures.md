# Closures and callable boundaries

Doc Status: normative

A closure's prepared signature owns parameter types, passing modes and result type. Value captures snapshot their inputs once and follow ordinary copy and handle identity rules. A reference capture retains the original storage and requires a proven lifetime at its use site. Borrowed receivers cannot outlive their object.

Adapting a concrete closure to a callable requires an admitted signature. Reference parameters retain type and access requirements. A reference result needs a stable origin. An escaping callable cannot retain a borrow admitted only for the current call. See [native reference safety](../native_reference_safety.md) and [callable adaptation](../../compiler/docs/inline_adapters.md).

## Callable presence and reset

Testing presence observes whether a callable has a target without invoking it. Reset releases the stored target and its owned captures. A concrete closure retains its own type and capture lifetime.
