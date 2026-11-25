<?php

declare(strict_types=1);

use Prism\Bridge\Php\ScppFastCgiBridge;

require_once __DIR__ . '/../src/bootstrap.php';

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
		'env' => [
			'SCPP_ENV' => 'dev',
		],
	],
]);

$response = $bridge->scpp_call(
	'invoice_app',
	'/invoice/render',
	'POST',
	'',
	json_encode([
		'invoice_id' => 10,
		'customer_name' => 'Alex',
	], JSON_THROW_ON_ERROR),
	[
		'Content-Type' => 'application/json',
		'X-Trace-Id' => 'demo-trace-123',
	],
	[
		'session_id' => 'abc123',
	]
);

echo "Status: {$response->status_code}\n";
echo "Body:\n{$response->body}\n";
