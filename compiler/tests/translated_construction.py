"""Compare a translated constructor slot against retained Python compilation and native execution."""
import json
from pathlib import Path
import re
import shutil
import subprocess
import tempfile

from translated_preparation_recovery import TRANSLATION, ROOT, artifacts, fresh_build, invoke, measure, service, source_edit


def check(work):
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{"entry":"main.py"}')
    record, main = app/'box.py', app/'main.py'
    implicit = 'class Box:\n    value: int = 5\n'
    explicit = implicit + '    def __init__(self, n: int):\n        self.value = n\n'
    with_arg = 'return Box(7).value\n'
    empty = 'return Box().value\n'
    source_edit(record, explicit)
    source_edit(main, with_arg)
    compiler = service(app, out)
    toolchain = work/'toolchain.json'
    toolchain.write_text(json.dumps({'cxx': 'clang++', 'flags': ['-std=c++23', '-stdlib=libc++', '-O0',
                                                               '-I', str(ROOT/'runtime/generated/public')]}))

    def success(value):
        report = invoke(compiler)
        assert 'error' not in report, report
        manifest = json.loads((out/'manifest.json').read_text())
        unit, = [out/path for src, path in manifest['sources'].items() if Path(src).name == 'main.py']
        expression, = re.findall(rb'entry_exit_checked\(\((.*?)\)->value\)', unit.read_bytes())
        build = subprocess.run(['php', ROOT/'compiler/main.php', 'build', app, ROOT/'build/clang/native-layout/facts.json',
                                work/'build-out', work/'native-app', toolchain], capture_output=True)
        assert build.returncode == 0, build.stderr
        run = subprocess.run([work/'native-app/program'], capture_output=True)
        assert (run.returncode, run.stdout, run.stderr) == (value, b'', b''), run
        return expression + b'\n'

    def reject(detail):
        before = artifacts(out), (out/'manifest.json').read_bytes()
        error = invoke(compiler).get('error', '')
        assert 'main.py:1:8: ' + detail in error, error
        assert before == (artifacts(out), (out/'manifest.json').read_bytes())

    try:
        first = success(7)
        source_edit(record, implicit)
        reject('Only zero-argument initialization is available for Box')
        source_edit(main, empty)
        default = success(5)
        record.unlink()
        reject('Unknown function: Box')
        source_edit(record, explicit)
        reject('Argument count mismatch')
        source_edit(main, with_arg)
        repaired = success(7)
        fresh = work/'fresh'
        assert 'error' not in fresh_build(app, fresh)
        assert repaired == first and artifacts(out) == artifacts(fresh)
    finally:
        compiler.stdin.close()
        assert compiler.wait() == 0

    translated = work/'translated'
    translated.mkdir()
    (translated/'module.json').write_text('{}')
    (translated/'main.php').write_text('check_construction(); return 8;')
    shutil.copyfile(TRANSLATION, translated/'preparation.php')
    shutil.copyfile(TRANSLATION.with_name('construction.php.scpp'), translated/'construction.php')
    generated = work/'generated'
    report = fresh_build(translated, generated)
    assert 'error' not in report, report
    measure(generated, work/'native', first + default + repaired)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-construction-') as tmp:
        check(Path(tmp))
