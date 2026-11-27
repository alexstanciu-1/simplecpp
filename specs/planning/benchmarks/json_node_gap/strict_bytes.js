const fs = require('node:fs');
const assert = require('node:assert/strict');
const {isUtf8} = require('node:buffer');
const bytes = fs.readFileSync(0);
const expected = JSON.parse(fs.readFileSync('expected.json', 'utf8'));
const [mode, count] = process.argv.slice(2);
const decoder = new TextDecoder('utf-8', {fatal: true, ignoreBOM: true});
const convert = {
    fatal: b => decoder.decode(b),
    checked: b => {
        if (!isUtf8(b)) throw new Error('invalid UTF-8');
        return b.toString('utf8');
    },
    ordinary: b => b.toString('utf8'),
}[mode];
const read = b => JSON.parse(convert(b)).value;
if (mode !== 'ordinary') {
    for (const invalid of [[0xff], [0xe0, 0x80, 0x80], [0xf0, 0x9f, 0x98]]) {
        const doc = Buffer.concat([Buffer.from('{"value":["'), Buffer.from(invalid), Buffer.from('"]}')]);
        assert.throws(() => read(doc));
    }
}
assert.deepEqual(read(bytes), expected);
function measure(n) {
    let sum = 0;
    const start = process.hrtime.bigint();
    for (let i = 0; i < n; ++i) sum += read(bytes).length;
    const elapsed = process.hrtime.bigint() - start;
    assert.equal(sum, n * expected.length);
    return Number(elapsed) / n;
}
measure(Math.max(1, count >> 3));
console.log(measure(Number(count)));
