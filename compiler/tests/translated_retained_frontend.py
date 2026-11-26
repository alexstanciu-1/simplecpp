"""Drive one native frontend process through edits and compare its publication with a cold process."""
from contextlib import contextmanager
import fcntl
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


def frontend_project(work, *, socket_service=False):
    examples = ROOT/'compiler/examples'
    tool = work/'tool'
    tool.mkdir()
    modules = ['filesystem'] if socket_service else ['filesystem', 'io']
    (tool/'module.json').write_text(json.dumps({'general_api': True, 'dependencies': {
        'runtime': {'modules': modules}}}))
    frontend = ['frontend', 'function_ast']
    session = ['session', 'frontend_requests']
    if socket_service:
        shutil.copyfile(examples/'retained_frontend/socket/main.php.scpp', tool/'main.php')
    else:
        frontend.append('compiler')
        session.extend(['main', 'lowering'])
    for name in frontend:
        shutil.copyfile(examples/f'translated_path/{name}.php.scpp', tool/f'{name}.php')
    for name in session:
        shutil.copyfile(examples/f'retained_frontend/{name}.php.scpp', tool/f'{name}.php')
    shutil.copyfile(examples/'tokenizer/utf8.php.scpp', tool/'utf8.php')
    with (tool/'punctuation.php').open('wb') as output:
        subprocess.run(['php', examples/'tokenizer/emit_punctuation.php'], stdout=output, check=True)
    return tool


def verify(work, cxx, linker):
    tool = frontend_project(work)
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

    definition.write_text(first)
    main.write_text('return answer();')
    with service(exe, app) as run:
        def prepare(status, frontend, prepared, parsed, work_done, reused):
            row = run('prepare')
            assert row.split('|') == [status, str(frontend), str(prepared), parsed, work_done, reused], row
            print(row)

        prepare('ok', 1, 1, both, both, '')
        prepare('ok', 2, 2, '', '', both)
        definition.write_text(first.replace('1', '2'))
        prepare('ok', 3, 3, one, one, caller)
        good = run('prepared-snapshot')
        assert good.startswith('answer|func:answer|2|')
        definition.write_text(first.replace('1', '9223372036854775808'))
        error = f'definition.php:{first.index("1")}: Integer literal is outside int64 range'
        prepare(error, 4, 3, one, one, both)
        assert run('prepared-snapshot') == good
        prepare(error, 5, 3, '', one, both)
        definition.write_text(first.replace('1', '3'))
        prepare('ok', 6, 6, one, one, caller)
        repaired = run('prepared-snapshot')
        assert repaired.startswith('answer|func:answer|3|') and repaired != good
        assert b'return 3LL;' in bytes.fromhex(repaired.split('|')[-1])
        with service(exe, app) as cold:
            assert cold('prepare').split('|') == ['ok', '1', '1', both, both, '']
            assert cold('prepared-snapshot') == repaired

        # A changed public name invalidates an unchanged caller; both candidates publish together.
        definition.write_text(first.replace('answer', 'other'))
        prepare('main.php:7: Unknown function: answer', 7, 6, one, both, both)
        assert run('prepared-snapshot') == repaired
        main.write_text('return other();')
        prepare('ok', 8, 8, caller, both, '')
        assert run('prepared-snapshot').startswith('other|func:other|1|')

    definition.write_text(first)
    main.write_text('return answer();')
    generated = app/'generated'
    cpp = ['definition.cpp', 'main.cpp', 'entry.cpp']

    def artifacts():
        if not generated.exists():
            return {}
        assert all(p.name in [*cpp, '.scpp-bundle.lock'] for p in generated.iterdir())
        return {n: ((generated/n).read_bytes(), (generated/n).stat().st_ino, (generated/n).stat().st_mtime_ns)
                for n in cpp if (generated/n).is_file()}

    with service(exe, app) as run:
        def lower(case, status, prepared, emitted, changed):
            before = artifacts()
            row = run('lower')
            assert row.split('|') == [status, prepared, emitted, changed], (case, row)
            after = artifacts()
            actual = [n for n in cpp if before.get(n) != after.get(n)]
            expected = changed.rstrip(',').split(',') if changed else []
            assert actual == expected, (case, actual)
            print(f'{case}: {row}')

        lower('cold', 'ok', both, both, 'definition.cpp,main.cpp,entry.cpp,')
        lower('unchanged', 'ok', '', '', '')
        definition.write_text(first.replace('1', '2'))
        lower('body edit', 'ok', one, one, 'definition.cpp,')
        definition.write_text(definition.read_text() + ' // comment')
        lower('comment edit', 'ok', one, one, '')
        definition.write_text(first.replace('1', '9223372036854775808'))
        lower('preparation error', error, one, '', '')
        definition.write_text(first.replace('1', '3'))
        lower('repair', 'ok', one, one, 'definition.cpp,')
        definition.write_text(first.replace('1', '4'))
        with (generated/'.scpp-bundle.lock').open('rb') as reader:
            fcntl.flock(reader, fcntl.LOCK_SH)
            lower('publication blocked', 'Output bundle busy', one, one, '')
        lower('retry same input', 'ok', '', one, 'definition.cpp,')
        lower('settled', 'ok', '', '', '')

        # Make the second destination invalid. The first replacement has already happened.
        saved = artifacts()
        definition.write_text(first.replace('answer', 'renamed').replace('1', '5'))
        main.write_text('return renamed();')
        (generated/'main.cpp').rename(work/'saved-caller')
        (generated/'main.cpp').mkdir()
        lower('rollback during publication', 'Invalid output file', both, both, '')
        (generated/'main.cpp').rmdir()
        (work/'saved-caller').rename(generated/'main.cpp')
        assert artifacts() == saved
        lower('retry after rollback', 'ok', '', both, 'definition.cpp,main.cpp,')
        fresh_app = work/'cold-app'
        fresh_app.mkdir()
        for name in ('definition.php', 'main.php'):
            shutil.copyfile(app/name, fresh_app/name)
        with service(exe, fresh_app) as cold:
            assert cold('lower').split('|') == ['ok', both, both, 'definition.cpp,main.cpp,entry.cpp,']
        assert all((generated/n).read_bytes() == (fresh_app/'generated'/n).read_bytes() for n in cpp)
        binary = work/'lowered-program'
        subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', f'--ld-path={linker}', '-I',
                        ROOT/'runtime/generated/public', *(generated/n for n in cpp),
                        *manifest['link_inputs'], '-o', binary], check=True)
        assert subprocess.run([binary], timeout=10).returncode == 5


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-retained-frontend-') as tmp:
        verify(Path(tmp), *sys.argv[1:])
