# PHP developer walkthroughs

These are small PHP-flavor applications and exploratory probes for the [developer QoS investigation](../../specs/planning/php_flavor_developer_qos.md). The note groups the findings by task. The executable source here is the reproducer. Only the file under `stock-php/` is a conventional PHP application.

## Build the providers

Run from the repository root on Linux with the [default native toolchain](../../README.md). The drivers use Python 3, PHP 8.5 on PATH as `php` and `build/clang/native-layout/facts.json`. The extended examples also need the development files for PCRE2, libcurl, libfcgi and MariaDB Connector/C. The service checks use the Debian/Ubuntu Apache module directory, `apache2`, `cgi-fcgi`, `mariadb-install-db`, `mariadbd` and `mariadb`. They create temporary services under the current unprivileged user. Run them on a host that permits Unix sockets and loopback HTTP.

```sh
cmake --preset default -DSCPP_ENABLE_FILESYSTEM=ON -DSCPP_ENABLE_JSON=ON \
  -DSCPP_ENABLE_IO=ON -DSCPP_ENABLE_REGEX=ON -DSCPP_ENABLE_DATETIME=ON \
  -DSCPP_ENABLE_FCGI=ON -DSCPP_ENABLE_CURL=ON -DSCPP_ENABLE_MYSQLI=ON
cmake --build --preset default --target scpp_filesystem scpp_json scpp_io \
  scpp_regex scpp_datetime scpp_fcgi scpp_curl scpp_mysqli
```

If the FastCGI SDK lives outside the system search paths, supply `SCPP_FCGI_INCLUDE` and `SCPP_FCGI_LIBRARY` when configuring. For a smaller first run, enable filesystem and JSON, build the two corresponding providers, then run the first command below.

## Run the examples

```sh
python3 -B samples/php-developer-qos/check_apps.py invoice-summary link-report catalog-view settings-patch
python3 -B samples/php-developer-qos/check_apps.py
python3 -B samples/php-developer-qos/input_corpus.py
```

The runner copies each project to a temporary directory, invokes the public compiler build command and executes the native program. It compares exit status and output for accepted and rejected inputs. The corpus adds generated input cases with independent controls. To inspect one program, open its `main.phs` and `module.json` side by side. The ticket report also builds `checked-count.phs` for immediate SQL parameters and `query-states.phs` for separate result-set and status paths. Both use temporary application manifests.

## Reproduce one finding

```sh
python3 -B samples/php-developer-qos/snippets.py strict-sentinel-hit strict-sentinel-miss guard-search
python3 -B samples/php-developer-qos/presence.py search environment class-handle
python3 -B samples/php-developer-qos/edit_cycle.py --ordinary-saves
```

Run `snippets.py` and `presence.py` with no arguments for every case. `type_positions.py` prints one tab-separated row per storage experiment. `compare_php.py` runs the byte-expression comparison and stock-PHP syntax controls. Each result distinguishes rejection at build time, a runtime failure and successful output. The snippet expectations describe observations at the investigated revision. A change to an expectation warrants review, particularly when a defect has been fixed. These drivers are outside the regression suite.

Use `snippets.py --show NAME` to print a snippet and its module settings. `compare_states.py` checks pairs of wrapper states across four comparison operators. `wire_shapes.py` compares boxed JSON, document observations and both stock PHP decode modes. `project_layout.py` tries recursive discovery, explicit files and module visibility in the same edit/build directories.
