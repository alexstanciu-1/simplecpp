# Strict Project Samples

These are small checked-in reference projects for the strict PHP profile.

Goals:

- show the intended visible strict API shape
- keep examples runnable with `scpp build`
- cover a few realistic combinations beyond one tiny end-to-end check

Each sample uses:

- `runtime.languages.php.profile = "strict"`
- `runtime.modules = ["json", "filesystem"]`

The visible strict API uses flat family-prefixed names such as:

- `fs_get(...)`
- `fs_put(...)`
- `str_strlen(...)`
- `io_open(...)`
- `json_decode(...)`

Validation:

- run all checked strict samples with `./tests/run_examples.sh`
- each sample is compared against checked-in stdout under `expected/`
