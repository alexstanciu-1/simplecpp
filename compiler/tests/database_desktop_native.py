"""Build selected optional module callers and run their private database/display fixtures."""
import argparse
from http.server import BaseHTTPRequestHandler, HTTPServer
from threading import Thread
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import time

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
fixtures = root/'compiler/tests/fixtures/resource_modules'


def compile_app(work, module, text, clang, negatives=()):
    app, out = work/'app', work/'out'
    app.mkdir()
    main = app/'main.php'
    main.write_text(text)
    manifest = app/'module.json'
    settings = {'general_api': True, 'dependencies': {'runtime': {'modules': [module]}}}
    manifest.write_text(json.dumps(settings))
    compiler = service(app, out)
    try:
        response = invoke(compiler)
        assert 'error' not in response, response
        expected = artifacts(out)
        if module == 'mysqli':
            units = b'\n'.join(data for path, data in expected.items() if path.suffix == '.cpp')
            assert b'::scpp::mysqli_api::bind_param((*(var_select_' in units
            assert b').pin()), scpp::string{"i", 1}, var_id_' in units
        for source, diagnostic in negatives:
            main.write_text(source)
            result = invoke(compiler)
            assert diagnostic in result.get('error',''), result
        main.write_text(text)
        manifest.write_text('{"general_api":true}')
        assert 'error' in invoke(compiler)
        manifest.write_text(json.dumps(settings))
        assert 'error' not in invoke(compiler) and artifacts(out)==expected
        bundle = json.loads((out/'manifest.json').read_text())
        inputs = bundle['link_inputs']
        assert any('libscpp_'+module+'.a' in p for p in inputs)
        if module == 'ui':
            assert all('webkit' not in p and 'webview' not in p for p in inputs)
        subprocess.run([clang,'-std=c++23','-stdlib=libc++','-Wno-unused-result','-I',root/'runtime/generated/public',
                        *(out/u for u in bundle['translation_units']),*inputs,'-o',work/'run'],check=True)
    finally:
        compiler.stdin.close()
        compiler.wait()


def database(work, clang):
    sock = work/'db.sock'
    text = (fixtures/'mysqli.php').read_text().replace('@SOCKET@', str(sock))
    start = '$db=new mysqli(); $id=1; $s mysqli_stmt=$db->prepare("SELECT ?"); '
    bind = '$ok=$s->bind_param("i",$id); '
    compile_app(work,'mysqli',text,clang,[
        (start+bind+'$copy=$s; return 0;', 'without aliases'),
        (start+'$copy=$s; '+bind+'return 0;', 'without aliases'),
        (start+'if(true){$tmp=1;$s->bind_param("i",$tmp);} return 0;', 'may expire'),
        (start+'$s->bind_param("i",1); return 0;', 'stable typed slot'),
    ])
    subprocess.run(['mariadb-install-db','--no-defaults','--datadir='+str(work/'data'),
                    '--auth-root-authentication-method=normal'], stdout=subprocess.DEVNULL,check=True)
    with (work/'server.log').open('w') as log:
        proc = subprocess.Popen(['mariadbd','--no-defaults','--datadir='+str(work/'data'),'--skip-networking',
                                 '--socket='+str(sock),'--pid-file='+str(work/'server.pid')],stdout=log,stderr=log)
        try:
            for _ in range(200):
                if proc.poll() is not None:
                    raise RuntimeError((work/'server.log').read_text()[-1600:])
                if sock.exists() and subprocess.run(['mariadb-admin','--no-defaults','--socket='+str(sock),
                                                     '-uroot','ping'],capture_output=True).returncode==0:
                    break
                time.sleep(.05)
            else:
                raise RuntimeError('Private MariaDB did not become ready')
            subprocess.run([work/'run'],cwd=work,check=True,timeout=20)
        finally:
            if proc.poll() is None:
                proc.terminate()
                try:
                    proc.wait(timeout=10)
                except subprocess.TimeoutExpired:
                    proc.kill()
                    proc.wait()


def desktop(work, module, clang):
    src = 'desktop.php' if module=='webview' else 'window.php'
    compile_app(work,module,(fixtures/src).read_text(),clang)
    shutil.copytree(fixtures/'assets',work/'assets')
    command = ['xvfb-run','-a','dbus-run-session','--',work/'run']
    if module == 'ui':
        subprocess.run(command,cwd=work,timeout=40,check=True)
    else:
        verify_website(work, command)


def verify_website(work, command):
    class Website(BaseHTTPRequestHandler):
        def log_message(self, *args):
            pass

        def do_GET(self):
            self.send_response(200)
            self.send_header('Content-Type','text/html; charset=utf-8')
            self.end_headers()
            if self.path == '/':
                self.wfile.write(b"<script>location.replace(window.scpp || window.webkit?.messageHandlers?.scpp ? '/failed' : '/passed')</script>")

    with HTTPServer(('127.0.0.1',0),Website) as server:
        url = 'http://127.0.0.1:'+str(server.server_port)+'/'
        (work/'assets/site.js').write_text('window.verificationWebsite='+json.dumps(url)+';')
        thread = Thread(target=server.serve_forever)
        thread.start()
        try:
            subprocess.run(command,cwd=work,timeout=40,check=True)
        finally:
            server.shutdown()
            thread.join()


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('module',choices=['mysqli','ui','webview'])
    parser.add_argument('clang')
    args = parser.parse_args()
    with tempfile.TemporaryDirectory(prefix='scpp-module-') as tmp:
        if args.module=='mysqli':
            database(Path(tmp),args.clang)
        else:
            desktop(Path(tmp),args.module,args.clang)
