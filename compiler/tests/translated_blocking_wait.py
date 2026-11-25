"""Verify direct producer selection and recovered wait-helper lowering against the PHP compiler."""
from pathlib import Path
import shutil
import tempfile

from translated_preparation_recovery import TRANSLATION, artifacts, fresh_build, function_text, invoke, measure, service, source_edit


def check(work):
    app, out = work/'app', work/'out'
    app.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.php').write_text('echo probe(); return 8;')
    probe, job = app/'probe.php', app/'job.php'
    source = 'function probe():int {return async_wait(job(7));}'
    declaration = 'async function job(int $n):int {return $n;}'
    source_edit(probe, source)
    source_edit(job, declaration)
    process = service(app, out)
    try:
        assert 'error' not in invoke(process)
        first = function_text(out, 'probe')
        measure(out, work/'initial-program', b'7')
        published = artifacts(out), (out/'manifest.json').read_bytes()
        source_edit(job, declaration.removeprefix('async '))
        failure = invoke(process).get('error', '')
        assert 'probe.php:1:30: async_wait requires an async function' in failure, failure
        assert published == (artifacts(out), (out/'manifest.json').read_bytes())
        source_edit(job, declaration)
        source_edit(probe, source.replace('job(7)', '7'))
        failure = invoke(process).get('error', '')
        assert 'probe.php:1:30: async_wait requires one direct async call' in failure, failure
        assert published == (artifacts(out), (out/'manifest.json').read_bytes())
        source_edit(probe, source.replace('7', '9'))
        assert 'error' not in invoke(process)
        second = function_text(out, 'probe')
        measure(out, work/'repaired-program', b'9')
        assert first != second
        assert 'error' not in fresh_build(app, work/'fresh')
        assert artifacts(out) == artifacts(work/'fresh')
    finally:
        process.stdin.close()
        assert process.wait() == 0

    translated = work/'translated'
    translated.mkdir()
    (translated/'module.json').write_text('{}')
    (translated/'main.php').write_text('check_wait(); return 8;')
    shutil.copyfile(TRANSLATION, translated/'preparation.php')
    shutil.copyfile(TRANSLATION.with_name('blocking_wait.php.scpp'), translated/'wait.php')
    generated = work/'generated'
    result = fresh_build(translated, generated)
    assert 'error' not in result, result
    measure(generated, work/'native', first + second)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-blocking-producer-') as directory:
        check(Path(directory))
