# Generic contracts and publication

Doc Status: normative

Preparation resolves declared parameter kinds and constraints before materialization. Formation, copying, traversal, indexing and equality have separate requirements. A value-storable type need not be copyable. A container operation that copies its element requires that element capability, including through nested applications.

Materialize requested concrete operations and record dependencies for incremental reuse. Runtime metadata publishes the supported applications and their native requirements. An unresolved or unsatisfied requirement fails at the requesting boundary. See [runtime bindings](../compiler/docs/runtime_bindings.md) and [runtime publication](runtime_public_headers.md).
