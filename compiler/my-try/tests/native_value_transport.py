"""Existing runtime casts and split providers across ordinary/concrete storage."""
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
cases = {
    'carriers': ('''$r result<string> = "result";
$f result_or_false<string> = "falseable"; $empty result_or_false<string> = false_sentinel;
$b result_or_bool<string> = true_sentinel; $number ?int = 42;
echo "[" . $r . "]", "[" . $f . "]", "[" . $empty . "]", "[" . $b . "]", ":", (int)$number;
''', b'[result][falseable][][]:42'),
    'cast': ('''function next_value(int &$calls): ?string { $calls++; return "retained"; }
$present ?string = "value"; $absent ?string = null; $calls = 0;
echo "[" . $present . "]", "[" . $absent . "]", (string)$present, ":", (string)$absent,
 ":", "[" . next_value($calls) . "]", ":", $calls;
''', b'[value][]value::[retained]:1'),
    'split': ('''function source(int &$calls, string &$separator): string { $calls++; $separator = ":"; return "a,b"; }
echo implode("|", explode(",", "a,,b,")), ";", implode("|", explode(",", "a,b,c", 2)), ";",
 implode("|", explode(",", "a,b,c", 0)), ";", implode("|", explode(",", "a,b,c", -1)), ";";
$empty = explode(",", "abc", -1); echo count($empty), ";";
$one = explode(",", ""); echo count($one), ";";
$separator = ","; $calls = 0;
$parts = explode($separator, source($calls, $separator));
echo implode("|", $parts), ";", $separator, ";", $calls;
''', b'a||b|;a|b,c;a,b,c;a|b;0;1;a|b;:;1'),
}
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(mode, name):
        folder = out / (mode + '-' + name)
        (folder / 'source').mkdir(parents=True)
        source, expected = cases[name]
        (folder / 'source/main.phs').write_text(source)
        runner.run(folder.name + '-publish', ['php8.5', APP / 'tests/async_tasks_publish.php', folder, ROOT / 'runtime/metadata/modules', 'strings', mode])
        build = json.loads(runner.run(folder.name + '-build', ['php8.5', APP / 'tools/build_cpp.php', folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(folder.name + '-run', [build['executable']]) == expected
    results = run_tasks([Task(mode + '-' + name, lambda mode=mode, name=name: check(mode, name))
                         for mode in ['ordinary', 'concrete'] for name in cases])
(out / 'summary.json').write_text(json.dumps([{'name': r.name, 'error': r.error, 'seconds': r.seconds} for r in results], indent=2) + '\n')
sys.exit(any(r.error for r in results))
