# Project compilation
Doc Status: supporting

The PHP host CLI accepts `--project PROJECT_DIRECTORY` or `--project PRISM_JSON`.
The existing project services in `bin/project_services.php` own manifest decoding,
normalized transitive dependency graphs, source inventory and project-module
report/warn/fail policies. `host/project.php` adapts their output to compiler inputs;
it does not perform source name resolution or invoke v0.1 STAN.

```bash
mkdir -p /absolute/project/.prism
php compiler/my-try/main.php --project /absolute/project/prism.json --output /absolute/project/.prism/mytry
php compiler/my-try/tools/build_cpp.php /absolute/project/.prism/mytry /absolute/project/.prism/mytry-cache
```

The manifest must explicitly select the strict PHP runtime profile. `entrypoint`
selects the root program source; `--entry` overrides it. The root runtime module
selection applies to the whole program, with core and strings always available.
Missing optional functions fail normal shared preparation; modules with no current
metadata fail startup. Template prelowering remains the default. The CLI emits
its artifact bundle on stdout and a project policy report on stderr after successful
validation. Publication starts before project configuration, so a failed build
attempt cannot leave its previous publication ready for the native-build consumer.

Each dependency project becomes a named compiler module. Relative dependency paths
supply stable logical names across checkout relocation. Explicit source inventories
exclude generated/VCS trees and assign nested dependency subtrees to their own
project. Overlapping roots are allowed only for explicit, disjoint selected files.
Ordinary directory modules retain recursive discovery and the overlap rejection.
Source selection changes reset compilation; identical configuration preserves it.
Notifications cannot admit an unselected source. Configuration is validated privately
before replacing the existing session.

After preparation, `Project_Evidence` exports transient declaration/reference rows
from collected occurrences and their settled facts. Callable/member private
collections reuse their original file provenance. Method/property evidence identifies
the source type; this is declaration-level policy, not member access control.
Runtime identities have no project source edge. Candidate lookup subscriptions are
not direct dependency evidence. Unprepared deferred bodies do not invent edges.
The host translates these identities into the existing project's evidence schema.
The same policy functions enforce report/warn/fail for both compiler frontends;
empty public-export lists remain unconstrained. Source visibility remains owned by
shared semantic preparation, independently of these project reporting policies.

This entry is a bounded S2S/project integration, not full `scpp build` parity.
Native-library packaging, build planner/Ninja parity, JSS/legacy projects, metadata
hot reload and a checked run/current-build reader remain separate. Explicit native
library declarations and JSS sources reject here. Existing source-function overload
restrictions still apply. The project policy helpers retain their existing module-name
and assignment semantics; this slice does not redesign those policies.

The [acceptance report](../planning/project_startup_metadata.md) distinguishes native
execution of emitted programs from a native rebuild of the compiler itself.
