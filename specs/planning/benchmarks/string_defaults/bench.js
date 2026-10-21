'use strict';
const fs = require('fs');
const flatten = process.argv[3] === 'flat';
const backend = flatten ? 'js_flat' : 'js';
let sink;
// Node/V8-specific black box: keep invariant string work inside the timed loop.
// Launch with --allow-natives-syntax. The length control includes this call cost.
function opaque(value) { return value; }
%NeverOptimizeFunction(opaque);
function batch(op, s, t, needle, repl, count) {
    let sum=0;
    const inputs={s,t,needle,repl};
    const start = process.hrtime.bigint();
    if (op === 'replace') {
        for (let i=0;i<count;i++) {
            const x=opaque(inputs);
            const out=x.s.replaceAll(x.needle,x.repl);
            // Diagnostic variant: materialize UTF-8 bytes, including conversion/allocation.
            if (flatten) { const buf=Buffer.from(out,'utf8'); sum+=buf.length; sink=buf; }
            else { sum+=out.length; sink=out; }
        }
    } else if (op === 'compare') {
        for (let i=0;i<count;i++) {
            const x=opaque(inputs);
            sum += 1 + (x.s>x.t) - (x.s<x.t);
        }
    } else {
        for (let i=0;i<count;i++) sum+=opaque(inputs).s.length;
    }
    return [Number(process.hrtime.bigint()-start),sum];
}
for (const line of fs.readFileSync(process.argv[2],'utf8').trimEnd().split('\n')) {
    const [id,op,s,t,needle,repl,expected]=line.split('\t');
    if (op==='replace' && s.replaceAll(needle,repl)!==expected) throw Error('replacement mismatch');
    if (op==='compare' && (s>t)-(s<t)!==Number(expected)) throw Error('comparison mismatch');
    let count=1;
    while(batch(op,s,t,needle,repl,count)[0]<20000000 && count<2**26) count*=2;
    for(let trial=0;trial<7;trial++) {
        const [ns,sum]=batch(op,s,t,needle,repl,count);
        console.log([id,backend,trial,count,ns,sum].join(','));
    }
}
