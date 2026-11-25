# FastCGI host intake

Doc Status: planning

## Decision proposed

Bring FastCGI to v0.2 as an optional Linux runtime module with a Simple C++ application entry and handler. Start with one synchronous request at a time over a Unix socket. Treat the PHP bridge, worker concurrency and automatic companion executable as independent follow-ups. A FastCGI client such as a web server can drive the first slice directly.

## What the donor shipped

At release `d2f63e37e`, enabling FastCGI produced both the normal executable and a companion with the suffix `_fcgi`. The build reused project objects and compiled a second entry unit without main. The host supplied main, linked libfcgi and pthread, and dispatched to a handwritten C++ function. The my-try revision `624ab8a2d` contains the host files too. Their presence establishes shared native code rather than a second source-language contract.

| Component | Evidence and consequence |
| --- | --- |
| Request | The host copied method, path, raw query and binary body, normalized header names, and parsed query parameters and cookies. Its map kept the last repeated query value and the first repeated cookie. Retaining the raw query makes that information available without adopting the donor parser immediately. |
| Response | The handler returned a status, header map and binary body. The host supplied content length and a default JSON content type. A single-value header map loses repeated fields such as Set-Cookie. |
| Lifecycle | The host owned the socket, worker threads, request-count limit and a reserved health route. The implementation removes an existing absolute path before binding without checking its type or ownership. Its shutdown flag also needs a way to wake an idle accept. These choices need explicit contracts for a long-running application. |
| PHP bridge | Separate host-PHP code encoded FastCGI records, decoded the response and could start the companion under a lock and poll its health. Calling an already running service and supervising a child are separable features. |
| Error mapping | The host returned a handler exception message in its JSON response and concatenated that text without JSON escaping. A broad outer catch mapped request processing failures to 413. Give body-limit failures, application failures and transport failures distinct handling. |

[Build mode](https://github.com/alexstanciu-1/simplecpp/blob/d2f63e37e/docs/fastcgi.md) · [Host loop](https://github.com/alexstanciu-1/simplecpp/blob/d2f63e37e/runtime/include/hosts/fastcgi/fastcgi_main.cpp) · [Handler ABI](https://github.com/alexstanciu-1/simplecpp/blob/d2f63e37e/runtime/include/hosts/fastcgi/fastcgi.hpp) · [PHP bridge](https://github.com/alexstanciu-1/simplecpp/blob/d2f63e37e/integrations/php/src/ScppFastCgiBridge.php)

## Fit with the current compiler

An application today has one selected entry, an adapter owning main, and a link manifest for its module dependencies. A module can provide a compiled archive, published data and opaque handles. Keep libfcgi implementation headers private. Let the application's selected entry call accept, call its typed handler directly, and finish the request. This makes the handler and its dependencies visible to ordinary source compilation. A CLI and a server can use separate entry selections later. [Module build contract](../../../compiler/docs/modules.md) · [Compiler entry contract](../../compiler_entry_v0_2.md)

A temporary PHP-flavor project compiled and linked a direct `handle(Request): Response` call with source-defined value structs. Two successive calls preserved independent response bodies, including an embedded NUL. This proves the application call shape. Module-defined request/response publication and the FastCGI wire path still need their own proof. A native serve(callback) façade would need separate work: the current named-callback contract excludes inline record values. An explicit accept loop avoids making that extension a prerequisite.

## First slice and acceptance

- Publish a listener plus a per-request exchange identifying the reply destination. Keep owned request bytes valid after completion. Finishing or abandoning an exchange must invalidate all its aliases and finish the native request exactly once. Distinguish normal shutdown from accept/read failure.
- Start with method, path, raw query, request headers and binary body. For responses, support status, body and ordered headers with repeatable names. The host owns response framing and content length. Keep decoded query/cookie convenience fields for the next slice unless donor compatibility is required from day one.
- Use a configured body limit, preserve NUL bytes, return 413 for oversized requests, and discard a partial response on handler failure before sending a generic 500 where possible. An application owns routing, including any health endpoint. Define safe socket ownership and shutdown while idle before enabling production use.
- Prove two consecutive requests over a real socket, followed by a normal stop. Exercise a repeated response header, a binary body, an oversized request followed by a successful request, failure during handling, and an attempted second reply through an alias. Build a minimal CLI project alongside it and verify its compile and link inputs exclude FastCGI.
- Local evaluation found the cgi-fcgi client and the libfcgi.so.0 runtime library. The normal development header, unversioned linker library and pkg-config entry were absent. The source probe used Clang and the existing v0.2 runtime; no wire round trip was run.

## First frontend decision

Apache httpd is the first consumer. Use mod_proxy and mod_proxy_fcgi to forward an application URL prefix to the Unix socket owned by an independently started Simple C++ service. For the initial test, use Apache 2.4.26 or newer with ProxyFCGIBackendType GENERIC scoped to the application route and the default connection reuse setting (off). PHP bridge and process supervision remain separate follow-ups. [Apache FastCGI proxy documentation](https://httpd.apache.org/docs/2.4/mod/mod_proxy_fcgi.html)

Drive the acceptance test through HTTP on an isolated Apache instance. Verify the handler sees the expected method, mounted path, raw query and body, then verify the HTTP client sees the status, two separate Set-Cookie headers and the exact response bytes. ProxyPass normally omits PATH_INFO, so test routing from REQUEST_URI and QUERY_STRING. The local Apache binary and both proxy module files are present. End-to-end verification awaits the host implementation.

## Implementation evidence

The optional linux provider and general bindings now follow the explicit accept-loop design. The request snapshot stores normalized headers as parallel name/value vectors. Response headers are staged on the exchange before the final status/body reply. The local integration test compiled a Simple C++ application and sent HTTP requests through Apache 2.4 with mod_proxy_fcgi to its Unix socket. It checked repeated Set-Cookie, a binary POST, the mounted path and raw query, access to snapshot data after finish, 413 and 500 recovery, idle close and rejection of a second reply through an alias. The earlier development-header blocker was resolved for the test by extracting the matching libfcgi development package to a temporary directory. [Published contract](../../fastcgi_module.md)
