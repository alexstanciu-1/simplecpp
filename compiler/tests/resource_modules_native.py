"""Compile module callers, link exactly their published inputs and check resident recovery."""
import json
from pathlib import Path
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke, service

root = Path(__file__).resolve().parents[2]
io = r'''
$h file_stream=fopen("stream.dat","w+b"); $alias=$h;
$n int=fputs($h,"a\0b\nx"); if($n!==5 || !fflush($alias) || !rewind($h)){return 1;}
$zero int=ftell($alias); $empty string=fgets($alias,1); $first string=fgets($h);
$last string=fgets($alias); if($zero!==0 || $empty!=="" || $first!=="a\0b\n" || $last!=="x"){return 2;}
if(isset(fgets($h)) || !feof($alias)){return 3;}
$bytes string=fread($alias,4); if($bytes!=="" || isset(fseek($h,-1,SEEK_SET))){return 4;}
$ok int=io_seek($h,0,origin()); if($ok!==0 || feof($alias)){return 5;}
if(!fclose($alias) || isset(fopen("missing","rb"))){return 6;}
return 0;
'''
def prove(module, source, disabled_call, helper='', failures=()):
    with tempfile.TemporaryDirectory(prefix=module+'-source-') as directory:
        work = Path(directory)
        app, out = work/'app', work/'out'
        app.mkdir()
        manifest = app/'module.json'
        deps = dict(general_api=True, dependencies={'runtime': {'modules': module.split('+')}})
        manifest.write_text(json.dumps(deps))
        main = app/'main.php'
        main.write_text(source)
        if helper:
            (app/'helper.cpp').write_text(helper)
        proc = service(app, out)
        try:
            result = invoke(proc)
            assert 'error' not in result, result
            baseline = artifacts(out)
            bundle = json.loads((out/'manifest.json').read_text())
            selected = set(module.split('+'))
            inputs = {Path(arg).name for arg in bundle['link_inputs']}
            for name in ('io','process','curl'):
                assert ('libscpp_'+name+'.a' in inputs) == (name in selected), inputs
            assert ('libcurl.so' in inputs) == ('curl' in selected), inputs
            def link():
                current = json.loads((out/'manifest.json').read_text())
                subprocess.run([sys.argv[1], '-std=c++23', '-stdlib=libc++', '-I', root/'runtime/generated/public',
                                *(out/u for u in current['translation_units']), *current['link_inputs'], '-o', work/'run'], check=True)
            link()
            subprocess.run([work/'run'], cwd=work, check=True, timeout=30)
            for invalid_source, diagnostic in failures:
                main.write_text(invalid_source)
                result = invoke(proc)
                assert 'error' not in result, result
                link()
                run = subprocess.run([work/'run'], cwd=work, capture_output=True, timeout=30)
                assert run.returncode and diagnostic.encode() in run.stderr, run
            manifest.write_text(json.dumps({'general_api': True}))
            main.write_text(disabled_call+'; return 0;')
            assert 'requires module runtime-'+module.split('+')[0] in invoke(proc).get('error','')
            manifest.write_text(json.dumps({'dependencies': deps['dependencies']}))
            if module == 'io' or module == 'curl':
                assert 'error' not in invoke(proc)
            general_call = {'io': 'io_open("a","r")', 'process': 'process_start("/bin/true",[],"",0)',
                            'curl': 'curl_request_init()'}[module.split('+')[0]]
            main.write_text(general_call + '; return 0;')
            assert 'Unknown function: '+general_call.split('(')[0] in invoke(proc).get('error','')
            manifest.write_text(json.dumps(deps))
            main.write_text(source)
            assert 'error' not in invoke(proc) and artifacts(out) == baseline
            fresh = service(app, work/'fresh')
            try:
                assert 'error' not in invoke(fresh) and artifacts(work/'fresh') == baseline
            finally:
                fresh.stdin.close()
                fresh.wait()
        finally:
            proc.stdin.close()
            proc.wait()

if sys.argv[2] == 'io':
    prove('io', io, 'fopen("a","r")', 'int origin(){return SEEK_SET;}', [
        ('$bad=fopen("a","x"); return 0;', 'unsupported stream mode'),
        ('$h file_stream=fopen("stream.dat","rb"); $a=$h; $closed=fclose($h); $bad=fread($a,1); return 0;', 'stream is closed'),
    ])

if sys.argv[2] == 'process' and not sys.platform.startswith('linux'):
    prove('process', '$a vector<string> = []; if(isset(process_start("/bin/true",$a,"",0))){return 1;} return 0;',
          'process_start("/bin/true",[],"",0)')

if sys.argv[2] == 'process' and sys.platform.startswith('linux'):
    # Fill both outputs before consuming stdin. Distinct binary patterns expose
    # truncation, interleaving and swapping the streams.
    child = """import sys
for stream, marker in [(sys.stdout.buffer, b'O'), (sys.stderr.buffer, b'E')]:
    stream.write((marker + bytes(1)) * 524288)
    stream.flush()
sys.stdout.buffer.write(sys.stdin.buffer.read())
"""
    prove('process', r'''
$args vector<string> =[];
$h process_handle=process_start("/bin/cat",$args,"a\0b",0); $alias=$h;
$done bool=process_poll($h); while(!$done){$done=process_poll($alias);}
$o process_output=process_result($alias); $ok bool=process_close($h);
if(!$ok || isset(process_poll($alias)) || $o->stdout_text!=="a\0b" || $o->exit_code!==0){return 1;}
$ok=process_close($alias); if(!$ok || isset(process_start("/missing/scpp-program",$args,"",0))){return 2;}
$args=["-c","echo problem >&2; exit 127"];
$h=process_start("/bin/sh",$args,"",0,"/tmp");
$done=process_poll($h); while(!$done){$done=process_poll($h);}
$o=process_result($h); if($o->exit_code!==127 || $o->stderr_text!=="problem\n"){return 3;}
$args=["10"]; $h=process_start("/bin/sleep",$args,"",1);
$done=process_poll($h); while(!$done){$done=process_poll($h);}
$o=process_result($h); if(!$o->timed_out || $o->stopped){return 4;}
$h=process_start("/bin/sleep",$args,"",0); $done=process_poll($h);
if($done || isset(process_result($h))){return 5;}
$ok=process_stop($h); $done=process_poll($h); while(!$done){$done=process_poll($h);}
$o=process_result($h); if(!$ok || !$o->stopped || $o->timed_out || $o->signal===0){return 6;}
$input string=str_repeat("I\0",1048576);
$args=["-c",CHILD_SCRIPT];
$h=process_start(PYTHON_EXE,$args,$input,0);
$done=process_poll($h); while(!$done){$done=process_poll($h);}
$o=process_result($h);
if($o->exit_code!==0 || $o->stdout_text!==(str_repeat("O\0",524288).$input)){return 7;}
if($o->stderr_text!==str_repeat("E\0",524288)){return 8;}
return 0;
'''.replace('CHILD_SCRIPT', json.dumps(child)).replace('PYTHON_EXE', json.dumps(sys.executable)),
          'process_start("/bin/true",[],"",0)')

if sys.argv[2] == 'curl':
    with tempfile.TemporaryDirectory(prefix='curl-file-') as tmp:
        file = Path(tmp)/'bytes'
        file.write_bytes(b'a\0b')
        empty = Path(tmp)/'empty'
        empty.touch()
        prove('curl', r'''
$h curl_handle=curl_init("URL"); $alias=$h; $status=curl_getinfo($h);
$r curl_response=curl_request_exec($h); $bytes string=curl_exec($alias);
if($bytes!=="a\0b" || $r->body!==$bytes || $r->effective_url!=="URL"){return 1;}
$code mixed=curl_request_getinfo($h,CURLINFO_RESPONSE_CODE);
$info=curl_getinfo($h); $headers=$r->headers;
$url string=$info["effective_url"]; $zero int=$code;
if($url!=="URL" || $zero!==0 || curl_errno($h)!==0){return 2;}
if(!curl_setopt($h,option_id(),"EMPTY") || !curl_setopt($h,CURLOPT_RETURNTRANSFER,true)){return 3;}
$empty=curl_exec($h); if(!isset($empty)){return 4;} $body string=$empty; if($body!==""){return 5;}
$v mixed="URL"; if(!curl_setopt($h,CURLOPT_URL,$v)){return 6;}
$ok bool=curl_request_setopt($h,CURLOPT_CONNECTTIMEOUT,1);
if(!$ok || curl_setopt($h,CURLOPT_SSL_VERIFYHOST,1) || curl_errno($h)===0){return 7;}
if(!curl_reset($alias) || isset(curl_exec($h)) || curl_error($h)===""){return 8;}
if(!curl_close($alias) || isset(curl_request_exec($h)) || $r->body!=="a\0b"){return 9;}
return 0;
'''.replace('URL"',file.as_uri()+'"').replace('EMPTY"',empty.as_uri()+'"'), 'curl_init()', 'int option_id(){return CURLOPT_URL;}')

if sys.argv[2] == 'http':
    from http.server import BaseHTTPRequestHandler, HTTPServer
    from threading import Thread

    class Handler(BaseHTTPRequestHandler):
        def log_message(self, *args):
            pass

        def do_GET(self):
            if self.path == '/redirect':
                self.send_response(302)
                self.send_header('Location', '/done')
            else:
                self.send_response(404)
            self.end_headers()

        def do_POST(self):
            body = self.rfile.read(int(self.headers['Content-Length']))
            self.send_response(201 if self.headers.get('X-Test') == 'yes' else 400)
            self.send_header('Content-Type', 'application/octet-stream')
            self.end_headers()
            self.wfile.write(body)

    with HTTPServer(('127.0.0.1', 0), Handler) as server:
        worker = Thread(target=server.serve_forever)
        worker.start()
        try:
            prove('curl', r'''
$h curl_handle=curl_request_init("BASE/redirect");
$ok bool=curl_request_setopt($h,CURLOPT_FOLLOWLOCATION,true);
$r curl_response=curl_request_exec($h);
if(!$ok || $r->status_code!==404 || $r->redirect_count!==1 || $r->body!==""){return 1;}
if(!curl_setopt($h,CURLOPT_URL,"BASE/post") || !curl_setopt($h,CURLOPT_HTTPHEADER,["X-Test: yes"])){return 2;}
if(!curl_setopt($h,CURLOPT_POSTFIELDS,"a\0b")){return 3;}
$p curl_response=curl_request_exec($h); $lines=$p->headers;
if($p->status_code!==201 || $p->body!=="a\0b" || $p->content_type!=="application/octet-stream"){return 4;}
if($p->header_size<=0 || $p->request_size<=0 || $lines[0]==="" || $r->status_code!==404){return 5;}
return 0;
'''.replace('BASE', 'http://127.0.0.1:'+str(server.server_port)), 'curl_init()')
        finally:
            server.shutdown()
            worker.join()

if sys.argv[2] == 'all':
    with tempfile.TemporaryDirectory(prefix='combined-modules-') as directory:
        path = Path(directory)/'input'
        prove('io+process+curl', r'''
$stream file_stream=fopen("FILE","wb"); $written int=fwrite($stream,"x\0y");
if(!fclose($stream) || $written!==3){return 1;}
$curl curl_handle=curl_init("URL"); $payload string=curl_exec($curl);
$args vector<string> = []; $child process_handle=process_start("/bin/cat",$args,$payload,0);
$done bool=process_poll($child); while(!$done){$done=process_poll($child);}
$capture process_output=process_result($child);
if(!matches($capture->stdout_text)){return 2;} return 0;
'''.replace('FILE', str(path)).replace('URL', path.as_uri()), 'fopen("a","r")',
              'bool matches(string bytes){return bytes=="x\\0y";}')

if sys.argv[2] == 'headers':
    public = root/'runtime/generated/public'
    for module in sys.argv[3:]:
        headers = sorted((public/'scpp'/module).glob('*.hpp'))
        assert headers, module
        for header in headers:
            command = [sys.argv[1], '-std=c++23', '-stdlib=libc++', '-x', 'c++', '-I', public]
            source = '#include "' + header.relative_to(public).as_posix() + '"\n'
            subprocess.run([*command, '-fsyntax-only', '-'], input=source, text=True, check=True)
            includes = subprocess.check_output([*command, '-M', '-'], input=source, text=True)
            for forbidden in ('/curl/curl.h','/sys/wait.h','/stdio.h','/private/', '/scpp/php/', '/scpp/js/', '/gtk/', '/webkit2/', '/webkit/', '/mysql.h'):
                assert forbidden not in includes, (header, forbidden)
