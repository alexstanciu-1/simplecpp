import sys,json
from pathlib import Path
root=Path(sys.argv[1]);out=Path(sys.argv[2]);out.mkdir(parents=True)
sys.path.insert(0,str(root/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
cases={
 'handle':'$h curl_handle;$e error;if(!take($h,$e,curl_init())){return 1;}echo "handle\\n";',
 'response':'$h curl_handle;$r curl_response;$e error;take($h,$e,curl_init("file:///etc/hostname"));if(!take($r,$e,curl_exec($h))){return 1;}echo $r->status_code,"\\n";',
 'mixed':'$h curl_handle;$r curl_response;$e error;$m mixed;take($h,$e,curl_init("file:///etc/hostname"));take($r,$e,curl_exec($h));if(!take($m,$e,curl_getinfo($h,CURLINFO_RESPONSE_CODE))){return 1;}$n int=$m;echo $n,"\\n";',
}
with CommandRunner(out/'logs',root,jobs=12) as runner:
 def runtime():
  runner.run('runtime-build',['clang++','-std=c++23','-DSCPP_HAS_CURL=1','-DSCPP_LANGUAGE_TARGET_PHP=1','-I',root/'runtime/include',root/'tests/runtime/native/test_curl.cpp',root/'runtime/include/modules/curl/curl.cpp',root/'runtime/include/core/runtime.cpp','-lcurl','-pthread','-o',out/'runtime-probe'],timeout=300)
  runner.run('runtime-execute',[out/'runtime-probe'])
 def probe(name,source):
  d=out/name;(d/'source').mkdir(parents=True);(d/'source/main.phs').write_text(source)
  runner.run(name+'-publish',['php','/tmp/curl-publish.php',root,d],cli_reason='Default prelowering probe')
  b=json.loads(runner.run(name+'-build',['php',root/'compiler/my-try/tests/fixtures/native_cache_driver.php',d/'published',d/'cache',root/'runtime/include','/usr/bin/clang++','--no-pch'],timeout=300,cli_reason='Native production probe'))
  runner.run(name+'-execute',[b['executable']])
 run_tasks([Task('runtime',runtime)]+[Task(n,lambda n=n,s=s:probe(n,s)) for n,s in cases.items()])
