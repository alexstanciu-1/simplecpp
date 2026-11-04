<?php
/** Detached frontend host entry point; launched by Frontend_Service, not a compilation stage. */
declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/frontend_service.php';
try {
	\scpp\compiler\Frontend_Service::serve($argv[1] ?? '');
}
catch (Throwable $error) {
	fwrite(\STDERR, $error->getMessage() . "\n");
	exit(1);
}
