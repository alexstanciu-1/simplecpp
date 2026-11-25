"""Run a bounded translated preparation stage on real frontend AST inputs."""
import argparse
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from classes_native import fixture
from exceptions_native import fresh_build
from extends_native import ROOT, source_edit


TRANSLATION = ROOT / 'compiler/examples/preparation_recovery/preparation.php.scpp'
FUNCTIONS = ('changed', 'broken', 'quiet', 'missing')


def parsed_return(path, flavor):
    result = subprocess.run(
        ['php', ROOT / 'compiler/main.php', 'parse', flavor, path],
        capture_output=True, text=True, check=True,
    )
    declarations = json.loads(result.stdout)['functions']
    assert len(declarations) == 1
    declaration = declarations[0]
    assert declaration['return_type']['text'] == 'int' and not declaration['parameters']
    assert len(declaration['body']) == 1 and declaration['body'][0]['kind'] == 'return'
    expression = declaration['body'][0]['value']
    integer, callee = '', ''
    if expression['kind'] == 'integer':
        integer = expression['spelling']
        assert integer.isascii() and integer.isdecimal()
        assert str(int(integer)) == integer and int(integer) <= 2**63 - 1
    else:
        assert expression['kind'] == 'call' and not expression['arguments']
        callee = expression['name']['text']
    return path.name, declaration['qualified_name'], integer, callee, expression['span']['start']


def function_text(output, name):
    manifest = json.loads((output / 'manifest.json').read_text())
    unit = output / next(path for key, path in manifest['sources'].items()
                         if Path(key).stem == name)
    contents = unit.read_bytes()
    start = contents.index(f'::scpp::int64 {name}_()\n'.encode())
    end = contents.index(b'\n}\n', start) + len(b'\n}\n')
    return contents[start:end]


def host_oracle(work, flavor):
    app, output = work / 'host-input', work / 'host-output'
    app.mkdir()
    fn = fixture(flavor)['function']
    (app / 'module.json').write_text('{}')
    (app / f'main.{flavor}').write_text('echo changed(), broken(), quiet(); return 8;')

    def write(name, expression):
        path = app / f'{name}.{flavor}'
        source_edit(path, fn(name, 'int', [], f'return {expression};'))
        return parsed_return(path, flavor)

    original_changed = write('changed', '10')
    original_broken = write('broken', '21')
    quiet = write('quiet', '40')
    process = service(app, output)
    try:
        assert 'error' not in invoke(process)
        old_bundle = artifacts(output), (output / 'manifest.json').read_bytes()
        changed = write('changed', '14')
        broken = write('broken', 'missing()')
        first = invoke(process)
        source, _, _, _, offset = broken
        assert f'{source}:1:{offset + 1}: Unknown function: missing' in first.get('error', ''), first
        assert invoke(process)['error'] == first['error']
        assert old_bundle == (artifacts(output), (output / 'manifest.json').read_bytes())

        missing = write('missing', '29')
        repaired = invoke(process)
        assert 'error' not in repaired and repaired['preparation']['prepared'] == 2, repaired
        assert repaired['preparation']['reused'] == 3, repaired
        assert f'app/quiet.{flavor}' not in repaired['emitted_sources'], repaired
        fresh = work / 'fresh-host'
        assert 'error' not in fresh_build(app, fresh)
        assert artifacts(output) == artifacts(fresh)
        expected = b''.join(function_text(fresh, name) for name in FUNCTIONS)
        return (original_changed, original_broken, quiet, changed, broken, missing), expected
    finally:
        process.stdin.close()
        assert process.wait() == 0


def driver(inputs):
    declaration_names = ('original_changed', 'original_broken', 'quiet', 'changed', 'broken', 'missing')
    declarations = []
    for label, fields in zip(declaration_names, inputs, strict=True):
        args = ', '.join(json.dumps(field) for field in fields)
        declarations.append(f'${label} = new Return_Syntax({args});')
    return '\n'.join(declarations) + r'''
function require_state(bool $valid, string $message): void {
    if (!$valid) { throw Exception::make($message); }
}

$compiler = new Incremental_Preparation();
$compiler->publish_declaration($original_changed);
$compiler->publish_declaration($original_broken);
$quiet_body = $compiler->publish_declaration($quiet);
$compiler->run();
require_state($compiler->prepared === 3 && $compiler->preparation->owner === null, "initial preparation");
$old_output = $compiler->output;
$worker = $compiler->preparation;
// Exercise restoration of a live outer context as well as the empty initial context.
$worker->owner = $quiet_body;
$changed_body = $compiler->publish_declaration($changed);
$broken_body = $compiler->publish_declaration($broken);

for ($attempt int = 0; $attempt < 2; $attempt++) {
    $caught = false;
    try {
        $compiler->run();
    } catch (Preparation_Diagnostic $error) {
        $caught = true;
        require_state($error->source === $broken->source && $error->offset === $broken->offset, "diagnostic location");
        require_state($error->detail === "Unknown function: missing", "diagnostic text");
        require_state($compiler->prepared_before_failure === 1 - $attempt, "completed work before failure");
    }
    require_state($caught, "body failure was expected");
    require_state($worker->owner === $quiet_body, "active preparation owner was not restored");
    require_state($changed_body->prepared() && !$broken_body->prepared(), "body completion state");
    require_state(count($compiler->work->pending) === 1 && $compiler->work->pending["broken"] === $broken_body, "pending work");
    require_state(count($compiler->work->emission_sources) === 2, "unfinished output work");
    require_state($compiler->output["changed"] === $old_output["changed"], "changed output escaped a failed preparation");
    require_state($compiler->output["broken"] === $old_output["broken"], "last good output was lost");
}

$compiler->publish_declaration($missing);
$compiler->run();
require_state($worker === $compiler->preparation && $worker->owner === $quiet_body, "retry changed the worker or active owner");
require_state($compiler->prepared === 2 && $compiler->reused === 2, "retry did not reuse completed work");
require_state($compiler->symbols->functions["quiet"] === $quiet_body && $compiler->symbols->functions["broken"] === $broken_body,
    "retry replaced an unaffected or pending body");
require_state(count($compiler->work->pending) === 0 && count($compiler->work->emission_sources) === 0, "retry did not acknowledge completed work");

$fresh = new Incremental_Preparation();
$fresh->publish_declaration($changed);
$fresh->publish_declaration($broken);
$fresh->publish_declaration($quiet);
$fresh->publish_declaration($missing);
$fresh->run();
require_state($fresh->prepared === 4, "fresh preparation");
$compiler->run();
require_state($compiler->prepared === 0 && $compiler->reused === 4, "no-op preparation");

$names vector<string> = ["changed", "broken", "quiet", "missing"];
foreach ($names as $name) {
    require_state($compiler->output[$name] === $fresh->output[$name], "recovered artifact differs from fresh");
    echo $compiler->output[$name];
}
return 8;
'''


def prove(work, flavor):
    work.mkdir()
    inputs, expected = host_oracle(work, flavor)
    app, output = work / 'translated-input', work / 'translated-output'
    app.mkdir()
    (app / 'module.json').write_text('{}')
    shutil.copyfile(TRANSLATION, app / 'preparation.php')
    (app / 'main.php').write_text(driver(inputs))
    result = fresh_build(app, output)
    assert 'error' not in result, result
    timing = measure(output, work / 'native', expected)
    print(f'{flavor}: same-instance recovery and fresh output passed (Clang frontend {timing["frontend_ms"]:.1f} ms)', flush=True)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-translated-preparation-recovery-') as temporary:
        work = (args.output or Path(temporary)).resolve()
        work.mkdir(exist_ok=True)
        for flavor in ('php', 'cpp', 'js'):
            prove(work / flavor, flavor)


if __name__ == '__main__':
    main()
