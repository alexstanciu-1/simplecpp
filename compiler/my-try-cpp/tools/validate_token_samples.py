"""Compare every C++ token span against the old authored tokenizer on all three samples."""
import hashlib
import json
from pathlib import Path
import re
import sys
import tempfile

PROJECT = Path(__file__).resolve().parents[1]
ROOT = PROJECT.parents[1]
sys.path.insert(0, str(ROOT / 'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks

SAMPLES = {'accounting-small': 'accounting-small', 'accounting-large': 'accounting-large', 'my-try': 'my-try/phpp'}
SINGLES = dict(zip(';(){}?:,&[]<>+-*/%.!~|^\\=',
                  'semicolon lparen rparen lbrace rbrace question colon comma ampersand lbracket rbracket less greater plus minus star slash percent dot bang tilde pipe caret backslash assign'.split()))
MULTI = dict(zip(['#[','...','**=','<<=','>>=','===','!==','<=>','==','!=','<=','>=','=>','->','::','??','&&','||','++','--','**','+=','-=','*=','/=','%=','.=','&=','|=','^='],
                 'attribute ellipsis power_assign left_assign right_assign identical not_identical spaceship equal not_equal less_equal greater_equal arrow member scope coalesce logical_and logical_or increment decrement power plus_assign minus_assign star_assign slash_assign percent_assign concat_assign and_assign or_assign xor_assign'.split()))
WORDS = set('abstract and as async await break case catch class clean const continue data default do echo else elseif empty enum extends false final finally fn for foreach function if implements instanceof interface isset lock match move namespace new null object_cast or private protected public readonly return set static struct switch take template throw trait true try typename union unset use void weak while xor'.split())
TAGS = {name: int(value) for name, value in re.findall(r'(\w+) = (\d+)',
        (PROJECT / 'middleware/include/scpp_compiler/token_kind.hpp').read_text())}


def parse(data):
    files = {}
    current = None
    for line in data.decode().splitlines():
        if line.startswith('FILE '):
            current = line[5:]
            files[current] = []
        elif line:
            files[current].append(tuple(map(int, line.split())))
    return files


def expected_tag(text):
    spelling = text.decode('ascii', errors='replace')
    if text.startswith(b'$'):
        name = 'variable'
    elif text.startswith(b"'"):
        name = 'single_string'
    elif text.startswith(b'"'):
        name = 'double_string'
    elif re.fullmatch(rb'(?:[0-9]+(?:\.[0-9]*)?|\.[0-9]+)(?:[eE][+-]?[0-9]+)?', text):
        name = 'floating' if any(x in text for x in (b'.', b'e', b'E')) else 'integer'
    elif spelling in MULTI:
        name = MULTI[spelling]
    elif spelling in SINGLES:
        name = SINGLES[spelling]
    elif spelling in WORDS:
        name = 'kw_' + spelling
    else:
        assert re.fullmatch(rb'[A-Za-z_][A-Za-z_0-9]*', text), repr(text)
        name = 'identifier'
    return TAGS[name]


def main():
    summary = {}
    with tempfile.TemporaryDirectory(prefix='token-oracle-') as temporary:
        with CommandRunner(Path(temporary) / 'logs', ROOT) as runner:
            def validate(name, relative):
                source = PROJECT / 'samples' / relative
                expected = parse(runner.run(name + '-old', ['php', PROJECT / 'tests/token_oracle.php', source]))
                dump = runner.run(name + '-cpp', [PROJECT / 'build/compiler/tokenization-profile', source, '16', 'dump'])
                actual = parse(dump)
                assert actual.keys() == expected.keys(), name
                total = 0
                digest = hashlib.sha256()
                for file, tokens in actual.items():
                    content = (source / file).read_bytes()
                    digest.update(file.encode() + b'\0' + content)
                    assert [(a, b) for a, b, _ in tokens] == expected[file], (name, file)
                    for offset, length, tag in tokens:
                        assert 0 < length <= 0xffffff and 0 < tag <= 255
                        assert tag == expected_tag(content[offset:offset + length]), (name, file, offset, tag)
                    total += len(tokens)
                summary[name] = {'files': len(actual), 'tokens': total, 'source_sha256': digest.hexdigest(), 'dump_sha256': hashlib.sha256(dump).hexdigest(),
                                 'all_spans_match_old': True, 'all_tags_checked': True}
            run_tasks([Task(name, lambda n=name, p=path: validate(n, p)) for name, path in SAMPLES.items()], jobs=3)
    (PROJECT / 'samples/token-validation.json').write_text(json.dumps(summary, indent=2) + '\n')
    print(json.dumps(summary, indent=2))


if __name__ == '__main__':
    main()
