#!/usr/bin/env python3
"""Deterministically materialize three supported-language benchmark apps; no builds are generated here."""
import json
from pathlib import Path

ROOT = Path(__file__).resolve().parent
COMMON = '''namespace Bench {
global int seed = 9;
const int BONUS = 5;
const string EMPTY = "";
int adjust(int &value, int step = 2) {
    value = value + step;
    return value;
}
void announce(string label) { echo label; }
null pass_null(null value) { return value; }
byte pass_byte(byte value) { return value; }
float pass_float(float value) { return value; }
string pass_string(string value) { return value; }
bool pass_bool(bool value) { return value; }
int feature_score() {
    byte raw = 8;
    byte copied = pass_byte(raw);
    int number = copied;
    bool truth = number;
    int one = truth;
    float ratio = number;
    int rounded = ratio;
    null empty = pass_null(null);
    string text = pass_string("ok");
    string backup = text;
    text = "0";
    float zero = pass_float(-0.0);
    bool yes = pass_bool(true);
    auto inferred = 3;
    int score = 0;
    if (raw == copied) { score = score + 1; }
    if (raw != copied) { score = score + 100; }
    if (yes == true) { score = score + 1; }
    if (yes != false) { score = score + 1; }
    if (ratio == 8.0) { score = score + 1; }
    if (ratio != zero) { score = score + 1; }
    if (zero < ratio) { score = score + 1; }
    if (zero <= 0.0) { score = score + 1; }
    if (ratio > zero) { score = score + 1; }
    if (ratio >= 8.0) { score = score + 1; }
    if (text) { score = score + 100; }
    else if (backup) { score = score + 1; }
    else { score = score + 100; }
    if (number) { score = score + 1; }
    if (ratio) { score = score + 1; }
    if (yes) { score = score + 1; }
    if (one <= inferred) { score = score + 1; }
    if (rounded >= 8) { score = score + 1; }
    if (number != 7) { score = score + 1; }
    if (number > 1) { score = score + 1; }
    if (inferred < 8) { score = score + 1; }
    if (true) { int score = 99; score = 100; }
    int mutation = 0;
    ++mutation; mutation++; --mutation; mutation--;
    if (mutation == 0) { score += 1; }
    if (true || adjust(mutation)) { score += 1; }
    if (false && adjust(mutation)) { score += 100; }
    if (mutation == 0) { score += 1; }
    for (int i = 0; i < 4; i += 1) {
        if (i == 1) { continue; }
        if (i == 3) { break; }
        ++score;
    }
    score -= 1; score *= 2; score /= 2;
    int remainder = 9; remainder %= 4; score += remainder;
    score += (int) (float) true;
    return score;
}
}
'''
LABELS = '''namespace Bench {
const FLOOR: int = 1;
function label(value: string = "invoice"): string {
    let ticks: int = 0;
    for (let i: int = 0; i < 1; ++i) { ticks += 1; }
    if (!(ticks == (int) FLOOR)) { value = "bad-label"; }
    return value;
}
function finish(): void { echo "\\n"; return; }
}
'''

def unit(index, functions, flavor):
    var = lambda n: ('$' if flavor == 'php' else '') + n
    def decl(n,t,v):
        return {'php':f'${n} {t} = {v};', 'cpp':f'{t} {n} = {v};', 'js':f'let {n}: {t} = {v};'}[flavor]
    code=[]
    for j in range(functions):
        factor=index*functions+j+2
        name=f'line_{j}'
        params={'php':'int $price, int $quantity = 3','cpp':'int price, int quantity = 3','js':'price: int, quantity: int = 3'}[flavor]
        head=f'int {name}({params})' if flavor=='cpp' else f'function {name}({params}): int'
        code.append(head+' {\n    '+('\n    '.join([
            decl('subtotal','int',f'{var("price")} * {factor} + {var("quantity")}'),
            decl('index','int','0'),
            f'while ({var("index")} < {var("quantity")}) {{',
            f'    {var("subtotal")} = {var("subtotal")} + {var("index")};',
            f'    {var("index")} = {var("index")} + 1;', '}',
            f'if ({var("subtotal")} % 2 == 0) {{',
            f'    {var("subtotal")} = {var("subtotal")} / 2;',
            f'}} else if ({var("subtotal")} > 100) {{',
            f'    {var("subtotal")} = {var("subtotal")} - 7;',
            '} else {',f'    {var("subtotal")} = {var("subtotal")} + 5;', '}',
            f'do {{ {var("subtotal")} = {var("subtotal")} + 1; }} while (false);',
            decl('ticks','int','0'),
            f'for ({decl("loop","int","0")} {var("loop")} < {var("quantity")}; {var("loop")}++) {{',
            f'    if ({var("loop")} == 1) {{ continue; }}',
            f'    if ({var("loop")} > 2) {{ break; }}',
            f'    ++{var("ticks")};', '}',
            f'if (!({var("ticks")} == 2) || !({var("quantity")} > 0 && {var("price")} > 0)) {{ return 0; }}',
            f'{var("subtotal")} += (int) (byte) 2;',
            f'{var("subtotal")} -= 1;', f'{var("subtotal")} *= 2;',
            f'{var("subtotal")} /= 2;', f'{var("subtotal")} %= 1000000;', f'--{var("subtotal")};',
            f'for ({var("index")} = 0; {var("index")} < 1; {var("index")} += 1) {{ {var("subtotal")} += EXTRA; }}',
            f'return {var("subtotal")};',
        ]))+'\n}\n')
    namespace=f'Bench\\Batch{index}' if flavor=='php' else (f'Bench::Batch{index}' if flavor=='cpp' else f'Bench.Batch{index}')
    constant = 'const EXTRA: int = 1;' if flavor == 'js' else 'const int EXTRA = 1;'
    return f'namespace {namespace};\n'+constant+'\n'+''.join(code) if flavor=='php' else f'namespace {namespace} {{\n'+constant+'\n'+''.join(code)+'}\n'


def create(name, units, functions):
    root=ROOT/name; root.mkdir(exist_ok=True)
    files={'support/core.scpp':('cpp',COMMON), 'support/labels.jss':('js',LABELS)}
    main='''namespace App;
use function Bench\\{adjust, announce, feature_score};
use function Bench\\label as heading;
use const Bench\\{BONUS as extra, EMPTY};
$price int = \\Bench\\$seed;
adjust($price);
$total = feature_score();
$total += extra;
echo EMPTY;
announce(heading());
echo ": ";
'''
    expected=24+5
    for i in range(units):
        flavor=['php','cpp','js'][i%3]
        path=f'billing/batch_{i:03d}.'+{'php':'phs','cpp':'scpp','js':'jss'}[flavor]
        files[path]=(flavor,unit(i,functions,flavor))
        for j in range(functions):
            qty=3 if j%2==0 else 4
            args='$price' if qty==3 else '$price, 4'
            main+=f'$total = $total + \\Bench\\Batch{i}\\line_{j}({args});\n'
            subtotal=11*(i*functions+j+2)+qty+sum(range(qty))
            expected+=(subtotal//2 if subtotal%2==0 else (subtotal-7 if subtotal>100 else subtotal+5))+2
    main+='echo $total;\n\\Bench\\finish();\nreturn 8;\n'
    files['main.phs']=('php',main)
    for path,(_,code) in files.items():
        p=root/path;p.parent.mkdir(parents=True,exist_ok=True);p.write_text(code)
    (root/'module.json').write_text(json.dumps({'name':f'benchmark-{name}','entry':'main.phs','sources':{p:f for p,(f,_) in files.items()}},indent=2)+'\n')
    (root/'expected.json').write_text(json.dumps({'stdout':f'invoice: {expected}\n','exit':8,'edit_file':'support/labels.jss',
        'body_from':'return value;', 'body_to':'return "invoice-edited";',
        'signature_from':'function label(value: string = "invoice"): string',
        'signature_to':'function label(value: string = "invoice"): int',
        'signature_body':'return 42;',
        'constant_file':'support/core.scpp', 'constant_from':'const int BONUS = 5;',
        'constant_to':'const int BONUS = 50;'},indent=2)+'\n')
    print(name,len(files),sum(len(c.encode()) for _,c in files.values()),expected)

if __name__=='__main__':
    create('small',1,1)
    create('medium',12,4)
    create('large',64,8)
