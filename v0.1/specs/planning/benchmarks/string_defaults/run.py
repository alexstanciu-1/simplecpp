#!/usr/bin/env python3
"""Reproducible string benchmark. Build outputs stay in an explicit work directory."""
import argparse
import csv
import hashlib
import json
import os
from pathlib import Path
import platform
import random
import statistics
import subprocess
import sys

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]


def corpus(work):
    rows, metadata = [], []
    for encoding, tile, needle in [("ascii", "abCdEf12", "ab"), ("utf8", "é中😀", "é")]:
        for size in (64, 4096, 65536):
            text = (tile * (size // len(tile.encode()))) + "a" * (size % len(tile.encode()))
            cases = [
                ("replace_absent", "replace", text, text, "~missing~", "R"),
                ("replace_dense", "replace", text, text, needle, "R"),
                ("replace_grow", "replace", text, text, needle, "replacement"),
                ("compare_equal", "compare", text, text, "_", "_"),
                ("compare_early", "compare", "a"+text[1:], "z"+text[1:], "_", "_"),
                ("compare_late", "compare", text+"a", text+"z", "_", "_"),
                ("baseline", "baseline", text, text, "_", "_"),
            ]
            for name, op, s, t, n, r in cases:
                ident = f"{encoding}_{size}_{name}"
                expected = s.replace(n, r) if op == "replace" else str((s > t) - (s < t))
                rows.append([ident, op, s, t, n, r, expected])
                result = expected if op == "replace" else s
                metadata.append(dict(id=ident, op=op, encoding=encoding, size=size,
                    bytes=len(s.encode()), codepoints=len(s), utf16_units=len(s.encode('utf-16-le'))//2,
                    output_bytes=len(result.encode()), output_codepoints=len(result),
                    output_utf16_units=len(result.encode('utf-16-le'))//2,
                    compare_sum=1+(s>t)-(s<t), matches=s.count(n)))
    random.Random(21021).shuffle(rows)
    for ident, op, s, t, n, r, expected in rows:
        case_dir = work/'inputs'/ident
        case_dir.mkdir(parents=True, exist_ok=True)
        for name, value in [('s',s),('t',t),('n',n),('r',r),('expected',expected)]:
            (case_dir/name).write_text(value, encoding='utf-8')
    path = work / "corpus.tsv"
    path.write_text("".join("\t".join(row)+"\n" for row in rows), encoding="utf-8")
    (work / "cases.json").write_text(json.dumps(metadata, indent=2)+"\n")
    return path


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--work', type=Path, required=True)
    p.add_argument('--rustc', default='rustc')
    p.add_argument('--java-home', type=Path)
    p.add_argument('--skip-build', action='store_true')
    p.add_argument('--only', nargs='*')
    p.add_argument('--compiler-root', type=Path, default=ROOT)
    args = p.parse_args()
    compiler_root = args.compiler_root.resolve()
    work = args.work.resolve()
    work.mkdir(parents=True, exist_ok=True)
    inputs = corpus(work)
    commands = json.loads((work/'commands.json').read_text()) if (work/'commands.json').exists() else []

    def run(cmd, name):
        cmd = list(map(str, cmd))
        if name.startswith('run_'):
            cmd = ['taskset', '-c', str(min(os.sched_getaffinity(0)))] + cmd
        commands.append(cmd)
        print(name, flush=True)
        result = subprocess.run(cmd, cwd=ROOT, text=True, capture_output=True)
        (work / (name+'.stdout')).write_text(result.stdout)
        (work / (name+'.stderr')).write_text(result.stderr)
        (work / 'commands.json').write_text(json.dumps(commands, indent=2)+'\n')
        if result.returncode:
            raise RuntimeError(f"{name} failed: {result.stderr[-3000:]} {result.stdout[-1000:]}")
        return result.stdout

    java = str(args.java_home/'bin/java') if args.java_home else 'java'
    javac = str(args.java_home/'bin/javac') if args.java_home else 'javac'
    if not args.skip_build:
        run(['clang++','-std=c++20','-O3','-DNDEBUG','-I'+str(ROOT/'runtime/include'),HERE/'bench.cpp','-o',work/'bench_cpp'], 'build_cpp')
        run(['clang++','-std=c++20','-O3','-DNDEBUG','-DALLOCATION_PROBE','-I'+str(ROOT/'runtime/include'),HERE/'bench.cpp','-o',work/'alloc_probe'], 'build_allocations')
        run([args.rustc,'-O',HERE/'bench.rs','-o',work/'bench_rust'], 'build_rust')
        run([javac,'-d',work,HERE/'Bench.java'], 'build_java')
        source = work/'phs'
        source.mkdir(exist_ok=True)
        calls=[]
        for line in inputs.read_text().splitlines():
            ident,op,*_=line.split('\t')
            calls.append('run_case('+','.join(json.dumps(v) for v in [ident,op,str(work/'inputs'/ident)])+');')
        (source/'main.phs').write_text((HERE/'main.phs').read_text().replace('@@CASES@@','\n'.join(calls)))
        (source/'prism.json').write_text('{"runtime":{"languages":{"php":{"profile":"strict"}}}}\n')
        for mode, flag in [('concrete','--prelower-templates'),('ordinary','--no-prelower-templates')]:
            pub = work/('published_'+mode)
            run(['php','-d','xdebug.mode=off',HERE/'publish.php',compiler_root,source,pub,mode], 'publish_'+mode)
            out=run(['php','-d','xdebug.mode=off',compiler_root/'compiler/my-try/tools/build_cpp.php',pub,work/('native_'+mode),'--no-pch','-O3'], 'build_'+mode)
            (work/('exe_'+mode+'.txt')).write_text(json.loads(out)['executable'])

    versions = {}
    for name, cmd in [('clang',['clang++','--version']),('php',['php','-n','--version']),('rust',[args.rustc,'--version']),('python',[sys.executable,'--version']),('node',['node','--version']),('java',[java,'-version'])]:
        r=subprocess.run(cmd,text=True,capture_output=True)
        versions[name]=(r.stdout+r.stderr).strip()
    versions.update(revision=subprocess.check_output(['git','rev-parse','HEAD'],cwd=ROOT,text=True).strip(),
                    platform=platform.platform(),cpu=next((line.split(':',1)[1].strip() for line in Path('/proc/cpuinfo').read_text().splitlines() if line.startswith('model name')),platform.processor()),
                    pinned_cpu=min(os.sched_getaffinity(0)),
                    corpus_sha256=hashlib.sha256(inputs.read_bytes()).hexdigest(),
                    sources={p.name:hashlib.sha256(p.read_bytes()).hexdigest() for p in HERE.iterdir() if p.is_file()})
    versions['runtime_sources']={name:hashlib.sha256((ROOT/name).read_bytes()).hexdigest() for name in [
        'runtime/include/core/string_support.hpp', 'runtime/include/modules/strings/strings.hpp',
        'runtime/include/scpp/string_t.hpp',
    ]}
    (work/'runtime.patch').write_text(subprocess.check_output(
        ['git','diff','HEAD','--','runtime/include'],cwd=ROOT,text=True))
    compiler_files = [compiler_root/'compiler/my-try/boot.php']
    for directory in ['01_prepare_inputs','02_tokenize','03_parse','04_analyze','05_backend','compiler']:
        compiler_files.extend((compiler_root/'compiler/my-try'/directory).rglob('*.php'))
    compiler_files.extend((compiler_root/'runtime/metadata/modules').glob('*.json'))
    versions['compiler_root'] = str(compiler_root)
    versions['compiler_sources'] = {str(p.relative_to(compiler_root)):hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(compiler_files)}
    if compiler_root == ROOT:
        (work/'compiler.patch').write_text(subprocess.check_output(
            ['git','diff','HEAD','--','compiler/my-try','runtime/metadata'],cwd=ROOT,text=True))
    else:
        (work/'compiler.patch').write_text('External compiler snapshot; see compiler_root and compiler_sources in environment.json.\n')
    versions['executables']={name:hashlib.sha256(path.read_bytes()).hexdigest() for name,path in {
        'cpp':work/'bench_cpp','rust':work/'bench_rust', 'java':work/'Bench.class',
        'concrete':Path((work/'exe_concrete.txt').read_text()),
        'ordinary':Path((work/'exe_ordinary.txt').read_text()),
    }.items()}
    (work/'environment.json').write_text(json.dumps(versions,indent=2)+'\n')
    targets={
        'mytry_concrete':[Path((work/'exe_concrete.txt').read_text())],
        'mytry_ordinary':[Path((work/'exe_ordinary.txt').read_text())],
        'runtime':[work/'bench_cpp',inputs,'runtime'],
        'runtime_snapshot':[work/'bench_cpp',inputs,'runtime_snapshot'],
        'cpp':[work/'bench_cpp',inputs,'cpp'],
        'cpp_reserved':[work/'bench_cpp',inputs,'cpp_reserved'],
        'php':['php','-n',HERE/'bench.php',inputs],
        'rust':[work/'bench_rust',inputs],
        'python':[sys.executable,HERE/'bench.py',inputs],
        'python_bytes':[sys.executable,HERE/'bench.py',inputs,'bytes'],
        'js':['node','--allow-natives-syntax',HERE/'bench.js',inputs],
        'js_flat':['node','--allow-natives-syntax',HERE/'bench.js',inputs,'flat'],
        'java':[java,'-Xbatch','-Xms128m','-Xmx512m','-cp',work,'Bench',inputs],
    }
    cases={x['id']:x for x in json.loads((work/'cases.json').read_text())}
    # Sequential execution: no competing benchmark processes.
    for name,cmd in targets.items():
        if args.only and name not in args.only: continue
        output=run(cmd,'run_'+name)
        rows=list(csv.reader(output.splitlines()))
        assert len(rows)==len(cases)*7, (name,len(rows))
        seen=set()
        for ident,label,trial,n,ns,checksum in rows:
            c=cases[ident]
            unit='output_bytes'
            if name=='python': unit='output_codepoints'
            if name in ('js','java'): unit='output_utf16_units'
            if name=='js_flat' and c['op']=='baseline': unit='output_utf16_units'
            expected=c['compare_sum'] if c['op']=='compare' else c[unit]
            assert int(checksum)==int(n)*expected,(name,ident,checksum,expected,n)
            assert int(ns)>0
            seen.add((ident,int(trial)))
        assert len(seen)==len(cases)*7
    for backend in ['runtime','runtime_snapshot','cpp','cpp_reserved']:
        output=run([work/'alloc_probe',inputs,backend],'alloc_'+backend)
        (work/('alloc_'+backend+'.csv')).write_text(output)
    records=[]
    grouped={}
    for path in list(work.glob('rounds/*/run_*.stdout')) + list(work.glob('run_*.stdout')):
        name=path.stem.removeprefix('run_')
        for ident,label,trial,n,ns,checksum in csv.reader(path.read_text().splitlines()):
            grouped.setdefault((name,ident),[]).append(int(ns)/int(n))
    for (name,ident),values in grouped.items():
        records.append(dict(backend=name,id=ident,median_ns=statistics.median(values),min_ns=min(values),max_ns=max(values),samples_ns=values))
    (work/'summary.json').write_text(json.dumps(records,indent=2)+'\n')
    print('Results:',work/'summary.json')

if __name__=='__main__': main()
