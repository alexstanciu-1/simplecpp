# Carrier state and extraction

Doc Status: normative

Nullable absence and result failure are distinct states. A successful result containing an absent nullable payload remains a successful result. Presence, successful extraction and the truth of a contained value are separate questions. An admitted explicit scalar cast checks carrier state before converting the payload. A failed conversion cannot replace the destination.

The [carrier contract](carrier_slice.md) and [source carrier operations](../compiler/docs/carrier_values.md) describe the published surface. General source-level `take` extraction remains deferred.
