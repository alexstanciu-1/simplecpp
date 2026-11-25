#!/usr/bin/env python3
"""Render retained benchmark measurements; no timings are synthesized."""
import argparse
import csv
import json
from pathlib import Path

LABELS = {
    'mytry_concrete':'Simple C++ / my-try default', 'mytry_ordinary':'my-try ordinary lowering',
    'runtime':'Direct runtime helper', 'runtime_snapshot':'Runtime + argument copies',
    'cpp':'Native C++ find/append + compare', 'cpp_reserved':'C++ reserved output',
    'php':'PHP', 'rust':'Rust', 'python':'Python str', 'python_bytes':'Python bytes',
    'js':'JavaScript', 'js_flat':'JS + UTF-8 output materialization', 'java':'Java',
}

def main():
    parser=argparse.ArgumentParser()
    parser.add_argument('results',type=Path)
    args=parser.parse_args()
    folder=args.results
    records=json.loads((folder/'summary.json').read_text())
    sample_counts=sorted({len(x['samples_ns']) for x in records})
    rows={(x['backend'],x['id']):x for x in records}
    with (folder/'summary.csv').open('w') as f:
        w=csv.writer(f)
        w.writerow(['backend','case','median_ns','min_ns','max_ns'])
        for x in records: w.writerow([x['backend'],x['id'],x['median_ns'],x['min_ns'],x['max_ns']])
    lines=['# String benchmark tables','', 'Doc Status: supporting','',
           f'All values below are median microseconds per operation across {sample_counts} batches per case. Lower is faster. These include each language’s loop/result-consumption costs; see the methodology and caveats in the accompanying report.','']
    for encoding in ['ascii','utf8']:
        for size in [64,4096,65536]:
            lines += [f'## {encoding.upper()}, nominal {size} input bytes','',
                      '| Implementation | Replace absent | Replace dense | Replace growing | Compare equal | Compare early | Compare late | Length-loop control |',
                      '| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: |']
            for backend,label in LABELS.items():
                vals=[]
                for case in ['replace_absent','replace_dense','replace_grow','compare_equal','compare_early','compare_late','baseline']:
                    vals.append(f"{rows[backend,f'{encoding}_{size}_{case}']['median_ns']/1000:.3f}")
                lines.append('| '+label+' | '+' | '.join(vals)+' |')
            lines.append('')
    (folder/'tables.md').write_text('\n'.join(lines)+'\n')

    import matplotlib
    matplotlib.use('Agg')
    import matplotlib.pyplot as plt
    backends=['mytry_concrete','cpp','php','rust','python','js','java']
    fig,axes=plt.subplots(1,2,figsize=(13,6),layout='constrained')
    for ax,case,title in zip(axes,['replace_dense','compare_late'],['Literal replace-all: dense matches','Lexicographic comparison: late mismatch']):
        for shift,encoding,color in [(-.18,'ascii','#2675b8'),(.18,'utf8','#d68228')]:
            values=[rows[b,f'{encoding}_65536_{case}']['median_ns']/1000 for b in backends]
            errors=[
                [value-rows[b,f'{encoding}_65536_{case}']['min_ns']/1000 for b,value in zip(backends,values)],
                [rows[b,f'{encoding}_65536_{case}']['max_ns']/1000-value for b,value in zip(backends,values)],
            ]
            ax.barh([i+shift for i in range(len(backends))],values,height=.34,label=encoding.upper(),color=color,xerr=errors,error_kw={'elinewidth':.7,'capsize':2})
        ax.set_yticks(range(len(backends)),[LABELS[b] for b in backends])
        ax.invert_yaxis()
        ax.set_xscale('log')
        ax.set_xlabel('µs per operation (log scale; lower is faster)')
        ax.set_title(title)
        ax.grid(axis='x',alpha=.2)
        ax.legend()
    fig.suptitle('String operations on ~64 KiB inputs — medians with observed min/max\nUTF-8 corpus is decoded to each language’s native text representation')
    fig.savefig(folder/'comparison.png',dpi=160)
    fig.savefig(folder/'comparison.svg')

if __name__=='__main__': main()
