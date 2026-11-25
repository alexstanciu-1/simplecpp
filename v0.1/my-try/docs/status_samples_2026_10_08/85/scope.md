# 🔴 Use by ordinary `scpp build` / `scpp run`
Doc Status: supporting

scpp build/run is project orchestration. Current selection remains legacy; these isolated generator invocations do not change CLI routing.

Example strict project configuration (workflow illustration, not executed):

```json
{"runtime":{"languages":{"php":{"profile":"strict"}}}}
```

```sh
scpp build --no-stan
scpp run --no-stan
```

There is no C++ fragment that selects a compiler backend. Current CLI wiring is the
subject of this row; invoking the isolated v2 S2S entrypoint does not change it.
