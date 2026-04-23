# Simple C++ test UI
Doc Status: supporting
Place this folder under `public_html/test`.

Assumptions:
- the `ast` PHP extension is installed and enabled
- the PHP CLI binary is available as `php`
- a C++ compiler is available as `g++`
- the project layout stays the same relative to `public_html/test`

Behavior:
- top-left: PHP source input
- top-right: generated C++ code, or generator error text when generation fails
- bottom-left: PHP output, or PHP execution error text when PHP execution fails
- bottom-right: C++ output, or C++ compile/runtime error text when the generated code fails
- bottom panes get a green outline when both outputs match exactly and neither side has an error

Performance notes:
- the UI now caches a prebuilt C++ runtime archive under `runtime/build/test_ui_cache`
- the runtime archive is rebuilt only when runtime sources/headers change
- normal runs recompile/link only the small generated sample against the cached runtime archive


## Memory test mode

The UI exposes a **Mem test (ASan)** checkbox.

When enabled:
- the cached runtime archive is built in a dedicated ASan cache bucket
- the generated sample is compiled and linked with `-fsanitize=address`
- the sample link step also includes `-ljemalloc`

The normal mode cache and the ASan cache are kept separate.

The test harness compiles the runtime and generated snippets with `-DSCPP_LANGUAGE_TARGET_PHP=1` so PHP-target array-key semantics are active during browser-driven tests.



UI updates:
- generated C++ header and source are now editable textareas
- the right panel uses tabs so header/source overlap in the same editor area
- a new **Compile & run edited C++** button recompiles the edited C++ without rerunning the PHP->C++ generator
