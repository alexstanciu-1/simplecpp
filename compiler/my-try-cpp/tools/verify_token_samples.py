"""Verify remote token output against the locally old-tokenizer-validated corpus digest."""
import hashlib
import json
from pathlib import Path
import subprocess

PROJECT = Path(__file__).resolve().parents[1]
expected = json.loads((PROJECT / 'samples/token-validation.json').read_text())
for name, result in expected.items():
    source = PROJECT / 'samples' / ('my-try/phpp' if name == 'my-try' else name)
    for jobs in (1, 16):
        dump = subprocess.check_output([str(PROJECT / 'build/compiler/tokenization-profile'), str(source), str(jobs), 'dump'])
        assert hashlib.sha256(dump).hexdigest() == result['dump_sha256'], (name, jobs)
    digest = hashlib.sha256()
    for file in sorted(source.rglob('*.phs')):
        digest.update(str(file.relative_to(source)).encode() + b'\0' + file.read_bytes())
    assert digest.hexdigest() == result['source_sha256'], name
    print(name, 'source hashes and every token span/tag match the old-validated reference at 1 and 16 jobs')
