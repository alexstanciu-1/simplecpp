"""Host execution of the production bootstrap driver, publishing/building native programs."""
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
with CommandRunner(out / 'logs', ROOT) as runner:
    def check(name, source, expected):
        folder = out / name
        (folder / 'source').mkdir(parents=True)
        (folder / 'source/main.phs').write_text(source)
        (folder / 'request.txt').write_text(str(folder / 'source'))
        (folder / 'output.txt').write_text(str(folder / 'published'))
        driver = (APP / 'tests/bootstrap_driver.php.in').read_text()
        for key, value in {'__REQUEST_FILE__': folder / 'request.txt', '__OUTPUT_FILE__': folder / 'output.txt',
                           '__RUNTIME_CATALOG_DIR__': ROOT / 'runtime/metadata/modules'}.items():
            driver = driver.replace(key, repr(str(value)))
        (folder / 'driver.php').write_text(driver)
        command = ['php8.5', '-r', 'require $argv[1]; require $argv[2];', APP / 'boot.php', folder / 'driver.php']
        assert runner.run(name + '-publish', command) == b'OK'
        manifest = json.loads((folder / 'published/.scpp-output.manifest').read_text())
        assert manifest['application_templates'] == 'forbidden'
        result = json.loads(runner.run(name + '-build', ['php8.5', APP / 'tools/build_cpp.php',
                                                       folder / 'published', folder / 'cache'], timeout=300))
        assert runner.run(name + '-execute', [result['executable']]) == expected
    cases = [('scalar', 'echo 6 * 7;', b'42'),
             ('shared', 'class Node { public int $value = 7; } $node = new Node(); $same = $node; $same->value = 9; echo $node->value;', b'9')]
    results = run_tasks([Task(n, lambda n=n, s=s, e=e: check(n, s, e)) for n, s, e in cases])
(out / 'summary.json').write_text(json.dumps([{'name':r.name, 'seconds':r.seconds, 'passed':r.error is None} for r in results], indent=2) + '\n')
