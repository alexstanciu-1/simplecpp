# Simple C++ STAN VS Code Scaffold

This is a small development scaffold for running the STAN language server from this repository inside VS Code.

## Current Shape

- launches `php /path/to/bin/stan_lsp_server.php`
- targets `.phs` files
- uses the real long-lived STAN server session
- supports diagnostics, hover, definition, references, and document symbols through the current LSP bridge

## Local Development

1. Open this folder in VS Code:
   - `/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp`
2. Run:
   - `npm install`
   - `npm run check`
3. Press `F5` to launch an Extension Development Host.
4. The host will open directly into:
   - [smoke-workspace](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace)
5. In development mode, the scaffold will also try to open:
   - [main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace/main.phs)

## Debug Launch

- A ready-to-use launch config lives in:
  - [launch.json](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/.vscode/launch.json)
- Open the scaffold folder itself in VS Code and run:
  - `Run Simple C++ STAN Extension`
- The launched Extension Development Host opens the bundled smoke workspace automatically.
- The launch now uses:
  - [smoke-workspace.code-workspace](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace.code-workspace)
- There is also a command available in the Extension Development Host:
  - `Simple C++: Open Smoke File`

## Smoke Workspace

- The bundled smoke target lives at:
  - [smoke-workspace](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace)
- The bundled workspace file lives at:
  - [smoke-workspace.code-workspace](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace.code-workspace)
- It includes:
  - [prism.json](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace/prism.json)
  - [main.phs](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace/main.phs)
  - [README.md](/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode-stan-lsp/smoke-workspace/README.md)

## Settings

- `simplecpp.stan.phpBinary`
  - defaults to `php`
- `simplecpp.stan.serverScript`
  - optional absolute override for `bin/stan_lsp_server.php`
- `simplecpp.stan.trace.server`
  - `off`, `messages`, or `verbose`

## Windows + WSL

- If the workspace is opened through a `\\wsl$\...` path, the scaffold will try to launch the STAN server through:
  - `wsl.exe -d <distro> --cd <linux-workspace> php <linux-server-script>`
- This avoids the fragile `Windows PHP + UNC workspace path` startup path that can break `prism.json` discovery.

## Notes

- This is still a scaffold, not a release-ready extension.
- The server side already supports unsaved-buffer overlays and `publishDiagnostics`.
- The client is multi-root aware and will start one STAN language client per workspace folder.
- The next likely client-side steps are:
  - richer status/error reporting
  - workspace-root selection for multi-root workspaces
  - explicit `.php`/profile-aware language activation if desired
