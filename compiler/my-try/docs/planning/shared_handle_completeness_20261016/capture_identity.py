import hashlib,json,subprocess
from pathlib import Path
B=Path(__file__).resolve().parent
R=B.parent/'repo/runtime'
def sha(p):return hashlib.sha256(p.read_bytes()).hexdigest()
record=dict(compiler_base_revision='85c2e2e8',compiler_overlay='shared-handle stored-value completeness; see implementation.patch',sha256={str(p):sha(p) for p in [B/'phpp/.prism/build/main',B/'mold',B/'measure-clang',B/'source/main.php']},accounting_head=subprocess.check_output(['git','-C',str(B/'accounting'),'rev-parse','HEAD'],text=True).strip(),runtime_head=subprocess.check_output(['git','-C',str(R),'rev-parse','HEAD'],text=True).strip(),runtime_baseline_hashes={str(p.relative_to(R)):sha(p) for folder in ['include','metadata'] for p in (R/folder).rglob('*') if p.is_file()},source_hashes={str(p.relative_to(B/'source')):sha(p) for p in (B/'source').rglob('*.php')})
(B/'experiment-identity.json').write_text(json.dumps(record,indent=2))
