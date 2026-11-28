"""Observe source discovery and PHP name visibility with normal project builds."""
import json
from pathlib import Path
import subprocess
import tempfile

from check_apps import build


def attempt(label, app, work, wanted):
    try:
        exe = build(app, work)
    except RuntimeError as error:
        actual = str(error).strip().replace(str(app)+'/', '')
        if wanted not in actual:
            raise AssertionError((label, wanted, actual))
    else:
        actual = subprocess.check_output([exe], cwd=work).decode()
        if actual != wanted:
            raise AssertionError((label, wanted, actual))
    print(f'{label}: {actual}')


def projects(work):
    app = work/'web'
    app.mkdir()
    src = app/'src'
    src.mkdir()
    (app/'module.json').write_text('{}')
    (app/'main.phs').write_text('echo response(); return 0;')
    (src/'http.phs').write_text('function response():string{return "live";}')
    attempt('discover helper in src', app, work, 'live')
    tests = app/'tests'
    tests.mkdir()
    (tests/'control.php').write_text('<?php\necho "PHP test only";')
    attempt('ordinary PHP under tests', app, work, 'live')
    (tests/'module.json').write_text('{}')
    attempt('nested module boundary', app, work, 'live')
    (tests/'module.json').unlink()
    selected = {'sources': ['main.phs', 'src/http.phs']}
    (app/'module.json').write_text(json.dumps(selected))
    attempt('select application files explicitly', app, work, 'live')
    (src/'http.phs').rename(src/'response.phs')
    try:
        build(app, work)
    except RuntimeError as error:
        print('stale explicit source list:', str(error).strip().replace(str(app)+'/', ''))
    else:
        raise AssertionError('moved explicit source silently built')
    selected['sources'][1] = 'src/response.phs'
    (app/'module.json').write_text(json.dumps(selected))
    attempt('select moved helper', app, work, 'live')


def visibility(work):
    app = work/'web'
    app.mkdir()
    (work/'input').write_text('live')
    forms = {'PHP alias': 'file_get_contents', 'general operation': 'fs_get'}
    for name, operation in forms.items():
        (app/'main.phs').write_text(f'$r={operation}("input"); $s string=$r; echo $s; return 0;')
        for modules, opt_in in [([], False), (['filesystem'], False), (['filesystem'], True), ([], True)]:
            (app/'module.json').write_text(json.dumps({
                'dependencies': {'runtime': {'modules': modules}}, 'general_api': opt_in}))
            wanted = 'live' if modules and (name == 'PHP alias' or opt_in) else (
                'Unknown function: fs_get' if name == 'general operation' and not opt_in else 'requires module')
            attempt(f'{name} modules={modules} general={opt_in}', app, work, wanted)


if __name__ == '__main__':
    for run in (projects, visibility):
        with tempfile.TemporaryDirectory(prefix='php-layout-') as directory:
            run(Path(directory))
