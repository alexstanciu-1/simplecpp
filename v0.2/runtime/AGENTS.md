# Runtime implementation rules

Follow the [public header boundary](../specs/runtime_public_headers.md).
Keep public include closures minimal and implementations in compiled runtime sources.
For generated declarations, change the generator or its templates, then regenerate;
never use edits to runtime/generated as the final fix. Check isolated header use,
behavior and relevant native compiler integration.
