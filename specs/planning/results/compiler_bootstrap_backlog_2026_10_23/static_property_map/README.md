# Local runtime property map — 2026-10-25
Doc Status: planning

CPP_Runtime_Catalog::install constructs a local object-keyed hash and publishes it
through the existing Model field in finally. Initial reset remains in place. Property
identity lookup and callable binding neither read the property map nor invoke user
callbacks, as verified in Runtime_Members::property_identity and
CPP_Callable_Bindings::bind_function. The loop order is unchanged; callers observe
the same complete map on success and partial map on exception. PHP retains the same
SplObjectStorage identity; native value-container mutation occurs locally and is
assigned back once. No container reference syntax or compiler change is introduced.

The extracted production install method passes PHP/v0.1/both v0.2 native proofs:
property/function order, retained value identity, initial reset, and partial failure
publication. The first conversion required an explicit annotation at the
SplObjectStorage constructor; corrected in source with the existing spelling.
Host runtime_types, runtime_protocols and incremental_preparation pass.

Standard measured bootstrap: 1:30.31 / 633432 KiB RSS / 600.5 MiB PHP heap.
Categories 3 -> 2: required-null JSON binding and nonpolymorphic narrowing remain.
Native compiler/second-generation acceptance remains outstanding. Further source
snapshot analysis is independent and not included in this measured snapshot.
