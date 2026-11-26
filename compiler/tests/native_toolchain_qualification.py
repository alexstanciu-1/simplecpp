"""Compare qualification decisions and all four observed traces with the Python reference."""
import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import sys
import tempfile
import time

ROOT = Path(__file__).resolve().parents[2]
REFERENCE = ROOT/'tools/layout_probe/qualification.py'


def verify(root, native, compiler, full):
    root.mkdir()
    include = root/'include files'
    include.mkdir()
    header = include/'cstdio'
    header.write_text('#include <nested/marker.h>\n#include_next <cstdio>\n')
    fallback = root/'fallback'
    (fallback/'nested').mkdir(parents=True)
    (include/'nested').mkdir()
    (fallback/'nested/marker.h').touch()
    flags = ['-std=c++23', '-I', str(include), '-I', str(fallback)]
    if 'clang' in Path(compiler).name:
        flags += ['-stdlib=libc++']
    native_cache, oracle_cache = root/'native', root/'python'
    implementations = [([native, 'qualify'], native_cache), ([sys.executable, '-B', REFERENCE], oracle_cache)]

    def pair(expected, *, fail=False):
        rows = []
        for command, cache in implementations:
            stamp = root/(cache.name + '.stamp')
            args = [*command, '--cxx', compiler, '--cache', cache, '--context-stamp', stamp,
                    *('--flag='+flag for flag in flags)]
            run = subprocess.run(args, capture_output=True, text=True, timeout=60)
            if fail:
                assert run.returncode and 'bad_header' in run.stderr, run
                rows.append(stamp.read_text().strip())
                continue
            assert run.returncode == 0, run.stderr
            match = re.fullmatch(r'Native argument probe (qualified|cached): ([a-f0-9]{64}) (.+)\n', run.stdout)
            assert match and match[1] == expected and stamp.read_text() == match[2]+'\n', run.stdout
            rows.append((match[2], json.loads(match[3])))
        if fail:
            return rows
        assert rows[0][1] == rows[1][1]
        return [row[0] for row in rows], rows[0][1]

    cold, traces = pair('qualified')
    stamps = [root/(cache.name+'.stamp') for _, cache in implementations]
    times = [path.stat().st_mtime_ns for path in stamps]
    assert pair('cached')[0] == cold
    assert [path.stat().st_mtime_ns for path in stamps] == times
    from native_build import host
    lookup = dict(action='lookup', cwd=str(root), cxx=str(Path(shutil.which(compiler)).absolute()),
                  flags=flags, cache=str(oracle_cache))
    with host(cache_only=True) as php:
        assert php(lookup)['result'] == cold[1]
        shadow = include/'nested/marker.h'
        shadow.write_text('#error bad_header shadow selected\n')
        assert php(lookup)['result'] is None
        assert pair(None, fail=True) == cold
        shadow.unlink()
        assert pair('cached')[0] == cold
        assert php(lookup)['result'] == cold[1]
    # Same size and mtime. The depfile snapshot's nanosecond ctime must catch it.
    old = header.stat()
    header.write_text('#include <nested/marker.h>\n#include_next "cstdio"\n')
    os.utime(header, ns=(old.st_atime_ns, old.st_mtime_ns))
    edited, _ = pair('qualified')
    assert all(a != b for a, b in zip(cold, edited))
    assert pair('cached')[0] == edited
    print(f'{Path(compiler).name}: cold qualified, unchanged cached, header edit qualified; {traces}', flush=True)
    if not full:
        return
    header.write_text('#error bad_header\n')
    assert pair(None, fail=True) == edited
    header.write_text('#include_next <cstdio>\n')
    repaired, _ = pair('qualified')
    flags.append('-O2')
    changed, _ = pair('qualified')
    assert all(a != b for a, b in zip(repaired, changed))
    assert pair('cached')[0] == changed
    # An unrecognized option takes the slow fingerprint path even with a valid record.
    flags.append('-DQUALIFICATION_EXAMPLE=1')
    slow, _ = pair('qualified')
    assert pair('cached')[0] == slow
    idx = next(p for p in native_cache.glob('native-*.json')
               if json.loads(json.loads(p.read_text())['payload'])['key'] == slow[0])
    record = oracle_cache/(slow[1]+'.json')
    py = json.loads(record.read_text())
    py['tested_at'] = time.time() - 604801
    record.write_text(json.dumps(py))
    n = json.loads(json.loads(idx.read_text())['payload'])
    n['time'] = int(py['tested_at'])
    payload = json.dumps(n)
    idx.write_text(json.dumps(dict(payload=payload, checksum=hashlib.sha256(payload.encode()).hexdigest())))
    assert pair('qualified')[0] == slow
    for corrupt in ['{', '{}']:
        idx.write_text(corrupt)
        record.write_text(corrupt)
        assert pair('qualified')[0] == slow


if __name__ == '__main__':
    full = '--full' in sys.argv[3:]
    with tempfile.TemporaryDirectory() as tmp:
        verify(Path(tmp)/'clang', Path(sys.argv[1]).resolve(), sys.argv[2], full)
        if full:
            verify(Path(tmp)/'gcc', Path(sys.argv[1]).resolve(), shutil.which('g++') or 'g++', True)
