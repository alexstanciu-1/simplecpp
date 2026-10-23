# INIT-02 explicit backing layout proof
Doc Status: planning

Revision d3165fdf6; new test only. No production converter/compiler changes.
Command: python3 tests/portability/staged_storage.py --results /tmp/staged-storage-layout-01
Shared runner: PHP passes; legacy native 21.14 s, ordinary v0.2 23.13 s,
concrete prelowering 21.50 s, all pass. Exception framework installed on both routes.

The fixture uses private nullable backing with explicit checked getters/whole setters.
It covers class and scalar-value-record payloads, early getter/argument reads, delayed
assignment, legitimate nullable absence, class identity, replacement and preserved prior
storage after a throwing RHS. Struct copies use an existing explicit owner operation:
mutating the input after a write or mutating an extracted value does not change stored
contents; whole replacement does. COPY-01 marker/helper decisions remain separate.

No automatic _uninitialized_ handling, collision/visibility rewrite validation,
reference/probe rejection, invalid-null-write proof, struct-owner layout, or retained
conversion coverage is claimed. These remain INIT-02 acceptance work. No new bootstrap:
production is unchanged; latest measured checkpoint remains nullable_selection.
