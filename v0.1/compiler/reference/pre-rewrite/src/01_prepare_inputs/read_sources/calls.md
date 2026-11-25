# Read sources call map
Doc Status: supporting

Compiler_Session runs discovery; Phases::run_tokenization() runs snapshot reads immediately
before tokenization. scan.php and read.php are independent workers. scan_join.php accepts
each scan batch so its children can form the next batch; snapshot_join.php accepts byte
snapshots. utilities/paths.php owns path checks; data/ owns rows and datasets.
Source scan tasks either list a directory or observe fixed immediate file_names;
explicit selections use the same metadata, identity join and source-read pipeline.

The following are ordered lifecycle calls, not calls between siblings.

```text
Source_Discovery                                  main_discover_sources.php
  init() -> [action] resolve folder/file selections; seed scan tasks
  run() -> Source_Scanner::scan(); Source_Scan_Join::join() [each breadth-first batch]
  finalize() -> [action] reconcile removals, indexes and entry
  result() => Source_Set

Source_Reader                                  main_read_sources.php
  init() -> Source_Read_Selection::select()
  run() -> Snapshot_Reader::read() [each task]
  finalize() -> Snapshot_Join::join()
  result() => Source_Set
```

status() and supports_run() are always available. Result/store access requires
finished status; invalid timing throws without changing the state.
