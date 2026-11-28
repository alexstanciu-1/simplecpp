"""Bounded Linux edit/recovery profile for the isolated Apache fixture."""
import json
import os
from pathlib import Path
import time


def children(pid):
    return set(map(int, Path(f'/proc/{pid}/task/{pid}/children').read_text().split()))


def usage(pid):
    proc = Path(f'/proc/{pid}')
    fields = (proc / 'stat').read_text().rsplit(')', 1)[1].split()
    ticks = os.sysconf('SC_CLK_TCK')
    values = {line.split(':')[0]: int(line.split()[1]) for line in
              (proc / 'status').read_text().splitlines() if line.startswith(('VmRSS:', 'VmHWM:'))}
    return [1000 * sum(map(int, fields[i:i+2])) / ticks for i in (11, 13)] + [
        values['VmRSS'], values['VmHWM'], len(list((proc / 'fd').iterdir()))]


def measure(work, entry, source, proxy, call, expected):
    helper = entry.with_name('tag.phs')
    leaf = entry.with_name('version.phs')
    def definition(version):
        return f'function version(): string {{ return "{version}|"; }}'
    leaf.write_text(definition('A'))
    helper.write_text('function tag(): string { return version(); }')
    entry.write_text(source.replace('"A|"', 'tag()'))
    rows = []
    pid = None
    active = set()
    def request(kind, want, diagnostic=None):
        nonlocal pid, active
        before = usage(pid) if pid else [0, 0]
        objects = {p: p.stat().st_mtime_ns for p in work.glob('scpp-fcgi-*/native/*.o')}
        old_workers = children(proxy) - {pid}
        started = time.perf_counter()
        response = call()
        received = time.time_ns()
        ms = (time.perf_counter() - started) * 1000
        if diagnostic is not None:
            assert response[0] == 500 and diagnostic in response[2], response
        else:
            assert response == expected(want.encode()), response
        kids = children(proxy)
        if pid is None:
            pid, = [p for p in kids if b'compiler.php' in Path(f'/proc/{p}/cmdline').read_bytes()]
        assert pid in kids, kids
        after = usage(pid)
        current = {p: p.stat().st_mtime_ns for p in work.glob('scpp-fcgi-*/native/*.o')}
        changed = sum(objects.get(p) != t for p, t in current.items())
        fresh = kids - {pid} - old_workers
        reused = not fresh
        if fresh:
            assert len(fresh) == 2, fresh
            active = fresh
        assert len(active) == 2 and active <= kids, kids
        if kind in ('unchanged', 'error', 'comment'):
            assert changed == 0 and reused, (kind, changed, reused)
        if kind in ('leaf', 'stamp-collision', 'recovery'):
            assert 0 < changed < len(current) and not reused, (kind, changed, reused)
        rows.append(dict(kind=kind, wall_ms=round(ms, 2), php_cpu_ms=after[0]-before[0],
                         build_children_cpu_ms=after[1]-before[1], php_rss_kib=after[2],
                         php_hwm_kib=after[3], php_fds=after[4], changed_objects=changed,
                         total_objects=len(current), reused_workers=reused,
                         after_activation_ms=None if reused else round(
                             (received - (work / 'supervisor.log').stat().st_mtime_ns) / 1e6, 2)))
    request('cold', 'A')
    for _ in range(12):
        request('unchanged', 'A')
    leaf.write_text(definition('A') + ' // comment')
    request('comment', 'A')
    for i, version in enumerate('BCDEFG'):
        stamp = leaf.stat()
        # A body edit, then repeated preserved-timestamp edits of equal length.
        leaf.write_text(definition(version))
        if i % 2:
            os.utime(leaf, ns=(stamp.st_atime_ns, stamp.st_mtime_ns))
        request('stamp-collision' if i % 2 else 'leaf', version)
        broken = helper.read_text().replace('version()', 'unknown_version()')
        helper.write_text(broken)
        request('error', version, b'unknown_version')
        helper.write_text(broken.replace('unknown_version()', 'version()'))
        leaf.write_text(definition(version.lower()))
        request('recovery', version.lower())
        for _ in range(3):
            request('unchanged', version.lower())
    # A return-contract edit invalidates a caller in another source.
    leaf.write_text('function version(): void {}')
    request('error', '', b'')
    leaf.write_text(definition('Z'))
    request('recovery', 'Z')
    deadline = time.monotonic() + 5
    while children(proxy) != active | {pid}:
        assert time.monotonic() < deadline, 'old workers did not retire'
        time.sleep(.02)
    workers = sorted(active)
    processes = [proxy, pid, *workers]
    initial = [usage(p) for p in processes]
    time.sleep(1)
    final = [usage(p) for p in processes]
    print(json.dumps(dict(rows=rows, compiler_pid=pid, idle_cpu_ms=[b[0]-a[0] for a,b in zip(initial,final)],
                          final_rss_kib=[v[2] for v in final]), indent=2))
