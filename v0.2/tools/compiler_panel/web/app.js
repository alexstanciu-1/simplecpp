'use strict';
const $ = id => document.getElementById(id);
let token, report, busy = false;
function exampleData(name, flavor='php') {
  const extension={php:'phs',cpp:'cpp',js:'js'}[flavor];
  const variable=flavor==='php'?'$ret':'ret';
  const declaration=value=>({php:`$ret int = ${value};`,cpp:`int ret = ${value};`,js:`let ret: int = ${value};`}[flavor]);
  const definition=(name,body)=>flavor==='cpp'?`void ${name}() {${body}}`:`function ${name}(): void {${body}}`;
  const entry=`main.${extension}`;
  const file=(name,source)=>({name,flavor,source});
  if(name==='large') {
    let source='', index=1;
    while(source.length < 64*1024) {
      const variable=(flavor==='php'?'$':'')+`ret_${index}`;
      const value=index+7;
      const declaration={php:`${variable} int = ${value};`,cpp:`int ${variable} = ${value};`,js:`let ${variable}: int = ${value};`}[flavor];
      source+=`${declaration}\necho "Result: ";\necho ${variable};\necho "\\n";\n\n`;
      index++;
    }
    return {entry,files:[file(entry,source+'return 0;\n')]};
  }
  if(name==='multi')return {entry,files:[file(entry,'greet();\nreturn 8;'),file(`support.${extension}`,definition('greet','\n    echo "Hello from support\\n";\n    return;\n'))]};
  if(name==='duplicate')return {entry,files:[file(entry,[definition('ping',' echo "first"; '),definition('ping',' echo "second"; '),declaration(8),declaration(9),`return ${variable};`].join('\n'))]};
  return {entry,files:[file(entry,[declaration(8),'echo "Result: ";',`echo ${variable};`,'echo "\\n";',`return ${variable};`].join('\n'))]};
}
function node(tag, text, cls) {const el=document.createElement(tag);if(text!==undefined)el.textContent=text;if(cls)el.className=cls;return el;}
function addFile(file, entry=false) {
  const box=node('div',undefined,'file'), head=node('div',undefined,'file-head');
  const name=node('input');name.className='file-name';name.value=file.name;name.setAttribute('aria-label','File name');
  const flavor=node('select');flavor.setAttribute('aria-label','Source flavor');for(const f of ['php','cpp','js']){const o=node('option',f);o.value=f;flavor.append(o);}flavor.value=file.flavor;
  const label=node('label'), radio=node('input');radio.type='radio';radio.name='entry';radio.checked=entry;label.append(radio,document.createTextNode(' Entry'));
  const remove=node('button','×');remove.setAttribute('aria-label','Remove file');remove.onclick=()=>{box.remove();if(!$('files').querySelector(':checked')){$('files').querySelector('input[type=radio]')?.click();}};
  head.append(name,flavor,label,remove);const code=node('textarea');code.value=file.source;code.spellcheck=false;code.setAttribute('aria-label','Source code');box.append(head,code);$('files').append(box);
}
function load(data){$('optimization').value=data.optimization||'O2';$('pch').checked=data.pch===true;$('validate-pch').checked=data.validate_pch!==false;if(data.mode)$('mode').value=data.mode;$('files').replaceChildren();data.files.forEach(f=>addFile(f,f.name===data.entry));}
function inputs(){const files=[], boxes=$('files').children;let entry='';for(const box of boxes){const name=box.querySelector('.file-name').value;files.push({name,flavor:box.querySelector('select').value,source:box.querySelector('textarea').value});if(box.querySelector('input[type=radio]').checked)entry=name;}return {entry,files,mode:$('mode').value,optimization:$('optimization').value,pch:$('pch').checked,validate_pch:$('validate-pch').checked};}
function pre(parent,label,text){parent.append(node('h3',label),node('pre',text));}
function render(data){
  $('stages').replaceChildren();$('summary').replaceChildren();
  let total=0;for(const stage of data.stages||[])total+=stage.ms||0;
  for(const text of [`${(data.stages||[]).length} stages reported`,`${total.toFixed(2)} ms measured work`])$('summary').append(node('span',text,'metric'));
  for(const stage of data.stages||[]){
    const box=node('details',undefined,`stage ${stage.status}`);box.open=stage.status==='error'||stage.name==='Execution';
    const summary=node('summary');summary.append(node('span',stage.status.toUpperCase(),'badge'),document.createTextNode(stage.name+(stage.pch_cache?' · PCH '+stage.pch_cache.status:'')),node('span',`${(stage.ms||0).toFixed(3)} ms`,'time'));
    const content=node('div',undefined,'stage-content');
    if(stage.php_peak_bytes!==undefined)content.append(node('p',`PHP live-memory delta: ${stage.memory_delta_bytes.toLocaleString()} B · cumulative peak: ${stage.php_peak_bytes.toLocaleString()} B`,'muted'));
    if(stage.diagnostics)pre(content,'Diagnostics',stage.diagnostics.join('\n'));
    if(stage.command)pre(content,'Command (argument list)',JSON.stringify(stage.command,null,2));
    if(stage.pch_cache){content.append(node('p',`Runtime PCH: ${stage.pch_cache.status.toUpperCase()} · Ninja check/build/link ${stage.ninja_ms.toFixed(2)} ms · Clang validation ${stage.validate_pch?'on':'off'}`));pre(content,'PCH cache',JSON.stringify(stage.pch_cache,null,2));}
    if(stage.build_graph)pre(content,`Ninja graph · ${stage.jobs} jobs · ${stage.linker} · PCH ${stage.pch?'on':'off'}`,stage.build_graph);
    if(stage.executable_bytes!==undefined)content.append(node('p',`Executable: ${stage.executable_bytes.toLocaleString()} bytes (${(stage.executable_bytes/1024).toFixed(2)} KiB), unstripped`));
    if(stage.exit_code!==undefined){content.append(node('p',`Exit code: ${stage.exit_code}${stage.timed_out?' · TIME LIMIT EXCEEDED':''}`));pre(content,'stdout',stage.stdout||'(empty)');pre(content,'stderr',stage.stderr||'(empty)');if(stage.stderr_truncated)content.append(node('p','stderr display truncated.'));}
    if(stage.data?.files){for(const [path,text] of Object.entries(stage.data.files)){const d=node('details',undefined,'artifact');d.open=true;d.append(node('summary',path),node('pre',text));content.append(d);}}
    else if(stage.data!==undefined)pre(content,'Inspection',JSON.stringify(stage.data,null,2));
    box.append(summary,content);$('stages').append(box);
  }
  $('status').textContent=data.ok?'Pipeline completed. Positive execution exit codes are reported as source return values.':'Pipeline stopped. Earlier stage reports remain available; later stages were not run.';
  $('status').className=data.ok?'':'error';$('download').disabled=false;
}
async function run(){if(busy||!token)return;busy=true;$('run').disabled=true;$('status').textContent='Running compiler pipeline…';$('status').className='';try{const data=inputs();localStorage.setItem('scpp-panel-source',JSON.stringify(data));const response=await fetch('/run',{method:'POST',headers:{'Content-Type':'application/json','X-Panel-Token':token},body:JSON.stringify(data)});const result=await response.json();if(!response.ok)throw new Error(result.error||response.statusText);report=result;render(result);}catch(error){$('status').textContent=error.message;$('status').className='error';}finally{busy=false;$('run').disabled=false;}}
$('run').onclick=run;$('add').onclick=()=>addFile({name:`unit${$('files').children.length+1}.phs`,flavor:'php',source:'function helper(): void {\n    return;\n}'});
$('load').onclick=()=>load({...exampleData($('example').value,$('example-flavor').value),mode:$('mode').value,optimization:$('optimization').value,pch:$('pch').checked,validate_pch:$('validate-pch').checked});
$('download').onclick=()=>{const url=URL.createObjectURL(new Blob([JSON.stringify(report,null,2)],{type:'application/json'}));const a=node('a');a.href=url;a.download='compiler-report.json';a.click();setTimeout(()=>URL.revokeObjectURL(url),1000);};
document.addEventListener('keydown',event=>{if((event.ctrlKey||event.metaKey)&&event.key==='Enter'){event.preventDefault();run();}});
try{load(JSON.parse(localStorage.getItem('scpp-panel-source'))||exampleData('single'));}catch{load(exampleData('single'));}
fetch('/config').then(r=>r.json()).then(config=>{token=config.token;$('run').disabled=false;$('status').textContent='Ready. Choose an example or enter your source.';$('environment').textContent=`PHP: ${config.php} · Runtime: ${config.runtime}`;}).catch(error=>{$('status').textContent=error.message;});
