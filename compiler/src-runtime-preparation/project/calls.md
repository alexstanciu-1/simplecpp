# Source-dependent project preparation
Doc Status: supporting

```text
Compiler_Bridge with accepted Source_Export_Coordinator results
  -> Source_Adapter::argument()                       source_adapter.php
       -> Source_Export_Preparation::validate()        compiler backend owner
       -> Adapter::validate(); declaration()          adapter.php
  -> families/Preparation::select()
       -> Requests::arguments()                       profile authorization per formal slot
       -> Module::from_arguments()                    module.php [merge source obligations]
       -> Requests::export()                          explicit copy-in / copy-out permissions
  -> families/Preparation::execute()
       -> Runtime_Preparation::stage()                shared generation/publication machinery
            -> Bridge::free_function() -> Adapter::copy_in(); copy_out()
            -> Module::imports()                      every LLVM/bitcode variant
  -> families/Join::join()
       -> Package_Candidate::validate()
            -> Prepared_Request::validate()
                 -> Module::validate()                exact context and all variant import sets
  -> Package_Candidate::publish()                      shared stable slot and reader protection
```

`structures.php` owns version-one source payload/import and project-module contracts.
The compiler adapter copies portable identity, accepted layout and field/lifecycle
provenance; it retains no compiler type IDs, source operation implementation names,
ASTs, stores or backend configuration objects. Checksums authenticate artifact content;
exact keys and allocated output slots continue to own identity.

`Adapter` generates a distinct native class containing aligned bytes and forwarding
special members. Its members call complete compiler operations. Deleted moves cannot
silently become copy fallback. Clang checks size, alignment, two-element array stride
and payload offset zero. Copy-in creates an adapter from a source address; copy-out
copies its payload through the compiler operation. Source addresses are never cast to
native adapter references. Compiler field implementations are not regenerated as C++.

`Module` checks the exact authorized source symbol namespace, declaration ABI and
target for every artifact; locally defined source functions and unexpected source
imports reject. The authenticated `project.json` records the selected contract and
each variant's actual required source imports. Native standard-library references
remain native link obligations. Project preparation does not claim final link closure.
Ordinary packages still require the existing no-undefined native link. Their reader
rejects project modules, and ordinary native type descriptions cannot carry source
obligations. Native family results depending on source types are not exported as
ordinary native descriptions.

After publication, `Compiler_Bridge::project_binding()` validates the portable source
provenance against current exports and passes an explicit compiler `project_binding`
to `Package_Adapter::open()`. Nested native recipes use
`Native_Types::from_project_package()` with the same source obligations. The compiler
keeps canonical source types and emits the complete source operations at final link.
See [compiler import](../../src/01_prepare_inputs/load_runtime/calls.md) and
[backend preparation](../../src/05_generate_code/prepare_backend/calls.md).
