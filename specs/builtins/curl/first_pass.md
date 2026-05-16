# Curl builtins - first pass
Doc Status: normative

This page defines the first-pass Prism++ / Simple C++ curl builtin intake scope.

Curl is runtime-owned and lives under `namespace scpp::curl`.
Two PHP-facing source layers are currently defined:

- strict PHP profile: flat `curl_*` source names registered directly to the shared runtime module
- legacy PHP profile: flat `curl_*` source names registered to thin `scpp::php` compatibility wrappers

## Header split

Curl support is intentionally kept out of generic `php.hpp` growth.
Use the dedicated headers instead:

- runtime module header: `runtime/include/modules/curl/curl.hpp`
- public umbrella header: `runtime/include/scpp/curl.hpp`

## First-pass module policy

- backend: libcurl easy interface
- module name: `curl`
- strict reusable namespace root: `scpp::curl`
- current build policy: opt-in only
- current dependency policy: explicit host development package install is required
- current error policy: strict/runtime functions return `result<T>` with explicit `error_t`
- current compatibility target: practical curl familiarity, not full PHP-curl parity
- current cross-platform policy: implementation must prefer tool-driven dependency discovery and fail clearly when the module is enabled but libcurl is not available

## Current implementation slice

- build/config/runtime module plumbing for opt-in curl support
- shared runtime handle model
- shared runtime response model
- strict registry exposure for flat `curl_*` names
- legacy registry exposure for flat `curl_*` names
- legacy wrapper layer under `runtime/include/lang/php/php_curl.hpp`
- explicit option validation
- explicit runtime error propagation through `result<T>`
- explicit last-error tracking for `curl_errno()` / `curl_error()`
- explicit disabled-module stubs so calls fail with a clue instead of crashing

## Typed strict/runtime surface

- `curl_init(): result<curl_handle>`
- `curl_init(string $url): result<curl_handle>`
- `curl_setopt(curl_handle $handle, int $option, string $value): result<bool>`
- `curl_setopt(curl_handle $handle, int $option, int $value): result<bool>`
- `curl_setopt(curl_handle $handle, int $option, bool $value): result<bool>`
- `curl_setopt(curl_handle $handle, int $option, vector<string> $value): result<bool>`
- `curl_exec(curl_handle $handle): result<curl_response>`
- `curl_getinfo(curl_handle $handle, int $info): result<mixed>`
- `curl_errno(curl_handle $handle): int`
- `curl_error(curl_handle $handle): string`
- `curl_reset(curl_handle $handle): result<bool>`
- `curl_close(curl_handle $handle): result<bool>`
- `curl_strerror(int $code): string`

## Legacy PHP-facing surface

The current legacy wrapper layer is intentionally narrower than historical PHP-curl, but it exposes familiar flat names and falseable call behavior.

- `curl_init(): result_or_false<curl_handle>`
- `curl_init(string $url): result_or_false<curl_handle>`
- `curl_setopt(curl_handle $handle, int $option, string|int|bool|array $value): bool`
- `curl_exec(curl_handle $handle): result_or_false<string>`
- `curl_getinfo(curl_handle $handle): mixed`
- `curl_getinfo(curl_handle $handle, int $info): mixed`
- `curl_errno(curl_handle $handle): int`
- `curl_error(curl_handle $handle): string`
- `curl_reset(curl_handle $handle): bool`
- `curl_close(curl_handle $handle): bool`
- `curl_strerror(int $code): string`

Legacy wrapper notes:

- `curl_exec()` currently returns the response body string on success
- failed execution returns `false`
- `curl_getinfo($handle)` returns a PHP-shaped info array as `mixed`
- `curl_getinfo($handle, $selector)` returns a single PHP-shaped scalar `mixed`
- `CURLOPT_RETURNTRANSFER` is accepted for familiarity, but the current wrapper always returns the body string on success

## First-pass option scope

The first pass accepts these curl-shaped option names:

- `CURLOPT_URL`
- `CURLOPT_RETURNTRANSFER`
- `CURLOPT_HTTPHEADER`
- `CURLOPT_POST`
- `CURLOPT_POSTFIELDS`
- `CURLOPT_CUSTOMREQUEST`
- `CURLOPT_TIMEOUT`
- `CURLOPT_CONNECTTIMEOUT`
- `CURLOPT_FOLLOWLOCATION`
- `CURLOPT_USERAGENT`
- `CURLOPT_SSL_VERIFYPEER`
- `CURLOPT_SSL_VERIFYHOST`

The first pass exposes these `curl_getinfo` selectors:

- `CURLINFO_RESPONSE_CODE`
- `CURLINFO_EFFECTIVE_URL`
- `CURLINFO_CONTENT_TYPE`
- `CURLINFO_TOTAL_TIME_MS`
- `CURLINFO_HEADER_SIZE`
- `CURLINFO_REQUEST_SIZE`
- `CURLINFO_REDIRECT_COUNT`

## Current response model

`curl_exec()` returns a `curl_response` object with public fields:

- `status_code`
- `headers`
- `body`
- `effective_url`
- `content_type`
- `total_time_ms`
- `header_size`
- `request_size`
- `redirect_count`

## Contract narrowing

This first pass intentionally does **not** aim for full PHP-curl parity.

Key decisions:

- the strict/runtime contract returns a typed response object instead of PHP's mixed `curl_exec()` return shape
- `CURLOPT_RETURNTRANSFER` is accepted as a compatibility option, but the strict/runtime surface always returns a `curl_response`
- the legacy wrapper currently returns the body string on success rather than supporting the full historical `CURLOPT_RETURNTRANSFER` / output-stream split
- `curl_getinfo()` is supported only for the listed selectors above
- only the listed option subset is supported in this pass
- request/response payloads use `string_t`, which is treated as byte-preserving storage in this module
- response headers are returned as `vector_t<string_t>` raw header lines without trailing CRLF
- TLS/HTTPS support is required when the host libcurl provides it
- unsupported options or info selectors fail explicitly with descriptive `error_t` messages

## Compatibility notes

- familiar curl function names -> kept
- familiar `CURLOPT_*` option naming -> kept
- full PHP-curl mixed `curl_exec()` return shape -> modified
- wide PHP `curl_getinfo()` surface -> modified
- broad option universe -> dropped for the current pass
- silent failure or clue-free crashes -> dropped

## Configuration visibility

- Available only when the opt-in `curl` runtime module is enabled for the project.
- Enabling `curl` without a detectable libcurl development environment must fail clearly during build configuration.

## Testing note

Minimum required coverage in this pass:

- successful file-scheme transfer through the shared runtime surface
- binary-safe body roundtrip through `string_t`
- strict PHP profile coverage for local `file://`, local HTTP GET, local HTTP POST, and local JSON request/response
- legacy PHP profile coverage for local `file://`, local HTTP GET, local HTTP POST, and local JSON request/response
- opt-in external HTTPS coverage through a pinned raw GitHub URL
- unsupported option failure
- missing URL / invalid handle state failure
- explicit build-configuration failure when `curl` is enabled but libcurl detection fails
