"""Drive one native frontend process through edits and compare its publication with a cold process."""
from contextlib import contextmanager
import json
from pathlib import Path
import select
import shutil
import subprocess
import sys
import tempfile

from translated_vector_recipe import ROOT, build, fresh


@contextmanager
def service(exe, app):
    proc = subprocess.Popen([exe, app], stdin=subprocess.PIPE, stdout=subprocess.PIPE, text=True)

    def request(command):
        proc.stdin.write(command + '\n')
        proc.stdin.flush()
        assert select.select([proc.stdout], [], [], 10)[0], 'frontend response timed out'
        line = proc.stdout.readline()
        assert line.endswith('\n'), (proc.poll(), line)
        return line.rstrip('\n')

    try:
        yield request
    finally:
        proc.stdin.close()
        try:
            assert proc.wait(timeout=5) == 0
        finally:
            if proc.poll() is None:
                proc.kill()
                proc.wait()
            proc.stdout.close()


def verify(work, cxx, linker):
    examples = ROOT/'compiler/examples'
    tool = work/'tool'
    tool.mkdir()
    (tool/'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {
        'runtime': {'modules': ['filesystem', 'io']}}}))
    for name in ('frontend', 'function_ast'):
        shutil.copyfile(examples/f'translated_path/{name}.php.scpp', tool/f'{name}.php')
    for name in ('main', 'session'):
        shutil.copyfile(examples/f'retained_frontend/{name}.php.scpp', tool/f'{name}.php')
    shutil.copyfile(examples/'tokenizer/utf8.php.scpp', tool/'utf8.php')
    with (tool/'punctuation.php').open('wb') as output:
        subprocess.run(['php', examples/'tokenizer/emit_punctuation.php'], stdout=output, check=True)
    bundle, exe = work/'bundle', work/'frontend'
    manifest = fresh(tool, bundle)
    build(bundle, exe, cxx, linker, manifest['link_inputs'])

    app = work/'app'
    app.mkdir()
    definition = app/'definition.php'
    main = app/'main.php'
    main.write_text('return answer();')
    first = 'function answer(): int { return 1; }'
    definition.write_text(first)
    both, one, caller = 'definition.php,main.php,', 'definition.php,', 'main.php,'
    with service(exe, app) as run:
        def step(status, generation, attempted, reused):
            row = run('run')
            assert row.split('|') == [status, str(generation), attempted, attempted, reused], row
            print(row)
        step('ok', 1, both, '')
        initial = run('snapshot')
        step('ok', 2, '', both)
        assert run('snapshot') == initial
        definition.write_text(first.replace('1', '2'))
        step('ok', 3, one, caller)
        published = run('snapshot')
        assert published != initial
        invalid = first.replace('1;', '3')
        definition.write_text(invalid)
        step(f'definition.php:{invalid.index("}")}: Expected ;', 3, one, both)
        assert run('snapshot') == published
        definition.write_text(first.replace('1', '3'))
        step('ok', 4, one, caller)
        repaired = run('snapshot')
        assert repaired != published
        with service(exe, app) as cold:
            assert cold('run').split('|') == ['ok', '1', both, both, '']
            assert cold('snapshot') == repaired
        # A valid first candidate must also roll back if parsing the second file fails.
        definition.write_text(first)
        main.write_text('return answer()')
        step('main.php:15: Expected ;', 4, both, both)
        assert run('snapshot') == repaired
        definition.unlink()
        step('definition.php:0: Cannot read source', 4, '', both)
        definition.write_bytes(b'\xff')
        assert run('run').split('|') == ['definition.php:0: Invalid UTF-8 source', '4', one, '', both]
        assert run('snapshot') == repaired


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-retained-frontend-') as tmp:
        verify(Path(tmp), *sys.argv[1:])
