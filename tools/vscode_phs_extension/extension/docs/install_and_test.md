# Load And Test In VS Code

This extension is currently the repo-local `Simple C++` extension workspace with both language support and STAN integration.

## Prerequisites

- Visual Studio Code installed
- this repository opened locally
- `scpp` available in the shell used by VS Code terminals

## Load The Extension

1. Open this repository in Visual Studio Code.
2. Open the `tools/vscode_phs_extension/extension/` folder in a separate VS Code window, or keep it visible in the current workspace.
3. Open the extension folder as the active extension project:
   - `/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/extension`
4. Press `F5` in VS Code to launch an Extension Development Host window.
5. In the Extension Development Host window, the bundled STAN smoke workspace should open automatically.
6. Open the fixture project file when you want to test static completion:
   - `/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/testdata/completion_project/main.phs`

Recommended fixture companions:

- `/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/testdata/completion_project/lib.phs`
- `/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/testdata/completion_project/prism.json`

## What To Check

### Language registration

- `main.phs` from the fixture project opens as `PHP++ (PHS)`
- the status bar language mode shows the `phs` language

### Syntax highlighting

- comments, strings, keywords, types, variables, and function calls are colored
- doc comments highlight tags such as `@lib-export`
- `DBG_*` constants are highlighted
- `<?php` is visibly marked as invalid in `.phs` files

### Snippets

Try these snippet prefixes in a `.phs` file:

- `fn`
- `libfn`
- `take`
- `dbg`
- `typed`

### Commands

Open the Command Palette and run:

- `Simple C++: Create Project`
- `Simple C++: Build Project`
- `Simple C++: Run Project`
- `Simple C++: Doctor`
- `Simple C++: Show Strict Docs`

Each command should open a VS Code terminal named `Simple C++` and run the matching `scpp` command.

The fixture project at:

- `/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/testdata/completion_project/`

is the canonical Phase 1 editor-check target.

### STAN integration

Open the bundled smoke workspace file:

- `/home/alexv/__AI/simple_cpp/simple_cpp_02/tools/vscode_phs_extension/extension/smoke-workspace.code-workspace`

Then confirm:

- diagnostics show one warning on `$value = helper();`
- hover on `helper()` returns content
- go-to-definition on `helper()` jumps to the helper declaration
- references on `helper()` includes the declaration and usage
- `Simple C++: Restart STAN Server` works from the Command Palette

### Static completion

In `main.phs`, trigger completion with `Ctrl+Space` and check that suggestions include:

- language keywords such as `function`, `foreach`, and `return`
- strict builtins such as `take`, `dbg`, `json_decode`, `fs_get`, `io_open`, and `dt_now`
- basic types such as `int`, `string`, `vector`, and `hash`
- visible variables when completing after `$`
- declared local class names after `new`

Context expectations:

- after `$`, prefer visible variable names such as `$userName`, `$userId`, `$payload`, and `$service`
- after `new`, prefer declared type names such as `SampleService`, `Runner`, and `FixtureUser`
- after `->`, Phase 1 currently stays quiet rather than guessing members
- in obvious type positions, prefer type names

## Current Limits

This extension still does not yet provide:

- member completion after `->`
- analyzer-backed completion

Analyzer-backed diagnostics and navigation now come from the STAN language server bridge.
