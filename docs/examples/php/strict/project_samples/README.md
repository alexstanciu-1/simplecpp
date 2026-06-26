# Strict Project Samples

These are small checked-in reference projects for the strict PHP profile.

Goals:

- show the intended visible strict API shape
- keep examples runnable with `scpp build`
- cover a few realistic combinations beyond one tiny end-to-end check

Each sample uses:

- `runtime.languages.php.profile = "strict"`
- `runtime.modules = ["json", "filesystem", "datetime"]` by default

Regex-specific samples opt into `"regex"` explicitly.

WebView-specific samples such as `strict_webview_bridge/` and `strict_webview_events/` opt into `"webview"` explicitly. They are reference projects for GUI-capable machines and CI render jobs, not part of the default console sample runner. On Linux, building them requires GTK and WebKitGTK development packages.

The visible strict API uses plain PHP-like names for general helpers and family-prefixed names for subsystem helpers, such as:

- `fs_get(...)`
- `fs_put(...)`
- `strlen(...)`
- `io_open(...)`
- `json_decode(...)`

Validation:

- run all checked strict samples with `./tests/run_examples.sh`
- each sample is compared against checked-in stdout under `expected/`
- build or run WebView samples only on a host with a supported native WebView backend and display session
