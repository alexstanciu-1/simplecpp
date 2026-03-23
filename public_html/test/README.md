# Simple C++ test UI

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
