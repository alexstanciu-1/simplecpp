const fs = require("node:fs");
const [count, size, loops] = process.argv.slice(2).map(Number);
const wire = fs.readFileSync(0, "utf8");
const alphabet = "abcdefghijklmnopqrstuvwxyz";
const expected = alphabet.repeat(Math.ceil(size / alphabet.length)).slice(0, size);
const values = JSON.parse(wire).value;
if (values.length !== count || values.some(s => s !== expected)) throw Error("incorrect value");
function measure(k) {
    let total = 0;
    const start = process.hrtime.bigint();
    for (let i = 0; i < k; i++) total += JSON.parse(wire).value.length;
    const elapsed = Number(process.hrtime.bigint() - start);
    if (total !== count * k) throw Error("checksum mismatch");
    return elapsed / k;
}
measure(Math.max(1, Math.floor(loops / 8)));
console.log(measure(loops));
