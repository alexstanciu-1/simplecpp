<?php
declare(strict_types=1);

require_once __DIR__ . '/../../bin/bootstrap.php';
require_once __DIR__ . '/../../bin/project_services.php';

final class ScppTasksModuleTest
{
	private string $root;

	public function __construct()
	{
		$this->root = normalize_path(sys_get_temp_dir() . '/scpp_tasks_module_' . getmypid() . '_' . bin2hex(random_bytes(4)));
		register_shutdown_function(function (): void {
			$this->removeTree($this->root);
		});
	}

	public function run(): int
	{
		if (find_command_path(['ninja']) === null) {
			echo "SKIP: ninja not found\n";
			return 0;
		}
		if (resolve_compiler(['build' => []]) === null) {
			echo "SKIP: compiler not found\n";
			return 0;
		}

		try {
			$project = $this->root . '/app';
			$this->mkdir($project);
			$this->write($project . '/prism.json', json_encode([
				'name' => 'tasks-module-regression',
				'entrypoint' => 'main.phs',
				'build_dir' => '.prism/build',
				'runtime' => [
					'languages' => [
						'php' => ['profile' => 'strict'],
					],
					'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
				],
			], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
			$this->write($project . '/main.phs', <<<'PHS'
task_set_worker_pool_size(2);

$items vector<int> = [];
$items[] = 1;
$items[] = 2;
$items[] = 3;

$result = task_run($items, 2, function (int $item): int {
	return $item * 2;
});

echo $result[0], ",", $result[1], ",", $result[2], "\n";

$published vector<int> = [];
$publishedBatches int = 0;
$publishedCount int = task_run_publish(
	$items,
	2,
	function (int $item): int {
		dt_sleep_ms((4 - $item) * 5);
		return $item * 100;
	},
	function (vector<int> $batch) use (&$published, &$publishedBatches): void {
		$publishedBatches = $publishedBatches + 1;
		$batchIndex int = 0;
		while ($batchIndex < count($batch)) {
			$published[] = $batch[$batchIndex];
			$batchIndex = $batchIndex + 1;
		}
	}
);

echo "publish:", $publishedCount, ",", $published[0], ",", $published[1], ",", $published[2], ",", $publishedBatches, "\n";
$publishHoldMetricPresent int = task_publish_lock_hold_us() >= 0 ? 1 : 0;
echo "publish-metrics:", task_publish_published_count(), ",", task_publish_batch_count(), ",", task_publish_max_batch_size(), ",", $publishHoldMetricPresent, "\n";
task_set_publish_try_lock(true);
task_set_publish_try_lock(false);

$cappedPublished vector<int> = [];
$cappedPublishedCount int = task_run_publish(
	$items,
	2,
	function (int $item): int {
		dt_sleep_ms((4 - $item) * 5);
		return $item * 1000;
	},
	function (vector<int> $batch) use (&$cappedPublished): void {
		$batchIndex int = 0;
		while ($batchIndex < count($batch)) {
			$cappedPublished[] = $batch[$batchIndex];
			$batchIndex = $batchIndex + 1;
		}
	},
	null,
	0,
	2
);
echo "publish-capped:", $cappedPublishedCount, ",", $cappedPublished[0], ",", $cappedPublished[1], ",", $cappedPublished[2], ",", task_publish_max_batch_size(), "\n";

$indexed = task_run(
	$items,
	2,
	function (int $item): int {
		return $item * 5;
	},
	function (int $item): int {
		return $item + 10;
	},
	null,
	null
);

echo $indexed[11], ",", $indexed[12], ",", $indexed[13], "\n";

$stringIndexed = task_run(
	$items,
	2,
	function (int $item): int {
		return $item * 7;
	},
	function (int $item): string {
		return "task-" . $item;
	},
	null,
	null,
	0
);

echo $stringIndexed["task-1"], ",", $stringIndexed["task-2"], ",", $stringIndexed["task-3"], "\n";

$voidItems vector<int> = [];
$voidItems[] = 1;
$voidItems[] = 2;
$voidItems[] = 3;

$voidResult = task_run($voidItems, 2, function (int $item): void {
	$item + 1;
});

echo count($voidResult), "\n";

$targetVector vector<int> = [];
$targetVector[] = 100;

$targetVectorResult = task_run(
	$items,
	2,
	function (int $item): int {
		return $item + 50;
	},
	null,
	$targetVector,
	null,
	0
);

echo $targetVectorResult[0], ",", $targetVectorResult[1], ",", $targetVectorResult[2], ",", $targetVectorResult[3], "\n";

$targetHash hash<int> = [];
$targetHash["task-2"] = 200;

$targetHashResult = task_run(
	$items,
	2,
	function (int $item): int {
		return $item + 60;
	},
	function (int $item): string {
		return "task-" . $item;
	},
	$targetHash,
	null,
	0
);

echo $targetHashResult["task-1"], ",", $targetHashResult["task-2"], ",", $targetHashResult["task-3"], "\n";

$nullableHashTarget hash<int, int> = [];
$nullableHashTarget[10] = 500;

$nullableHashResult = task_run(
	$items,
	2,
	function (int $item): int {
		return $item + 70;
	},
	function (int $item): ?int {
		if ($item === 2) {
			return null;
		}
		return $item * 10;
	},
	$nullableHashTarget,
	null,
	0
);

echo $nullableHashResult[10], ",", $nullableHashResult[11], ",", $nullableHashResult[30], "\n";

$nullableVectorTarget vector<int> = [];
$nullableVectorTarget[] = 900;

$nullableVectorResult = task_run(
	$items,
	2,
	function (int $item): int {
		return $item + 80;
	},
	function (int $item): ?int {
		if ($item === 2) {
			return null;
		}
		return $item;
	},
	$nullableVectorTarget,
	null,
	0
);

echo $nullableVectorResult[0], ",", $nullableVectorResult[1], ",", $nullableVectorResult[2], ",", $nullableVectorResult[3], "\n";

$lookup hash<int> = [];
$lookup["a"] = 4;
$lookup["b"] = 5;

$mapped = task_run($lookup, 2, function (int $item): int {
	return $item + 10;
});

echo $mapped["a"], ",", $mapped["b"], "\n";

$hashIndexed = task_run(
	$lookup,
	2,
	function (int $item): int {
		return $item * 4;
	},
	function (int $item): string {
		return "hash-" . $item;
	},
	null,
	null
);

echo $hashIndexed["hash-4"], ",", $hashIndexed["hash-5"], "\n";

$hashTarget hash<int> = [];
$hashTarget["a"] = 1000;
$hashTarget["seed"] = 999;

$hashTargetResult = task_run(
	$lookup,
	2,
	function (int $item): int {
		return $item + 100;
	},
	null,
	$hashTarget,
	null,
	0
);

echo $hashTargetResult["a"], ",", $hashTargetResult["b"], ",", $hashTargetResult["seed"], "\n";

$hashNullableTarget hash<int, int> = [];
$hashNullableTarget[20] = 2000;

$hashNullableResult = task_run(
	$lookup,
	2,
	function (int $item): int {
		return $item + 200;
	},
	function (int $item): ?int {
		if ($item === 5) {
			return null;
		}
		return $item * 10;
	},
	$hashNullableTarget,
	null,
	0
);

echo $hashNullableResult[20], ",", $hashNullableResult[40], ",", $hashNullableResult[41], "\n";

$mixedList mixed = [];
$mixedList[] = 2;
$mixedList[] = 4;

$mixedListResult = task_run($mixedList, 2, function (mixed $item): mixed {
	return $item + 3;
});

echo $mixedListResult[0], ",", $mixedListResult[1], "\n";

$mixedMap mixed = [];
$mixedMap["left"] = 5;
$mixedMap["right"] = 8;

$mixedMapResult = task_run($mixedMap, 2, function (mixed $item): mixed {
	return $item + 4;
});

echo $mixedMapResult["left"], ",", $mixedMapResult["right"], "\n";

$statusItems vector<int> = [];
$statusItems[] = 7;

$statusResult = task_run($statusItems, 1, function (int $item, task_context $ctx): int {
	task_set_status($ctx, "working");
	return $item + 1;
});

echo $statusResult[0], "\n";

$errorItems vector<int> = [];
$errorItems[] = 1;
$errorItems[] = 2;
$errorItems[] = 3;

$errorResult = task_run(
	$errorItems,
	2,
	function (int $item): int {
		if ($item === 2) {
			throw new Exception("bad task item");
		}
		return $item * 10;
	},
	null,
	null,
	function (int $item, task_error $error): int {
		return $item + 90;
	}
);

echo $errorResult[0], ",", $errorResult[1], ",", $errorResult[2], "\n";

$timeoutHandledItems vector<int> = [];
$timeoutHandledItems[] = 1;

$timeoutHandledResult = task_run(
	$timeoutHandledItems,
	1,
	function (int $item): int {
		dt_sleep_ms(20);
		return $item * 10;
	},
	null,
	null,
	function (int $item, task_error $error): int {
		if ($error->timeout) {
			return $item + 100;
		}
		return $item + 200;
	},
	5
);

echo $timeoutHandledResult[0], "\n";

$hashErrorItems hash<int> = [];
$hashErrorItems["x"] = 1;
$hashErrorItems["y"] = 2;

$hashErrorResult = task_run(
	$hashErrorItems,
	2,
	function (int $item): int {
		if ($item === 2) {
			throw new Exception("bad hash task item");
		}
		return $item * 3;
	},
	null,
	null,
	function (int $item, task_error $error): int {
		return $item + strlen($error->kind);
	}
);

echo $hashErrorResult["x"], ",", $hashErrorResult["y"], "\n";

$backgroundItems vector<int> = [];
$backgroundItems[] = 4;
$backgroundItems[] = 5;

$batch = task_start($backgroundItems, 2, function (int $item): int {
	if ($item === 4) {
		dt_sleep_ms(20);
	}
	return $item + 5;
});

$wasDone = task_done($batch);
$progress = task_progress($batch);
$joined = task_join($batch);
$joinedAgain = task_join($batch);
task_cancel($batch);
$afterProgress = task_progress($batch);
$isDone = task_done($batch);

echo $wasDone, ",", $progress->total(), ",", $joined[0], ",", $joined[1], ",", $joinedAgain[0], ",", $isDone, "\n";
echo $afterProgress->completed(), ",", $afterProgress->queued(), ",", $afterProgress->active(), ",", $afterProgress->errors(), ",", (int) $afterProgress->stop_requested(), "\n";

$cancelItems vector<int> = [];
$cancelItems[] = 10;
$cancelItems[] = 20;
$cancelItems[] = 30;

$cancelBatch = task_start($cancelItems, 1, function (int $item): int {
	dt_sleep_ms(20);
	return $item + 1;
});

dt_sleep_ms(5);
task_cancel($cancelBatch);
$cancelProgress = task_progress($cancelBatch);
$cancelJoined = task_join($cancelBatch);

echo $cancelProgress->stop_requested(), ",", $cancelProgress->total(), ",", count($cancelJoined), ",", $cancelJoined[0], "\n";
echo $cancelProgress->completed(), ",", $cancelProgress->queued(), ",", $cancelProgress->active(), ",", $cancelProgress->errors(), "\n";

$statusBatchItems vector<int> = [];
$statusBatchItems[] = 6;

$statusBatch = task_start($statusBatchItems, 1, function (int $item, task_context $ctx): int {
	task_set_status($ctx, "bg-working");
	dt_sleep_ms(20);
	return $item;
});

dt_sleep_ms(5);
$liveStatus = task_status($statusBatch);
$statusProgress = task_progress($statusBatch);
$statusJoined = task_join($statusBatch);

echo $liveStatus, ",", $statusProgress->status(), ",", $statusJoined[0], "\n";

$backgroundErrorItems vector<int> = [];
$backgroundErrorItems[] = 1;
$backgroundErrorItems[] = 2;

$handledBatch = task_start(
	$backgroundErrorItems,
	2,
	function (int $item): int {
		if ($item === 2) {
			throw new Exception("background handled item");
		}
		return $item * 4;
	},
	null,
	null,
	function (int $item, task_error $error): int {
		return $item + 40;
	},
	0
);

$handledJoined = task_join($handledBatch);
echo $handledJoined[0], ",", $handledJoined[1], "\n";

$backgroundHashItems hash<int> = [];
$backgroundHashItems["left"] = 3;
$backgroundHashItems["right"] = 6;

$hashBatch = task_start($backgroundHashItems, 2, function (int $item): int {
	return $item + 30;
});

$hashProgress = task_progress($hashBatch);
$hashJoined = task_join($hashBatch);

echo $hashProgress->total(), ",", $hashJoined["left"], ",", $hashJoined["right"], "\n";

$backgroundTargetItems vector<int> = [];
$backgroundTargetItems[] = 7;
$backgroundTargetItems[] = 8;

$backgroundTarget vector<int> = [];
$backgroundTarget[] = 300;

$targetBatch = task_start(
	$backgroundTargetItems,
	2,
	function (int $item): int {
		return $item + 70;
	},
	null,
	$backgroundTarget,
	null
);

$targetJoined = task_join($targetBatch);
echo $targetJoined[0], ",", $targetJoined[1], ",", $targetJoined[2], "\n";

$backgroundHashTargetItems hash<int> = [];
$backgroundHashTargetItems["north"] = 1;
$backgroundHashTargetItems["south"] = 2;

$backgroundHashTarget hash<int> = [];
$backgroundHashTarget["north"] = 999;

$hashTargetBatch = task_start(
	$backgroundHashTargetItems,
	2,
	function (int $item): int {
		return $item + 80;
	},
	null,
	$backgroundHashTarget,
	null
);

$hashTargetJoined = task_join($hashTargetBatch);
echo $hashTargetJoined["north"], ",", $hashTargetJoined["south"], "\n";

$backgroundIndexedTargetItems vector<int> = [];
$backgroundIndexedTargetItems[] = 3;
$backgroundIndexedTargetItems[] = 4;

$backgroundIndexedTarget hash<int> = [];
$backgroundIndexedTarget["job-3"] = 700;

$indexedTargetBatch = task_start(
	$backgroundIndexedTargetItems,
	2,
	function (int $item): int {
		return $item + 90;
	},
	function (int $item): string {
		return "job-" . $item;
	},
	$backgroundIndexedTarget,
	null
);

$indexedTargetJoined = task_join($indexedTargetBatch);
echo $indexedTargetJoined["job-3"], ",", $indexedTargetJoined["job-4"], "\n";

$backgroundHashIndexedItems hash<int> = [];
$backgroundHashIndexedItems["left"] = 5;
$backgroundHashIndexedItems["right"] = 6;

$backgroundHashIndexedTarget hash<int> = [];
$backgroundHashIndexedTarget["slot-5"] = 800;

$hashIndexedTargetBatch = task_start(
	$backgroundHashIndexedItems,
	2,
	function (int $item): int {
		return $item + 100;
	},
	function (int $item): string {
		return "slot-" . $item;
	},
	$backgroundHashIndexedTarget,
	null
);

$hashIndexedTargetJoined = task_join($hashIndexedTargetBatch);
echo $hashIndexedTargetJoined["slot-5"], ",", $hashIndexedTargetJoined["slot-6"], "\n";

$backgroundNullableItems vector<int> = [];
$backgroundNullableItems[] = 9;
$backgroundNullableItems[] = 10;

$backgroundNullableTarget vector<int> = [];
$backgroundNullableTarget[] = 9000;

$nullableTargetBatch = task_start(
	$backgroundNullableItems,
	2,
	function (int $item): int {
		return $item + 110;
	},
	function (int $item): ?int {
		if ($item === 10) {
			return null;
		}
		return 0;
	},
	$backgroundNullableTarget,
	null
);

$nullableTargetJoined = task_join($nullableTargetBatch);
echo $nullableTargetJoined[0], ",", $nullableTargetJoined[1], "\n";

$backgroundErrorTargetItems vector<int> = [];
$backgroundErrorTargetItems[] = 1;
$backgroundErrorTargetItems[] = 2;

$backgroundErrorTarget vector<int> = [];
$backgroundErrorTarget[] = 30;

$errorTargetBatch = task_start(
	$backgroundErrorTargetItems,
	2,
	function (int $item): int {
		if ($item === 2) {
			throw new Exception("background target replacement");
		}
		return $item * 3;
	},
	null,
	$backgroundErrorTarget,
	function (int $item, task_error $error): int {
		return $item + 130;
	}
);

$errorTargetJoined = task_join($errorTargetBatch);
echo $errorTargetJoined[0], ",", $errorTargetJoined[1], ",", $errorTargetJoined[2], "\n";

$perfItems vector<int> = [];
$perfItems[] = 1;
$perfItems[] = 2;
$perfItems[] = 3;
$perfItems[] = 4;
$perfItems[] = 5;
$perfItems[] = 6;

$perfStart = dt_monotonic_ms();
$perfBatch = task_start($perfItems, 3, function (int $item): int {
	dt_sleep_ms(120);
	return $item;
});
$perfStartedAfter = dt_monotonic_ms() - $perfStart;
$perfJoined = task_join($perfBatch);
$perfTotal = dt_monotonic_ms() - $perfStart;
echo "perf:", $perfStartedAfter, ",", $perfTotal, ",", count($perfJoined), "\n";

task_set_worker_pool_size(0);
PHS
 . "\n");

			$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime', '--no-stan'], $project, 120);
			$this->assertSame(0, $run['exit_code'], "task_run project should build and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
			$this->assertContains("2,4,6\n", $run['stdout'], 'task_run should preserve vector order and return callback results');
			$this->assertContains("publish:3,100,200,300,", $run['stdout'], 'task_run_publish should publish ordered worker batches without returning a value vector');
			$this->assertContains("publish-metrics:3,", $run['stdout'], 'task_run_publish should expose publish diagnostics for the latest publish run');
			$this->assertContains("publish-capped:3,1000,2000,3000,2\n", $run['stdout'], 'task_run_publish should cap publish callback batch size independently of timeout');
			$this->assertContains("5,10,15\n", $run['stdout'], 'task_run should support coordinator-side vector custom index callbacks');
			$this->assertContains("7,14,21\n", $run['stdout'], 'task_run should support string keys from vector custom index callbacks');
			$this->assertContains("3\n", $run['stdout'], 'task_run should return null placeholders for successful void vector callbacks');
			$this->assertContains("100,51,52,53\n", $run['stdout'], 'task_run should append vector results into a pre-populated target vector');
			$this->assertContains("61,62,63\n", $run['stdout'], 'task_run should write vector custom-index results into a pre-populated target hash and overwrite existing keys');
			$this->assertContains("71,72,73\n", $run['stdout'], 'task_run should append nullable int custom-index null keys into an integer-key target hash');
			$this->assertContains("900,81,82,83\n", $run['stdout'], 'task_run should append nullable int custom-index null keys into a target vector');
			$this->assertContains("14,15\n", $run['stdout'], 'task_run should preserve typed hash keys and return callback results');
			$this->assertContains("16,20\n", $run['stdout'], 'task_run should support coordinator-side hash custom index callbacks');
			$this->assertContains("104,105,999\n", $run['stdout'], 'task_run should write hash results into a pre-populated target while preserving original keys');
			$this->assertContains("2000,204,205\n", $run['stdout'], 'task_run should append nullable custom-index null keys from hash input into an integer-key target');
			$this->assertContains("5,7\n", $run['stdout'], 'task_run should accept packed mixed input as a vector-like collection');
			$this->assertContains("9,12\n", $run['stdout'], 'task_run should accept associative mixed input as a hash-like collection');
			$this->assertContains("8\n", $run['stdout'], 'task_run should pass a task_context to two-argument worker callbacks');
			$this->assertContains("10,92,30\n", $run['stdout'], 'task_run should allow the error callback to replace a failed item result');
			$this->assertContains("101\n", $run['stdout'], 'task_run should allow the error callback to replace a timed-out item result');
			$this->assertContains("3,11\n", $run['stdout'], "task_run should expose task_error fields and preserve hash keys for replacement results:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
			$this->assertContains(",2,9,10,9,1\n", $run['stdout'], 'task_start should expose linked progress and task_join should return cached results after completion');
			$this->assertContains("2,0,0,0,0\n", $run['stdout'], 'task_progress should expose completed success counters after background join and leave cancel-after-done as a no-op');
			$this->assertContains("1,3,1,11\n", $run['stdout'], 'task_cancel should request cooperative stop and join partial background vector results');
			$this->assertContains("1,2,0,0\n", $run['stdout'], 'task_progress should expose partial counters after cooperative cancellation');
			$this->assertContains("bg-working,bg-working,6\n", $run['stdout'], 'task_status and task_progress should expose worker status from a live background batch');
			$this->assertContains("4,42\n", $run['stdout'], 'task_start should accept optional error handler and timeout arguments');
			$this->assertContains("2,33,36\n", $run['stdout'], 'task_start should support background hash batches and preserve keys on join');
			$this->assertContains("300,77,78\n", $run['stdout'], 'task_start should append background vector results into a pre-populated target vector');
			$this->assertContains("81,82\n", $run['stdout'], 'task_start should write background hash results into a pre-populated target hash');
			$this->assertContains("93,94\n", $run['stdout'], 'task_start should write background custom-index vector results into a pre-populated target hash');
			$this->assertContains("105,106\n", $run['stdout'], 'task_start should write background custom-index hash results into a pre-populated target hash');
			$this->assertContains("119,120\n", $run['stdout'], 'task_start should append nullable custom-index null keys into a background target vector');
			$this->assertContains("30,3,132\n", $run['stdout'], 'task_start should append handled error replacements into a background result target');
			$this->assertTaskStartPerformanceShape($run['stdout'], $run['stderr']);

			$generated = $this->read($project . '/.prism/generated/main.cpp');
			$this->assertContains('tasks::run', $generated, 'strict task_run source call should resolve through the tasks runtime registry');
			$this->assertContains('tasks::run_publish', $generated, 'strict task_run_publish source call should resolve through the tasks runtime registry');
			$this->assertContains('tasks::configure_default_worker_pool', $generated, 'strict task_set_worker_pool_size source call should resolve through the tasks runtime registry');
			$this->assertContains('tasks::configure_publish_try_lock', $generated, 'strict task_set_publish_try_lock source call should resolve through the tasks runtime registry');
			$this->assertContains('tasks::publish_lock_hold_us', $generated, 'strict task publish metric source calls should resolve through the tasks runtime registry');
			$strictRuntimeSymbols = $this->read(resolve_repo_root() . '/runtime/generated/stan/runtime_symbols_strict.phs');
			$this->assertContains('function task_start(mixed $items, int $workers, mixed $exec', $strictRuntimeSymbols, 'strict runtime shallow source should expose the shaped task_start signature');
			$this->assertContains('function task_run_publish(mixed $items, int $workers, mixed $work, mixed $publish, mixed $error = null, int $timeout_ms = 0, int $max_publish_batch_size = 0): int', $strictRuntimeSymbols, 'strict runtime shallow source should expose the shaped task_run_publish signature with publish cap');
			$this->assertContains('mixed $result = null', $strictRuntimeSymbols, 'strict runtime shallow source should name the fifth task argument result');
			$this->assertContains('function task_progress(task_batch $batch): task_progress_info', $strictRuntimeSymbols, 'strict runtime shallow source should expose typed task_progress handles');
			$this->assertContains('function task_set_worker_pool_size(int $workers): void', $strictRuntimeSymbols, 'strict runtime shallow source should expose task worker pool sizing');
			$this->assertContains('function task_set_publish_try_lock(bool $enabled): void', $strictRuntimeSymbols, 'strict runtime shallow source should expose task publish try-lock diagnostics control');
			$this->assertContains('function task_publish_lock_hold_us(): int', $strictRuntimeSymbols, 'strict runtime shallow source should expose task publish metrics');
			$this->assertContains('public function stop_requested(): bool', $strictRuntimeSymbols, 'strict runtime shallow source should expose task progress stop_requested');
			$this->assertContains('public function status(): string', $strictRuntimeSymbols, 'strict runtime shallow source should expose task progress status');
			$this->assertContains('class task_error', $strictRuntimeSymbols, 'strict runtime shallow source should expose the task_error handle shape');
			$this->assertContains('public mixed $key;', $strictRuntimeSymbols, 'strict runtime shallow source should expose task_error key');
			$this->assertContains('public bool $timeout;', $strictRuntimeSymbols, 'strict runtime shallow source should expose task_error timeout flag');

			$this->assertDisabledModuleFailsClearly();
			$this->assertTaskRunUnhandledErrorReportsClearly();
			$this->assertTaskStartJoinErrorReportsClearly();
			$this->assertTaskStartJoinErrorRepeatsClearly();
			$this->assertTaskStartTimeoutReportsClearly();
			$this->assertInvalidTaskBatchReportsClearly();
			$this->assertTaskRunDuplicateCustomIndexOverwritesClearly();
			$this->assertTaskRunDuplicateStringCustomIndexOverwritesClearly();
			$this->assertTaskRunDuplicateHashCustomIndexOverwritesClearly();
			$this->assertTaskRunNegativeVectorResultKeyReportsClearly();
			$this->assertTaskRunSparseVectorResultKeyReportsClearly();
			$this->assertTaskRunResultTargetErrorHandlerWritesReplacement();
			$this->assertTaskRunIndexedResultTargetErrorHandlerWritesReplacement();
			$this->assertTaskStartNegativeVectorResultKeyReportsClearly();
			$this->assertTaskStartProgressAfterWorkerErrorReportsClearly();
			$this->assertTaskRunCustomIndexTimeoutReportsClearly();
			$this->assertTaskRunCustomIndexWorkerErrorReportsClearly();
			$this->assertTaskRunTimeoutReportsClearly();
			$this->assertTaskRunMixedScalarInputReportsClearly();
			$this->assertConfiguredWorkerPoolBuildConfig();
			$this->assertReusableWorkerPoolReusesWorkers();

			echo "PASS: scpp tasks module\n";
			return 0;
		} finally {
			$this->removeTree($this->root);
		}
	}

	private function assertDisabledModuleFailsClearly(): void
	{
		$project = $this->root . '/disabled-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-module-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$result = task_run($items, 1, function (int $item): int {
	return $item;
});

echo $result[0], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'task_run without tasks module should fail clearly');
		$this->assertContains('Operation: task_run', $run['stderr'], "disabled tasks module should identify the failing task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved disabled tasks module diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $error['stdout'], 'saved diagnostic should include the raw missing tasks module message');

		$poolProject = $this->root . '/disabled-pool-app';
		$this->mkdir($poolProject);
		$this->write($poolProject . '/prism.json', json_encode([
			'name' => 'tasks-pool-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($poolProject . '/main.phs', <<<'PHS'
task_set_worker_pool_size(2);
echo "unreachable\n";
PHS
 . "\n");

		$poolRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $poolProject, 120);
		$this->assertNotSame(0, $poolRun['exit_code'], 'task_set_worker_pool_size without tasks module should fail clearly');
		$this->assertContains('Operation: task_set_worker_pool_size', $poolRun['stderr'], "disabled task_set_worker_pool_size should identify the failing task operation:\nSTDOUT:\n" . $poolRun['stdout'] . "\nSTDERR:\n" . $poolRun['stderr']);

		$poolError = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $poolProject, 30);
		$this->assertSame(0, $poolError['exit_code'], 'scpp error should read the saved disabled task_set_worker_pool_size diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $poolError['stdout'], 'saved task_set_worker_pool_size diagnostic should include the raw missing tasks module message');

		$indexedProject = $this->root . '/disabled-indexed-app';
		$this->mkdir($indexedProject);
		$this->write($indexedProject . '/prism.json', json_encode([
			'name' => 'tasks-indexed-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($indexedProject . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$result = task_run(
	$items,
	1,
	function (int $item): int {
		return $item;
	},
	function (int $item): string {
		return "item-" . $item;
	},
	null,
	null,
	0
);

echo $result["item-1"], "\n";
PHS
 . "\n");

		$indexedRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $indexedProject, 120);
		$this->assertNotSame(0, $indexedRun['exit_code'], 'indexed task_run without tasks module should fail clearly');
		$this->assertContains('Operation: task_run', $indexedRun['stderr'], "disabled indexed task_run should identify the failing task operation:\nSTDOUT:\n" . $indexedRun['stdout'] . "\nSTDERR:\n" . $indexedRun['stderr']);

		$indexedError = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $indexedProject, 30);
		$this->assertSame(0, $indexedError['exit_code'], 'scpp error should read the saved disabled indexed task_run diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $indexedError['stdout'], 'saved indexed diagnostic should include the raw missing tasks module message');

		$targetProject = $this->root . '/disabled-target-app';
		$this->mkdir($targetProject);
		$this->write($targetProject . '/prism.json', json_encode([
			'name' => 'tasks-target-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($targetProject . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$target hash<int> = [];
$result = task_run(
	$items,
	1,
	function (int $item): int {
		return $item;
	},
	function (int $item): string {
		return "item-" . $item;
	},
	$target,
	null
);

echo $result["item-1"], "\n";
PHS
 . "\n");

		$targetRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $targetProject, 120);
		$this->assertNotSame(0, $targetRun['exit_code'], 'target-result task_run without tasks module should fail clearly');
		$this->assertContains('Operation: task_run', $targetRun['stderr'], "disabled target-result task_run should identify the failing task operation:\nSTDOUT:\n" . $targetRun['stdout'] . "\nSTDERR:\n" . $targetRun['stderr']);

		$targetError = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $targetProject, 30);
		$this->assertSame(0, $targetError['exit_code'], 'scpp error should read the saved disabled target-result task_run diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $targetError['stdout'], 'saved target-result diagnostic should include the raw missing tasks module message');

		$startProject = $this->root . '/disabled-start-app';
		$this->mkdir($startProject);
		$this->write($startProject . '/prism.json', json_encode([
			'name' => 'tasks-start-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($startProject . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$batch = task_start($items, 1, function (int $item): int {
	return $item;
});

$result = task_join($batch);
echo $result[0], "\n";
PHS
 . "\n");

		$startRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $startProject, 120);
		$this->assertNotSame(0, $startRun['exit_code'], 'task_start without tasks module should fail clearly');
		$this->assertContains('Operation: task_start', $startRun['stderr'], "disabled tasks module should identify task_start as the failing operation:\nSTDOUT:\n" . $startRun['stdout'] . "\nSTDERR:\n" . $startRun['stderr']);

		$startError = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $startProject, 30);
		$this->assertSame(0, $startError['exit_code'], 'scpp error should read the saved disabled task_start diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $startError['stdout'], 'saved task_start diagnostic should include the raw missing tasks module message');

		$targetStartProject = $this->root . '/disabled-target-start-app';
		$this->mkdir($targetStartProject);
		$this->write($targetStartProject . '/prism.json', json_encode([
			'name' => 'tasks-target-start-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($targetStartProject . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$target hash<int> = [];
$batch = task_start(
	$items,
	1,
	function (int $item): int {
		return $item;
	},
	function (int $item): string {
		return "item-" . $item;
	},
	$target,
	null
);

$result = task_join($batch);
echo $result["item-1"], "\n";
PHS
 . "\n");

		$targetStartRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $targetStartProject, 120);
		$this->assertNotSame(0, $targetStartRun['exit_code'], 'target-result task_start without tasks module should fail clearly');
		$this->assertContains('Operation: task_start', $targetStartRun['stderr'], "disabled target-result task_start should identify task_start as the failing operation:\nSTDOUT:\n" . $targetStartRun['stdout'] . "\nSTDERR:\n" . $targetStartRun['stderr']);

		$targetStartError = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $targetStartProject, 30);
		$this->assertSame(0, $targetStartError['exit_code'], 'scpp error should read the saved disabled target-result task_start diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $targetStartError['stdout'], 'saved target-result task_start diagnostic should include the raw missing tasks module message');

		$hashStartProject = $this->root . '/disabled-hash-start-app';
		$this->mkdir($hashStartProject);
		$this->write($hashStartProject . '/prism.json', json_encode([
			'name' => 'tasks-hash-start-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($hashStartProject . '/main.phs', <<<'PHS'
$items hash<int> = [];
$items["one"] = 1;

$batch = task_start($items, 1, function (int $item): int {
	return $item;
});

$result = task_join($batch);
echo $result["one"], "\n";
PHS
 . "\n");

		$hashStartRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $hashStartProject, 120);
		$this->assertNotSame(0, $hashStartRun['exit_code'], 'hash task_start without tasks module should fail clearly');
		$this->assertContains('Operation: task_start', $hashStartRun['stderr'], "disabled hash task_start should identify task_start as the failing operation:\nSTDOUT:\n" . $hashStartRun['stdout'] . "\nSTDERR:\n" . $hashStartRun['stderr']);

		$hashStartError = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $hashStartProject, 30);
		$this->assertSame(0, $hashStartError['exit_code'], 'scpp error should read the saved disabled hash task_start diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $hashStartError['stdout'], 'saved hash task_start diagnostic should include the raw missing tasks module message');

		$hashTargetStartProject = $this->root . '/disabled-hash-target-start-app';
		$this->mkdir($hashTargetStartProject);
		$this->write($hashTargetStartProject . '/prism.json', json_encode([
			'name' => 'tasks-hash-target-start-disabled-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($hashTargetStartProject . '/main.phs', <<<'PHS'
$items hash<int> = [];
$items["one"] = 1;

$target hash<int> = [];
$batch = task_start(
	$items,
	1,
	function (int $item): int {
		return $item;
	},
	function (int $item): string {
		return "item-" . $item;
	},
	$target,
	null
);

$result = task_join($batch);
echo $result["item-1"], "\n";
PHS
 . "\n");

		$hashTargetStartRun = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $hashTargetStartProject, 120);
		$this->assertNotSame(0, $hashTargetStartRun['exit_code'], 'hash target-result task_start without tasks module should fail clearly');
		$this->assertContains('Operation: task_start', $hashTargetStartRun['stderr'], "disabled hash target-result task_start should identify task_start as the failing operation:\nSTDOUT:\n" . $hashTargetStartRun['stdout'] . "\nSTDERR:\n" . $hashTargetStartRun['stderr']);

		$hashTargetStartError = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $hashTargetStartProject, 30);
		$this->assertSame(0, $hashTargetStartError['exit_code'], 'scpp error should read the saved disabled hash target-result task_start diagnostic');
		$this->assertContains('tasks runtime module is not enabled', $hashTargetStartError['stdout'], 'saved hash target-result task_start diagnostic should include the raw missing tasks module message');
	}

	private function assertTaskRunTimeoutReportsClearly(): void
	{
		$project = $this->root . '/timeout-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-timeout-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run($items, 1, function (int $item): int {
	if ($item === 1) {
		dt_sleep_ms(20);
	}
	return $item;
}, null, null, null, 1);

echo $result[0], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'task_run timeout should fail the run');
		$this->assertContains('Operation: task_run', $run['stderr'], "task_run timeout should identify the failing task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved task timeout diagnostic');
		$this->assertContains('task batch timed out', $error['stdout'], 'saved diagnostic should include the raw timeout message');
	}

	private function assertTaskRunMixedScalarInputReportsClearly(): void
	{
		$project = $this->root . '/mixed-scalar-input-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-mixed-scalar-input-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items mixed = 123;

$result = task_run($items, 1, function (mixed $item): mixed {
	return $item;
});

echo $result[0], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'task_run scalar mixed input should fail clearly');
		$this->assertContains('Operation: task_run', $run['stderr'], "scalar mixed task input should identify the failing task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved scalar mixed input diagnostic');
		$this->assertContains('mixed/dynamic input must resolve', $error['stdout'], "saved scalar mixed input diagnostic should explain the collection shape requirement:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertReusableWorkerPoolReusesWorkers(): void
	{
		$project = $this->root . '/reusable-worker-pool-app';
		$this->mkdir($project . '/native_cpp');
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-reusable-worker-pool-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'native_cpp_dir' => 'native_cpp',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
echo "pool-probe\n";
PHS
 . "\n");
		$this->write($project . '/native_cpp/pool_probe.cpp', <<<'CPP'
#include "scpp/tasks.hpp"

#include <atomic>
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <exception>
#include <thread>

namespace {

[[noreturn]] void fail_pool_probe(const char *message)
{
	std::fprintf(stderr, "task pool probe failed: %s\n", message);
	std::abort();
}

void assert_pool_probe(bool condition, const char *message)
{
	if (!condition) {
		fail_pool_probe(message);
	}
}

struct reusable_worker_pool_probe final {
	reusable_worker_pool_probe()
	{
		using namespace scpp;

		tasks::shutdown_default_worker_pool();

		vector_t<int_t<>> items;
		items.push_back(int_t<>(1));
		items.push_back(int_t<>(2));
		items.push_back(int_t<>(3));
		items.push_back(int_t<>(4));

		auto run_repeated_batches = [&]() -> std::int64_t {
			const auto start = std::chrono::steady_clock::now();
			for (int round = 0; round < 12; ++round) {
				auto result = tasks::run(items, int_t<>(4), [](int_t<> item) -> int_t<> {
					return int_t<>(item.native_value() + 10);
				});
				assert_pool_probe(result.size() == 4, "task_run benchmark batch should return every item");
				assert_pool_probe(result.at(0).native_value() == 11, "task_run benchmark batch should preserve vector result order");
			}
			return std::chrono::duration_cast<std::chrono::milliseconds>(std::chrono::steady_clock::now() - start).count();
		};

		const auto local_batch_ms = run_repeated_batches();
		assert_pool_probe(tasks::default_worker_pool_created_workers().native_value() == 0, "unconfigured batches should use batch-local workers outside the reusable pool");

		tasks::configure_default_worker_pool(int_t<>(4));
		const auto pooled_batch_ms = run_repeated_batches();
		const auto pooled_created = tasks::default_worker_pool_created_workers().native_value();
		assert_pool_probe(pooled_created == 4, "pooled benchmark should create one keepalive worker per configured slot");
		std::printf("pool-benchmark:%lld,%lld,%lld\n",
			static_cast<long long>(local_batch_ms),
			static_cast<long long>(pooled_batch_ms),
			static_cast<long long>(pooled_created));

		tasks::shutdown_default_worker_pool();
		tasks::configure_default_worker_pool(int_t<>(2));
		assert_pool_probe(tasks::default_worker_pool_size().native_value() == 2, "configured pool should report requested keepalive size");
		assert_pool_probe(tasks::default_worker_pool_created_workers().native_value() == 2, "configuring two keepalive workers should create two workers");

		for (int round = 0; round < 4; ++round) {
			auto result = tasks::run(items, int_t<>(4), [](int_t<> item) -> int_t<> {
				return int_t<>(item.native_value() + 10);
			});
			assert_pool_probe(result.size() == 4, "pooled task_run should return every item");
			assert_pool_probe(result.at(0).native_value() == 11, "pooled task_run should preserve vector result order");
		}

		assert_pool_probe(tasks::default_worker_pool_created_workers().native_value() == 2, "repeated batches should reuse configured keepalive workers");

		std::atomic<int> completed_items{0};
		std::exception_ptr worker_error = nullptr;
		std::thread live_batch([&]() {
			try {
				auto result = tasks::run(items, int_t<>(4), [&](int_t<> item) -> int_t<> {
					std::this_thread::sleep_for(std::chrono::milliseconds(30));
					completed_items.fetch_add(1, std::memory_order_relaxed);
					return item;
				});
				assert_pool_probe(result.size() == 4, "live pooled batch should complete after keepalive reduction");
			} catch (...) {
				worker_error = std::current_exception();
			}
		});

		std::this_thread::sleep_for(std::chrono::milliseconds(5));
		tasks::configure_default_worker_pool(int_t<>(0));
		live_batch.join();
		if (worker_error) {
			std::rethrow_exception(worker_error);
		}
		assert_pool_probe(completed_items.load(std::memory_order_relaxed) == 4, "reducing keepalive workers should not interrupt live worker closures");

		for (int attempt = 0; attempt < 100 && tasks::default_worker_pool_live_workers().native_value() != 0; ++attempt) {
			std::this_thread::sleep_for(std::chrono::milliseconds(2));
		}
		assert_pool_probe(tasks::default_worker_pool_live_workers().native_value() == 0, "idle workers should retire after keepalive count is reduced to zero");
	}

	~reusable_worker_pool_probe()
	{
		scpp::tasks::shutdown_default_worker_pool();
	}
};

reusable_worker_pool_probe probe;

} // namespace
CPP
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "reusable worker pool native probe should build and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("pool-probe\n", $run['stdout'], 'reusable worker pool probe project should reach the PHS entrypoint');
		$this->assertPoolBenchmarkShape($run['stdout'], $run['stderr']);
	}

	private function assertConfiguredWorkerPoolBuildConfig(): void
	{
		$config = resolve_runtime_build_config([
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
				'tasks' => [
					'default_worker_pool_size' => 2,
				],
			],
		]);
		$this->assertSame(2, runtime_tasks_default_worker_pool_size($config), 'runtime.tasks.default_worker_pool_size should normalize into the runtime config');

		$project = $this->root . '/configured-worker-pool-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-configured-worker-pool-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
				'tasks' => [
					'default_worker_pool_size' => 2,
				],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run($items, 2, function (int $item): int {
	return $item + 20;
});

echo $result[0], ",", $result[1], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime', '--no-stan'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "configured worker pool project should build and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("21,22\n", $run['stdout'], 'configured worker pool project should preserve task_run behavior without a source-level pool helper call');

		$ninja = $this->read($project . '/.prism/build/build.ninja');
		$this->assertContains('-DSCPP_TASKS_DEFAULT_WORKER_POOL_SIZE=2', $ninja, 'configured worker pool size should be compiled into the project-local tasks runtime');
	}

	private function assertPoolBenchmarkShape(string $stdout, string $stderr): void
	{
		if (!preg_match('/^pool-benchmark:(\d+),(\d+),(\d+)$/m', $stdout, $matches)) {
			throw new RuntimeException("task worker pool benchmark did not print metrics:\nSTDOUT:\n" . $stdout . "\nSTDERR:\n" . $stderr);
		}
		$this->assertSame(4, (int) $matches[3], 'task worker pool benchmark should report the configured keepalive worker count');
	}

	private function assertTaskStartPerformanceShape(string $stdout, string $stderr): void
	{
		if (!preg_match('/^perf:(\d+),(\d+),(\d+)$/m', $stdout, $matches)) {
			throw new RuntimeException("task_start performance probe did not print metrics:\nSTDOUT:\n" . $stdout . "\nSTDERR:\n" . $stderr);
		}
		$startedAfterMs = (int) $matches[1];
		$totalMs = (int) $matches[2];
		$count = (int) $matches[3];
		$this->assertSame(6, $count, 'task_start performance probe should join all results');
		if ($startedAfterMs >= 180) {
			throw new RuntimeException("task_start should return before worker sleep completes; start took {$startedAfterMs}ms:\nSTDOUT:\n" . $stdout . "\nSTDERR:\n" . $stderr);
		}
		if ($totalMs >= 600) {
			throw new RuntimeException("task_start join should reflect parallel work, not serial sleep time; total took {$totalMs}ms:\nSTDOUT:\n" . $stdout . "\nSTDERR:\n" . $stderr);
		}
	}

	private function assertTaskRunDuplicateCustomIndexOverwritesClearly(): void
	{
		$project = $this->root . '/duplicate-index-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-duplicate-index-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		return $item * 2;
	},
	function (int $item): int {
		return 7;
	},
	null,
	null,
	0
);

echo $result[7], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "task_run duplicate custom index should overwrite and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("4\n", $run['stdout'], "duplicate custom task index should keep the later value:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
	}

	private function assertTaskRunDuplicateStringCustomIndexOverwritesClearly(): void
	{
		$project = $this->root . '/duplicate-string-index-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-duplicate-string-index-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		return $item * 2;
	},
	function (int $item): string {
		return "same";
	},
	null,
	null
);

echo $result["same"], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "task_run duplicate string custom index should overwrite and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("4\n", $run['stdout'], "duplicate string custom task index should keep the later value:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
	}

	private function assertTaskRunDuplicateHashCustomIndexOverwritesClearly(): void
	{
		$project = $this->root . '/duplicate-hash-index-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-duplicate-hash-index-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items hash<int> = [];
$items["a"] = 1;
$items["b"] = 2;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		return $item * 2;
	},
	function (int $item): string {
		return "same";
	},
	null,
	null
);

echo $result["same"], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "task_run duplicate hash custom index should overwrite and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("4\n", $run['stdout'], "duplicate hash custom task index should keep the later value:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
	}

	private function assertTaskRunNegativeVectorResultKeyReportsClearly(): void
	{
		$project = $this->root . '/negative-vector-result-key-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-negative-vector-result-key-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$target vector<int> = [];
$result = task_run(
	$items,
	1,
	function (int $item): int {
		return $item * 2;
	},
	function (int $item): int {
		return -1;
	},
	$target,
	null
);

echo count($result), "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'negative vector result key should fail the run');
		$this->assertContains('Operation: task_run', $run['stderr'], "negative vector result key should identify the failing task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved negative vector result key diagnostic');
		$this->assertContains('negative vector result key', $error['stdout'], "saved diagnostic should include the raw negative-key message:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertTaskRunSparseVectorResultKeyReportsClearly(): void
	{
		$project = $this->root . '/sparse-vector-result-key-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-sparse-vector-result-key-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$target vector<int> = [];
$result = task_run(
	$items,
	1,
	function (int $item): int {
		return $item * 2;
	},
	function (int $item): int {
		return 2;
	},
	$target,
	null
);

echo count($result), "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'sparse vector result key should fail the run');
		$this->assertContains('Operation: task_run', $run['stderr'], "sparse vector result key should identify the failing task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved sparse vector result key diagnostic');
		$this->assertContains('sparse vector result key', $error['stdout'], "saved diagnostic should include the raw sparse-key message:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertTaskRunResultTargetErrorHandlerWritesReplacement(): void
	{
		$project = $this->root . '/result-target-error-handler-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-result-target-error-handler-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$target vector<int> = [];
$target[] = 9;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		if ($item === 2) {
			throw new Exception("replace me");
		}
		return $item * 2;
	},
	null,
	$target,
	function (int $item, task_error $error): int {
		return $item + 40;
	}
);

echo $result[0], ",", $result[1], ",", $result[2], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "task_run result target with error handler should run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("9,2,42\n", $run['stdout'], "task_run should append handled error replacements into the result target:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
	}

	private function assertTaskRunIndexedResultTargetErrorHandlerWritesReplacement(): void
	{
		$project = $this->root . '/indexed-result-target-error-handler-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-indexed-result-target-error-handler-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$target hash<int> = [];
$target["seed"] = 7;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		if ($item === 2) {
			throw new Exception("replace indexed");
		}
		return $item * 5;
	},
	function (int $item): string {
		return "item-" . $item;
	},
	$target,
	function (int $item, task_error $error): int {
		return $item + 140;
	}
);

echo $result["seed"], ",", $result["item-1"], ",", $result["item-2"], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "task_run indexed result target with error handler should run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("7,5,142\n", $run['stdout'], "task_run should key handled error replacements through the custom index into the result target:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
	}

	private function assertTaskStartNegativeVectorResultKeyReportsClearly(): void
	{
		$project = $this->root . '/background-negative-vector-result-key-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-background-negative-vector-result-key-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;

$target vector<int> = [];
$batch = task_start(
	$items,
	1,
	function (int $item): int {
		return $item;
	},
	function (int $item): int {
		return -1;
	},
	$target,
	null
);

$result = task_join($batch);
echo count($result), "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'background negative vector result key should fail the run');
		$this->assertContains('Operation: task_run', $run['stderr'], "background negative vector result key should identify the failing task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved background negative vector result key diagnostic');
		$this->assertContains('negative vector result key', $error['stdout'], "saved diagnostic should include the raw background negative-key message:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertTaskStartProgressAfterWorkerErrorReportsClearly(): void
	{
		$project = $this->root . '/background-error-progress-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-background-error-progress-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$batch = task_start($items, 1, function (int $item): int {
	if ($item === 2) {
		throw new Exception("progress failure");
	}
	return $item;
});

try {
	$ignored = task_join($batch);
	echo "missing\n";
} catch (Exception $e) {
	$progress = task_progress($batch);
	echo task_done($batch), ",", $progress->errors(), ",", $progress->stop_requested(), "\n";
}
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "background worker error progress project should build and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("1,1,1\n", $run['stdout'], "task_progress should remain readable after a failed background join:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
	}

	private function assertTaskRunCustomIndexTimeoutReportsClearly(): void
	{
		$project = $this->root . '/indexed-timeout-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-indexed-timeout-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run(
	$items,
	1,
	function (int $item): int {
		if ($item === 1) {
			dt_sleep_ms(20);
		}
		return $item;
	},
	function (int $item): string {
		return "item-" . $item;
	},
	null,
	null,
	1
);

echo $result["item-1"], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'indexed task_run timeout should fail the run');
		$this->assertContains('Operation: task_run', $run['stderr'], "indexed task_run timeout should identify the failing task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved indexed task timeout diagnostic');
		$this->assertContains('task batch timed out', $error['stdout'], "saved indexed timeout diagnostic should include the raw timeout message:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertTaskRunCustomIndexWorkerErrorReportsClearly(): void
	{
		$project = $this->root . '/indexed-worker-error-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-indexed-worker-error-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		if ($item === 2) {
			$zero = 0;
			return $item / $zero;
		}
		return $item;
	},
	function (int $item): string {
		return "item-" . $item;
	},
	null,
	null,
	0
);

echo $result["item-1"], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'indexed task_run worker error should fail the run');
		$this->assertContains('Operation: /', $run['stderr'], "indexed task_run worker error should report the original failing operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved indexed worker diagnostic');
		$this->assertContains('division_by_zero', $error['stdout'], "saved indexed worker diagnostic should include the worker failure category:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertTaskRunUnhandledErrorReportsClearly(): void
	{
		$project = $this->root . '/unhandled-error-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-unhandled-error-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run($items, 2, function (int $item): int {
	if ($item === 2) {
		$zero = 0;
		return $item / $zero;
	}
	return $item;
});

echo $result[0], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'task_run unhandled worker error should fail the run');
		$this->assertContains('Operation: /', $run['stderr'], "task_run unhandled worker error should report the original failing operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved unhandled worker diagnostic');
		$this->assertContains('division_by_zero', $error['stdout'], "saved diagnostic should include the worker failure category:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertTaskStartJoinErrorReportsClearly(): void
	{
		$project = $this->root . '/background-error-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-background-error-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$batch = task_start($items, 2, function (int $item): int {
	if ($item === 2) {
		$zero = 0;
		return $item / $zero;
	}
	return $item;
});

$result = task_join($batch);
echo $result[0], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'task_join should report background worker errors');
		$this->assertContains('Operation: /', $run['stderr'], "task_join should report the original background worker operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved background worker diagnostic');
		$this->assertContains('division_by_zero', $error['stdout'], "saved diagnostic should include the background worker failure category:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertTaskStartJoinErrorRepeatsClearly(): void
	{
		$project = $this->root . '/background-repeated-error-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-background-repeated-error-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$batch = task_start($items, 2, function (int $item): int {
	if ($item === 2) {
		throw new Exception("repeatable background failure");
	}
	return $item;
});

try {
	$ignoredFirst = task_join($batch);
	echo "missing-first\n";
} catch (Exception $e) {
	echo "first\n";
}

try {
	$ignoredSecond = task_join($batch);
	echo "missing-second\n";
} catch (Exception $e) {
	echo "second\n";
}
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertSame(0, $run['exit_code'], "repeated task_join background error project should build and run:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
		$this->assertContains("first\nsecond\n", $run['stdout'], "task_join should rethrow cached background worker errors on repeated joins:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);
	}

	private function assertTaskStartTimeoutReportsClearly(): void
	{
		$project = $this->root . '/background-timeout-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-background-timeout-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$batch = task_start($items, 1, function (int $item): int {
	if ($item === 1) {
		dt_sleep_ms(20);
	}
	return $item;
}, null, null, null, 1);

$result = task_join($batch);
echo $result[0], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'task_join should report background timeout');
		$this->assertContains('Operation: task_run', $run['stderr'], "background timeout should identify the originating task operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved background timeout diagnostic');
		$this->assertContains('task batch timed out', $error['stdout'], "saved diagnostic should include the raw background timeout message:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	private function assertInvalidTaskBatchReportsClearly(): void
	{
		$project = $this->root . '/invalid-batch-app';
		$this->mkdir($project);
		$this->write($project . '/prism.json', json_encode([
			'name' => 'tasks-invalid-batch-regression',
			'entrypoint' => 'main.phs',
			'build_dir' => '.prism/build',
			'runtime' => [
				'languages' => [
					'php' => ['profile' => 'strict'],
				],
				'modules' => ['json', 'filesystem', 'datetime', 'tasks'],
			],
		], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
		$this->write($project . '/main.phs', <<<'PHS'
$batch task_batch = null;
$result = task_join($batch);
echo $result[0], "\n";
PHS
 . "\n");

		$run = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'run', '--build-runtime'], $project, 120);
		$this->assertNotSame(0, $run['exit_code'], 'task_join should reject invalid task batches');
		$this->assertContains('Operation: task_join', $run['stderr'], "invalid task batch should identify the task_join operation:\nSTDOUT:\n" . $run['stdout'] . "\nSTDERR:\n" . $run['stderr']);

		$error = $this->runCommand([PHP_BINARY, resolve_repo_root() . '/bin/scpp.php', 'error'], $project, 30);
		$this->assertSame(0, $error['exit_code'], 'scpp error should read the saved invalid task batch diagnostic');
		$this->assertContains('invalid task batch', $error['stdout'], "saved diagnostic should include the invalid task batch message:\nSTDOUT:\n" . $error['stdout'] . "\nSTDERR:\n" . $error['stderr']);
	}

	/** @return array{exit_code:int,stdout:string,stderr:string} */
	private function runCommand(array $command, string $cwd, int $timeoutSeconds): array
	{
		$progress = getenv('SCPP_TASKS_TEST_PROGRESS') === '1';
		$label = basename($cwd);
		if ($progress) {
			fwrite(STDERR, '[tasks-test] start ' . $label . ': ' . implode(' ', $command) . PHP_EOL);
		}
		$descriptor = [
			0 => ['file', '/dev/null', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open($command, $descriptor, $pipes, $cwd, scpp_build_process_environment([
			'SCPP_CXX_LAUNCHER' => ' ',
		]));
		if (!is_resource($process)) {
			throw new RuntimeException('Failed to start command: ' . implode(' ', $command));
		}
		$stdout = '';
		$stderr = '';
		$started = microtime(true);
		$lastProgress = $started;
		$observedExitCode = null;
		foreach ([1, 2] as $index) {
			stream_set_blocking($pipes[$index], false);
		}
		while (true) {
			$status = proc_get_status($process);
			$stdout .= (string) stream_get_contents($pipes[1]);
			$stderr .= (string) stream_get_contents($pipes[2]);
			if (($status['running'] ?? false) !== true) {
				$exitCode = $status['exitcode'] ?? null;
				$observedExitCode = is_int($exitCode) ? $exitCode : null;
				break;
			}
			$now = microtime(true);
			if ($progress && ($now - $lastProgress) >= 10.0) {
				fwrite(STDERR, '[tasks-test] still running ' . $label . ' after ' . (int)($now - $started) . 's' . PHP_EOL);
				$lastProgress = $now;
			}
			if (($now - $started) > $timeoutSeconds) {
				proc_terminate($process);
				throw new RuntimeException('Timed out after ' . $timeoutSeconds . 's: ' . implode(' ', $command));
			}
			usleep(100000);
		}
		$stdout .= (string) stream_get_contents($pipes[1]);
		$stderr .= (string) stream_get_contents($pipes[2]);
		fclose($pipes[1]);
		fclose($pipes[2]);
		$exitCode = proc_close($process);
		if ($progress) {
			$finalExitCode = $observedExitCode ?? (is_int($exitCode) ? $exitCode : 1);
			fwrite(STDERR, '[tasks-test] done ' . $label . ': exit=' . $finalExitCode . ' elapsed=' . (int)(microtime(true) - $started) . 's' . PHP_EOL);
		}
		return [
			'exit_code' => $observedExitCode ?? (is_int($exitCode) ? $exitCode : 1),
			'stdout' => $stdout,
			'stderr' => $stderr,
		];
	}

	private function assertSame(mixed $expected, mixed $actual, string $message): void
	{
		if ($expected !== $actual) {
			throw new RuntimeException($message . ' expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
		}
	}

	private function assertNotSame(mixed $unexpected, mixed $actual, string $message): void
	{
		if ($unexpected === $actual) {
			throw new RuntimeException($message . ' unexpectedly got ' . var_export($actual, true));
		}
	}

	private function assertContains(string $needle, string $haystack, string $message): void
	{
		if (!str_contains($haystack, $needle)) {
			throw new RuntimeException($message . ' missing `' . $needle . '`');
		}
	}

	private function mkdir(string $path): void
	{
		if (!is_dir($path) && !mkdir($path, 0777, true)) {
			throw new RuntimeException('Failed to create ' . $path);
		}
	}

	private function write(string $path, string $contents): void
	{
		$dir = dirname($path);
		if (!is_dir($dir)) {
			$this->mkdir($dir);
		}
		if (file_put_contents($path, $contents) === false) {
			throw new RuntimeException('Failed to write ' . $path);
		}
	}

	private function read(string $path): string
	{
		$contents = file_get_contents($path);
		if (!is_string($contents)) {
			throw new RuntimeException('Failed to read ' . $path);
		}
		return $contents;
	}

	private function removeTree(string $path): void
	{
		if (!is_dir($path)) {
			return;
		}
		$items = scandir($path);
		if ($items === false) {
			return;
		}
		foreach ($items as $item) {
			if ($item === '.' || $item === '..') {
				continue;
			}
			$child = $path . '/' . $item;
			if (is_dir($child) && !is_link($child)) {
				$this->removeTree($child);
				continue;
			}
			@unlink($child);
		}
		@rmdir($path);
	}
}

$test = new ScppTasksModuleTest();
exit($test->run());
