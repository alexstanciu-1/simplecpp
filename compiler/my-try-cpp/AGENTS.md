# my-try-cpp working rules

Before editing handwritten C++ sources, read and follow
[docs/code_style.md](docs/code_style.md). It applies to `src/`, `model/`, `support/`, `middleware/` and
the C++ tests. Do not edit generated files or dependency snapshots to apply style.

Keep behavior, APIs and ownership unchanged during formatting-only work. Follow
the explicit runtime/middleware build boundaries described in [README.md](README.md).

Keep each data type responsible for its own representation; move processing into
the workers that own it. Use friendship only where a worker needs privileged
access. Follow the detailed ownership rules in `docs/code_style.md`; do not force
data-only structures or one companion processing class per data type.
