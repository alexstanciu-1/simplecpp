<?php
namespace scpp\compiler;
// Host-only measurement entrypoint shared byte-for-byte by before/after trees.
$started = hrtime(true);
require_once $argv[1] . '/compiler/my-try/boot.php';
$loaded = hrtime(true);
$compiler = new Compiler(new cpp_lowering_options($argv[3] === 'concrete'));
$compiler->init([$argv[2]]);
$compiler->sync([]);
$parsed = hrtime(true);
$compiler->prepare(true);
$prepared = hrtime(true);
$compiler->cpp();
$emitted = hrtime(true);
$bytes = 0;
foreach (Model::$cpp_files as $artifact) { $bytes += strlen($artifact->text); }
echo json_encode(['boot_ms'=>($loaded-$started)/1e6,'frontend_ms'=>($parsed-$loaded)/1e6,
	'prepare_ms'=>($prepared-$parsed)/1e6,'emit_ms'=>($emitted-$prepared)/1e6,
	'total_ms'=>($emitted-$started)/1e6,'artifact_bytes'=>$bytes,'peak_bytes'=>memory_get_peak_usage(true)]) . "\n";
