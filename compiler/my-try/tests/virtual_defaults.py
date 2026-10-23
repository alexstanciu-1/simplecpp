"""Explicit virtual defaults: inherited bodies, slots, traits and container signatures."""
import argparse
import json
import sys
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = r'''interface Contract { function values(vector<int> $items): vector<int>; }
class Base_Record {
 #[\Virtual] public function values(vector<int> $items): vector<int> { return $items; }
 #[\Virtual] protected function score(): int { return 3; }
 public function read_score(): int { return $this->score(); }
}
class Default_Record extends Base_Record {}
trait Values {
 #[\Override] public function values(vector<int> $items): vector<int> { $out vector<int> = [9]; return $out; }
}
class Derived_Record extends Base_Record implements Contract {
 use Values;
 #[\Override] public final function score(): int { return parent::score() + 4; }
}
$default Base_Record = new Default_Record();
$derived = new Derived_Record();
$base Base_Record = $derived;
$contract Contract = $derived;
$input vector<int> = [5];
$a = $default->values($input);
$b = $base->values($input);
$c = $contract->values($input);
echo $a[0], ":", $b[0], ":", $c[0], ":", $base->read_score(), ":", $default->read_score();
'''
with CommandRunner(out / 'logs', ROOT) as runner:
    def preparation():
        runner.run('preparation', ['php8.5', APP / 'tests/virtual_defaults.php', out / 'retained'])
    def native(mode):
        folder = out / mode
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        runner.run(mode + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder,
                                       ROOT / 'runtime/metadata/modules', 'core', mode])
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == ('allowed' if mode == 'ordinary' else 'forbidden')
        build = json.loads(runner.run(mode + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(mode + '-run', [build['executable']]) == b'5:9:9:7:3'
    results = run_tasks([Task("preparation", preparation)] + [Task(mode, lambda mode=mode: native(mode)) for mode in ['ordinary', 'concrete']])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
