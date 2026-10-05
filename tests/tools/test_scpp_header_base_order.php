<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

// Generated class shapes: multiple/virtual bases and cyclic pointer signatures.
$directory = sys_get_temp_dir() . '/scpp_base_order_' . bin2hex(random_bytes(6));
mkdir($directory);
$headers = [
	'child.hpp' => 'namespace scpp::scpp::compiler {
class Child final : public scpp::compiler::Base, public virtual scpp::compiler::Role { };
}',
	'base.hpp' => 'namespace scpp::scpp::compiler {
class Child;
class Base : public virtual scpp::compiler::Marker { Child* value; Tag tag = Tag::ready; };
}',
	'role.hpp' => 'namespace scpp::scpp::compiler {
class Child;
class Role : virtual public scpp::compiler::Marker { virtual Child* get() = 0; };
}',
	'marker.hpp' => 'namespace scpp::scpp::compiler {
class Marker { };
}',
	'tag.hpp' => 'namespace scpp::scpp::compiler {
enum class Tag : unsigned char { ready };
}',
];
try {
	foreach ($headers as $name => $text) {
		file_put_contents($directory . '/' . $name, "#pragma once\n" . $text . "\n");
	}
	$paths = array_map(static fn(string $name): string => $directory . '/' . $name, array_keys($headers));
	$metadata = read_project_header_class_metadata($paths[0])['classes'];
	if (count($metadata) !== 1 || $metadata[0]['bases'] !== ['scpp::compiler::Base', 'scpp::compiler::Role']) {
		throw new RuntimeException('Multiple generated base definitions were lost');
	}
	foreach ([$paths, array_reverse($paths)] as $input) {
		$ordered = sort_project_unit_include_headers($input);
		$positions = array_flip(array_map('basename', $ordered));
		if ($positions['marker.hpp'] >= $positions['base.hpp']
			|| $positions['tag.hpp'] >= $positions['base.hpp']
			|| $positions['marker.hpp'] >= $positions['role.hpp']
			|| $positions['base.hpp'] >= $positions['child.hpp']
			|| $positions['role.hpp'] >= $positions['child.hpp']) {
			throw new RuntimeException('Soft signature cycle defeated base ordering');
		}
		$source = implode("\n", array_map(static fn(string $path): string => '#include "' . $path . '"', $ordered));
		file_put_contents($directory . '/proof.cpp', $source);
		$process = proc_open(['clang++-18', '-std=c++20', '-fsyntax-only', $directory . '/proof.cpp'],
			[0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipes);
		if (!is_resource($process)) {
			throw new RuntimeException('Cannot start native header-order proof');
		}
		fclose($pipes[0]);
		if (proc_close($process) !== 0) {
			throw new RuntimeException('Ordered generated header shapes do not compile');
		}
	}
	echo "PASS: multiple/virtual base ordering with cyclic signatures\n";
}
finally {
	foreach (array_keys($headers) as $name) {
		unlink($directory . '/' . $name);
	}
	if (is_file($directory . '/proof.cpp')) {
		unlink($directory . '/proof.cpp');
	}
	rmdir($directory);
}
