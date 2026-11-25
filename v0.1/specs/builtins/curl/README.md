# Curl builtins
Doc Status: normative

This folder defines the first-pass Prism++ / Simple C++ curl runtime module surface.

Current first-pass scope:

- strict/runtime surface under `scpp::curl`
- legacy PHP-facing wrapper surface under `scpp::php`
- `curl_init`
- `curl_setopt`
- `curl_exec`
- `curl_getinfo`
- `curl_errno`
- `curl_error`
- `curl_reset`
- `curl_close`
- `curl_strerror`

See also:

- `specs/builtins/curl/first_pass.md`
- `docs/curl_builtins.md`
