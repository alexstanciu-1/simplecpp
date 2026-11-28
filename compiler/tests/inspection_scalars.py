"""Check scalar JSON through the CLI and panel, including globals in a second source unit."""
from source_suffix import source_suffix
import json
from pathlib import Path
import subprocess
import tempfile

from collection_goal_native import revisions

ROOT = Path(__file__).resolve().parents[2]
FACTS = ROOT / 'build/clang/native-layout/facts.json'


def prove(work, flavor):
    def declaration(name, kind, value):
        return {'php': f'${name} {kind} = {value};', 'cpp': f'{kind} {name} = {value};',
                'js': f'let {name}:{kind} = {value};'}[flavor]

    var = '$' if flavor == 'php' else ''
    fields = [('flag', 'bool', 'false', False), ('yes', 'bool', 'true', True),
              ('maximum', 'int', '9223372036854775807', '9223372036854775807'),
              ('zero', 'float', '-0.0', '-0x0.0000000000000p-1022'),
              ('fraction', 'float', '1.5', '0x1.8000000000000p0'), ('nil', 'null', 'null', None),
              ('converted', 'int', 'true', '1')]
    globals_text = ''.join('global ' + declaration(n, t, v) for n, t, v, _ in fields)
    body = declaration('known', 'null', 'null') + declaration('unknown', 'bool', var + 'flag')
    body += 'echo (int)' + var + 'unknown;return 8;'
    app = work / flavor
    app.mkdir()
    sources = app / 'sources'
    sources.mkdir()
    main = sources / ('main.' + source_suffix(flavor))
    main.write_text(globals_text + body)
    cli = json.loads(subprocess.check_output(['php', ROOT/'compiler/main.php', 'prepare', flavor, main, FACTS]))
    # Compare JSON types as well as values (Python equates False and 0).
    expected = {n: v for n, _, _, v in fields}
    actual = {row['name']: row['initializer'] for row in cli['globals']}
    assert json.dumps(actual, sort_keys=True) == json.dumps(expected, sort_keys=True), actual
    known, unknown = [s['initializer'] for s in cli['statements'][:2]]
    assert known['constant'] is None and known['constant_known'] is True
    assert unknown['constant'] is None and unknown['constant_known'] is False
    library = sources / ('library.' + source_suffix(flavor))
    library.write_text(globals_text)
    main.write_text(body)
    request = app / 'request.json'
    request.write_text(json.dumps({'entry': main.name, 'files': [
        {'name': f.name, 'flavor': flavor} for f in (main, library)]}))
    report = json.loads(subprocess.check_output(['php', ROOT/'tools/compiler_panel/report.php', request]))
    assert report['ok'], report.get('error')
    stage = next(s['data'] for s in report['stages'] if s['name'].startswith('Preparation'))
    rows = next(iter(stage['globals_by_file'].values()))
    assert json.dumps(rows) == json.dumps(cli['globals'])
    assert stage['entry']['statements'] == cli['statements']

    # Exercise the identical source through executable edits and fresh-compilation recovery.
    native = work / 'native'
    native.mkdir(exist_ok=True)
    revisions(native, flavor, dict(main=body, library=globals_text), [(None, None, b'0'), (None, None, b'0'),
              ('library', globals_text.replace('false', 'true'), b'1'), ('library', None, None),
              ('library', globals_text, b'0')])


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-inspection-scalars-') as tmp:
        for flavor in ('php', 'cpp', 'js'):
            prove(Path(tmp), flavor)
