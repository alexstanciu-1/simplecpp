const fs = require("node:fs");
const [n, size, loops] = process.argv.slice(2).map(Number);
const wire = fs.readFileSync(0, "utf8");
const half = Math.floor(size / 2);
const expected = "a".repeat(half) + "b" + "z".repeat(size - half - 1);
const values = JSON.parse(wire).value;
if (values.length !== n || values.some(value => value !== expected)) throw Error("fixture mismatch");
function run(k) {
    let total = 0;
    const start = process.hrtime.bigint();
    for (let i = 0; i < k; ++i) total += JSON.parse(wire).value.length;
    const elapsed = Number(process.hrtime.bigint() - start);
    if (total !== k * n) throw Error("checksum mismatch");
    return elapsed / k;
}
run(Math.max(1, Math.floor(loops / 8)));
console.log(run(loops));
