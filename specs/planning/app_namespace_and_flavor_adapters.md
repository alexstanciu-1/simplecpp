# Application C++ namespace and runtime flavor adapters

Doc Status: planning

The current compiler already prepares string prefix predicates in three source
spellings. Its generated adapters forward them to `scpp::str::starts_with`.
The following is the agreed direction for generated application and runtime C++.

## Names and ownership

- The selected project's C++ lowering root defaults to `App`. Allow an explicit
  qualified root and an opt-out. Entry-app manifest spelling:
  `"cpp_namespace": "acme::ledger"`; JSON null selects global emission.
  Validate the root as a C++ namespace path. Keep source names, symbol keys,
  watchers and the existing native identifier encoding independent of this option.
  Hosts linking multiple apps can give each a distinct root.
- Place app function/global declarations and definitions, including cross-unit
  headers and the entry body, consistently under the selected root. The real
  `::main()` delegates to the qualified entry body. Native dependencies retain
  their published ABI. A root edit invalidates all app C++ artifacts.
- Runtime operations belong to provider namespaces such as `::scpp::str`.
  Generate concrete flavor adapters in `::scpp::php`, `::scpp::js` and
  `::scpp::cpp`. One metadata declaration supplies the runtime implementation,
  source spellings and adapter bindings to both generators. Preparation resolves
  each call once. Lowering consumes the selected target, header and call form.
  A flavor-neutral binding can call the provider directly.
- Generate includes per translation unit, by the providers and flavors it uses.
  Put `using namespace ::scpp::<flavor>;` inside the app namespace in a private
  implementation file (function scope in global-emission mode). Public app and
  runtime headers use qualified names. Emit a fully qualified call whenever
  ordinary lookup, shadowing or argument-dependent lookup could alter the
  selected target. A conservative short-name allowlist for generated adapters is
  sufficient initially. Qualify runtime types and adapter internals from `::`.
- Inline small forwarders with light public dependencies. Library implementation,
  vendor includes and expensive algorithms stay in provider implementation files.
  Generator templates produce concrete app-facing declarations. A source method
  may lower to a free adapter with the receiver as its first operand. Preserve
  the existing call sequencing path for effectful operands.

## First vertical proof

The examples are alternative source units. Names below illustrate the call and
header contract; the real emitter retains its current app-symbol encoding.

| Flavor | Source function | Generated header | Lowered return expression |
| --- | --- | --- | --- |
| PHP | `function matches(string $s, string $p): bool { return str_starts_with($s, $p); }` | `scpp/php/string.hpp` | `str_starts_with(local_0, local_1)` |
| JS | `function matches(s: string, p: string): bool { return s.startsWith(p); }` | `scpp/js/string.hpp` | `startsWith(local_0, local_1)` |
| C++ | `bool matches(string s, string p) { return s.starts_with(p); }` | `scpp/cpp/string.hpp` | `starts_with(local_0, local_1)` |

Each generated header includes the canonical public string provider. For example:

```cpp
#pragma once
#include <scpp/string.hpp>

namespace scpp::js {
inline bool startsWith(const ::scpp::string& value, const ::scpp::string& prefix) {
    return ::scpp::str::starts_with(value, prefix);
}
}
```

The PHP and C++ headers generate the same typed forwarder with their respective
namespace and spelling. The shared runtime function takes two const string
references and compares length-aware byte views, including NUL and high bytes.

Prove default, custom and disabled roots; standalone flavor headers; call target
under shadowing; mixed-flavor, cross-unit linking to one runtime archive; exact
byte behavior including empty prefix; and equivalent fresh and incremental output
after changing and restoring the root. Check source-only edits still reuse
unaffected units. Record Clang frontend, backend and link times. Other predicates
and third-party library wrappers can adopt the proven path in later slices.
