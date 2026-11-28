# Helpers and library exposure

Doc Status: planning

[Filesystem donor audit and implemented boundary](filesystem_audit.md)

[PHP array and string inventory, including vector, hash, mixed and dynamic admission](php_array_string_inventory.md)

[Current Stage 1 family inventory, deferrals and remaining decisions](intake_inventory.md) · [Family and receiver index](donor_surfaces.tsv) · [Native family methods](donor_native_methods.tsv) · [Operation crosswalk](intake.tsv) · [Donor types, constants and members](donor_entities.tsv)

The [family integration process](integration_process.md) records how the promoted contracts govern new work. The design discussion and single-operation suggestions below describe the earlier pilot. The current inventory records the implemented visibility rules.

## Inventory and scope

The [symbol inventory](symbols.tsv) joins the legacy and strict PHP runtime symbol maps at release/0.1.78 (d2f63e37e) with the fifteen runtime-owned module catalogs consumed by my-try at 624ab8a2d. Look up a spelling to see both historical targets and every matching catalog ID. An empty cell means absent from that input. Some map entries such as echo_eval and condition_truthy implement lowering. A name match is a discovery aid and says nothing about signature or behavior equivalence.

The release maps contain 187 legacy and 257 strict entries. The release also has native module directories for binary, curl, datetime, filesystem, json, mysql, process, regex, source, strings, tasks, tokenizer, ui and webview. PHP wrappers and operators also live under runtime/include/lang/php. For example, mysqli has a PHP wrapper and a MySQL provider, while the my-try catalogs have no mysql module. Use the release maps to identify profile-specific aliases, then inspect the provider and builtin contract before considering a port.

The following counts come from [runtime/metadata/modules](../../../v0.1/runtime/metadata/modules). Callable IDs in the TSV locate parameter modes, defaults, returns, qualified aliases and type membership in functions, and native bindings in cpp. Constants, properties and types have their own catalog records. Counts are per module.

| Module | Distinct callable names | Signature rows | Types | Constants | Properties |
| --- | ---: | ---: | ---: | ---: | ---: |
| compiler | 16 | 30 | 4 | 0 | 0 |
| core | 32 | 55 | 3 | 19 | 0 |
| curl | 9 | 13 | 2 | 19 | 9 |
| datetime | 11 | 11 | 0 | 0 | 0 |
| filesystem | 35 | 35 | 1 | 0 | 0 |
| io | 10 | 12 | 1 | 0 | 0 |
| json | 30 | 31 | 4 | 0 | 3 |
| process | 5 | 5 | 2 | 0 | 6 |
| regex | 13 | 25 | 0 | 2 | 0 |
| source | 20 | 20 | 4 | 0 | 0 |
| strings | 54 | 73 | 2 | 3 | 0 |
| tasks | 28 | 406 | 5 | 0 | 7 |
| tokenizer | 12 | 12 | 1 | 0 | 0 |
| ui | 12 | 12 | 3 | 0 | 0 |
| webview | 12 | 12 | 1 | 0 | 0 |

The [my-try coverage review](../../../v0.1/my-try/docs/planning/runtime_catalog_coverage.md) identifies upstream contract gaps such as boxed joins and callback restrictions. Keep its startup registry, template expansion and external build requirements as donor evidence. In particular, the tasks rows contain many overloads. Importing them individually would commit v0.2 to a representation before reviewing its async contract. Regex also brings a native PCRE2 dependency. The my-try host helpers Storage_Abstract, Storage, Keyed_Storage, Key_Storage_List and Storage_Cursor are compiler infrastructure. The compiler module separately registers source-visible storage operations.

## How v0.2 reaches a call

1. Runtime metadata owns the operation, native symbol, published type identities and declaration provider. The publication request selects supported operations and specializations for a runtime build. The [filesystem preparer](../../../tools/api_generator/php/module_functions.php) validates each selected signature and resolves parameter and result providers, passing modes, header and owning module.
2. Provider nodes own header paths, direct dependencies and exported-type dependencies. Publication finalization checks availability and cycles, then computes closures. Runtime rendering emits public declarations and a private implementation bridge. Compiler generation emits typed callable descriptors from the same plan. Application compilation queries generated PHP bindings. [Public header boundary](../../runtime_public_headers.md)
3. Project manifests select dependency modules, defaults and archive link inputs. A provider can be published in a build and still be unavailable in an app. A naming policy must preserve this check. The compiler emits the application sources and passes the dependency artifacts to the linker. [Module workflow](../../../compiler/docs/modules.md)
4. Preparation resolves a source declaration first and records its dependency. It then queries the runtime registry for the caller's flavor. Unqualified builtin fallback and exact imported or qualified names have distinct keys. Import validation checks even unused imports. The current registry checks a flavor-neutral key before its flavor-specific counterpart, so adding a flavor entry alongside a neutral entry with the same name cannot override it. [Call lookup](../../../compiler/04_analyze/prepare/prepare.php) · [Import validation](../../../compiler/04_analyze/prepare/imports.php) · [Generated lookup template](../../../tools/api_generator/templates/core/type_registry.php.tpl)
5. The selected descriptor supplies the call target, public header, argument modes, result and module requirement. Translation units collect the headers used by their own lowering. Strings already demonstrate metadata for per-flavor free names, receiver names, arities and result adaptation. PHP strpos wraps a missed byte search as false while JS indexOf yields −1. Public flavor wrappers may inline a small conversion or forwarding call. Heavy IO and vendor definitions belong in compiled providers. [String metadata](../../../runtime/metadata/runtime.json) · [Generated registry renderer](../../../tools/api_generator/php/render_compiler.php) · [Namespace and adapter examples](../../../compiler/docs/namespaces.md)

**Current include granularity.** The generator can publish just fs_get, but all filesystem declarations selected in a build currently share scpp/filesystem.hpp. A translation unit calling one operation sees that provider's closure. String flavor headers already split out string_vectors.hpp, while the PHP string.hpp group also pulls in carriers for falseable operations. Adding every library wrapper to one flavor umbrella would defeat selective consumption. Group declarations by the public types their signatures actually need and verify closures with the complete profile as well as a reduced one.

## Default flavor surface and optional standard surface

The requested default is the flavor-inspired library for each source unit. The internal standard spellings become an explicit option. Runtime module selection still controls which libraries an application may consume. As a proposed first policy, make the option additive at application scope, so mixed PHP, JS and C++ sources can keep their flavor APIs and opt into the same standard operations. Manifest spelling, scope and collision precedence for overlapping names need agreement. No existing manifest field implements this choice.

| Filesystem read | PHP default | Internal standard option |
| --- | --- | --- |
| Source call | file_get_contents(path) | fs_get(path) |
| Success | Exact file bytes, including an empty string | Same bytes |
| Operational failure | result_or_false<string> carrying false | result<string> carrying the provider error |
| Module and native work | Require filesystem, call the existing read provider once, adapt its result | Require filesystem, call scpp::fs::get directly |

The [PHP contract](../../../v0.1/specs/builtins/filesystem/file_get_contents.md) covers the single-path form. The [native provider](../../../runtime/src/filesystem/filesystem.cpp) owns byte-preserving IO and rejects embedded NUL in paths. Keep both result contracts explicit in metadata. No JS or C++ filesystem spelling has been chosen by this scan. Publishing those names requires a contract for their call form and failure behavior.

**First implementation slice for review.** Extend the filesystem metadata and preparation path to describe the PHP exposure, canonical binding and required result conversion. Generate the selected public header and compiler descriptor together. Gate the neutral standard lookup on the chosen project option, including exact imports and rooted calls. Preserve source shadowing, module gating and per-unit header ownership. Apply the gate to library exposures with reviewed mappings. Inventory compiler intrinsics independently before changing neutral fallback. Include the setting in incremental project configuration so changing it rechecks existing consumers.

**Acceptance checks.** PHP default accepts file_get_contents, rejects unimported standard fs_get and diagnoses a missing filesystem module. The opt-in admits fs_get with structured errors. Check both success branches with empty and binary files and both failures on a missing file. Exercise imports, shadowing, mixed-flavor files, changing and restoring the option in one session, and fresh versus incremental output. Verify each generated header compiles alone, inspect actual transitive includes, and check an unrelated unit acquires no filesystem or other-flavor header.

## Scan verification

Ran emit-runtime and emit-compiler from this checkout twice using the host checkout's Clang layout facts/context and configured base publication. One request omitted filesystem and one added only fs_get. The first emitted no filesystem header. The second emitted scpp/filesystem.hpp with a single get declaration and direct includes of scpp/carriers.hpp and scpp/string.hpp. Its compiler descriptor named ::scpp::fs::get, required runtime-filesystem, and used the neutral func:fs_get key. Clang accepted the generated header in isolation. This checks generator and declaration behavior. The proposed PHP filesystem adapter and option still require implementation and end-to-end proof.

## Compiler lowering and toolchain measurements

The [lowering know-how note](lowering_know_how.md) tracks compiler actions, their semantic constraints, measured Clang and LLVM costs, and the next controlled comparisons.

The [v0.2 integration process draft](integration_process.md) gives the proposed review sequence, evidence and first enforcement points for new helpers and libraries.
