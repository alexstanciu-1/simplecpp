#!/usr/bin/env python3
"""Compare reused runtime PCH against no PCH for an existing large emitted workload; no compiler changes."""
import argparse
import json
from pathlib import Path
import sys

HERE = Path(__file__).resolve().parent
sys.path.insert(0, str(HERE))
from benchmark import run, clean, link_ms, summary, write_build


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--input', type=Path, required=True, help='One benchmark sample output directory, containing php.json and cpp/')
    parser.add_argument('--output', type=Path, required=True)
    args = parser.parse_args()
    source = args.input.resolve()
    output = args.output.resolve(); output.mkdir(parents=True, exist_ok=False)
    php = json.loads((source/'php.json').read_text())
    expected = json.loads((source/'sources/expected.json').read_text())
    units = [source/'cpp'/path for path in php['cpp_files']]
    configs = {}
    for mode in ['no-pch','minimal-pch','used-headers-pch']:
        work = output/mode;work.mkdir()
        command = write_build(work, units, 'O2', mode!='no-pch');command[2]='8'
        startup = None
        if mode!='no-pch':
            if mode=='used-headers-pch':
                with (work/'runtime_pch.hpp').open('a') as stream:
                    stream.write('#include <scpp/string.hpp>\n#include <scpp/arithmetic.hpp>\n')
            startup, log, error = run([command[0],'-j','8','runtime.pch'],work)
            (work/'pch-build.log').write_bytes(log+error)
        run(command,work)
        configs[mode] = {'work':work,'command':command,'startup_ms':startup,'rows':[]}
    # Rotate modes to reduce a fixed run-order advantage. All app objects are rebuilt each time.
    modes=list(configs)
    for sample in range(3):
        for mode in modes[sample:]+modes[:sample]:
            print(f'{mode}: sample {sample+1}/3',flush=True)
            row=configs[mode];work=row['work'];pch=work/'runtime.pch'
            stamp=pch.stat().st_mtime_ns if pch.exists() else None
            clean(work)
            elapsed,log,error=run(row['command'],work)
            (work/f'build-{sample}.log').write_bytes(log+error)
            _,stdout,_=run([work/'program'],expected=expected['exit'])
            assert stdout==expected['stdout'].encode()
            assert stamp==(pch.stat().st_mtime_ns if pch.exists() else None)
            row['rows'].append({'native_ms':elapsed,'link_ms':link_ms(work)})
    result={mode:{'pch_creation_ms':row['startup_ms'],'samples':row['rows'],
                  'native_ms':summary([r['native_ms'] for r in row['rows']]),
                  'link_ms':summary([r['link_ms'] for r in row['rows']])}
            for mode,row in configs.items()}
    (output/'results.json').write_text(json.dumps(result,indent=2)+'\n')
    print(json.dumps(result,indent=2))

if __name__=='__main__':
    main()
