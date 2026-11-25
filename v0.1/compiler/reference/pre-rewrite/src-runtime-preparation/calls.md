# Runtime preparation call map
Doc Status: supporting

```text
Command::run()                                      main.php
  -> Runtime_Preparation::run()                     prepare.php
    -> Runtime_Preparation::stage()                prepare.php
      -> Definitions; Bridge; Clang_Toolchain
      -> Package_Reservation::acquire(); take()     reservation.php
      -> Publication::recover_publication()        publication.php
      -> inputs(); reusable(); build() [if needed]
      -> [action] seal private artifacts; return Package_Candidate with exclusive lease
    -> Package_Candidate::publish()                candidate.php
      -> validate() -> Prepared_Request::validate() request_adapter.php
      -> Runtime_Preparation::inputs() [revalidate]
      -> Publication::publish(); cleanup_publication()
    -> Package_Candidate::release() [finally]

Definitions::__construct()
  -> [action] validate types, operations and lifecycle references
  -> Resource_Contracts::type(); operation()         resources.php
  -> Storage_Contracts::validate()                   storage.php

Bridge::__construct()
  -> operation() -> construct() / source_construct() / copy_assign() / free_function() [declared adaptation]
  -> Symbols::name(); append()                       symbols.php [complete reversible identities]

Runtime_Preparation::inputs()
  -> Clang_Toolchain::dependencies(); Files::hashes() [rediscover and fingerprint inputs]
Runtime_Preparation::build()
  -> Clang_Toolchain::llvm(); declarations()
  -> Metadata::type(); operation()                   metadata.php
  -> Clang_Toolchain::bitcode(); lto()
  -> Clang_Toolchain::inspect_bitcode(); Metadata::operation() [each variant]
  -> [ordinary package] Clang_Toolchain::verify_link()
  -> [project module] project/Module::imports() [each variant]; emit project.json
  -> Native_Types::context(); export()                native_types.php [owned native descriptions]
```

Bridge adaptations derive semantic/ABI roles from declared contracts: byte constructors
publish borrowed spans and caller-storage results; free functions adapt values and
const/mutable borrows to direct or owned results. Conversion purpose is independent of
the C++ signature. Configured provider headers supply string/console implementations;
object adaptations verify explicit `cleanup:none` against C++ trivial destruction.

Input fingerprints include current provider headers. After private generation, stage()
rechecks those inputs and seals the artifact manifest. Candidate publication revalidates
the request and inputs before replacing stable `package/` plus its pointer. Publication::publish() temporarily preserves the previous package and restores it on
failure; cleanup_publication() removes backups and legacy hashed package directories.

`Files` owns local filesystem/JSON helpers. `Clang_Toolchain` uses the independent
bounded [tool_process\Tool_Process](../tool_process/calls.md) service; its bootstrap
loads no compiler stages. No compiler session or phases execute.
The whole provider package is the current work/replacement unit. Generation is
serial, inputs are rechecked before publication, and private outputs are accepted
together. Individual operation/module scheduling is not implemented.

Plain native records: Definitions calls Record_Exposure::validate() (records.php).
Bridge adds Record_Exposure::source() checks/offset constants; Metadata::type() calls
Record_Exposure::measure() after obtaining the AST. Only the complete supported public
field/zero-construction/value-copy/no-cleanup contract can be published.

Isolated specialization path (no compiler stages):

```text
Specialization_Driver::run()                         tests/specializations/prepare.php
  Specialization_Request::export()                   requests.php
    validate fixed scalar/source/family demands and exact scoped identities
    generate source declarations, native aliases and ordinary operation definitions
  Runtime_Preparation::run()                         prepare.php (same package path above)
    Bridge::free_function()                          mutable/const borrows; owned record results
    Metadata::record_declaration()                   record or opaque native alias
  Prepared_Request::__construct()                    request_adapter.php
    hold read lock; verify artifact integrity, request context, types and operations
  [fixture] generate LLVM consumer from accepted facts; compare with native C++
```

The fixture serializes workspace writes; the package read lock covers artifact
consumption/linking. The isolated driver retains its JSON input boundary and uses typed family parsing/substitution.
Production runtime-only family selection, joins and caching use the [family process](families/calls.md).
Compiler-driven preparation uses Compiler_Bridge through coordinator-selected family
work. Source-family registration uses typed exposure/mapping exports through
Family_Adapter; registration itself does not prepare ABI.

Bridge::source_construct() emits copy or construction from `T&&` according to the
explicit lifecycle role. Clang selects the native constructor, possibly copying;
Metadata verifies pointer ABI and traits. Family export supplies these same ordinary
rows, so compilation and adapter acceptance remain shared.

Bridge::copy_assign() emits a native assignment on two live same-type objects.
Metadata::operation() verifies its `void(ptr, ptr)` ABI and measured copy-assignable
trait. Its semantic destination/source borrows and explicit self-assignment policy
are validated by the compiler adapter before ordinary lifecycle preparation.

Project modules reuse the same stage/candidate/reservation/publication lifecycle with
an explicit `project\module_contract`. They record authorized unresolved compiler
imports and never claim `native_link_no_undefined`. `Prepared_Request::validate()`
distinguishes ordinary closure from project import validation. See the
[project boundary map](project/calls.md); compiler routing remains the following gate.
