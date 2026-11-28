"""Exercise filesystem lock ownership across generated source processes."""
import fcntl
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import time

from app_namespace_native import invoke, service

root = Path(__file__).resolve().parents[2]
fixture = root/'compiler/tests/fixtures/resource_modules/file_locks.phs'
settings = {'general_api': True, 'dependencies': {'runtime': {'modules': ['filesystem']}}}

with tempfile.TemporaryDirectory(prefix='scpp-file-lock-') as tmp:
    work = Path(tmp)
    app, out = work/'app', work/'out'
    app.mkdir()
    manifest, main = app/'module.json', app/'main.phs'
    manifest.write_text(json.dumps(settings))
    main.write_text(fixture.read_text())
    compiler = service(app, out)
    try:
        response = invoke(compiler)
        assert 'error' not in response, response
        bundle = json.loads((out/'manifest.json').read_text())
        exe = work/'lock'
        subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                        *(out/u for u in bundle['translation_units']), *bundle['link_inputs'], '-o', exe], check=True)
        main.write_text('fs_lock_try(fs_lock_empty(), "path"); return 0;')
        assert 'requires stable variable storage' in invoke(compiler).get('error', '')
        main.write_text('class Holder { public readonly file_lock_handle $token; '
                        'public function __construct(){ $this->token=fs_lock_empty(); } } '
                        '$h=new Holder(); fs_lock_try($h->token,"path"); return 0;')
        assert 'requires stable variable storage' in invoke(compiler).get('error', '')
        main.write_text('$lock=fs_lock_empty(); return 0;')
        for off in ({'general_api': True}, {'dependencies': settings['dependencies']}):
            manifest.write_text(json.dumps(off))
            assert 'error' in invoke(compiler)
        manifest.write_text(json.dumps(settings))
        assert 'error' not in invoke(compiler)
    finally:
        compiler.stdin.close()
        compiler.wait()

    path = work/'data.lock'
    path.write_bytes(b'persistent\0content')
    inode = path.stat().st_ino
    def attempt(role, status):
        result = subprocess.run([exe, role, path], cwd=work, timeout=5)
        assert result.returncode == status, (role, result.returncode, status)

    holder = subprocess.Popen([exe,'hold',path], cwd=work, stdin=subprocess.PIPE)
    try:
        deadline = time.monotonic() + 5
        while not (work/'ready').exists():
            assert holder.poll() is None and time.monotonic() < deadline, holder.returncode
            time.sleep(.01)
        attempt('reader', 3)
        attempt('writer', 3)
        holder.communicate(b'release', timeout=5)
        assert holder.returncode == 0, holder.returncode
    finally:
        if holder.poll() is None:
            holder.kill()
            holder.wait()
    attempt('writer', 0)
    with path.open('rb') as reader:
        fcntl.flock(reader, fcntl.LOCK_SH)
        attempt('reader', 0)
        attempt('writer', 3)
    assert path.stat().st_ino == inode and path.read_bytes() == b'persistent\0content'

    for name in ['file_lock_handle','result_file_lock_handle','lock_empty','lock_try','lock_release','lock_transfer']:
        header = '#include <scpp/filesystem/'+name+'.hpp>\n'
        flags = [sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public', '-x', 'c++']
        subprocess.run([*flags,'-fsyntax-only','-'], input=header, text=True, check=True)
        deps = subprocess.check_output([*flags,'-M','-'], input=header, text=True)
        assert all(h not in deps for h in ['sys/file.h','fcntl.h','unistd.h','/private/','/scpp/php/']), deps

    native = work/'lifetime'
    subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                    root/'tests/filesystem_lock_lifetime.cpp', '-Wl,--wrap=close,--wrap=flock', *bundle['link_inputs'], '-o', native], check=True)
    subprocess.run([native], cwd=work, timeout=10, check=True)
