const fs = require('node:fs');
const assert = require('node:assert/strict');
const bytes = fs.readFileSync(0);
const decoder = new TextDecoder('utf-8', {fatal: true, ignoreBOM: true});
const text = decoder.decode(bytes);
const expected = JSON.parse(fs.readFileSync('expected.json', 'utf8'));
const [mode, iterations] = process.argv.slice(2);
const read = mode === 'bytes'
    ? () => JSON.parse(decoder.decode(bytes)).value
    : () => JSON.parse(text).value;
assert.deepEqual(read(), expected);
function measure(count) {
    let sum = 0;
    const start = process.hrtime.bigint();
    for (let i = 0; i < count; ++i) sum += read().length;
    const elapsed = process.hrtime.bigint() - start;
    assert.equal(sum, count * expected.length);
    return Number(elapsed) / count;
}
measure(Math.max(1, Number(iterations) >> 3));
console.log(measure(Number(iterations)));
