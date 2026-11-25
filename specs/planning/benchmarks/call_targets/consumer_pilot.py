"""Run the real compiler with baseline and pilot target storage in disposable copies."""
import argparse
import json
import os
from pathlib import Path
import shutil
import statistics
import subprocess
from tempfile import TemporaryDirectory

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]


def compiler(work, mode, profile=False):
    tree = work / mode / 'compiler'
    shutil.copytree(ROOT / 'compiler', tree)
    (tree.parent / 'runtime').symlink_to(ROOT / 'runtime', target_is_directory=True)
    (tree.parent / 'tools').symlink_to(ROOT / 'tools', target_is_directory=True)
    if mode == 'weak':
        subprocess.run(['patch', '--batch', '--fuzz=0', '-s', '-p1', '-i', HERE / 'weak_interface.patch'], cwd=tree, check=True)
    # Identical clocks around actual compiler phases; omit frontend, bundle I/O and cleanup.
    entry = tree / 'compiler/incremental_project.php'
    text = entry.read_text()
    for key, expr in [('prepare', '$report = Incremental_Preparation::prepare($session, $this->target);'),
                      ('emit', '$output = Cpp_Program::emit($program, $this->output, $changed, $plans);')]:
        assert text.count(expr) == 1, expr
        before = 'monitor_targets(); ' if profile else ''
        after = f" end_monitor('{key}');" if profile else ''
        text = text.replace(expr, before + "$clock = hrtime(true);\n\t\t" + expr + "\n\t\t$GLOBALS['phase_times']['" + key + "'] = hrtime(true) - $clock;" + after)
    entry.write_text(text)
    return tree


def run(tree, app, out, mode):
    app.mkdir(exist_ok=True)
    flags = ['-d', 'zend_extension=xdebug'] if mode == 'counts' else []
    return json.loads(subprocess.check_output(['php', *flags, HERE/'consumer_pilot.php', tree/'boot.php', app, out, mode],
                                              env={**os.environ, 'XDEBUG_MODE':'develop' if mode == 'counts' else 'off'}))


def execute(out, exe):
    manifest = json.loads((out/'manifest.json').read_text())
    subprocess.run(['clang++', '-std=c++23', '-stdlib=libc++', '-O2', '-I', ROOT/'runtime/generated/public',
                    *(out/u for u in manifest['translation_units']), ROOT/'build/clang/libscpp_runtime.a',
                    '-o', exe], check=True)
    result = subprocess.run([exe], capture_output=True)
    assert (result.returncode, result.stdout, result.stderr) == (16, b'', b''), result


def compare(work, profile):
    trees = {k: compiler(work,k) for k in ('baseline','weak')}
    app = work/'app'
    reference = None
    for mode, tree in trees.items():
        out = work/(mode+'-out')
        rows = run(tree, app, out, 'verify')
        execute(out, work/(mode+'-exe'))
        if reference is None:
            reference = rows
        else:
            assert rows == reference, [(p, rows[p], reference[p]) for p in rows if rows[p] != reference[p]]
    print('Matched baseline and incremental repairs:', ', '.join(reference), flush=True)
    # Preserve the actual serialization failure as a pilot finding and detect any other regression.
    for mode, tree in trees.items():
        for script in ('functions.php', 'incremental_preparation.php', 'python_frontend.php'):
            test = subprocess.run(['php', tree/'tests'/script], capture_output=True, text=True,
                                  env={**os.environ, 'XDEBUG_MODE':'off'})
            if test.returncode:
                assert mode == 'weak' and "Serialization of 'WeakReference' is not allowed" in test.stderr, test.stderr
                print(mode, script, 'blocked at whole-program serialization')
            else:
                print(mode, script, 'passed')
    samples = {}
    work_counts = None
    for mode in ('baseline', 'weak', 'weak', 'baseline') * 4:
        result = run(trees[mode], app, work/(mode+'-out'), 'timing')
        if work_counts is None:
            work_counts = result['work']
        assert work_counts == result['work']
        for phase, times in result['samples'].items():
            for t in times:
                for stage, ns in t.items():
                    samples.setdefault((mode, phase, stage), []).append(ns/1e6)
    print(work_counts)
    if profile:
        profiled = compiler(work/'profiled', 'weak', profile=True)
        counts = run(profiled, app, work/'counts-out', 'counts')
        print('Weak-interface queries:', counts)
        for phases in counts.values():
            assert phases['prepare']['call_signature'] > 0 and phases['emit']['lowering_operand'] > 0
    for key, values in samples.items():
        q1, _, q3 = statistics.quantiles(values, n=4)
        print(*key, f'{statistics.median(values):.4f} ms (IQR {q1:.4f}..{q3:.4f}, n={len(values)})')


if __name__ == '__main__':
    with TemporaryDirectory() as tmp:
        parser = argparse.ArgumentParser(description=__doc__)
        parser.add_argument('--counts', action='store_true', help='count target queries separately with Xdebug')
        compare(Path(tmp), parser.parse_args().counts)
