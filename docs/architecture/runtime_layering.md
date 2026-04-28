# Runtime layering
Doc Status: supporting
Prism++ / Simple C++ now treats the runtime as a composition of:

- non-language runtime core
- language runtime layers
- optional runtime modules

## Public headers

Use:

- `#include <scpp/runtime.hpp>` for the non-language runtime surface
- `#include <scpp/lang/php.hpp>` for generated or handwritten PHP-target code

`scpp/runtime.hpp` is no longer the umbrella for PHP-specific headers.

## Current runtime targets

Current runtime targets include:

- `scpp_runtime` â†’ non-language runtime core
- `scpp_lang_php` â†’ PHP runtime layer
- `scpp_json` â†’ JSON runtime module
- `scpp_filesystem` â†’ filesystem runtime module

## Dependency rule

The intended direction is:

- runtime core does not depend on `lang/*`
- runtime modules do not depend on `lang/*`
- language runtimes depend on the runtime core and runtime modules

## Wrapper rule

When functionality is reusable across languages, the implementation should live in the runtime and the PHP layer should keep only thin wrappers.

Current examples:

- JSON implementation lives in `modules/json/` and `namespace scpp::json`
- filesystem implementation lives in `modules/filesystem/` and `namespace scpp::fs`
- PHP keeps wrapper headers in `lang/php/`


## Runtime build composition

`scpp build` now reads runtime composition from `prism.json` under:

```json
{
  "runtime": {
    "languages": {
      "php": {
        "profile": "legacy"
      }
    },
    "modules": ["json", "filesystem", "mysqli"]
  }
}
```

Legacy list-style `runtime.languages` remains accepted as a compatibility shape and defaults PHP to profile `legacy`.

Current default behavior keeps all known runtime modules active. Unsupported language or module names must fail clearly during build configuration.
