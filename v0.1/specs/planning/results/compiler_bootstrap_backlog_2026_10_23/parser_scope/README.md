# Explicit parser staged scope
Doc Status: planning

Candidate: 99f4bb63d plus Parser_Run current_scope backing/accessor adaptation.
Reuse: proven nullable-storage/checked-accessor layout. Parser entry paths select a
scope after construction; all twelve direct whole assignments now call select_scope,
and reads call current_scope. Nested save/restore and finally restoration remain in
place. No algorithmic constructor fields were reclassified as staged.

Six parser/AST/trait/retained lookup PHP checks pass in /tmp/parser-scope-checks-01.
Three namespace lifecycle PHP checks pass in /tmp/parser-scope-namespaces-01.
/tmp/parser-scope-portable-01 extracts the production accessor methods and backing
field into a minimal owner fixture: PHP, legacy native (21.43 s), ordinary (23.15 s)
and concrete prelowering (22.06 s) pass early failure and scope identity restoration.
The broader prior staged class/struct copy proof remains included. This does not
execute the whole parser natively or implement automatic marker conversion.

Bootstrap 1:14.42 GNU wall, 342388 KiB RSS, 312 MiB PHP heap. First syntax_node blocker
and aggregate diagnostic set unchanged. No OOM/fatal or native compiler rebuild.
