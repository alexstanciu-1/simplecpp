# Completion Fixture Project

Purpose: tiny strict-mode Simple C++ project used for Visual Studio Code extension fixture testing.

This project is intentionally small and editor-focused.
It exists to exercise:

- `.phs` language registration
- syntax highlighting
- doc comment and attribute highlighting
- static completion for builtins, variables, and type positions
- local type-name completion after `new`

It should stay stable and easy to inspect by hand.
