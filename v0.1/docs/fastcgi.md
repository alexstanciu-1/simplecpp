# FastCGI build mode
Doc Status: supporting
Prism++ can emit a second executable for FastCGI serving when `prism.json` enables FastCGI.

## Minimal config

```json
{
	"fastcgi": {
		"enabled": true,
		"workers": 1,
		"max_body_size": 4194304,
		"max_requests": 0
	},
	"native_cpp_dir": "native_cpp"
}
```

## Build outputs

When FastCGI is enabled, `scpp build` emits:

- the normal executable
- a FastCGI companion executable with `_fcgi` suffix

Shared generated units are still reused. The current implementation emits one extra no-`main()` entrypoint object for the FastCGI target so the FastCGI binary can link against the generated project code without a duplicate `main()` symbol.

## Project handler

The FastCGI host calls the hardcoded project symbol:

```cpp
scpp::fcgi::response_t scpp::fcgi::http_handle(const scpp::fcgi::request_t& request);
```

Define that handler in a handwritten C++ source file under `native_cpp/`.

## Request / response model

Headers are normalized to lowercase.

The v1 request includes:

- `method`
- `path`
- `query_string`
- `query_params`
- `body`
- `headers`
- `cookies`

The v1 response includes:

- `status_code`
- `headers`
- `body`

`/__health` is handled by the FastCGI host directly and returns a default JSON health response.

## Runtime notes

- FastCGI owns the Unix socket.
- Socket cleanup is handled on startup/shutdown.
- Worker count is fixed from config unless overridden by CLI args.
- Logging is plain stdout/stderr in v1.
- `SCPP_DEBUG=1` enables extra host logging.

## Include model

For PHP-target FastCGI projects, handwritten helper code should normally include:

```cpp
#include <scpp/lang/php.hpp>
```

Use `scpp/runtime.hpp` only when you intentionally want the non-language runtime surface without PHP wrappers.
