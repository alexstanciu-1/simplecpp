"""Can a web application's value travel through each common source storage position?"""
from dataclasses import dataclass
from pathlib import Path
import tempfile

from snippets import Probe, exercise


@dataclass(frozen=True)
class Value:
    name: str
    type: str
    make: str
    observe: str
    output: str
    declarations: str = ''


values = [
    Value('int', 'int', '7', 'echo $got;', '7'),
    Value('string', 'string', '"item"', 'echo $got;', 'item'),
    Value('nullable-int', '?int', 'null', 'echo isset($got)?"yes":"absent";', 'absent'),
    Value('nullable-string', '?string', '""', 'echo isset($got)?"present":"missing";', 'present'),
    Value('falseable-int', 'result_or_false<int>', 'strpos("a","z")', 'echo isset($got)?"hit":"miss";', 'miss'),
    Value('result-string', 'result<string>', 'json_encode(false)', '$s string=$got; echo $s;', 'false'),
    Value('mixed', 'mixed', '["qty"=>7]', '$n int=$got["qty"]; echo $n;', '7'),
    Value('dynamic', 'dynamic', '["qty"=>7]', '$n int=$got["qty"]; echo $n;', '7'),
    Value('vector', 'vector<string>', '["item"]', 'echo $got[0];', 'item'),
    Value('hash', 'hash<int,string>', '["qty"=>7]', 'echo $got["qty"];', '7'),
    Value('enum', 'State', 'State::Ready', 'echo enum_value($got);', 'ready', 'enum State:string {case Ready="ready";}'),
    Value('class', 'Ticket', 'new Ticket()', 'echo $got->id;', '7', 'class Ticket {public int $id=7;}'),
    Value('nullable-class', '?Ticket', 'null', 'echo ($got === null)?"absent":"live";', 'absent',
          'class Ticket {public int $id=7;}'),
]

# DECL declarations are outside entry, RUN statements after a seeded named local.
positions = {
    'local': ('', '$got TYPE = $seed;'),
    'argument/return': ('function round_trip(TYPE $value):TYPE{return $value;}', '$got TYPE = round_trip($seed);'),
    'reference': ('function borrow(TYPE &$value):TYPE{return $value;}', '$got TYPE = borrow($seed);'),
    'field': ('class Holder {public TYPE $item; public function __construct(TYPE $v){$this->item=$v;}}',
              '$h=new Holder($seed); $got TYPE = $h->item;'),
    'vector-element': ('', '$v vector<TYPE> = []; $v[]=$seed; $got TYPE = $v[0];'),
    'hash-value': ('', '$h hash<TYPE,string> = []; $h["x"]=$seed; $got TYPE = $h["x"];'),
    'closure': ('', '$read=fn():TYPE => $seed; $got TYPE = $read();'),
    'into-mixed': ('', '$box mixed=$seed; $s string=json_encode($box); echo $s;'),
    'into-dynamic': ('', '$box dynamic=$seed; $s string=json_encode($box); echo $s;'),
}


def main():
    for value in values:
        for position, (declarations, statements) in positions.items():
            code = '\n'.join([value.declarations, declarations, f'$seed TYPE = {value.make};', statements,
                              '' if position.startswith('into-') else value.observe, 'return 0;'])
            code = code.replace('TYPE', value.type)
            with tempfile.TemporaryDirectory(prefix='php-position-') as directory:
                stage, result = exercise(Probe(value.name, code, modules=('json',)), Path(directory))
            if stage == 'output' and not position.startswith('into-') and result != value.output:
                stage = 'unexpected value'
            print(f'{value.name}\t{position}\t{stage}\t{result}', flush=True)


if __name__ == '__main__':
    main()
