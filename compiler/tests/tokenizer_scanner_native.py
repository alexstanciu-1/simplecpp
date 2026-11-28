"""Emit the bounded scanner in each source flavor and produce production lexer oracles."""
from source_suffix import source_suffix
import argparse
import json
from pathlib import Path
import subprocess

from app_namespace_native import service, invoke, artifacts
from classes_native import fixture
from extends_native import source_edit
from token_source_native import COMPILER

PATTERNS = {'identifiers': 'if iffy long_identifier123 _X9 while while2 ',
            'operators': '====== == = === ',
            'mixed': 'while x_12===if\r\nlet auto while2 == _ =\t'}


def prepare(work):
    work.mkdir(parents=True,exist_ok=True)
    inputs = ['', ' \t\r\n', '_9 ===while if9 while2 ====','A'*4096+'===', 'if == x','if === y','if @ y','if == x']
    inputs += [p*512 for p in PATTERNS.values()]
    for flavor in ('cpp','php','js'):
        path = work/flavor
        path.mkdir(exist_ok=True)
        (path/'module.json').write_text(json.dumps({'entry':'main.'+source_suffix(flavor)}))
        (path/('scanner.'+source_suffix(flavor))).write_bytes((COMPILER.parent/'specs/planning/benchmarks/tokenizer'/('scanner.'+source_suffix(flavor))).read_bytes())
        s=fixture(flavor)
        (path/('main.'+source_suffix(flavor))).write_text(s['infer']('r','scan("if",0)')+'return 8;')
        out=work/(flavor+'-out')
        proc=service(path,out)
        main=path/('main.'+source_suffix(flavor))
        original=main.read_text()
        try:
            row=invoke(proc)
            assert 'error' not in row,row
            for edit in [original.replace('"if"', '"while"'), '@', original]:
                source_edit(main,edit)
                before=artifacts(out)
                current=invoke(proc)
                fresh=service(path,work/(flavor+'-fresh'))
                try: clean=invoke(fresh)
                finally: fresh.stdin.close(); fresh.wait()
                if edit=='@':
                    assert current.get('error') and current['error']==clean.get('error')
                    assert artifacts(out)==before
                else:
                    assert current['emitted_sources']==[flavor+'/main.'+source_suffix(flavor)],current
                    assert artifacts(out)==artifacts(work/(flavor+'-fresh'))
            assert invoke(proc)['preparation']['prepared']==0
        finally: proc.stdin.close(); proc.wait()
        (out/'prepare.json').write_text(json.dumps(row))
        # Inspect the retained frontend object itself; reuse of emitted bytes is weaker.
        identity=r'''
require $argv[1].'/boot.php';
use scpp\compiler\Project_Compiler;
$c=new Project_Compiler();
$session=$c->frontend($argv[2]);
$key=$argv[3].'/scanner.'.$argv[3];
$sibling=$session->files()[$key]->tokens;
$path=$argv[2].'/main.'.$argv[3];
$original=file_get_contents($path);
try {
 foreach ([str_replace('"if"', '"while"', $original), $original] as $edit) {
    file_put_contents($path,$edit);
    clearstatcache();
    $session=$c->frontend($argv[2],$session);
    if ($session->files()[$key]->tokens !== $sibling) throw new RuntimeException('Sibling tokens replaced');
 }
} finally {file_put_contents($path,$original);}
'''
        subprocess.run(['php','-r',identity,COMPILER,path,flavor],check=True)
        oracle=r'''
require $argv[1].'/boot.php';
use scpp\compiler\{Tokenizer,source_file,flavor,diagnostic};
foreach(json_decode(stream_get_contents(STDIN)) as $bytes) {
 $source=new source_file('input',$bytes,constant(flavor::class.'::'.$argv[2]));
 try {$list=(new Tokenizer($source))->tokenize(); echo json_encode(array_map(fn($t)=>[$t->offset,$t->length,$t->kind->value],$list->tokens));}
 catch(diagnostic $e){echo json_encode([$e->offset,substr(strrchr($e->getMessage(),':'),2)]);}
 echo "\n";
}'''
        rows=subprocess.check_output(['php','-r',oracle,str(COMPILER),flavor],input=json.dumps(inputs).encode()).splitlines()
        for i,(data,row) in enumerate(zip(inputs,rows,strict=True)):
            (path/f'input{i}').write_bytes(data.encode())
            tokens=json.loads(row)
            expected=('error %d %s\n'%tuple(tokens) if tokens and isinstance(tokens[0],int)
                      else ''.join(f'{o} {n} {k} {data[o:o+n]}\n' for o,n,k in tokens))
            (path/f'expected{i}').write_bytes(expected.encode())

if __name__=='__main__':
    parser=argparse.ArgumentParser(description=__doc__)
    parser.add_argument('output',type=Path)
    prepare(parser.parse_args().output.resolve())
