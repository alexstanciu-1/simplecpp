# Compiler-owned debugging direction
Doc Status: planning

The agreed default is native compilation without Clang debug information. Debug
information may be requested explicitly by a user or a future deciding policy.
This choice is independent of optimization, source-language diagnostics, runtime
checks and specialization caching.

## Implemented boundary

my-try's native builder already defaulted to no native debug information. It now
accepts `--native-debug`, backed by `native_cpp_config::$native_debug`, to request
`-g` consistently for PCH, generated units and provider objects. Existing ordered
argument cache identities separate both configurations. The accounting conversion
benchmark now defaults to `-g0`, with the same explicit opt-in; historical results
remain unchanged. The existing public scpp debug profile is a separate workflow
whose runtime trace contract currently relies on full native debug information.

## Compiler ownership

The compiler should own source identities/spans, source-level callable and variable
identities, and the mapping from lowered representations back to those identities.
The parser already retains source-backed token spans; that is an input to debugging,
not a complete debug map. Generated artifact publication should eventually publish
versioned mappings alongside exactly matching source/artifact digests. The backend
owns mappings for synthesized temporaries, specialization bodies and concrete types;
shared semantic analysis remains the authority for source types and meanings.

A debug request should consume those facts and choose native debug emission or
explicit runtime instrumentation. Native build configuration owns the flags and
cached binary variant. A debugger service would consume the published mappings;
it should not reconstruct source-language meaning by parsing generated C++.

## Next bounded proof, not implemented here

Use one source function with a local and a failing checked operation. Establish a
source-attributed failure and an explicit debug rebuild against the same source
snapshot, including incremental edit/rebuild mapping invalidation. Define the
mapping publication owner and schema before introducing a retained debug registry.
Only then extend to stepping and variable inspection, including optimized-away
values, concrete specializations and synthetic temporaries.

No full debugger, automatic failure retry, source-level stepping, variable inspection,
source-mapped native stack or production instrumentation is claimed. A later rebuild
cannot recover the previous process's lost variable values; reproducing a failure
and inspecting a captured failure are separate capabilities. Re-executing a failed
program may repeat side effects and must not be an implicit consequence of this flag.

## Evidence

[Clang profiling](results/accounting_clang_profile_2026_10_18/README.md) measured
11.4% O0 and 15.8% O2 compilation savings from disabling full debug information on
the concrete accounting build. These measurements include all support compilation,
exclude linking, and do not measure a source-level debugger implementation.
