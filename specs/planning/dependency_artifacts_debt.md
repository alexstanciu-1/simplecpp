# Dependency artifact and interface debt

Doc Status: planning
Date: 2026-11-02

Agreed current boundary: compile only the selected application's sources. All
other modules, including Simple C++ dependencies and the runtime, are manually
built dependencies. Application compilation reads their manifests for selection,
dependencies and declared artifact paths. It does not discover/parse their sources,
scan their directories, rewrite their inventories, or check their code/artifact
versions, timestamps, existence or validity. The native linker remains responsible
for reporting unusable link inputs when the caller runs it.

Deferred work, to discuss before implementation:

- A declared code revision and a matching built-from revision for each dependency.
- Artifact existence, freshness, target/ABI and configuration compatibility checks.
- Compiler-readable exported declarations for Simple C++ dependencies, and their
  export/import contract. Loading an artifact does not currently expose callable
  declarations to preparation. Dependency calls remain unresolved; do not silently
  parse dependency sources to recover their declarations.
- Public include paths and build requirements supplied by modules, artifact choices
  for different targets/build profiles, and automated dependency builds.
- Native build/relink invalidation after a manually rebuilt dependency. The current
  project command does not notice artifact changes: callers rebuild/relink manually,
  and may use --force to regenerate the application's bundle.

The current `artifacts` manifest list is deliberately only a list of manually built
link inputs. Paths resolve relative to the declaring manifest without existence
checks. The bundled runtime manifests point at the current build/clang archives;
other build configurations require updating these paths manually. This is not a
package manager, version resolver, native compiler driver, or semantic API catalog.

The application retains mtime/size change checks and full compilation on detected
changes. Module dependency/activation graph changes still affect the selected
configuration. JSON syntax, names, paths to manifests and dependency cycles remain
checked so graph loading can function; these are not artifact/version validation.
