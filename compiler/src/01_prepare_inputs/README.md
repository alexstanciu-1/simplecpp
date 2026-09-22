# Input preparation order
Doc Status: supporting

1. read_manifest/main_read_manifest.php - read JSON or prepare a virtual one-file project.
2. load_runtime/main_load_runtime.php - read/reuse the language type catalog;
   load_runtime/runtime_import.php then selects package reads, reserves/validates
   configured providers and joins one fixed runtime input set.
   Family_Adapter validates configured normalized family exposures against that catalog.
3. read_sources/main_discover_sources.php - discover folders or observe explicitly selected files.
4. read_sources/main_read_sources.php - later read selected byte snapshots.

Compiler_Session calls the first three preparations in this order. Selected
snapshot reading runs inside Phases::run_tokenization(), immediately before
02_tokenize; it is still owned by read_sources. Input selection and type-cache
validity can request full work. Valid published inputs/target may skip later
semantic work and use the common finish path when no native family service needs
freshness checks. With that service, early package leases release before concrete
preparation and final ordinary/demanded packages are revalidated and reserved after
type resolution; see the load-runtime and compiler call maps.

Call maps: [manifest](read_manifest/calls.md), [catalog](load_runtime/calls.md),
[sources](read_sources/calls.md). Overall: [compiler](../compile/calls.md).
