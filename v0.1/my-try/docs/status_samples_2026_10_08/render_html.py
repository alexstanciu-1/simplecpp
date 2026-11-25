"""Standalone formatted table, using captured sources/output without regeneration."""
from pathlib import Path
import html,json,re
B=Path(__file__).resolve().parent
D=B.parent
esc=lambda text:html.escape(text,quote=False)
def markdown(text):
    pattern=r'\[([^\]]+)\]\(([^)]+)\)|`([^`]+)`|\*\*([^*]+)\*\*'
    out=[];start=0
    for m in re.finditer(pattern,text):
        out.append(esc(text[start:m.start()]))
        if m[1] is not None:out.append('<a href="'+html.escape(m[2],quote=True)+'">'+esc(m[1])+'</a>')
        elif m[3] is not None:out.append('<code>'+esc(m[3])+'</code>')
        else:out.append('<strong>'+esc(m[4])+'</strong>')
        start=m.end()
    out.append(esc(text[start:]))
    return ''.join(out)
def block(text):return '<pre><code>'+esc(text.rstrip().expandtabs(4))+'</code></pre>'
def examples(identifier):
    folder=B/identifier
    prefix='status_samples_2026_10_08/'+identifier+'/'
    source='<div class="file">main.phs</div>'+block((folder/'main.phs').read_text())
    if (folder/'helper.phs').exists():source+='<div class="file">helper.phs</div>'+block((folder/'helper.phs').read_text())
    out=[source+'<a class="reference" href="'+prefix+'README.md#php-sample">Reference ↗</a>']
    results=json.loads((folder/'result.json').read_text())
    for v in ('v1','v2'):
        ok=results[v]['exit_code']==0 and not results[v].get('error')
        cell='<div class="badge '+('emitted' if ok else 'error')+'">'+('Emitted · not compiled' if ok else 'Generation error')+'</div>'
        # Keep the specific caveats already recorded in the sample page.
        page=(folder/'README.md').read_text().split('## S2S generated '+v,1)[1].split('\n## ',1)[0]
        warning=re.search(r'^\*\*(.+)\*\*$',page,re.M)
        if warning:cell+='<p class="warning">⚠ '+esc(warning[1])+'</p>'
        text=(folder/f'{v}.cpp.txt').read_text()
        diagnostic=(folder/f'{v}.diagnostic.txt').read_text()
        if text.strip():cell+=block(text)
        if diagnostic.strip():cell+=block(diagnostic)
        if not text.strip():cell+='<p>No C++ emitted.</p>'
        cell+='<a class="reference" href="'+prefix+'README.md#s2s-generated-'+v+'">Reference ↗</a>'
        out.append(cell)
    return out
cases=json.loads((B/'samples.json').read_text())['cases']
rows=[];index=0
for line in (D/'status_comparison_2026_10_08.md').read_text().splitlines():
    if not line.startswith('| ') or line.startswith(('| Feature','| ---')):continue
    cells=re.split(r'(?<!\\)\|',line)[1:5]
    if line.startswith('| **CH'):
        rows.append('<tr class="chapter"><th colspan="7">'+markdown(cells[0].strip())+'</th></tr>');continue
    case=cases[index];index+=1
    if case['kind']=='probe':extra=examples(case['id'])
    elif case['id']=='75':extra=examples('44')
    else:
        detail=esc(case['scope'])
        if case['id']=='85':detail+=block('{"runtime":{"languages":{"php":{"profile":"strict"}}}}\n\nscpp build --no-stan\nscpp run --no-stan')+'<p>Workflow illustration; not executed.</p>'
        detail+='<a class="reference" href="status_samples_2026_10_08/'+case['id']+'/scope.md">Reference ↗</a>'
        extra=[detail,'N/A — compiler/build protocol','N/A — compiler/build protocol']
    gap=' gap' if '🔴' in cells[0] else ''
    rows.append('<tr class="feature'+gap+'" id="row-'+case['id']+'">'+''.join('<td>'+markdown(c.strip())+'</td>' for c in cells)+''.join('<td class="code-cell">'+c+'</td>' for c in extra)+'</tr>')
assert index==91
page='''<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Compiler status comparison — 8 October 2026</title>
<style>
:root { color-scheme: light dark; --bg:#fff; --ink:#18232e; --muted:#596b7c; --line:#d9e1e8; --panel:#f5f7fa; --accent:#1769aa; }
@media(prefers-color-scheme:dark){:root{--bg:#141a21;--ink:#e2e8ee;--muted:#a9b9c9;--line:#35414d;--panel:#1d2630;--accent:#8fc5ff}}
*{box-sizing:border-box} body{margin:0;background:var(--bg);color:var(--ink);font:14px/1.5 system-ui,sans-serif}
header{padding:22px 26px 16px} h1{font-size:24px;margin:0 0 8px} p{margin:8px 0} .sub{color:var(--muted);max-width:1100px}
a{color:var(--accent);text-underline-offset:3px} .controls{display:flex;align-items:center;gap:18px;flex-wrap:wrap;margin-top:16px}
input[type=search]{padding:10px 12px;width:380px;max-width:90vw;background:var(--bg);color:var(--ink);border:1px solid var(--line);border-radius:6px}
.table-wrap{overflow:auto;height:calc(100vh - 205px);min-height:400px;border-top:1px solid var(--line)}
table{border-collapse:separate;border-spacing:0;width:2600px;table-layout:fixed} col.feature{width:240px} col.status{width:225px} col.sample{width:390px} col.output{width:650px}
th,td{text-align:left;vertical-align:top;padding:14px;border-right:1px solid var(--line);border-bottom:1px solid var(--line);overflow-wrap:anywhere}
thead th{position:sticky;top:0;z-index:3;background:var(--panel);font-size:13px;border-bottom:2px solid var(--line)}
.chapter th{background:var(--panel);font-size:15px;padding:12px 14px;color:var(--accent)}
.feature td:first-child{font-weight:600}.gap td:first-child{border-left:4px solid #d44b4b;color:#d44b4b}
pre{margin:10px 0;padding:14px;background:var(--panel);border:1px solid var(--line);border-radius:6px;max-height:580px;overflow:auto;tab-size:4;white-space:pre;overflow-wrap:normal}
code{font:12px/1.65 ui-monospace,SFMono-Regular,Consolas,monospace} .file{font:600 12px/1.5 ui-monospace,monospace;color:var(--muted)}
.badge{display:inline-block;border:1px solid var(--line);border-radius:4px;padding:3px 8px;font-size:12px;font-weight:600}.error{color:#d44b4b}.warning{color:var(--muted);font-size:12px}.reference{display:inline-block;font-size:12px;margin-top:6px}
body.wrap pre{white-space:pre-wrap;overflow-wrap:anywhere}body.expand pre{max-height:none}
@media print{.controls{display:none}.table-wrap{height:auto;overflow:visible}pre{max-height:none}table{width:100%}code{font-size:8px}th,td{padding:5px}}
</style></head><body>
<header><h1>Compiler status comparison</h1>
<p class="sub">8 October 2026 · 91 features · PHP++ and actual generated output shown directly in the table. Red marks legacy support gaps. Emission is not a native compilation or execution pass.</p>
<p class="sub">Full code is preserved, including file separators. Scroll inside long code blocks or select “Expand code”. <a href="status_comparison_2026_10_08.md">Markdown report and evidence notes</a></p>
<div class="controls"><input id="search" type="search" placeholder="Filter features, e.g. Storage or closures" aria-label="Filter features"><label><input id="wrap" type="checkbox"> Wrap code</label><label><input id="expand" type="checkbox"> Expand code</label><span id="count">91 features</span></div></header>
<div class="table-wrap"><table><colgroup><col class="feature"><col class="status"><col class="status"><col class="status"><col class="sample"><col class="output"><col class="output"></colgroup><thead><tr>
'''+''.join('<th scope="col">'+h+'</th>' for h in ['Feature','Available in v0.2-my-try','Available in legacy S2S','Required in v0.2 compiler','PHP++ sample','S2S generated v1','S2S generated v2'])+'''</tr></thead><tbody>
'''+ '\n'.join(rows)+'''</tbody></table></div>
<script>
const rows=[...document.querySelectorAll('tr.feature')];
document.querySelector('#search').addEventListener('input',e=>{const q=e.target.value.toLowerCase();let n=0;for(const row of rows){const shown=[...row.cells].slice(0,4).some(c=>c.textContent.toLowerCase().includes(q));row.hidden=!shown;if(shown)n++}document.querySelector('#count').textContent=n+' features';document.querySelectorAll('tr.chapter').forEach(c=>{let next=c.nextElementSibling,visible=false;while(next&&!next.classList.contains('chapter')){if(!next.hidden)visible=true;next=next.nextElementSibling}c.hidden=!visible})});
for(const id of ['wrap','expand'])document.getElementById(id).addEventListener('change',e=>document.body.classList.toggle(id,e.target.checked));
</script></body></html>
'''
# Include the report's evidence notes as ordinary HTML below the table.
report=(D/'status_comparison_2026_10_08.md').read_text()
notes=report[report.index('Doc Status: supporting'):]
paragraphs=[]
for chunk in re.split(r'\n\s*\n',notes):
    if chunk.startswith('- '):
        items=re.split(r'\n(?=- )',chunk)
        paragraphs.append('<ul>'+''.join('<li>'+markdown(x.removeprefix('- ').replace('\n',' '))+'</li>' for x in items)+'</ul>')
    else:paragraphs.append('<p>'+markdown(chunk.replace('\n',' '))+'</p>')
page=page.replace('</body>', '<section style="padding:24px;max-width:1200px"><h2>Scope and evidence notes</h2>'+''.join(paragraphs)+'</section></body>')
(D/'status_comparison_2026_10_08.html').write_text(page)
print('Rendered 91 rows with multiline code and minimal HTML escaping.')
