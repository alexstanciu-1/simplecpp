"""Render captured evidence and populate only this chat's report columns."""
from pathlib import Path
import json,re,collections,html
B=Path(__file__).resolve().parent
D=B.parent
cases=json.loads((B/'samples.json').read_text())['cases']
stats={v:collections.Counter() for v in ('v1','v2')}
flags={
('07','v1'):'Unsupported xor placeholder in emitted text',
('35','v1'):'Unsupported array-cast placeholder in emitted text',
('62','v1'):'Trait declarations emitted as classes; trait use not composed',
('88','v1'):'Trait adaptation is not established by emitted classes',
('70','v1'):'Closure boxing emitted; not a proof of supported runtime storage',
('56','v1'):'Readonly qualifier is not preserved/enforced in emitted field',
('87','v1'):'Emission does not establish iteration safety',
('87','v2'):'Emission does not establish iteration safety',
('84','v1'):'S2S only; advisory/STAN behavior not measured',
('84','v2'):'S2S only; advisory behavior not measured',
('44','v1'):'Per-file C++; project include/link assembly not performed',
}
for c in cases:
 p=B/c['id']
 if c['kind']!='probe':continue
 results=json.loads((p/'result.json').read_text())
 # Keep raw logs; turn the v2 artifact envelope into a readable text bundle.
 raw=(B/'logs'/f"{c['id']}-v2.stdout").read_text()
 if results['v2']['exit_code']==0:
  envelope=json.loads(raw)
  assert envelope['format']=='scpp-cpp-artifacts-v1'
  text='// Readable bundle. File separator comments added by the report renderer.\n'
  for a in envelope['artifacts']:
   text+='\n// === '+a['path']+' ===\n'+a['text']
  (p/'v2.cpp.txt').write_text(text)
 sections=['# '+c['id']+' — '+c['feature'].removeprefix('🔴 '),'Doc Status: supporting','',c['scope'],'',
           'Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.','',
           '## PHP++ sample','', '[Source file](main.phs) · [Intended strict project configuration](prism.json)' if (p/'prism.json').exists() else '[Source file](main.phs)','',
           '```php',(p/'main.phs').read_text().rstrip(),'```','']
 if (p/'helper.phs').exists():
  sections+=['[Companion source](helper.phs):','','```php',(p/'helper.phs').read_text().rstrip(),'```','']
 for v in ('v1','v2'):
  r=results[v]
  state='Emitted' if r['exit_code']==0 and not r.get('error') else 'Generation error'
  stats[v][state]+=1
  sections+=['## S2S generated '+v,'',f"{state}; exit code `{r['exit_code']}`. [Command and raw logs](../logs/commands.json).",'']
  if (c['id'],v) in flags:sections+=['**'+flags[c['id'],v]+'.**','']
  diag=(p/f'{v}.diagnostic.txt').read_text().strip()
  if diag:sections+=['```text',diag,'```','']
  out=(p/f'{v}.cpp.txt').read_text()
  if out.strip():sections+=['[Complete emitted text]('+v+'.cpp.txt). File separators identify the emitted units.','','```cpp',out.rstrip(),'```','']
  else: sections+=['No C++ emitted. [Raw diagnostic]('+v+'.diagnostic.txt).','']
 (p/'README.md').write_text('\n'.join(sections)+'\n')
# Markdown preview treats inline HTML literally. Use native code spans only.
# Only table pipes need escaping; delimiters adapt if source contains backticks.
def inline_code(text):
 lines=[]
 for line in text.rstrip().expandtabs(4).split('\n'):
  if not line.strip():continue
  longest=max((len(m.group()) for m in re.finditer(r'`+',line)),default=0)
  fence='`'*(longest+1)
  lines.append(fence+' '+line.strip().replace('|',chr(92)+'|')+' '+fence)
 return ' '.join(lines)

def example_cells(identifier):
 folder=B/identifier
 prefix='status_samples_2026_10_08/'+identifier+'/'
 source=inline_code((folder/'main.phs').read_text())
 if (folder/'helper.phs').exists():
  source='**main.phs** '+source+' **helper.phs** '+inline_code((folder/'helper.phs').read_text())
 extra=[source+f' [Reference]({prefix}README.md#php-sample)']
 results=json.loads((folder/'result.json').read_text())
 for version in ('v1','v2'):
  result=results[version]
  label='Emitted C++' if result['exit_code']==0 and not result.get('error') else 'Generation error'
  cell='**'+label+'**'
  if (identifier,version) in flags:
   cell+=' ⚠ '+flags[identifier,version]
  emitted=(folder/f'{version}.cpp.txt').read_text()
  diagnostic=(folder/f'{version}.diagnostic.txt').read_text()
  if emitted.strip():cell+=' '+inline_code(emitted)
  if diagnostic.strip():cell+=' '+inline_code(diagnostic)
  if not emitted.strip():cell+=' No C++ emitted.'
  cell+=f' [Reference]({prefix}README.md#s2s-generated-{version})'
  extra.append(cell)
 return extra

# Fill every row, preserving the original four columns.
p=D/'status_comparison_2026_10_08.md'
lines=[];index=0
for line in p.read_text().splitlines():
 if line.startswith('| ') and not line.startswith(('| Feature','| ---','| **CH')):
  c=cases[index];index+=1
  cells=re.split(r'(?<!\\)\|',line)[1:5]
  prefix='status_samples_2026_10_08/'+c['id']+'/'
  if c['kind']=='probe':extra=example_cells(c['id'])
  elif c['id']=='75':extra=example_cells('44')
  else:
   description=c['scope'].replace('|',chr(92)+'|')
   if c['id']=='85':
    description=inline_code('{"runtime":{"languages":{"php":{"profile":"strict"}}}}')+' '+inline_code('scpp build --no-stan\nscpp run --no-stan')+' Workflow illustration; not executed.'
   extra=[description+f' [Reference]({prefix}scope.md)','N/A — compiler/build protocol','N/A — compiler/build protocol']
  line='|'+'|'.join(cells)+'| '+' | '.join(extra)+' |'
 lines.append(line)
assert index==91
s='\n'.join(lines)+'\n'
old='''The three example columns are reserved for a **PHP++ sample** and its actual
**S2S generated v1** (legacy `generators/php/`) and **S2S generated v2**
(`compiler/my-try/`) output. **Pending** means not yet collected; it is not an
unsupported-feature verdict. Longer samples/output can be linked from these cells.
No sample compilation or output generation was performed when adding these columns.'''
new='''The three example columns display **87 concrete PHP++ probes** and their actual
**S2S generated v1** (legacy `generators/php/`, strict profile) and **S2S generated v2**
(`compiler/my-try/`) output or diagnostics directly in the table. Complete emitted
bundles are shown using native Markdown code spans and minimal escaping. For preserved
line breaks, indentation, wider code columns and a sticky header, open the
[formatted HTML table](status_comparison_2026_10_08.html). References remain
next to the inline code for provenance and separate viewing. One artifact-layout row reuses the
multi-file probe; three compiler/build protocol rows are explicitly N/A.

**Emitted C++ is not a native compilation or execution pass.** Legacy is type-blind
and sometimes emits placeholders, missing composition, or unchecked calls. **⚠**
flags an observed limitation or a proof boundary needing special care. Generation
errors include the exact saved diagnostic; they may expose only the first blocker.
Samples cover representative subsets, not every feature grouped into a row. Existing
availability assessments retain their original broader evidence and are not silently
promoted by emission alone. The [sample index and collection details](status_samples_2026_10_08/README.md)
explain the results, source compatibility differences, and reproducible commands.
No native compiler build, emitted-program execution, STAN run or tests/php suite run
was performed. All source/output/log files are isolated in this chat's new evidence folder.'''
if old in s:
 s=s.replace(old,new)
else:
 start=s.index('The three example columns ')
 end=s.index('The **Required in v0.2 compiler**',start)
 s=s[:start]+new+'\n\n'+s[end:]
s=s.replace('No tests or compiler\nbuilds were run for either version during this review.', 'No test suites or native compiler\nbuilds were run. The later example collection invoked both S2S generators only.')
assert 'Pending | Pending' not in s
assert all(len(re.split(r'(?<!\\)\|',l))==9 for l in s.splitlines() if l.startswith('|'))
p.write_text(s)
summary=['# Status comparison samples — 2026-10-08','Doc Status: supporting','',
 '87 distinct source probes, 174 generator invocations in the final collection. Source snippets are strict PHP++ unless the row deliberately probes a deferred/rejected syntax. No generated program was compiled or executed. The artifact-layout row reuses the multi-file probe; the other three non-probe rows describe compiler/build protocols.','',
 '## Results','',
 '| Generator | Emitted text (exit 0) | Generation error |','| --- | --- | --- |']
for v in ('v1','v2'):summary.append(f"| {v} | {stats[v]['Emitted']} | {stats[v]['Generation error']} |")
summary+=['',
 'These are **generation outcomes, not test pass counts**. Exit zero does not prove usable C++. In particular, legacy xor and array-cast samples contain unsupported placeholders; trait use/adaptations are not established by the emitted class declarations; closure boxing emission is not a supported-runtime guarantee. Readonly field emission does not enforce readonly writes. Both versions emitting the mutation sample proves no iteration-safety guarantee.','',
 '## Method','',
 '- The same final source is supplied to both generators. The legacy adapter explicitly selects strict profile and writes only stdout/stderr. v2 uses the ordinary PHP-host S2S CLI, with main.phs explicitly selected as entry. No source compatibility rewrites are hidden between versions.',
 '- Multi-file probe 44 collects legacy header/source pairs separately without scpp project include/link assembly; v2 discovers the project files. It is an artifact example, not a full legacy project-build proof.',
 '- The shared 12-slot CommandRunner records every command, exit status and raw byte stream. CLI mode is explicit because these entrypoints use process exits. Its PASS progress messages mean evidence collection completed, including expected/unexpected generator rejections; they are not compiler success verdicts.',
 '- Preliminary samples were refined to remove unrelated ordering/cast/lookup blockers. Saved sources, results, SHA256 hashes and final logs describe the final collection. PHP implementation fingerprints found no edits during that collection.',
 '- The intended module configuration is recorded with samples; direct S2S bypasses project module discovery/STAN/build. A runtime API appearing in emitted text is not proof of registered native declarations or successful linking.',
 '- v2 raw stdout is an artifact JSON envelope. The readable v2.cpp.txt concatenates its exact artifact text, with added file-separator comments. Raw envelopes remain in logs/*.stdout. No generated C++ was hand-written or repaired.',
 '- Current v2 json_decode returns dynamic; json_decode_mixed is its mixed-family entry. Probe 36 deliberately uses the current dynamic spelling on both sides. A legacy emission is not proof of family-contract parity.',
 '- Probe 73 shows runtime error-to-failed-Result conversion, not a claimed structured-error constructor. Probes 25 and 42 stop at explicit shared type / weak assignment before later key/alias blockers. Probe 84 does not run STAN advisory checks.',
 '',
 '## Reproduce','',
 'From the repository root (these commands only regenerate this new evidence folder):','',
 '```sh','python3 compiler/my-try/docs/status_samples_2026_10_08/collect.py','```','',
 'Run render.py in the same folder after collection to refresh readable bundles and inline table code with references. The renderer populates the report from the saved final collection. Raw commands and diagnostics are available in [the command journal](logs/commands.json); [provenance](provenance.json) records implementation hashes and concurrent-change checks; [samples.json](samples.json) records scope per row. The PHP++ source hash for each result is in its result.json.','',
 '## Samples','', '| Row | Feature | Evidence |','| --- | --- | --- |']
for c in cases: summary.append('| '+c['id']+' | '+c['feature'].removeprefix('🔴 ')+' | [Read]('+c['id']+'/'+('README.md' if c['kind']=='probe' else 'scope.md')+') |')
(B/'README.md').write_text('\n'.join(summary)+'\n')
print({v:dict(x) for v,x in stats.items()})
