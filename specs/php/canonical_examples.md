# Simple C++ for PHP â€” Canonical Examples
Doc Status: normative
Status: Draft / AI anchor
Scope: Preferred PHP authoring patterns aligned with `specs/php/catalog.md` and `specs/runtime/catalog.md`

## 1. Purpose

These examples are canonical patterns for **Simple C++ for PHP**.

They are intended to:
- reinforce preferred authoring style
- align with runtime return-state semantics
- avoid ambiguous PHP shortcuts
- serve as AI conditioning examples

## 2. Format

Each example contains:
- **Preferred** â€” the canonical form
- **Avoid** â€” a weaker or ambiguous form
- **Notes** â€” the reason this pattern is preferred

---

# 3. Canonical Examples

## Example 1 â€” Strict equality for scalar comparison

**Preferred**
```php
$x = 10;

if ($x === 10) {
	echo "ok";
}
```

**Avoid**
```php
$x = 10;

if ($x == 10) {
	echo "ok";
}
```

**Notes**
- Prefer `===`
- Avoid loose comparison when exact state matters

---

## Example 2 â€” Explicit false check after query

**Preferred**
```php
$res = $db->query("SELECT id FROM users");

if ($res === false) {
	return;
}
```

**Avoid**
```php
$res = $db->query("SELECT id FROM users");

if (!$res) {
	return;
}
```

**Notes**
- `false` is a distinct failure state
- Do not rely on truthiness for falseable APIs

---

## Example 3 â€” Separate false from null

**Preferred**
```php
$value = find_something();

if ($value === false) {
	// failure
	return;
}

if ($value === null) {
	// absence
	return;
}
```

**Avoid**
```php
$value = find_something();

if (!$value) {
	return;
}
```

**Notes**
- `false` and `null` are not interchangeable
- Preserve runtime meaning explicitly

---

## Example 4 â€” Typed function boundary

**Preferred**
```php
function add(int $left, int $right): int {
	return $left + $right;
}
```

**Avoid**
```php
function add($left, $right) {
	return $left + $right;
}
```

**Notes**
- Prefer stable boundaries
- Explicit types improve lowering and runtime predictability

---

## Example 5 â€” Explicit nullable result handling

**Preferred**
```php
$row = $res->fetch_assoc();

if ($row === null) {
	return;
}

echo $row["id"];
```

**Avoid**
```php
$row = $res->fetch_assoc();

if (!$row) {
	return;
}

echo $row["id"];
```

**Notes**
- `fetch_assoc()` exhaustion is modeled as absence
- Avoid collapsing empty/dynamic/null states into one branch

---
## Example 5a â€” Do not use strings or mixed values directly as conditions

**Preferred**
```php
$status = get_status_text();

if ($status === "ready") {
	start_job();
}
```

**Avoid**
```php
$status = get_status_text();

if ($status) {
	start_job();
}
```

**Notes**
- String-to-bool intent must be made explicit
- Do not rely on `"0"`, `""`, or arbitrary non-empty strings as implicit condition values
- The same rule applies to `mixed` values: normalize first, then branch

---

## Example 6 â€” Array append with predictable intent

**Preferred**
```php
$items = [];
$items[] = 1;
$items[] = 2;
$items[] = 3;
```

**Avoid**
```php
$items = array();
array_push($items, 1, 2, 3);
```

**Notes**
- Prefer direct append syntax
- Keep array growth explicit and simple

---

## Example 7 â€” Mixed array with explicit key intent

**Preferred**
```php
$data = [];
$data["name"] = "Alex";
$data["age"] = 30;
```

**Avoid**
```php
$data = [];
$data[name] = "Alex";
$data[age] = 30;
```

**Notes**
- Use explicit string keys
- Avoid undefined-constant style shortcuts

---

## Example 8 â€” Foreach over dynamic structure

**Preferred**
```php
foreach ($rows as $row) {
	echo $row["id"], "\n";
}
```

**Avoid**
```php
for ($i = 0; $i < count($rows); $i++) {
	echo $rows[$i]["id"], "\n";
}
```

**Notes**
- Prefer direct iteration when sequence/object traversal is intended
- Avoid repeated `count()` / indexed access when not needed

---

## Example 9 â€” Explicit key existence vs emptiness

**Preferred**
```php
if (isset($row["id"])) {
	echo $row["id"];
}
```

**Avoid**
```php
if (!empty($row["id"])) {
	echo $row["id"];
}
```

**Notes**
- `isset()` checks presence/non-null
- `empty()` merges more states and should not be used casually

---

## Example 10 â€” Explicit empty check when that is truly the intent

**Preferred**
```php
if (empty($items)) {
	return;
}
```

**Avoid**
```php
if (!$items) {
	return;
}
```

**Notes**
- Use `empty()` only when emptiness semantics are intended
- Do not substitute generic truthiness for structural emptiness

---

## Example 11 â€” String search with explicit false handling

**Preferred**
```php
$pos = strpos($name, "Al");

if ($pos === false) {
	return;
}

echo $pos;
```

**Avoid**
```php
$pos = strpos($name, "Al");

if (!$pos) {
	return;
}

echo $pos;
```

**Notes**
- Position `0` is valid
- Truthiness breaks correct behavior

---

## Example 12 â€” File read with explicit failure handling

**Preferred**
```php
$content = file_get_contents($path);

if ($content === false) {
	return;
}

echo $content;
```

**Avoid**
```php
$content = file_get_contents($path);

if (!$content) {
	return;
}

echo $content;
```

**Notes**
- Empty file content is not the same as failure
- Keep falseable I/O explicit

---

## Example 13 â€” File write result check

**Preferred**
```php
$written = file_put_contents($path, $content);

if ($written === false) {
	return;
}

echo $written;
```

**Avoid**
```php
$written = file_put_contents($path, $content);

if (!$written) {
	return;
}

echo $written;
```

**Notes**
- `0` bytes written and `false` must remain distinct

---

## Example 14 â€” Realpath absence/failure contract

**Preferred**
```php
$resolved = realpath($path);

if ($resolved === false) {
	return;
}

echo $resolved;
```

**Avoid**
```php
$resolved = realpath($path);

if (!$resolved) {
	return;
}

echo $resolved;
```

**Notes**
- Preserve the documented sentinel state exactly
- Do not replace with generic truthiness

---

## Example 15 â€” Prepared statement with explicit step checks

**Preferred**
```php
$stmt = $db->prepare("SELECT id FROM users WHERE id = ?");

if ($stmt === false) {
	return;
}

if ($stmt->bind_param("i", $id) === false) {
	return;
}

if ($stmt->execute() === false) {
	return;
}

$result = $stmt->get_result();

if ($result === false) {
	return;
}
```

**Avoid**
```php
$stmt = $db->prepare("SELECT id FROM users WHERE id = ?");

if (!$stmt) {
	return;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
```

**Notes**
- Check each falseable step
- Do not assume success across API boundaries

---

## Example 16 â€” JSON decode as dynamic result

**Preferred**
```php
$data = json_decode($json);

if ($data === null) {
	return;
}

echo $data["name"];
```

**Avoid**
```php
$data = json_decode($json);

if (!$data) {
	return;
}

echo $data["name"];
```

**Notes**
- Preserve absence/failure rules as defined by the runtime contract
- Dynamic results still require explicit state handling

---

## Example 17 â€” Simple early return flow

**Preferred**
```php
function print_user_id($db): void {
	$res = $db->query("SELECT id FROM users");

	if ($res === false) {
		return;
	}

	$row = $res->fetch_assoc();

	if ($row === null) {
		return;
	}

	echo $row["id"];
}
```

**Avoid**
```php
function print_user_id($db) {
	$res = $db->query("SELECT id FROM users");
	if ($res) {
		$row = $res->fetch_assoc();
		if ($row) {
			echo $row["id"];
		}
	}
}
```

**Notes**
- Prefer flat, explicit early-return control flow
- Avoid nested truthiness pyramids

---

## Example 18 â€” String normalization before comparison

**Preferred**
```php
$email = trim(strtolower($email));

if ($email === "alex@example.com") {
	echo "match";
}
```

**Avoid**
```php
if (strtolower(trim($email)) == "alex@example.com") {
	echo "match";
}
```

**Notes**
- Prefer normalized intermediate values when reused or semantically important
- Still use strict comparison

---

## Example 19 â€” Count with explicit structure intent

**Preferred**
```php
$total = count($items);

if ($total === 0) {
	return;
}
```

**Avoid**
```php
if (!count($items)) {
	return;
}
```

**Notes**
- Prefer readable intermediate values when the count is meaningful
- Avoid burying structural checks inside negation

---

## Example 20 â€” Canonical query/fetch pattern

**Preferred**
```php
$res = $db->query("SELECT id, name FROM users");

if ($res === false) {
	return;
}

while (($row = $res->fetch_assoc()) !== null) {
	if (!isset($row["id"])) {
		continue;
	}

	echo $row["id"], ": ", $row["name"], "\n";
}
```

**Avoid**
```php
$res = $db->query("SELECT id, name FROM users");

if ($res) {
	while ($row = $res->fetch_assoc()) {
		echo $row["id"], ": ", $row["name"], "\n";
	}
}
```

**Notes**
- Query failure must be explicit
- Row exhaustion should be explicit
- Avoid assignment-in-condition patterns that hide state transitions

---

# 4. Summary Rules

These examples reinforce the following defaults:

1. Prefer `===` over `==`
2. Check `false` explicitly for falseable APIs
3. Check `null` explicitly for nullable results
4. Do not collapse value states through truthiness
5. Prefer typed/stable boundaries where possible
6. Prefer flat, explicit control flow
7. Prefer direct intent over compact PHP shortcuts

# 5. AI Rule

When generating PHP for Simple C++:
- use the **Preferred** form unless the user explicitly asks otherwise
- treat each return-state contract as normative
- do not introduce shorthand that merges `value`, `false`, and `null`
