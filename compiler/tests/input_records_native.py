"""Compare fixed source records and the derived path index with the PHP input scanner."""
from source_suffix import source_suffix
import json
import os
from pathlib import Path
import subprocess
import argparse
import tempfile

from classes_native import fixture
from collection_goal_native import revisions

ORACLE = Path(__file__).with_name('input_records_oracle.php')
SAVED = [('dialect', 'string'), ('mtime', 'int'), ('size', 'int')]
IDENTITY = [('moduleName', 'string'), ('root', 'string'), ('kind', 'string'), ('path', 'string')]


def record(s, flavor, name, indexed=False):
    fields = IDENTITY + SAVED if indexed else SAVED
    arguments = IDENTITY + [('stamp', 'SourceStamp')] if indexed else SAVED
    v, access = s['v'], s['access']
    params = ','.join(f'{key}:{typ}' if flavor == 'js' else f'{typ} {v}{key}' for key, typ in arguments)
    ctor = {'cpp': name, 'php': 'public function __construct', 'js': 'constructor'}[flavor]
    assignments = ''
    for field in fields:
        key = field[0]
        owner = 'stamp' + access if indexed and field in SAVED else ''
        assignments += f'{v}this{access}{key}={v}{owner}{key};'
    return s['record'](name, ''.join(s['field'](key, typ) for key, typ in fields)
                       + f'{ctor}({params}) {{{assignments}}}')


def program(flavor, root, inputs):
    s = fixture(flavor)
    v, a = s['v'], s['access']
    concat = '.' if flavor == 'php' else '+'
    body = ''.join(s['local'](name, typ).removesuffix(';') + ' = [];' for name, typ in (
        ('sources', 'hash<SourceStamp,string>'), ('index', 'hash<PathRow,string>')))
    for i, (path, dialect, mtime, size) in enumerate(reversed(inputs)):
        args = f'({json.dumps(dialect)},{mtime},{size})'
        body += s['local']('stamp' + str(i), 'SourceStamp').removesuffix(';') + args + ';'
        body += f'{v}sources[{json.dumps(path)}]={v}stamp{i};'
    body += s['infer']('root', json.dumps(str(root)))
    body += f'foreach ({v}sources as {v}relative => {v}stamp) {{'
    body += s['local']('row', 'PathRow').removesuffix(';') + f'("app",{v}root,"default",{v}relative,{v}stamp);'
    body += f'{v}index[{v}root {concat} "/" {concat} {v}relative]={v}row;}}'
    body += f'if (count({v}index) != {len(inputs)}) {{return 91;}}'
    for i, (path, *_) in enumerate(inputs):
        body += s['infer'](f'saved{i}', f'{v}sources[{json.dumps(path)}]')
        key = json.dumps(str(root / path))
        body += s['infer'](f'found{i}', f'{v}index[{key}]')
        saved, found = f'{v}saved{i}{a}', f'{v}found{i}{a}'
        values = [json.dumps(path), *(saved+k for k, _ in SAVED), key,
                  *(found+k for k, _ in IDENTITY + SAVED)]
        body += 'echo ' + ', "\\0", '.join(values) + ', "\\0";'
    return body + 'return 8;'


def snapshots(work):
    root = work / 'app'
    (root / 'sub').mkdir(parents=True)
    (root / 'main.cpp').write_text('return 8;')
    (root / 'facts.json').write_text('{}')
    helper = root / 'sub/helper.phs'
    cases = []
    for i, content in enumerate((b'', b'// edit', None, b'')):
        if content is None:
            helper.unlink()
        else:
            helper.write_bytes(content)
            stamp = 1800000000 + (i == 1)
            os.utime(helper, (stamp, stamp))
        inputs = []
        for path in sorted(root.rglob('*')):
            if path.suffix in ('.cpp', '.phs'):
                stat = path.stat()
                inputs.append((path.relative_to(root).as_posix(), 'php' if path.suffix == '.phs' else path.suffix[1:], int(stat.st_mtime), stat.st_size))
        bundle = work / f'bundle-{i}'
        index = json.loads(subprocess.check_output(['php', ORACLE, 'capture', root, bundle]))
        state = json.loads((bundle / 'manifest.json').read_bytes())['build']['inputs']
        sources = state['modules']['app']['sources']
        assert len(inputs) == len(index) == len(sources)
        fields = []
        for path, source in sources.items():
            key = str(root / path)
            fields.extend((path, *source.values(), key, *index[key].values()))
        expected = b''.join(str(value).encode() + b'\0' for value in fields)
        cases.append((inputs, expected, bundle))
    assert cases[0][1] == cases[-1][1] and len({case[1] for case in cases}) == 3
    initial, restored = [json.loads((cases[i][2] / 'manifest.json').read_bytes())['build']['inputs'] for i in (0, -1)]
    assert initial == restored
    return root, cases


def prove(work, flavor, root, cases):
    s = fixture(flavor)
    records = record(s, flavor, 'SourceStamp') + record(s, flavor, 'PathRow', indexed=True)
    mains = [program(flavor, root, inputs) for inputs, _, _ in cases]
    edits = [(None, None, cases[0][1])] + [
        ('main', mains[i], cases[i][1]) for i in range(1, len(cases))]
    revisions(work, flavor, {'records': records, 'main': mains[0]}, edits, unaffected_source='records.' + source_suffix(flavor))
    for i, (_, expected, bundle) in enumerate(cases):
        run = subprocess.run([work / f'{flavor}-native-{i}' / 'program'], capture_output=True)
        assert run.returncode == 8 and run.stdout == expected
        # A saved row and index row each start with their respective lookup key.
        width = 2 + 2 * len(SAVED) + len(IDENTITY)
        values = run.stdout[:-1].decode().split('\0')
        assert len(values) == width * len(cases[i][0])
        sources = {}
        for start in range(0, len(values), width):
            path, dialect, mtime, size = values[start:start + 4]
            sources[path] = dict(flavor=dialect, mtime=int(mtime), size=int(size))
        subprocess.run(['php', ORACLE, 'compare', root, bundle], input=json.dumps(sources).encode(), check=True)


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--quick", action="store_true")
    quick = parser.parse_args().quick
    with tempfile.TemporaryDirectory(prefix='scpp-input-records-') as directory:
        work = Path(directory)
        root, cases = snapshots(work)
        for flavor in (('php',) if quick else ('cpp', 'php', 'js')):
            prove(work, flavor, root, cases)
