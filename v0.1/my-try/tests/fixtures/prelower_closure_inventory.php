<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

// Audit consumer of the existing publication owner; never reconstruct readiness markers.
$root = $argv[1];
$manifest = Output_Publication::require_ready($root);
$document = json_read($manifest);
$files = $document->member('files');
for ($index = 0; $index < $files->size(); $index++) {
	$row = $files->at($index);
	$path = $row->member('path')->text();
	if (fs_output_digest($root, $path) !== $row->member('sha256')->text()) {
		throw new \RuntimeException('Published artifact changed: ' . $path);
	}
}
echo $manifest;
