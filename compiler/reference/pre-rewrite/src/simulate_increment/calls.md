# Increment simulation
Doc Status: supporting

Caller: ../main.php [if --simulate-increment]. This is a CLI harness, not a
compiler stage. Both requests use one ordinary Compiler_Session.

```text
Simulation::run()                             run.php
  -> prepare_folder_swap()
    -> check_pending(); [action] validate distinct trees
  -> Folder_Swap::begin()                     store.php
  -> Compiler_Session::compile() [original]
  -> Folder_Swap::park_original(); install_edited()
  -> Compiler_Session::compile() [edited]
  -> Folder_Swap::finish() [finally, including failure]
    -> [action] restore trees, finish journal and release locks
```

Folder_Swap owns paths, transition records and borrowed project locks. Hard
process crashes leave a journal for recovery. Normal CLI compilation also calls
Simulation::check_pending() to reject an unfinished swap. The harness does not
execute generated programs.
