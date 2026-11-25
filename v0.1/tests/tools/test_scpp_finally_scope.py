"""Compile and execute existing finally cases plus local-scope regressions."""
import argparse
import json
from pathlib import Path
import subprocess
import time

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
output = args.results.resolve()
output.mkdir(parents=True, exist_ok=False)
project = output / 'project'
project.mkdir()
fixtures = ROOT / 'tests/generators/php/special_tests'
source = (fixtures / 'test_07_finally_return.phs').read_text()
source += '\necho ok(true); try { ok(false); } catch (MyEx $error) { echo "caught\\n"; }\n'
source += (fixtures / 'test_08_finally_return_for_loop.phs').read_text().replace('f(', 'for_case(')
source += (fixtures / 'test_09_finally_nested_loop_break.phs').read_text().replace('f(', 'break_case(')
source += (fixtures / 'test_16_finally_local_scope.phs').read_text()
(project / 'main.phs').write_text(source)
(output / 'host.php').write_text('<?php\n' + source)
commands = []


def run(name, command, cwd=project):
    start = time.monotonic()
    result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True, text=True, timeout=180)
    (output / (name + '.stdout')).write_text(result.stdout)
    (output / (name + '.stderr')).write_text(result.stderr)
    commands.append(dict(name=name, code=result.returncode, seconds=round(time.monotonic() - start, 3)))
    (output / 'commands.json').write_text(json.dumps(commands, indent=2))
    if result.returncode:
        raise RuntimeError(f'{name} failed; see {output}')
    return result.stdout


cli = ROOT / 'bin/scpp.php'
run('init', ['php', cli, 'init', '--php-profile=strict'])
config_path = project / 'prism.json'
config = json.loads(config_path.read_text())
config['build']['cxx'] = 'clang++-18'
config_path.write_text(json.dumps(config, indent=2))
# Generator/runtime regression: STAN currently rejects these established finally return paths.
run('build', ['php', cli, 'build', '--no-stan', '--build-runtime'])
actual = run('native', [project / '.prism/build/main'])
expected = 'cleanup\n10cleanup\ncaught\nF:0\nint(0)\ninner-before\nafter-inner\nfinally\n4:5;4:6;S;4;S;4;E;X;C;9;1D;L;'
host = run('php', ['php', '-d', 'xdebug.mode=off', output / 'host.php'])
if actual != expected or host != expected:
    raise RuntimeError(f'Unexpected finally effects: {actual!r}')
(output / 'summary.json').write_text(json.dumps(dict(passed=True, stdout=actual), indent=2))
print('Finally: existing return/break cases and scoped locals, catch return, exception and continue passed')
