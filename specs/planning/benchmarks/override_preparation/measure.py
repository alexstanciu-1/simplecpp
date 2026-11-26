"""Run on dev002 after building the tree. Emit one CSV row per warmed edit/no-op pair."""
import csv
import json
import os
from pathlib import Path
import random
import subprocess
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[4]
sys.path.insert(0, str(ROOT / 'compiler/tests'))
from extends_native import source_edit


def timed_server(directory):
    # Run the production project, including normal publication and cleanup. Only
    # the preparation call has a timer. Use private copies for the probe.
    project = ROOT/'compiler/compiler/incremental_project.php'
    line = '$report = Incremental_Preparation::prepare($session, $this->target);'
    code = project.read_text()
    assert code.count(line) == 1
    timed = directory/'project.php'
    timed.write_text(code.replace(line, '$start = hrtime(true);\n' + line +
                                  "\n$report['elapsed_ns'] = hrtime(true) - $start;"))
    boot = (ROOT/'compiler/boot.php').read_text()
    original = "__DIR__ . '/compiler/incremental_project.php'"
    assert boot.count(original) == 1
    (directory/'boot.php').write_text(boot.replace(original, repr(str(timed))).replace(
        '__DIR__', repr(str(ROOT/'compiler'))))
    server = directory/'server.php'
    server.write_text((ROOT/'compiler/tests/incremental_executable.php').read_text().replace(
        "__DIR__ . '/../boot.php'", repr(str(directory/'boot.php'))))
    return server


def measure(directory, server, receiver, count, writer):
    app = directory/'app'
    app.mkdir(parents=True)
    source_edit(app/'module.json', '{}')
    source_edit(app/'base.php', 'class Base {public virtual function value():int {return 1;}}')
    child = 'class Child extends Base {public override function value():int {return %d;}}'
    source_edit(app/'child.php', child % 2)
    # Keep entry size and file count fixed. Every declared caller has a prepared
    # body; the preparation counts verify participation independently of timing.
    source_edit(app/'main.php', 'return 8;')
    callers = [f'function read{i}({receiver} $n):int {{return $n->value();}}' for i in range(count)]
    source_edit(app/'callers.php', '\n'.join(callers) + '\n')
    with subprocess.Popen(['php', server, app, directory/'out'], text=True,
                          stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                          env={**os.environ, 'XDEBUG_MODE': 'off'}) as proc:
        def request():
            proc.stdin.write('"run"\n')
            proc.stdin.flush()
            row = json.loads(proc.stdout.readline())
            assert 'error' not in row, row
            return row['preparation']

        try:
            assert request()['prepared'] == count + 3
            for pair in range(34):
                source_edit(app/'child.php', child % (3 if pair % 2 == 0 else 2))
                edit = request()
                noop = request()
                assert noop['prepared'] == 0, noop
                assert edit['prepared'] == 1 + (count if receiver == 'Child' else 0), edit
                if pair >= 3:
                    writer.writerow([receiver, count, pair - 2, edit['prepared'],
                                     edit['elapsed_ns'], noop['elapsed_ns']])
        finally:
            proc.stdin.close()


if __name__ == '__main__':
    writer = csv.writer(sys.stdout, lineterminator="\n")
    writer.writerow(['receiver', 'callers', 'pair', 'prepared', 'edit_ns', 'noop_ns'])
    with tempfile.TemporaryDirectory(prefix='override-timing-') as tmp:
        root = Path(tmp)
        server = timed_server(root)
        cases = [(r, n) for n in (0, 1, 8, 32, 128, 512) for r in ('Child', 'Base')]
        random.Random(42).shuffle(cases)
        for receiver, count in cases:
            measure(root/f'{receiver}-{count}', server, receiver, count, writer)
