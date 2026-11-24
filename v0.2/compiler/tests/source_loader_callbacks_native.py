"""Native proof for the frontend's explicit loader state and named workers."""
import argparse
from pathlib import Path
import tempfile

from app_namespace_native import artifacts
from classes_native import fixture
from collection_goal_native import revisions


def prove(work, flavor):
    form = fixture(flavor)
    v, access, fn, infer = form['v'], form['access'], form['function'], form['infer']
    field, klass = form['field'], form['klass']

    types = klass('SourceRequest', field('path', 'string', '"entry.scpp"'))
    types += klass('SourceSnapshot', field('text', 'string'))
    types += klass('SourceSnapshots', field('entries', 'hash<SourceSnapshot,string>'))

    loader_arguments = [('unit', 'SourceRequest'), ('snapshots', 'SourceSnapshots')]
    # The actual compiler reads the disk branch through its PHP host. Here the
    # worker supplies a distinct snapshot so the callback ABI can run natively.
    disk = fn('loadDisk', 'SourceSnapshot', loader_arguments,
              infer('source', 'new SourceSnapshot()') + f'{v}source{access}text = "disk"; return {v}source;')
    memory = fn('loadSnapshot', 'SourceSnapshot', loader_arguments,
                f'return {v}snapshots{access}entries[{v}unit{access}path];')
    runner = fn('runLoader', 'SourceSnapshot',
                [('load', 'callback<SourceSnapshot,SourceRequest,SourceSnapshots>'), *loader_arguments],
                f'return {v}load({v}unit, {v}snapshots);')
    factory = fn('makeSnapshots', 'SourceSnapshots', [('source', 'SourceSnapshot')],
                 infer('snapshots', 'new SourceSnapshots()')
                 + f'{v}snapshots{access}entries["entry.scpp"] = {v}source; return {v}snapshots;')

    main = infer('expected', 'new SourceSnapshot()') + f'{v}expected{access}text = "memory";'
    main += infer('snapshots', f'makeSnapshots({v}expected)') + infer('original', v + 'snapshots')
    main += f'{v}snapshots = new SourceSnapshots();' + infer('unit', 'new SourceRequest()')
    main += infer('disk', f'runLoader(loadDisk(...), {v}unit, {v}snapshots)')
    main += infer('memory', f'runLoader(loadSnapshot(...), {v}unit, {v}original)')
    main += f'''
        if ({v}memory !== {v}expected) {{ return 90; }}
        echo {v}disk{access}text, ":", {v}memory{access}text;
        return 8;
    '''
    sources = dict(main=main, types=types, runner=runner, disk=disk, memory=memory, factory=factory)
    work.mkdir()
    revisions(work, flavor, sources, [
        (None, None, b'disk:memory'),
        (None, None, b'disk:memory'),
        ('disk', disk.replace('"disk"', '"disk-updated"'), b'disk-updated:memory'),
        ('memory', fn('loadSnapshot', 'SourceSnapshot', [('unit', 'SourceRequest')], 'return new SourceSnapshot();'),
         'Callback target signature mismatch'),
        ('memory', None, 'Callback requires a source free function or static method'),
        ('memory', memory, b'disk-updated:memory'),
    ], unaffected_source=f'main.{flavor}', unaffected_revisions={2})
    generated = b'\n'.join(artifacts(work / f'{flavor}-out').values())
    assert b'(*var_load_' in generated and b'&loadDisk_' in generated and b'&loadSnapshot_' in generated
    assert b'std::function' not in generated and b'#include <functional>' not in generated


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument("--quick", action="store_true")
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-source-loader-callbacks-') as temporary:
        root = (args.output or Path(temporary)).resolve()
        root.mkdir(parents=True, exist_ok=True)
        for flavor in (('php',) if args.quick else ('php', 'cpp', 'js')):
            prove(root / flavor, flavor)


if __name__ == '__main__':
    main()
