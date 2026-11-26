<?php

declare(strict_types=1);
namespace scpp\compiler;

/** Bind only the Linux frontend service's private native dependency. */
function private_frontend_binding(string $key): ?runtime_function
{
	static $bindings = null;
	if ($bindings === null) {
		$string = type_registry::string();
		$void = type_void::instance();
		$bindings = [];
		foreach ([
			'host_directory' => [[], $void],
			'host_listen' => [[], $void],
			'host_request' => [[], $string],
			'host_reply' => [[$string], $void],
			'host_close' => [[], $void],
			'host_pid' => [[], type_registry::integer()],
			'host_exchange' => [[$string, $string], $string],
		] as $name => [$parameters, $result]) {
			$bindings['func:' . $name] = new runtime_function($name, $parameters, $result,
				'::App::' . $name . '_', 'frontend_host.hpp');
		}
	}
	return $bindings[$key] ?? null;
}
