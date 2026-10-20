import hashlib,json,os,platform,subprocess
from pathlib import Path
B=Path(__file__).resolve().parent
paths=[B/'phpp/.prism/build/main',B/'mold',B/'measure-clang',B/'measure-mold',B/'source/main.php',Path('/usr/bin/clang++-18'),Path('/usr/bin/ld'),Path('/usr/bin/ninja')]
result=dict(host=platform.node(),kernel=platform.release(),cpu=next(l.split(':',1)[1].strip() for l in Path('/proc/cpuinfo').read_text().splitlines() if l.startswith('model name')),logical_cpus=os.cpu_count(),load=os.getloadavg(),source_bytes=sum(p.stat().st_size for p in (B/'accounting').rglob('*.phs')),source_files=len(list((B/'accounting').rglob('*.phs'))),sha256={str(p):hashlib.sha256(p.read_bytes()).hexdigest() for p in paths},versions={p:subprocess.check_output([p,'--version'],text=True).splitlines()[0] for p in ['/usr/bin/clang++-18','/usr/bin/ld','/usr/bin/ninja',str(B/'mold')]})
(B/'mold-provenance.json').write_text(json.dumps(result,indent=2))
print(json.dumps(result,indent=2))
