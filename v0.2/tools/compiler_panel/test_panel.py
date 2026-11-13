"""HTTP boundary and compiler-to-native integration proofs on an ephemeral loopback port."""
import importlib.util
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import urllib.error
import urllib.request

HERE = Path(__file__).resolve().parent
cache = tempfile.TemporaryDirectory(prefix='scpp-panel-test-cache-')
server = subprocess.Popen([sys.executable, '-u', str(HERE / 'server.py'), '--port', '0', '--pch-cache', cache.name], stdout=subprocess.PIPE, stderr=subprocess.DEVNULL, text=True)
try:
    line = server.stdout.readline().strip()
    assert line.startswith('Compiler panel: '), line
    base = line.split()[2]
    host = base.removeprefix('http://')
    def request(path, data=None, headers=None):
        h = dict(headers or {})
        req = urllib.request.Request(base + path, data=None if data is None else json.dumps(data).encode(), headers=h)
        try:
            with urllib.request.urlopen(req, timeout=100) as response:
                return response.status, response.read(), response.headers
        except urllib.error.HTTPError as error:
            return error.code, error.read(), error.headers
    status, content, headers = request('/')
    assert status == 200 and b'Compiler workbench' in content
    assert "frame-ancestors 'none'" in headers['Content-Security-Policy']
    for path in ['/app.js', '/style.css']:
        assert request(path)[0] == 200
    token = json.loads(request('/config')[1])['token']
    allowed = {'Content-Type':'application/json', 'Origin':base, 'X-Panel-Token':token}
    simple = {'entry':'main', 'mode':'run', 'files':[{'name':'main','flavor':'cpp','source':'echo 8; return 8;'}]}
    assert request('/run', simple)[0] == 403
    assert request('/run', simple, {**allowed,'Origin':'https://example.com'})[0] == 403
    assert request('/config', headers={'Host':'example.com'})[0] == 403
    assert request('/../../compiler/boot.php')[0] == 404
    for name in ['../main','/tmp/main','main/sub','x;echo']:
        bad = {**simple,'files':[{**simple['files'][0], 'name':name}]}
        assert request('/run', bad, allowed)[0] == 400
    for flavor, source in [('cpp','int ret=8; echo ret; return ret;'),('php','$ret int=8; echo $ret; return $ret;'),('js','let ret: int=8; echo ret; return ret;')]:
        data = {**simple,'files':[{'name':'main','flavor':flavor,'source':source}]}
        status, content, _ = request('/run', data, allowed)
        report = json.loads(content)
        assert status == 200 and report['ok'], report
        assert report['stages'][-1]['exit_code'] == 8 and report['stages'][-1]['stdout'] == '8', report
        assert report['stages'][-2]['executable_bytes'] > 0
        assert len(report['stages']) == 9, report
        print('Panel compile/build/run passed:', flavor)
    repeat = json.loads(request('/run', simple, allowed)[1])
    native = repeat['stages'][-2]
    assert repeat['ok'] and native['qualification'] == 'in-process cache hit', native
    assert native['launcher_check_ms'] + native['ninja_ms'] <= native['ms']
    multi = {**simple,'files':[{'name':'main','flavor':'php','source':'ping(); return 8;'},
                              {'name':'support','flavor':'cpp','source':'void ping() { echo "support"; }'}]}
    report = json.loads(request('/run', multi, allowed)[1])
    assert report['ok'] and report['stages'][-1]['stdout'] == 'support', report
    symbols = next(stage['data'] for stage in report['stages'] if stage['name'] == 'Global symbol publication')
    assert len(symbols['ping']) == 1 and symbols['ping'][0]['file'] == 'support', symbols
    duplicate = {**multi, 'files':multi['files'] + [{'name':'duplicate','flavor':'cpp','source':'void ping() {}'}]}
    duplicate_report = json.loads(request('/run', duplicate, allowed)[1])
    assert not duplicate_report['ok'], duplicate_report
    symbols = next(stage['data'] for stage in duplicate_report['stages'] if stage['name'] == 'Global symbol publication')
    assert len(symbols['ping']) == 2 and {row['file'] for row in symbols['ping']} == {'support', 'duplicate'}, symbols
    for level in ['O0', 'O1', 'O2']:
        pch_report = json.loads(request('/run', {**multi, 'pch':True, 'optimization':level}, allowed)[1])
        assert pch_report['ok'], pch_report
        native = pch_report['stages'][-2]
        assert native['pch_cache']['status'] == 'built', native
        assert native['jobs'] == 4 and native['linker'] == 'mold' and native['pch']
        assert '-include-pch' in native['stdout'] and '--ld-path=' in native['stdout']
        assert pch_report['stages'][-1]['stdout'] == 'support'
        changed = {**multi, 'files':[multi['files'][0], {**multi['files'][1], 'source':'void ping() { echo \"changed\"; }'}]}
        reused = json.loads(request('/run', {**changed, 'pch':True, 'optimization':level, 'validate_pch':False}, allowed)[1])
        assert reused['ok'] and reused['stages'][-2]['pch_cache']['status'] == 'reused', reused
        assert reused['stages'][-1]['stdout'] == 'changed', reused
        assert '-x c++-header' not in reused['stages'][-2]['stdout']
        assert '-fno-validate-pch' in reused['stages'][-2]['stdout']
        assert reused['stages'][-2]['ninja_ms'] <= reused['stages'][-2]['ms']
    for options in [{'optimization':'O9'}, {'pch':'yes'}]:
        assert request('/run', {**simple, **options}, allowed)[0] == 400
    prepared = next(s for s in report['stages'] if s['name'].startswith('Preparation'))
    assert prepared['data']['functions_by_file']['support'][0]['body']['statements'][0]['kind'] == 'echo'
    duplicate = {**simple,'files':[{'name':'main','flavor':'cpp','source':'void f() {} void f() {} int x=1; int x=2; return 8;'}]}
    report = json.loads(request('/run', duplicate, allowed)[1])
    assert not report['ok'] and len(report['stages'][-1]['diagnostics']) == 2, report
    assert report['stages'][-1]['name'].startswith('Preparation')
    assert len(report['stages'][2]['data']['ast']['functions']) == 2
    for mode, final in [('inspect','C++ lowering + text emission'),('build','Native build · -O2')]:
        report = json.loads(request('/run', {**simple,'mode':mode}, allowed)[1])
        assert report['ok'] and report['stages'][-1]['name'] == final, report
    broken = {**simple,'files':[{'name':'main','flavor':'cpp','source':'return'}]}
    report = json.loads(request('/run', broken, allowed)[1])
    assert not report['ok'] and report['stages'][-1]['name'].startswith('Parse'), report
    # Prove the subprocess timeout without depending on the source language supporting loops.
    spec = importlib.util.spec_from_file_location('panel', HERE / 'server.py')
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    result = module.process([sys.executable, '-c', 'while True: pass'], HERE, 0.1)
    assert result['timed_out'] and result['exit_code'] < 0, result
    print('Mixed-file facts, diagnostics, actions, localhost protections and timeout checks passed')
finally:
    server.terminate()
    server.wait(timeout=5)
    cache.cleanup()
