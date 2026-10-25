from pathlib import Path
import json,subprocess,bisect
out=Path('/tmp/compiler-native-o2-heap-raw-01');d=json.loads((out/'volume-fast.json').read_text());binary=json.loads(Path('/tmp/compiler-native-o2-01/logs/build-o2.stdout').read_text())['executable'];symbols=[]
for l in subprocess.check_output(['nm','-n',binary],text=True).splitlines():
 f=l.split()
 if len(f)==3 and f[1] in ['t','T','W','w']:symbols.append((int(f[0],16),f[2]))
addresses=[x[0] for x in symbols];names={}
def resolve(ip):
 offset=ip-d['binary_base']
 if offset<0 or offset>0xb00000:return hex(ip)
 i=bisect.bisect_right(addresses,offset)-1
 if i<0:return hex(ip)
 name=symbols[i][1]
 if name not in names:names[name]=subprocess.check_output(['c++filt',name],text=True).strip().split('(')[0]
 return names[name]
for row in d['top']:row['stack']=[resolve(ip) for ip in row.pop('ips')]
(out/'volume-resolved.json').write_text(json.dumps(d,indent=2)+'\n')
print('total GiB',d['total_requested_bytes']/2**30,'calls',d['allocation_calls'])
for row in d['top'][:18]:
 record=next((x for x in row['stack'] if x.startswith('record_')),'unknown')
 print(round(row['bytes']/2**30,3),'GiB',row['calls'],'allocations',record)
 print(' <- '.join(row['stack'][:4]))
