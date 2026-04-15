# PHP FastCGI Bridge for Simple C++

This directory contains a standalone PHP bridge for calling Simple C++ applications over FastCGI.

## Design goals

- keep the public PHP API web-shaped
- keep the transport aligned with a future `web server -> Simple C++` deployment
- avoid in-process FFI crash sharing with PHP-FPM
- support on-demand startup with an actual health probe

## Public entrypoint

The main helper is `ScppFastCgiBridge::scpp_call()`:

```php
$response = $bridge->scpp_call(
	'invoice_app',
	'/invoice/render',
	'POST',
	'',
	json_encode($payload, JSON_THROW_ON_ERROR),
	[
		'Content-Type' => 'application/json',
	],
	[
		'session_id' => $sessionId,
	]
);
```

The response object uses snake_case fields:

```php
$response->status_code;
$response->headers;
$response->body;
```

## Files

- `src/ScppFastCgiBridge.php` - bridge, FastCGI client, startup flow
- `src/ScppAppConfig.php` - per-app config
- `src/ScppRequest.php` - web-shaped request DTO
- `src/ScppResponse.php` - response DTO
- `src/ScppBridgeException.php` - bridge exception type
- `src/bootstrap.php` - simple manual include entrypoint
- `examples/example_usage.php` - sample wiring

## App config shape

Each app is registered by a logical `app_id`, not by exposing raw socket syntax in the public API.

```php
$bridge = new ScppFastCgiBridge([
	'invoice_app' => [
		'bin_path' => '/opt/scpp/invoice_app',
		'socket_path' => '/run/scpp/invoice_app.sock',
		'cwd' => '/opt/scpp',
		'health_path' => '/__health',
		'command' => [
			'/opt/scpp/invoice_app',
			'--bind=unix:/run/scpp/invoice_app.sock',
		],
	],
]);
```

## Startup flow

For each call, the bridge does this:

1. resolve the app config by `app_id`
2. probe the FastCGI app with `GET /__health`
3. if unhealthy and `auto_start = true`, take a startup lock
4. start the process
5. wait until the health probe succeeds
6. send the real request

The lock is only used to prevent concurrent double-start races.
It is **not** treated as the source of truth for liveness.

## Notes

- The bridge expects the Simple C++ FastCGI process to return CGI/HTTP-like headers followed by a blank line and the body.
- `SCRIPT_FILENAME` is set to the configured app binary path.
- A Unix socket transport is used internally via `unix://<socket_path>`.
- This is a project-side component; do not place it under `public_html`.

## Startup note

On Unix-like systems the bridge starts the FastCGI app through a detached `nohup` launcher so the Simple C++ process can outlive the PHP request that triggered it. The Windows path remains a lighter fallback and may need project-specific hardening later.
