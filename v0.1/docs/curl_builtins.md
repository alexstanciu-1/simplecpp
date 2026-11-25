# Curl builtins - first pass
Doc Status: supporting

This page summarizes the first-pass Prism++ / Simple C++ curl builtin surface.

Curl is runtime-owned and lives under `namespace scpp::curl`.
The current module is exposed through:

- strict PHP profile flat `curl_*` names mapped directly to `scpp::curl`
- legacy PHP profile flat `curl_*` names mapped through `scpp::php` compatibility wrappers

## Header split

Use the dedicated headers:

- runtime module header: `runtime/include/modules/curl/curl.hpp`
- public umbrella header: `runtime/include/scpp/curl.hpp`

## First-pass contract shape

- opt-in runtime module
- libcurl-backed implementation
- strict/runtime calls return `result<T>` with explicit `error_t`
- legacy compatibility wrappers expose falseable/string-oriented PHP-facing behavior for the current subset
- response payload is a typed `curl_response` object
- last-error state is still tracked on the handle for `curl_errno()` and `curl_error()`
- unsupported options and disabled-module calls fail explicitly with descriptive messages

## Current strict/runtime entries

- `curl_init`
- `curl_setopt`
- `curl_exec`
- `curl_getinfo`
- `curl_errno`
- `curl_error`
- `curl_reset`
- `curl_close`
- `curl_strerror`

## Current legacy entries

- `curl_init`
- `curl_setopt`
- `curl_exec`
- `curl_getinfo`
- `curl_errno`
- `curl_error`
- `curl_reset`
- `curl_close`
- `curl_strerror`

Legacy behavior notes:

- `curl_exec()` currently returns the response body string on success and `false` on failure
- `curl_getinfo($handle)` returns a PHP-shaped info array as `mixed`
- `curl_getinfo($handle, $selector)` returns a single `mixed` scalar
- `CURLOPT_RETURNTRANSFER` is accepted and kept for familiarity, but the current legacy wrapper always returns the body string on success

## Current first-pass option scope

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

## Current `curl_getinfo` scope

- `CURLINFO_RESPONSE_CODE`
- `CURLINFO_EFFECTIVE_URL`
- `CURLINFO_CONTENT_TYPE`
- `CURLINFO_TOTAL_TIME_MS`
- `CURLINFO_HEADER_SIZE`
- `CURLINFO_REQUEST_SIZE`
- `CURLINFO_REDIRECT_COUNT`

## Current test coverage

- strict PHP profile:
  local `file://`, local HTTP GET, local HTTP POST, local JSON request/response
- legacy PHP profile:
  local `file://`, local HTTP GET, local HTTP POST, local JSON request/response
- opt-in external network:
  pinned GitHub raw `HTTPS` fetch

The PHP test harness also supports:

- per-test runtime module opt-in through `build.runtime_modules`
- per-test local HTTP server setup through `build.http_server`
- opt-in external network tests through `build.external_network = true` plus runner flag `--include-network`

For the full first-pass contract, see `specs/builtins/curl/`.
