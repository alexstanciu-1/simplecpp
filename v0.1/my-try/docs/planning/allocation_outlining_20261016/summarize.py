import json,statistics
from pathlib import Path
B=Path(__file__).resolve().parent/'dev002'
rows=json.loads((B/'results/summary.json').read_text())
summary=[]
for opt,variant in [(0,'previous'),(0,'outlined'),(1,'previous'),(1,'outlined'),(2,'outlined')]:
    for phase in ['full','baseline-unchanged','body-edit']:
        group=[r for r in rows if r['optimization']==opt and r['variant']==variant and r['name']==phase]
        r=sorted(group,key=lambda r:r['wall_s'])[len(group)//2]
        link=r['clang_sum_wall_s']['link'];compile_time=r['clang_active_wall_s']-link
        item=dict(optimization=opt,variant=variant,phase=phase,trials=len(group),wall_s=r['wall_s'],cpp_ready_s=r['stages_s']['request_to_cpp_ready'],compile_active_s=compile_time,link_s=link,other_s=r['wall_s']-r['stages_s']['request_to_cpp_ready']-r['clang_active_wall_s'],exe_ready_s=r['request_to_link_complete_s'],peak_tree_mib=max(x['tree_peak_rss_kib'] for x in group)/1024,actions=r['actions'],unit_wall_s=r['unit_wall_s'],executable_bytes=r['executable_bytes'],stages_s=r['stages_s'])
        summary.append(item)
        print(opt,variant,phase,*(round(item[x],3) for x in ['wall_s','cpp_ready_s','compile_active_s','link_s','peak_tree_mib']))
runtime=json.loads((B/'runtime-results/summary.json').read_text())['runs']
runtime_summary=[]
for tag in sorted(set(r['configuration'] for r in runtime)):
    group=[r for r in runtime if r['configuration']==tag]
    runtime_summary.append(dict(configuration=tag,median_wall_s=statistics.median(r['wall_s'] for r in group),min_wall_s=min(r['wall_s'] for r in group),max_wall_s=max(r['wall_s'] for r in group),peak_rss_kib=max(r['peak_rss_kib'] for r in group),runs=len(group)))
result=dict(build_cases=summary,runtime=runtime_summary,profiles=json.loads((B/'profiles/summary.json').read_text()),switches=json.loads((B/'switch-results/summary.json').read_text()),matrix_builds=len(rows))
(B.parent/'comparison.json').write_text(json.dumps(result,indent=2)+'\n')
print('RUNTIME',json.dumps(runtime_summary,indent=2))
