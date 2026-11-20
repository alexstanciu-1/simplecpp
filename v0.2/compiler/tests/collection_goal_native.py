"""Shared collection-goal proof runner: incremental/fresh equivalence and native stage timings."""
import json
from pathlib import Path
from app_namespace_native import artifacts, invoke, service
from application_vectors_native import measure
from extends_native import source_edit


def revisions(work,flavor,sources,edits, *, unaffected_source=None, unaffected_revisions=None):
    app=work/flavor;app.mkdir();out=work/(flavor+'-out');rows=[]
    source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor}))
    for name,text in sources.items():source_edit(app/(name+'.'+flavor),text)
    proc=service(app,out)
    try:
        for revision,(name,text,expected) in enumerate(edits):
            before=artifacts(out)
            if name:
                path=app/(name+'.'+flavor)
                if text is None:
                    if path.exists():path.unlink()
                else:source_edit(path,text)
            got=invoke(proc);fresh=work/f'{flavor}-fresh-{revision}';cleaner=service(app,fresh)
            try:clean=invoke(cleaner)
            finally:cleaner.stdin.close();cleaner.wait()
            if expected is None or isinstance(expected, str):
                assert 'error' in got and got['error']==clean.get('error'),(flavor,revision,got,clean)
                if isinstance(expected, str):
                    assert expected in got['error'], (flavor, revision, got)
                assert artifacts(out)==before
                continue
            assert 'error' not in got and 'error' not in clean,(flavor,revision,got,clean)
            assert artifacts(out)==artifacts(fresh),(flavor,revision)
            if revision and unaffected_source and (unaffected_revisions is None or revision in unaffected_revisions):
                assert got['preparation']['reused'] and not any(p.endswith('/'+unaffected_source) for p in got['emitted_sources']), got
            row={'revision':revision,'php_ms':got['php_ms'],'prepared':got['preparation']['prepared']}
            if revision==1 and name is None:assert got['preparation']['prepared']==0
            else:row.update(measure(out,work/f'{flavor}-native-{revision}',expected))
            rows.append(row);print(flavor,revision,'passed',flush=True)
    finally:proc.stdin.close();proc.wait()
    (work/(flavor+'-timings.json')).write_text(json.dumps(rows,indent=2)+'\n')


def guard(work,flavor,label,source,error, *, retry_entry=False):
    app=work/(flavor+'-'+label);app.mkdir();out=work/(flavor+'-'+label+'-out')
    source_edit(app/'module.json',json.dumps({'entry':'main.'+flavor}));source_edit(app/('main.'+flavor),source)
    p=service(app,out)
    try:got=invoke(p)
    finally:p.stdin.close();p.wait()
    assert 'error' not in got,got
    entry=out/'entry.cpp';text=entry.read_text().replace('int main(', 'int tested_entry(')
    on_expected = 'tested_entry()' if retry_entry else '8'
    entry.write_text('#include <stdexcept>\n#include <string_view>\n'+text+'\nint main() {try {tested_entry();} catch(const std::exception& e) {return std::string_view(e.what()) == '+json.dumps(error)+' ? '+on_expected+' : 91;} return 92;}\n')
    measure(out,work/(flavor+'-'+label+'-native'),b'')
