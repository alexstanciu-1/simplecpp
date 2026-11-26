"""PHP in-process qualification and native host: retained checks, cold failure, warm reuse and standalone gate."""
import argparse
import copy
from contextlib import contextmanager
import fcntl
import json
import os
from pathlib import Path
import shutil
import shlex
import statistics
import subprocess
import sys
import tempfile
import time
from unittest.mock import patch

ROOT = Path(__file__).resolve().parents[2]
sys.path.insert(0, str(ROOT / 'tools/layout_probe'))
import qualification as q

ENV = {**os.environ, 'XDEBUG_MODE': 'off'}
HOST = ROOT / 'compiler/tests/native_build_host.php'


def run(args, **kwargs):
    return subprocess.run(list(map(str, args)), capture_output=True, text=True, env=ENV, timeout=90, **kwargs)


@contextmanager
def host(cache_only=False):
    command = ['php']
    if cache_only:
        command += ['-d', 'disable_functions=proc_open,exec,shell_exec,system,passthru,popen,pcntl_exec']
    p = subprocess.Popen(command + [str(HOST)], stdin=subprocess.PIPE, stdout=subprocess.PIPE,
                         stderr=subprocess.PIPE, text=True, env=ENV)
    def request(data):
        p.stdin.write(json.dumps(data) + '\n'); p.stdin.flush()
        line = p.stdout.readline()
        assert line, p.stderr.read()
        result = json.loads(line)
        assert 'error' not in result, result
        return result
    try:
        yield request
    finally:
        p.stdin.close(); p.wait(timeout=5)
        errors = p.stderr.read()
        assert p.returncode == 0 and errors == '', errors
        p.stdout.close(); p.stderr.close()


def publish_fixture(cache, index, record):
    """Explicitly reseal a synthetic publisher fixture; corruption tests must not use this helper."""
    record['context']['cache_schema'] = q.CACHE_SCHEMA
    record['context']['inputs_sha256'] = q.sha(json.dumps(index['inputs'], sort_keys=True).encode())
    key = q.sha(json.dumps(record['context'], sort_keys=True).encode())
    index['key'] = key
    record_path = cache/(key+'.json')
    record_path.write_text(json.dumps(record))
    (cache/('inputs-'+record['context']['request']+'.json')).write_text(json.dumps(index))
    return key, record_path


def cache_proof(work, compiler):
    work.mkdir(parents=True)
    cache = work / 'cache'
    include = work / 'include é $'
    include.mkdir()
    flags = ['-std=c++23', '-O2', '-I', str(include)]
    key, _, _ = q.qualify(compiler, flags, cache)
    index_path, = cache.glob('inputs-*.json')
    record_path = cache / (key + '.json')
    index, record = json.loads(index_path.read_text()), json.loads(record_path.read_text())
    request = dict(action='lookup', cwd=str(work), cxx=compiler, flags=flags, cache=str(cache))
    with host(cache_only=True) as check:
        def lookup(**changes):
            return check({**request, **changes})['result']
        assert lookup() == key
        samples = [check(request)['ms'] for _ in range(31)]
        # Context and flag split must agree with the Python owner, not merely with record filenames.
        assert lookup(flags=flags + ['-O0']) is None
        assert lookup(link_flags=['-g0']) is None
        linked_key, _, _ = q.qualify(compiler, flags, cache, ['-g0'])
        assert lookup(link_flags=['-g0']) == linked_key
        assert lookup(flags=flags+['-g0']) is None
        assert lookup(cxx='/different/compiler') is None
        assert lookup(flags=flags + ['@options.rsp']) is None
        assert lookup(environment={'CPATH': str(work/'absent')}) is None
        assert lookup(environment={'CPATH': None}) == key
        assert lookup(environment={'LD_PRELOAD': '/missing/preload.so'}) is None
        assert lookup(environment={'LD_PRELOAD': None}) == key
        slot = index_path.stem.removeprefix('inputs-')
        with (cache / (slot + '.lock')).open('a') as lock:
            fcntl.flock(lock, fcntl.LOCK_EX)
            assert lookup() is None
        assert lookup() == key
        for replacement in ('{', '[]', '{}'):
            record_path.write_text(replacement)
            assert lookup() is None
        for patch in ({'tested_at': time.time()-q.WEEK-1}, {'tested_at': time.time()+3600},
                      {'tested_at': 'bad'}, {'traces': {'divide': ['LR']}}, {'context': []}):
            record_path.write_text(json.dumps({**record, **patch}))
            assert lookup() is None
        record_path.write_text(json.dumps(record))
        for patch in ({'key': '../bad'}, {'inputs': {}}, {'inputs': {str(include): [1]}},
                      {'inputs': {str(include): {'a': 1, 'b': 2, 'c': 3, 'd': 4, 'e': 5}}}):
            index_path.write_text(json.dumps({**index, **patch}))
            assert lookup() is None
        index_path.write_text(json.dumps(index))
        for path in [compiler, str(q.SOURCE), str(Path(q.__file__).resolve())]:
            stale = json.loads(json.dumps(index))
            stale['inputs'][path][2] += 1
            index_path.write_text(json.dumps(stale))
            assert lookup() is None
        index_path.write_text(json.dumps(index))
        # Clearstatcache is required: this same PHP process previously observed these exact paths.
        observed = include.stat()
        os.utime(include, (observed.st_atime, observed.st_mtime+2))
        assert lookup() is None
        os.utime(include, ns=(observed.st_atime_ns, observed.st_mtime_ns))
        # Deliberately store a fresh baseline after changing timestamps (ctime cannot be restored).
        index['inputs'][str(include)] = q.stamp(str(include))
        key, record_path = publish_fixture(cache, index, record)
        assert lookup() == key
        # Accepted precision debt: alter only nanosecond fractions in an input record.
        stamp = index['inputs'][str(include)]
        for field in (3, 4):
            stamp[field] = stamp[field]//10**9*10**9 + (stamp[field]+123456)%10**9
        key, record_path = publish_fixture(cache, index, record)
        assert lookup() == key
        include.rmdir()
        assert lookup() is None
        index['inputs'][str(include)] = None
        publish_fixture(cache, index, record)
        # An absent include tree requires preprocessing even with a matching null stat.
        assert lookup() is None
        include.mkdir()
        assert lookup() is None
    # Relative inputs are keyed by the build cwd; environment and symlink spellings survive cross-language hashing.
    previous = Path.cwd()
    try:
        os.chdir(work)
        relative_flags = ['-std=c++23', '-I', 'include é $']
        relative_key, _, _ = q.qualify(compiler, relative_flags, cache)
    finally:
        os.chdir(previous)
    with host(cache_only=True) as check:
        assert check({**request, 'flags': relative_flags})['result'] == relative_key
        assert check({**request, 'flags': relative_flags, 'cwd': str(previous)})['result'] is None
    print(json.dumps({'compiler': compiler, 'cache_inputs': len(index['inputs']),
                      'warm_check_median_ms': statistics.median(samples), 'cache_proof': 'passed'}), flush=True)


def protocol_parity(work, compiler):
    """Shared admission and integrity vectors, with process execution disabled for both readers."""
    work.mkdir(parents=True)
    cache = work/'cache'
    flags = ['-std=c++23', '-O2']
    key, _, _ = q.qualify(compiler, flags, cache)
    index_path, = cache.glob('inputs-*.json')
    original_index = json.loads(index_path.read_text())
    original_record = json.loads((cache/(key+'.json')).read_text())
    env_keys = ['PATH', 'CPATH', 'CPLUS_INCLUDE_PATH', 'C_INCLUDE_PATH', 'GCC_EXEC_PREFIX', 'COMPILER_PATH',
                'LIBRARY_PATH', 'LD_LIBRARY_PATH', 'LD_PRELOAD', 'SDKROOT', 'CCC_OVERRIDE_OPTIONS']
    cases = [(flags+[option], [], {}, True) for option in
             ['-O0','-O1','-O2','-O3','-Os','-Oz','-g','-g0','-DNDEBUG','-Wall','-Wextra','-Wpedantic','-Werror','-stdlib=libc++']]
    for name in ('-relative', 'relative', 'include é $'):
        (work/name).mkdir()
    cases += [(flags+['-I', '-relative'], [], {}, True),
              (flags+['-include-pch', '-relative.pch'], [], {}, True),
              (flags+['-I', 'relative'], [], {}, True), (flags+['-I', str(work/'relative')], [], {}, True),
              (flags+['-include-pch', 'relative.pch'], [], {}, True),
              (flags+['-Xclang','-fno-validate-pch'], [], {}, True),
              (flags, ['--ld-path=relative-linker'], {}, True), (flags, ['--ld-path=/usr/bin/ld'], {}, True),
              (flags, ['-g0'], {}, True), (flags+['@args.rsp'], [], {}, False),
              (flags+['-I'], [], {}, False), (flags+['-I',''], [], {}, False),
              (flags+['--ld-path='], [], {}, False), (flags+['-Xclang'], [], {}, False),
              (flags+['-Xclang','other'], [], {}, False), (flags, [], {'CPATH':'relative'}, True),
              (flags, [], {'CPATH':str(work/'include é $')}, True),
              (flags, [], {'LD_PRELOAD':'0'}, False), (flags, [], {'CCC_OVERRIDE_OPTIONS':'0'}, False)]
    old_cwd = Path.cwd()
    try:
        os.chdir(work)
        with host(cache_only=True) as php, patch.object(q, 'execute', side_effect=AssertionError('subprocess in warm reader')), \
                patch.object(q, 'file_hash', side_effect=AssertionError('binary hash in warm reader')):
            for current_flags, links, env, allowed in cases:
                with patch.dict(os.environ, env):
                    _, request, admitted, _ = q.request_context(compiler, current_flags, links)
                    slot = q.sha(json.dumps(request, sort_keys=True).encode())
                    record, index = copy.deepcopy(original_record), copy.deepcopy(original_index)
                    record['context'].update(request=slot, flags=current_flags, link_flags=links)
                    fixture_key, _ = publish_fixture(cache, index, record)
                    data = dict(action='lookup', cwd=str(work), cxx=compiler, flags=current_flags,
                                link_flags=links, cache=str(cache), environment={k:os.environ.get(k) for k in env_keys})
                    python_hit = q.qualify(compiler, current_flags, cache, links, cached_only=True)
                    php_hit = php(data)['result']
                    assert admitted == allowed, (current_flags, links, env, admitted)
                    assert php_hit == (fixture_key if allowed else None), (data, php_hit)
                    assert (python_hit[0] if python_hit else None) == php_hit, data
            # A nonempty replacement map or a single dropped dependency must miss in both readers.
            _, request, _, _ = q.request_context(compiler, flags)
            record, index = copy.deepcopy(original_record), copy.deepcopy(original_index)
            record['context']['request'] = q.sha(json.dumps(request, sort_keys=True).encode())
            fixture_key, record_path = publish_fixture(cache, index, record)
            index_path = cache/('inputs-'+record['context']['request']+'.json')
            data = dict(action='lookup', cwd=str(work), cxx=compiler, flags=flags, cache=str(cache),
                        environment={k:os.environ.get(k) for k in env_keys})
            dropped = dict(index['inputs']); dropped.pop(next(iter(dropped)))
            for inputs in [{str(work/'never-existed'):None}, dropped, {}, {str(work):[1,2,3,4,5]}]:
                index_path.write_text(json.dumps({**index, 'inputs':inputs}))
                assert q.qualify(compiler, flags, cache, cached_only=True) is None
                assert php(data)['result'] is None
            index_path.write_text(json.dumps(index))
            legacy = copy.deepcopy(record)
            del legacy['context']['cache_schema']; del legacy['context']['inputs_sha256']
            legacy_key = q.sha(json.dumps(legacy['context'], sort_keys=True).encode())
            (cache/(legacy_key+'.json')).write_text(json.dumps(legacy))
            index_path.write_text(json.dumps({**index, 'key':legacy_key}))
            assert q.qualify(compiler, flags, cache, cached_only=True) is None
            assert php(data)['result'] is None
        # Normal refresh repairs the legacy entry instead of perpetuating it.
        assert q.qualify(compiler, flags, cache)[2] == 'qualified'
        with host(cache_only=True) as php:
            assert php(data)['result'] == q.qualify(compiler, flags, cache, cached_only=True)[0]
    finally:
        os.chdir(old_cwd)
    print(f'{Path(compiler).name}: {len(cases)} admission vectors, incomplete indexes and legacy migration passed', flush=True)


def native_proof(work, compiler, flavor, runtime=None, *, quick=False):
    work.mkdir(parents=True)
    app, bundle, native = (work/n for n in ('app', 'generated', 'native'))
    app.mkdir(); (app/'module.json').write_text('{}')
    source = app/('main.'+flavor)
    source.write_text('echo "host"; return 8;')
    flags = ['-std=c++23', '-O2', '-g0', '-Wall', '-Wextra', '-Werror', '-I', str(ROOT/'runtime/generated/public')]
    if 'clang' in compiler:
        flags += ['-stdlib=libc++']
    config = dict(cxx=compiler, flags=flags)
    cfg = work/'toolchain.json'; cfg.write_text(json.dumps(config))
    args = ['php', ROOT/'compiler/main.php', 'build', app, ROOT/'build/clang/native-layout/facts.json', bundle, native, cfg]
    def invoke():
        if runtime is None:
            return run(args)
        payload = dict(action='project', cwd=str(work), runtime=str(runtime), app=str(app),
                       facts=str(ROOT/'build/clang/native-layout/facts.json'), bundle=str(bundle), native=str(native), config=config)
        result = run(['php', HOST], input=json.dumps(payload)+'\n')
        data = json.loads(result.stdout)
        return subprocess.CompletedProcess(args, 1 if 'error' in data else 0,
                                           json.dumps(data.get('result', data)), result.stderr)
    first = invoke()
    assert first.returncode == 0, (first.stdout[-2500:], first.stderr)
    report = json.loads(first.stdout)
    assert report['native']['exit_code'] == 0, report['native']
    assert report['native']['qualification'] == 'probe-gate', report
    executed = run([native/'program'])
    assert (executed.returncode, executed.stdout) == (8, 'host'), executed
    objects = {p.name:p.stat().st_mtime_ns for p in native.glob('*.o')}
    exe = (native/'program').stat().st_mtime_ns
    graph = (native/'build.ninja').read_bytes()
    second = invoke()
    assert second.returncode == 0, second.stderr
    assert json.loads(second.stdout)['native']['qualification'] == 'in-process-cache'
    assert (native/'build.ninja').read_bytes() == graph
    assert objects == {p.name:p.stat().st_mtime_ns for p in native.glob('*.o')}
    assert exe == (native/'program').stat().st_mtime_ns
    # Failing Python command proves warm native calls do not launch the qualification script.
    config['python'] = '/no/such/python'
    request = dict(action='build', cwd=str(work), bundle=str(bundle), native=str(native), config=config)
    with host() as build:
        warm = build(request)['result']
        assert warm['exit_code'] == 0 and warm['qualification'] == 'in-process-cache', warm
        cache = native/'qualification'
        index_path, = cache.glob('inputs-*.json')
        index = json.loads(index_path.read_text()); record_path = cache/(index['key']+'.json')
        record = json.loads(record_path.read_text())
        record_path.write_text(json.dumps({**record, 'tested_at': time.time()-q.WEEK-1}))
        failed = build(request)['result']
        assert failed['exit_code'] != 0 and 'executable' not in failed, failed
        assert objects == {p.name:p.stat().st_mtime_ns for p in native.glob('*.o')}
        config.pop('python')
        recovered = build(request)['result']
        assert recovered['exit_code'] == 0 and recovered['qualification'] == 'probe-gate', recovered
        assert build(request)['result']['qualification'] == 'in-process-cache'
        # Native failure must not report a stale binary, and the same PHP caller can recover.
        unit = next((bundle/'units').glob('*.cpp')); saved = unit.read_text()
        unit.write_text('invalid native source')
        failed = build(request)['result']
        assert failed['exit_code'] != 0 and 'executable' not in failed, failed
        unit.write_text(saved)
        assert build(request)['result']['exit_code'] == 0
    if quick:
        executed = run([native/'program'])
        assert (executed.returncode, executed.stdout) == (8, 'host'), executed
        return
    with host() as build:
        retained = {**request, 'action':'incremental', 'app':str(app), 'runtime':str(runtime) if runtime else None,
                    'facts':str(ROOT/'build/clang/native-layout/facts.json')}
        assert build(retained)['result']['native']['exit_code'] == 0
        unchanged = build(retained)['result']
        assert unchanged['preparation']['prepared'] == 0 and unchanged['emitted_sources'] == [], unchanged
        assert unchanged['native']['qualification'] == 'in-process-cache', unchanged
        source.write_text('echo "changed"; return 8;')
        changed = build(retained)['result']
        assert changed['native']['exit_code'] == 0 and len(changed['emitted_sources']) == 1, changed
        assert run([native/'program']).stdout == 'changed'
        # Membership changes relink without rebuilding surviving objects; restoration rebuilds only its unit.
        spare = app/('spare.'+flavor)
        spare.write_text('int spare(){return 4;}' if flavor == 'cpp' else 'function spare():int{return 4;}')
        added = build(retained)['result']
        assert added['native']['exit_code'] == 0 and len(added['emitted_sources']) == 1, added
        before = {p.name:p.stat().st_mtime_ns for p in native.glob('*.o')}
        linked = (native/'program').stat().st_mtime_ns
        spare.unlink()
        removed = build(retained)['result']
        assert removed['native']['exit_code'] == 0, removed
        assert before == {p.name:p.stat().st_mtime_ns for p in native.glob('*.o')}
        assert (native/'program').stat().st_mtime_ns != linked
        spare.write_text('int spare(){return 5;}' if flavor == 'cpp' else 'function spare():int{return 5;}')
        restored = build(retained)['result']
        assert restored['native']['exit_code'] == 0, restored
        changed_objects = [p.name for p in native.glob('*.o') if before.get(p.name) != p.stat().st_mtime_ns]
        assert len(changed_objects) == 1, changed_objects
        fresh = {**retained, 'action':'project', 'bundle':str(work/'fresh'), 'native':str(work/'fresh-native')}
        assert build(fresh)['result']['native']['exit_code'] == 0
        contents = lambda directory: {str(p.relative_to(directory)):p.read_bytes()
                                     for pattern in ('*.cpp','*.hpp') for p in directory.rglob(pattern)}
        assert contents(bundle) == contents(work/'fresh')
        assert run([native/'program']).stdout == run([work/'fresh-native/program']).stdout == 'changed'

    standalone = run(['ninja', '-f', 'build.ninja'], cwd=native)
    assert standalone.returncode == 0 and 'Native argument probe cached:' in standalone.stdout, standalone
    assert not list(native.glob('.scpp-qualified-*'))
    # Source errors stop before native orchestration and cannot execute an old application.
    source.write_text('unknown_function();')
    failed = invoke()
    assert failed.returncode != 0 and 'Native argument probe' not in failed.stdout, failed
    source.write_text('echo "new"; return 9;')
    recovered = invoke()
    assert recovered.returncode == 0, (recovered.stdout, recovered.stderr)
    executed = run([native/'program'])
    assert (executed.returncode, executed.stdout) == (9, 'new'), executed
    print(f'{Path(compiler).name}/{flavor}: cold/warm, retained deletion/restoration, fresh parity and standalone gate passed', flush=True)


def artifact_invalidation(work, compiler):
    """A verified toolchain context is an artifact dependency, in PHP and in saved standalone graphs."""
    work.mkdir(parents=True)
    bundle, native = work/'bundle', work/'native'
    (bundle/'units').mkdir(parents=True)
    quiet = 'units/'+'a'*64+'.cpp'
    (bundle/quiet).write_text('int untouched(){return 0;}')
    helper = work/'helper.cpp'; helper.write_text('int helper(){return 0;}')
    (bundle/'manifest.json').write_text(json.dumps({'format':'scpp-cpp-program-v2',
        'translation_units':['entry.cpp', quiet], 'native_sources':[str(helper)]}))
    (bundle/'entry.cpp').write_text('#include <choice.h>\nint helper();\nint main(){\n'
        '#ifdef HOST_OVERRIDE\nreturn HOST_OVERRIDE+helper();\n#else\nreturn CHOICE+helper();\n#endif\n}\n')
    for name, value in [('a',1),('b',2)]:
        (work/name).mkdir(); (work/name/'choice.h').write_text(f'#define CHOICE {value}\n')
    config = dict(cxx=compiler, flags=['-std=c++23','-O2'])
    request = dict(action='build', cwd=str(work), bundle=str(bundle), native=str(native), config=config)
    def objects():
        return {p.name:p.stat().st_mtime_ns for p in native.glob('*.o')}
    with host() as build:
        def step(path, expected, count, qualification):
            before = objects()
            result = build({**request, 'environment':{'CPATH':str(work/path)}})['result']
            assert result['exit_code'] == 0 and result['qualification'] == qualification, result
            assert run([native/'program']).returncode == expected
            now = objects()
            changed = [name for name in now if before.get(name) != now[name]]
            assert len(changed) == count, (path, changed, result)
            return result
        step('a', 1, 3, 'probe-gate')
        first_stamp = (native/'.scpp-native-context').read_bytes()
        step('b', 2, 3, 'probe-gate')
        assert (native/'.scpp-native-context').read_bytes() != first_stamp
        # Both contexts are now cached: changing back still rebuilds, without any Python helper.
        config['python'] = '/no/such/python'
        step('a', 1, 3, 'in-process-cache')
        stamp_time = (native/'.scpp-native-context').stat().st_mtime_ns
        step('a', 1, 0, 'in-process-cache')
        assert (native/'.scpp-native-context').stat().st_mtime_ns == stamp_time
        # Application header changes leave the toolchain identity intact and rebuild only its consumer.
        (work/'a'/'choice.h').write_text('#define CHOICE 3\n')
        step('a', 3, 1, 'in-process-cache')
        assert (native/'.scpp-native-context').stat().st_mtime_ns == stamp_time
        config.pop('python')
        step('a', 3, 0, 'in-process-cache')
        # The saved graph must also invalidate when its invocation environment changes.
        before = objects()
        standalone = subprocess.run(['ninja'], cwd=native, env={**ENV,'CPATH':str(work/'b')},
                                    capture_output=True, text=True, timeout=90)
        assert standalone.returncode == 0, standalone.stderr+standalone.stdout
        assert len([n for n,t in objects().items() if before[n] != t]) == 3
        assert run([native/'program']).returncode == 2
        before = objects(); linked = (native/'program').stat().st_mtime_ns
        standalone = subprocess.run(['ninja'], cwd=native, env={**ENV,'CPATH':str(work/'b')},
                                    capture_output=True, text=True, timeout=90)
        assert standalone.returncode == 0 and 'Native argument probe cached:' in standalone.stdout
        assert objects() == before and (native/'program').stat().st_mtime_ns == linked
        # Replacing an implementation at the same selected compiler path must rebuild too.
        wrapper = work/'cxx'
        wrapper.write_text('#!/bin/sh\nexec '+shlex.quote(compiler)+' "$@" -DHOST_OVERRIDE=4\n')
        wrapper.chmod(0o755); config['cxx'] = str(wrapper)
        step('b', 4, 3, 'probe-gate')
        old = wrapper.stat()
        wrapper.write_text('#!/bin/sh\nexec '+shlex.quote(compiler)+' "$@" -DHOST_OVERRIDE=5\n')
        os.utime(wrapper, (old.st_atime, old.st_mtime+2))
        step('b', 5, 3, 'probe-gate')
        step('b', 5, 0, 'in-process-cache')
        # Expiry refreshes qualification, not artifacts, when the context is unchanged.
        key = (native/'.scpp-native-context').read_text().strip()
        record_path = native/'qualification'/(key+'.json')
        record = json.loads(record_path.read_text()); record['tested_at'] = time.time()-q.WEEK-1
        record_path.write_text(json.dumps(record))
        stamp_time = (native/'.scpp-native-context').stat().st_mtime_ns
        step('b', 5, 0, 'probe-gate')
        assert (native/'.scpp-native-context').stat().st_mtime_ns == stamp_time
        config['python'] = '/no/such/python'
        step('b', 5, 0, 'in-process-cache')
    print(f'{Path(compiler).name}: context/driver invalidation, standalone restat and header-only rebuild passed', flush=True)


def rejected_path(work):
    bundle = work/"quote'input"
    bundle.mkdir(parents=True)
    (bundle/'manifest.json').write_text(json.dumps({'format':'scpp-cpp-program-v2', 'translation_units':['entry.cpp']}))
    (bundle/'entry.cpp').write_text('int main() { return 0; }')
    request = dict(action='build', cwd=str(work), bundle=str(bundle), native=str(work/'native'),
                   config={'cxx':'clang++', 'flags':['-std=c++23']})
    result = run(['php', '-d', 'disable_functions=proc_open,exec,shell_exec,system,passthru,popen', HOST],
                 input=json.dumps(request)+'\n')
    assert 'Unsupported Ninja input path spelling' in json.loads(result.stdout)['error'], result


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--output', type=Path)
    parser.add_argument('--quick', action='store_true')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-php-native-') as temporary:
        root = (args.output or Path(temporary)).resolve()
        rejected_path(root/'path-rejection')
        if args.quick:
            native_proof(root/'php', 'clang++', 'php', quick=True)
            return
        for name in ('clang++', 'g++'):
            compiler = shutil.which(name)
            assert compiler, name
            cache_proof(root/name/'cache-proof', compiler)
            protocol_parity(root/name/'protocol-parity', compiler)
            artifact_invalidation(root/name/'artifact-invalidation', compiler)
            runtime = None
            if name == 'g++':
                from extends_native import runtime as build_runtime
                library = build_runtime(root/name, name, [ROOT/'runtime'/path for path in (
                    'generated/src/hash.cpp', 'generated/src/boxed.cpp',
                    'src/boxed/boxed_value.cpp', 'src/boxed/boxed_conversion.cpp', 'src/hash/packed_key.cpp')])
                runtime = root/name/'runtime-module'
                runtime.mkdir()
                (runtime/'module.json').write_text(json.dumps({'name':'runtime', 'type':'c++',
                    'artifacts':[os.path.relpath(library, runtime)]}))
            for flavor in ('cpp', 'php', 'js'):
                native_proof(root/name/(flavor+" space $$: é"), compiler, flavor, runtime)


if __name__ == '__main__':
    main()
