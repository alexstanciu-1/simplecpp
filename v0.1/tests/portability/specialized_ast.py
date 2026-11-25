"""Prove the specialized AST's object accessor/dispatch contracts on an explicit candidate."""
import argparse
import json
from pathlib import Path
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[2]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--target-checkout', type=Path, required=True)
args = parser.parse_args()
cli = args.target_checkout.resolve() / 'bin/scpp.php'
with tempfile.TemporaryDirectory(prefix='scpp-ast-contract-') as temporary:
    work = Path(temporary)
    source = work / 'source'
    project = work / 'project'
    source.mkdir()
    original = (ROOT / 'tests/portability/fixtures/specialized_ast/main.php').read_text()
    # Separate the node declarations from consumers to prove cross-unit dispatch.
    declarations, entry = original.rsplit('$source = new integer_node();', 1)
    (source / 'nodes.php').write_text(declarations)
    (source / 'main.php').write_text('<?php\nnamespace ast_contract;\n$source = new integer_node();' + entry)

    def run(command, cwd=ROOT, success=True):
        result = subprocess.run(list(map(str, command)), cwd=cwd, capture_output=True,
                                text=True, timeout=180)
        assert (result.returncode == 0) == success, result.stdout + result.stderr
        return result

    host = run(['php', '-r', 'require $argv[1]; require $argv[2];',
                source / 'nodes.php', source / 'main.php'])
    assert host.stdout == '64:17:17:17:64\n', host.stdout
    run(['php', ROOT / 'tools/php_portability/convert.php', source, project])
    (project / 'prism.json').write_text(json.dumps({
        'config_version': 1, 'project_name': 'ast_contract', 'entrypoint': 'main.phs',
        'runtime': {'languages': {'php': {'profile': 'strict'}}, 'modules': ['compiler']},
        'build': {'cxx': 'clang++-18'}}))
    run(['php', cli, 'build', '--build-runtime'], project)
    native = run([project / '.prism/build/main'], project)
    assert native.stdout == host.stdout, native.stdout
    print('PASS PHP/native: covariant accessor, base/interface dispatch, trait fields, shared self and retained cursor', flush=True)
    # Explicit parent calls must bypass the dynamic override, including bridge slots.
    nodes = project / 'nodes.phs'
    nodes.write_text(nodes.read_text() + """
class accessor_base {
    public function item(): facts { $value = new facts(); $value->value = 5; return $value; }
}
class accessor_child extends accessor_base {
    public function item(): integer_facts { return new integer_facts(); }
    public function parent_value(): int { return parent::item()->value; }
}
""")
    main = project / 'main.phs'
    main.write_text(main.read_text() + "\n$derived = new accessor_child(); echo $derived->parent_value(), \"\\n\";\n")
    run(['php', cli, 'build'], project)
    native = run([project / '.prism/build/main'], project)
    assert native.stdout == host.stdout + '5\n', native.stdout
    print('PASS native: qualified parent accessor bypasses override', flush=True)
    # Unrelated object returns remain errors even though object spelling is accepted.
    path = project / 'nodes.phs'
    path.write_text(path.read_text().replace('class integer_facts extends facts', 'class integer_facts'))
    failure = run(['php', cli, 'build'], project, success=False)
    assert 'abstract method contract' in failure.stdout + failure.stderr
    print('PASS STAN: unrelated return rejected', flush=True)
