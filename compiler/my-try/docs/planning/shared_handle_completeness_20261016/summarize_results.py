"""Summarize the actual median-total trial and worst observed memory for each case."""
from pathlib import Path
import json,statistics
B=Path(__file__).resolve().parent
rows=json.loads((B/'dev002-results/edit-results/summary.json').read_text())
old=json.loads((B/'previous-application-summary.json').read_text())
result=[]
for name in ['full','baseline-unchanged','multiple-bodies-edit','signature-callers-edit','class-layout-edit','common-declaration-edit']:
 group=[r for r in rows if r['name']==name];before=[r for r in old if r['name']==name]
 assert len(group)==len(before)==3
 r=sorted(group,key=lambda r:r['wall_s'])[1];o=sorted(before,key=lambda r:r['wall_s'])[1]
 stage=r['stages_s'];link=r['clang_sum_wall_s'].get('link',0)
 result.append(dict(name=name,trial=r['trial'],total_s=r['wall_s'],previous_total_s=o['wall_s'],previous_compile_s=o['compile_active_wall_s'],previous_peak_tree_mib=max(x['tree_peak_rss_kib'] for x in before)/1024,setup_s=stage['setup'],sync_parse_s=stage['sync_parse'],prepare_s=stage['prepare'],emit_publish_s=stage['emit_publish'],cpp_ready_s=stage['request_to_cpp_ready'],compile_s=r['compile_active_wall_s'],pch_s=r['pch_wall_s'],link_s=link,other_s=r['wall_s']-stage['request_to_cpp_ready']-r['compile_active_wall_s']-link,exe_ready_s=r['request_to_link_complete_s'],peak_tree_mib=max(x['tree_peak_rss_kib'] for x in group)/1024,peak_compiler_mib=max(x['compiler_peak_rss_kib'] for x in group)/1024,rebuilt=len(r['rebuilt_objects']),previous_rebuilt=len(o['rebuilt_objects']),reused=r['reused_objects_count'],pch_rebuilt=r['actions']['pch'],rebuilt_objects=r['rebuilt_objects'],range_s=[min(x['wall_s'] for x in group),max(x['wall_s'] for x in group)]))
(B/'comparison.json').write_text(json.dumps(result,indent=2))
for r in result:
 print(r['name'],':',f"{r['previous_total_s']:.3f} -> {r['total_s']:.3f}",f"{r['previous_rebuilt']} -> {r['rebuilt']} objects",'peak',round(r['peak_tree_mib'],1),'MiB')
 print('stage',*[f'{r[k]:.3f}' for k in ['setup_s','sync_parse_s','prepare_s','emit_publish_s','cpp_ready_s','compile_s','link_s','other_s']])
for case in ['multiple-bodies','signature-callers','class-layout','common-declaration']:
 print(case,[(phase,round(statistics.median(r['wall_s'] for r in rows if r['case']==case and r['phase']==phase),3)) for phase in ['unchanged','revert','revert-unchanged']])
