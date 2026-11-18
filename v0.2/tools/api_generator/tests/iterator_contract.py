"""Iterator contract metadata drives generated names; invalid contracts fail before publication."""
import copy
import json
from pathlib import Path
import tempfile

from hash_providers import ROOT, run


def proof(work):
    metadata = json.loads((ROOT / 'runtime/metadata/runtime.json').read_text())
    publication = ROOT / 'runtime/metadata/publication.json'
    generator = ROOT / 'tools/api_generator/generate.php'
    layout = ROOT / 'build/clang/native-layout'

    def emit(data, accepted=True):
        path = work / 'metadata.json'
        path.write_text(json.dumps(data))
        output = work / 'compiler'
        run(['php', generator, 'emit-compiler', path, publication, layout / 'facts.json',
             layout / 'context.json', output], 0 if accepted else 1)
        return {str(p.relative_to(output)): p.read_bytes() for p in output.rglob('*.php')}

    baseline = emit(metadata)
    modified = copy.deepcopy(metadata)
    modified['compiler']['interfaces']['iterator'].update(name='PullValues', method='advance')
    after = emit(modified)
    assert [k for k in baseline if baseline[k] != after[k]] == ['types/interfaces.php']
    assert "'PullValues'" in after['types/interfaces.php'].decode()
    assert "'advance'" in after['types/interfaces.php'].decode()
    observed = run(['php', '-r', 'require $argv[1]; echo \\scpp\\compiler\\Iterator_Contract::NAME, ":", \\scpp\\compiler\\Iterator_Contract::METHOD;',
                    work / 'compiler/types/interfaces.php'])
    assert observed == 'PullValues:advance'
    for field, value in [('name', 'bad name'), ('result', 'int64'), ('type_parameters', 2), ('receiver', 'value')]:
        bad = copy.deepcopy(metadata)
        bad['compiler']['interfaces']['iterator'][field] = value
        emit(bad, False)
    print('Iterator metadata generation and rejection passed')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-iterator-metadata-') as directory:
        proof(Path(directory))
