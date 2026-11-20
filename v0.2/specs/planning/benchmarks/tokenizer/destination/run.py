"""On-demand AST plan trial. Substitute one emitter in a private bootstrap; never edit the checkout."""
import argparse
import json
import os
from pathlib import Path
import subprocess

HERE = Path(__file__).resolve().parent


def private_boot(work, compiler):
    emit = compiler / '05_backend/cpp/emit.php'
    hook = 'if ($statement instanceof ast_base_statement) continue;'
    text = emit.read_text()
    assert text.count(hook) == 1
    (work / 'emit.php').write_text(text.replace(hook, (HERE / 'emit.inc').read_text() + hook))
    boot = (compiler / 'boot.php').read_text()
    old = "__DIR__ . '/05_backend/cpp/emit.php'"
    assert boot.count(old) == 1
    (work / 'boot.php').write_text(boot.replace(old, repr(str(work / 'emit.php')))
                                   .replace('__DIR__', repr(str(compiler))))
    return work / 'boot.php'


def trial(work, compiler, php):
    work.mkdir(exist_ok=True)
    boot = private_boot(work, compiler)
    measurements = {}
    for flavor in ('cpp', 'php', 'js'):
        subprocess.run([php, HERE / 'incremental.php', boot, work, flavor], check=True, env={**os.environ, 'XDEBUG_MODE': 'off'})
        for case in ('scanner', 'second', 'live'):
            out = work / (flavor + '-' + case)
            proc = subprocess.run([php, '-d', 'opcache.enable_cli=0', HERE / 'trial.php', boot, out, flavor, case],
                                  text=True, stdout=subprocess.PIPE, check=True, env={**os.environ, 'XDEBUG_MODE': 'off'})
            measurements[out.name] = json.loads(proc.stdout)
    return measurements


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('compiler', type=Path)
    parser.add_argument('output', type=Path)
    parser.add_argument('--php', default='php8.5')
    args = parser.parse_args()
    print(json.dumps(trial(args.output.resolve(), args.compiler.resolve(), args.php), indent=2))
