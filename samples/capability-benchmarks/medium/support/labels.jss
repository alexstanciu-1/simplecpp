namespace Bench {
const FLOOR: int = 1;
function label(value: string = "invoice"): string {
    let ticks: int = 0;
    for (let i: int = 0; i < 1; ++i) { ticks += 1; }
    if (!(ticks == (int) FLOOR)) { value = "bad-label"; }
    return value;
}
function finish(): void { echo "\n"; return; }
}
