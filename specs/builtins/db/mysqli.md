# mysqli module (initial runtime slice)
Doc Status: normative
Status: initial implementation slice

## Goal

Provide one optional runtime module with thin PHP-facing wrappers and a separate MariaDB/MySQL implementation core.

## Backend

- first backend: MariaDB Connector/C
- intended target: MariaDB and MySQL servers
- current build toggle: `SCPP_WITH_MYSQLI`

## Current wrapper classes

- `scpp::mysqli`
- `scpp::mysqli_result`
- `scpp::mysqli_stmt`

## Current supported surface

### `mysqli`
- constructor connects immediately
- `query(string_t)`
- `prepare(string_t)`
- `close()`
- `set_charset(string_t)`
- `begin_transaction()`
- `commit()`
- `rollback()`
- `connect_errno`
- `connect_error`
- `error`
- `errno_code`
- `insert_id`
- `affected_rows`

### `mysqli_result`
- `fetch_assoc()`
- `fetch_row()`
- `num_rows`

### `mysqli_stmt`
- `bind_param(types, ...values)`
- `execute()`
- `get_result()`
- `close()`
- `error`
- `errno_code`
- `insert_id`
- `affected_rows`

## Important deviations / notes

1. PHP-style sentinel/bool contracts are restored at the PHP exposure layer for the methods implemented here.
   - `query()` returns `result_or_bool<shared_p<mysqli_result>>`
     - `false` on error
     - `true` on successful non-result statements
     - `mysqli_result` on successful result-producing statements
   - `prepare()` returns `result_or_false<shared_p<mysqli_stmt>>`
   - `set_charset()`, `begin_transaction()`, `commit()`, `rollback()`, `bind_param()`, and `execute()` return `bool`
   - `get_result()` returns `result_or_false<shared_p<mysqli_result>>`
   - `fetch_assoc()` and `fetch_row()` remain intentionally excluded from this correction pass

2. Fetch methods currently return `dynamic_t` for both row styles.
   - `fetch_row()` => packed dynamic row
   - `fetch_assoc()` => associative dynamic row

3. Prepared statement binding captures values at execute time via stored getters.

4. `b` currently behaves like string binding.

5. Exact public member name `errno` was not used in this initial C++ slice because the C runtime macro `errno` collides with that identifier in headers and translation units.
   - current wrapper field name: `errno_code`
   - later transpiler/property aliasing can map PHP `$obj->errno` to that field if desired

## Future config shape

Intended project config direction:

```json
{
	"modules": {
		"mysqli": {
			"enabled": true,
			"backend": "mariadb"
		}
	}
}
```


## Dev environment / package detection notes

These notes are important for Ubuntu/Debian-like systems because MariaDB development packages are not always exposed through `pkg-config` under the same name.

### Ubuntu 24.04 confirmed path

For the tested Ubuntu 24.04 environment:

- runtime libraries were present as:
	- `libmysqlclient.so.21`
	- `libmariadb.so.3`
- header path was:
	- `/usr/include/mariadb/mysql.h`
- the `pkg-config` file installed by the dev package was:
	- `/usr/lib/x86_64-linux-gnu/pkgconfig/libmariadb.pc`

Important consequence:

- `pkg-config --cflags mariadb` may fail
- `pkg-config --libs mariadb` may fail
- `pkg-config --cflags libmariadb` is the correct probe in that environment
- `pkg-config --libs libmariadb` is the correct probe in that environment

### Recommended install check sequence

1. Check headers:

```bash
ls -l /usr/include/mariadb/mysql.h
```

2. Check installed runtime libs:

```bash
ldconfig -p | grep -E "mariadb|mysql"
```

3. Check the dev package `.pc` file:

```bash
find /usr -name "mariadb.pc" -o -name "libmariadb.pc" -o -name "mysqlclient.pc" 2>/dev/null
```

4. Prefer this `pkg-config` probe order:

```bash
pkg-config --cflags libmariadb
pkg-config --libs libmariadb
```

Fallbacks only if needed:

```bash
pkg-config --cflags mariadb
pkg-config --libs mariadb
pkg-config --cflags mysqlclient
pkg-config --libs mysqlclient
```

### Recommended package install

On Ubuntu/Debian, install:

```bash
apt update
apt install libmariadb-dev
```

Optional compatibility package if required by a specific environment:

```bash
apt install libmariadb-dev-compat
```

### CMake detection guidance

Do not assume the `pkg-config` module name is `mariadb`.

Preferred detection order:

1. `libmariadb`
2. `mariadb`
3. `mysqlclient`

This order matches the tested Ubuntu 24.04 environment and avoids repeating the earlier detection failure.

### public_html/test cache note

If `public_html/test/` starts failing with missing `scpp::mysqli` symbols after a patch that changes runtime source inclusion, clear the cached test runtime archive and rebuild:

```bash
rm -rf runtime/build/test_ui_cache
```

This matters in both default and ASAN modes because a stale cached archive can hide a correct fix.
