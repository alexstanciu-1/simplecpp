# Project bootstrap for ChatGPT
Doc Status: supporting
## Workflow authority
Before performing repository Git workflow, pull-request, or release operations, follow:
- `specs/git_workflow_release_procedure.md`

## Sandbox setup
If this project archive contains `ext/ast*/ast.so`, install that extension in the sandbox before running any transpiler validation.

Preferred target:
- PHP 8.4 CLI

Typical steps:
1. Detect PHP 8.4 extension dir:
   - `php8.4 --info | grep extension_dir`
2. Copy `ext/ast*/ast.so` to that extension dir as `ast.so`
3. Create `/etc/php/8.4/cli/conf.d/20-ast.ini` with:
   - `extension=ast`
4. Verify:
   - `php8.4 -m | grep '^ast$'`
   - `php8.4 --ri ast`

## Validation rule
Do not trust prior sandbox state. Reinstall/recheck `ast` for each new chat/session before running `scpp.php`.

## Project defaults
- Prefer PHP 8.4 in sandbox validation
- User target remains PHP 8.5 for project design unless stated otherwise
