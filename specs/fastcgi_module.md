# Optional Linux FastCGI host

Doc Status: normative

A project selects the `fcgi` runtime module and uses its general API. Build the
provider with `SCPP_ENABLE_FCGI=ON` and the libfcgi development package. The
application owns its ordinary entry, routing and error policy. Start the
executable separately, with a dedicated socket directory controlled by its
operator. Directory permissions and the service umask must allow Apache to
connect. A listener and its exchanges belong to one synchronous serving thread.

| Operation | Contract |
| --- | --- |
| `fcgi_listen(path, max_body_bytes)` | Opens a listener at a new absolute Unix socket path. Existing entries cause an error. The body limit is nonnegative and measured in bytes. |
| `fcgi_accept(listener, idle_ms)` | Returns a nullable exchange. An idle poll timeout, interrupted poll or an oversized request already answered with 413 yields absence. Accept/read failures raise a runtime error. The nonnegative millisecond timeout bounds the idle poll only. An accepted connection may block waiting for protocol data. |
| `fcgi_request_read(exchange)` | Returns an owning request snapshot. Fields are method, mounted path, raw query_string, byte body, and parallel header_names/header_values string vectors. Header names are lowercase. Path comes from REQUEST_URI up to the first question mark. Empty path and method default to slash and GET. The snapshot survives finish. |
| `fcgi_response_header(exchange, name, value)` | Stages an ordered header. Repeated names such as Set-Cookie are preserved. Names use ASCII token syntax, and values reject control characters except tab. Status and transport framing are reserved. Content-Type can be set once. |
| `fcgi_reply(exchange, status, body)` | Sends a final status in 200–599 and a byte body. The provider computes content length and defaults content type to application/json with UTF-8 charset. The exchange finishes even on write failure. |
| `fcgi_abandon(exchange)` | Discards staged headers, attempts an empty generic 500 response and finishes. The last open handle's destructor has the same fallback. |
| `fcgi_close(listener)` | Closes an idle listener and removes the socket entry if it still matches the recorded identity. Repeated close is harmless. |

A listener allows one active request. Handle copies share state. Finishing
through one alias invalidates operations through every alias. Return a reply or
abandon the exchange before the next accept. Applications can poll a shutdown
condition during idle timeouts and break the loop to close normally. Following
abnormal process termination, the operator owns stale socket recovery.

### Apache2 example

An isolated virtual host can forward a URL prefix using mod_proxy and
mod_proxy_fcgi. Use Apache 2.4.26 or later for GENERIC mode. Keep connection
reuse disabled for this synchronous host.

```apache
<Location "/app/">
    ProxyFCGIBackendType GENERIC
</Location>
ProxyPass "/app/" "unix:/run/scpp/app.sock|fcgi://localhost/app/"
```

The [compiled source fixture](../compiler/tests/fixtures/resource_modules/fcgi.phs)
shows the accept loop, request dispatch, repeated response headers, and explicit
abandon on a handler exception. The [Apache integration test](../compiler/tests/fcgi_apache_native.py)
uses an isolated temporary configuration and leaves the system service untouched.
