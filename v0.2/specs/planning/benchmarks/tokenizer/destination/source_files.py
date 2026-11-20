"""Representative whole-file pairs for the experimental destination plan."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import statistics
import subprocess
import sys

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE.parent / 'byte_access'))
from source_classification import FILES, FLAVORS, lexical_helpers, prepare
from measure import once, run


def sources(repo, trial, out):
    prepare(repo, out)
    for f in FLAVORS:
        d = out / f
        keyword, extra = lexical_helpers(json.loads((d/'vocabulary.json').read_text()), f)
        extra = extra.replace('tokenizer_bench::split_view', 'scpp::detail::checked_byte_view').replace('.byte_at(', '.read(')
        normalized = []
        for mode in ('ordinary', 'selected'):
            units = trial / (f+'-scanner') / mode / 'units'
            header, = [p for p in units.glob('*.hpp') if 'Scan scan_(' in p.read_text()]
            text = header.with_suffix('.cpp').read_text()
            text = '#include <string_view>\n' + once(text, 'namespace App {\nscpp_records::Scan',
                'namespace App {\n'+keyword+extra+'scpp_records::Scan')
            text, n = re.subn(r'if \(\(\(var_i_5 - var_start_7\)[^\n]+\) \{\n\s*var_kind_8 = 62LL;',
                'if (keyword(std::string_view(var_out_2.source.data()+var_start_7,var_i_5-var_start_7))) {\n'
                '                var_kind_8 = 62LL;', text)
            assert n == 1
            text = once(text, 'else {\n\t\t\t(var_out_2).error',
                'else if (!(var_kind_8 = extra(scpp_byte_view,var_i_5,var_b_6))) {\n\t\t\t(var_out_2).error')
            (d/(mode+'.cpp')).write_text(text)
            start = (text.index('[[maybe_unused]] scpp_records::Token var_item_') if mode=='ordinary'
                     else text.index('(var_items_3).append_fields('))
            end = text.index(';\n\t}',start)+1
            normalized.append(text[:start]+text[end:])
        assert normalized[0] == normalized[1], f


def bench(trial, corpus, runtime, driver):
    results = {'cpu':sorted(os.sched_getaffinity(0)), 'versions':{}, 'files':{}, 'pairs_seconds':{}}
    assert len(results['cpu']) == 1
    cc = 'clang++'
    results['versions'][cc] = run([cc,'--version']).decode().splitlines()[0]
    for f in FLAVORS:
        d = corpus/f
        exes = {}
        for mode in ('ordinary','selected'):
            unit = trial/(f+'-scanner')/mode
            exe = d/(cc+'-'+mode)
            run([cc,'-std=c++23','-O2','-g0','-I',unit/'units','-I',unit,'-I',runtime/'generated/public',
                 d/(mode+'.cpp'),driver,trial/(cc+'.o'),'-o',exe])
            exes[mode] = exe
        for role in FILES:
            path = d/role
            oracle = (d/(role+'.oracle')).read_bytes()
            assert oracle and not oracle.startswith(b'error ')
            for exe in exes.values():
                for hints in ([],[path]):
                    assert run([exe,'dump',path,*hints]) == oracle,(cc,f,role,exe)
            results['files'][f+'/'+role] = {'path':(d/(role+'.path')).read_text(),
                'sha256':hashlib.sha256(path.read_bytes()).hexdigest(),'bytes':path.stat().st_size}
            pairs = []
            for i in range(17):
                row = {}
                for mode in (('ordinary','selected') if i%2 else ('selected','ordinary')):
                    samples = list(map(float,run([exes[mode],'time',path,path]).split()))
                    assert len(samples)==9 and min(samples)>0
                    row[mode]=statistics.median(samples)
                if i: pairs.append(row)
            results['pairs_seconds']['/'.join((cc,f,role))]=pairs
    return results


if __name__=='__main__':
    p=argparse.ArgumentParser(description=__doc__)
    sub=p.add_subparsers(dest='action',required=True)
    prep=sub.add_parser('prepare')
    for name in ('repo','trial','corpus'): prep.add_argument(name,type=Path)
    test=sub.add_parser('bench')
    for name in ('trial','corpus','runtime','driver'): test.add_argument(name,type=Path)
    a=p.parse_args()
    if a.action=='prepare': sources(a.repo.resolve(),a.trial.resolve(),a.corpus.resolve())
    else: print(json.dumps(bench(a.trial.resolve(),a.corpus.resolve(),a.runtime.resolve(),a.driver.resolve()),indent=2))
