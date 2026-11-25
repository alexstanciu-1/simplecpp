# Tasks examples
Doc Status: supporting

These examples show the first-pass strict PHP++ `tasks` module surface.

Release status: v1-alpha / experimental.
Use it for independent batch work over typed `vector`, typed `hash`, and table-shaped `mixed` / `dynamic` data.
Shared mutable object transfer, worker communication, STAN thread-safety enforcement, and thread-pool reuse are later work.

Enable the module in `prism.json`:

```json
{
	"runtime": {
		"languages": {
			"php": {
				"profile": "strict"
			}
		},
		"modules": ["json", "filesystem", "datetime", "tasks"]
	}
}
```

## Blocking batch

```php
$items vector<int> = [];
$items[] = 1;
$items[] = 2;
$items[] = 3;

$result = task_run($items, 2, function (int $item): int {
	return $item * 10;
});

echo $result[0], ",", $result[1], ",", $result[2], "\n";
```

## Background batch

```php
$items vector<int> = [];
$items[] = 4;
$items[] = 5;

$batch = task_start($items, 2, function (int $item): int {
	return $item + 1;
});

$progress = task_progress($batch);
echo $progress->total(), "\n";

$result = task_join($batch);
echo $result[0], ",", $result[1], "\n";
```

## Result target

```php
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$target vector<int> = [];
$target[] = 100;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		return $item + 10;
	},
	null,
	$target,
	null
);

echo $result[0], ",", $result[1], ",", $result[2], "\n";
```

## Custom index and nullable append

```php
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$target hash<int, int> = [];
$target[10] = 500;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		return $item + 20;
	},
	function (int $item): ?int {
		if ($item === 2) {
			return null;
		}
		return $item * 10;
	},
	$target,
	null
);

echo $result[10], ",", $result[11], "\n";
```

## Error replacement

```php
$items vector<int> = [];
$items[] = 1;
$items[] = 2;

$result = task_run(
	$items,
	2,
	function (int $item): int {
		if ($item === 2) {
			throw new Exception("bad item");
		}
		return $item * 2;
	},
	null,
	null,
	function (int $item, task_error $error): int {
		return $item + 40;
	}
);

echo $result[0], ",", $result[1], "\n";
```

## Timeout

```php
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
	null,
	null,
	null,
	1
);
```

Timeout is cooperative and post-item in the first pass.
The runtime checks the timeout after a worker callback returns and before a worker takes another item; it does not force-kill arbitrary user code while that code is running.

Timeout can be handled like an item error:

```php
$items vector<int> = [];
$items[] = 1;

$result = task_run(
	$items,
	1,
	function (int $item): int {
		dt_sleep_ms(20);
		return $item;
	},
	null,
	null,
	function (int $item, task_error $error): int {
		if ($error->timeout) {
			return $item + 100;
		}
		return $item;
	},
	5
);

echo $result[0], "\n";
```

## Mixed input

```php
$items mixed = [];
$items[] = 3;
$items[] = 4;

$result = task_run($items, 2, function (mixed $item): mixed {
	return $item * 3;
});

echo $result[0], ",", $result[1], "\n";
```

Only table-shaped `mixed` / `dynamic` input is accepted in the alpha surface.
Scalar `mixed` input is rejected because it is not a vector-like or hash-like collection.
