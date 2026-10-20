#!/usr/bin/env python3
"""Source method/constructor behavior and failure cleanup through the public concrete option."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    results = {}
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof(mode):
            folder = out / mode
            runner.run(mode + '-emit', ['php', APP / 'tests/fixtures/prelower_source_lifecycle.php',
                out / (mode + '-source'), folder, mode])
            manifest = json.loads((folder / '.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == ('forbidden' if mode == 'concrete' else 'allowed')
            command = ['php', APP / 'tools/build_cpp.php', folder, out / (mode + '-cache'), '--no-pch', '-O0']
            built = json.loads(runner.run(mode + '-build', command, cli_reason='Native builder process'))
            results[mode] = runner.run(mode + '-run', [built['executable']]).decode()
            warm = json.loads(runner.run(mode + '-warm', command, cli_reason='Native builder process'))
            assert warm['compiled'] == warm['linked'] == 0, warm
            if mode == 'concrete':
                edits = []
                for revision in ['method-edit', 'layout-edit']:
                    runner.run(revision + '-emit', ['php', APP / 'tests/fixtures/prelower_source_lifecycle.php',
                        out / (mode + '-source'), folder, mode, revision])
                    changed = json.loads(runner.run(revision + '-build', command, cli_reason='Native builder process'))
                    output = runner.run(revision + '-run', [changed['executable']]).decode()
                    if revision == 'method-edit':
                        assert output == results[mode], output
                        assert changed['layouts_built'] == 0 and changed['layouts_reused'] > 0, changed
                        # General concrete emission has a different dependency graph from
                        # the retired native-source partition; do not retain its unit count.
                        assert changed['compiled'] > 0 and changed['reused'] > 0, changed
                    else:
                        assert changed['layouts_built'] > 0, changed
                    edits.append(dict(revision=revision, **changed))
                (out / 'edits.json').write_text(json.dumps(edits, indent=2) + '\n')
        run_tasks([Task(mode, lambda mode=mode: proof(mode)) for mode in ['ordinary', 'concrete']])
    assert results['ordinary'] == results['concrete'], results
    assert len(results['concrete'].splitlines()) == 7, results
    assert int(results['concrete'].splitlines()[0].split()[5]) > 0, results
    (out / 'summary.json').write_text(json.dumps(results, indent=2) + '\n')


if __name__ == '__main__':
    main()
