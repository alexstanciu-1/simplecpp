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

GUI samples such as `strict_webview_bridge/` are checked-in reference projects but are not listed in `tests/samples_manifest.txt`, because the manifest runner is intentionally headless and stdout-comparison based.

The visible strict API uses plain PHP-like names for general helpers and family-prefixed names for subsystem helpers, such as:

- `fs_get(...)`
- `fs_put(...)`
- `strlen(...)`
- `io_open(...)`
- `json_decode(...)`

Validation:

- run all checked strict samples with `./tests/run_examples.sh`
- each sample is compared against checked-in stdout under `expected/`
