"""Bounded checked-span and FNV-1a proof; dev002, default prelowering."""
import argparse,json,sys
from pathlib import Path
APP=Path(__file__).resolve().parents[1];ROOT=APP.parents[1]
sys.path.insert(0,str(APP/'tools'))
from test_runner import CommandRunner,Task,run_tasks

def main():
    parser=argparse.ArgumentParser(description=__doc__);parser.add_argument('--results',type=Path,required=True)
    out=parser.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False)
    cases={}
    for name,path,expected in [
        ('buffer','source/strict_level_01/strict_source_001_buffer_span.phs','empty\n6\n98\ncde\n3\n100\nbcd\nhash-ok\nabcdef\n0\n'),
        ('hash','source/strict_level_01/strict_source_003_stable_hash.phs','cbf29ce484222325\n003f3219133dae62\nu64-match\nrepeat-ok\ndistinct\n'),
        ('builder','strings/strict_level_01/strict_strings_009_text_builder.phs','reserved\n15\nhello-42-1-span\nhello-42-1-span\n0\nagain\n0\n')]:
        source=(ROOT/'tests/php'/path).read_text().replace('/** source_buffer */','source_buffer').replace('/** byte_span */','byte_span')
        if name=='hash':
            source=source.replace('stable_hash_bytes_u64($span) === stable_hash_string_u64("bcd")','hash_bytes($span) === hash_string("bcd")').replace('stable_hash_string_u64("abc") === stable_hash_string_u64("abc")','hash_string("abc") === hash_string("abc")').replace('stable_hash_string_u64("abc") === stable_hash_string_u64("abd")','hash_string("abc") === hash_string("abd")')
        cases[name]=(source,expected,False)
    base='$text="abc";$buffer=source_buffer_take($text);$span=source_buffer_span($buffer,0,3);'
    cases['release']=(base+'source_buffer_release($buffer);echo byte_span_to_string($span);','',True)
    cases['uint64']=('echo stable_hash_string_u64("abc"),"\\n";','16654208175385433931\n',False)
    cases['replace']=(base+'$other="xyz";$buffer=source_buffer_take($other);echo byte_span_to_string($span);','',True)
    cases['destroy']=('function expired():byte_span {$text="abc";$buffer=source_buffer_take($text);return source_buffer_span($buffer,0,3);}$span=expired();echo byte_span_to_string($span);','',True)
    cases['copy']=(base+'$copy=$buffer;source_buffer_release($copy);echo byte_span_to_string($span),"\\n";','abc\n',False)
    cases['ordered']=("function start(int &$trace):int {$trace=$trace*10+1;return 1;}function length(int &$trace):int {$trace=$trace*10+2;return 2;}"+base+'$trace=0;$view=source_buffer_span($buffer,start($trace),length($trace));$builder=text_builder_create();text_builder_append_byte_span($builder,$view);source_buffer_release($buffer);echo text_builder_to_string($builder),":",$trace,"\\n";','bc:12\n',False)
    cases['uint64_span']=(base+'echo stable_hash_bytes_u64($span),"\\n";','16654208175385433931\n',False)
    with CommandRunner(out/'logs',ROOT,jobs=12) as runner:
        def runtime(sanitize):
            name='runtime-sanitized' if sanitize else 'runtime';exe=out/name
            flags=['-fsanitize=address,undefined','-fno-omit-frame-pointer'] if sanitize else []
            runner.run(name+'-compile',['/usr/bin/clang++','-std=c++23','-DSCPP_LANGUAGE_TARGET_PHP=1','-pthread','-I',ROOT/'runtime/include',*flags,ROOT/'tests/runtime/native/test_source_span_lifetime.cpp',ROOT/'runtime/include/core/runtime.cpp','-o',exe],timeout=300)
            runner.run(name+'-run',[exe])
        def check(name,case):
            source,expected,failed=case;folder=out/name;(folder/'source').mkdir(parents=True);(folder/'source/main.phs').write_text(source)
            runner.run(name+'-publish',['php',APP/'tests/span_hash_publish.php',folder])
            build=json.loads(runner.run(name+'-build',['php',APP/'tests/fixtures/native_cache_driver.php',folder/'published',folder/'cache',ROOT/'runtime/include','/usr/bin/clang++','--no-pch'],timeout=300))
            actual=runner.run(name+'-run',[build['executable']],expected=1 if failed else 0)
            if failed:
                assert actual==b'',actual
                assert (out/'logs'/(name+'-run.stderr')).read_bytes()==b'byte_span_to_string\n'
            else:
                assert actual==expected.encode(),(name,actual,expected)
                warm=json.loads(runner.run(name+'-warm',['php',APP/'tests/fixtures/native_cache_driver.php',folder/'published',folder/'cache',ROOT/'runtime/include','/usr/bin/clang++','--no-pch'],timeout=300))
                assert warm['compiled']==warm['linked']==0,warm
        run_tasks([Task('recovery',lambda:runner.run('recovery',['php',APP/'tests/span_hash_recovery.php',out/'recovery'])) ,Task('runtime',lambda:runtime(False)),Task('runtime-sanitized',lambda:runtime(True))]+[Task(n,lambda n=n,c=c:check(n,c)) for n,c in cases.items()])
    (out/'summary.json').write_text(json.dumps(dict(passed=True,cases=list(cases)),indent=2)+'\n')
if __name__=='__main__':main()
