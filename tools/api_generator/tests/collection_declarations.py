"""Withdraw local inference and field admission independently for both collection providers."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

from collection_arguments import GENERATOR, METADATA, ROOT


def cases(family, element, local, field):
    type_name = f'vector<{element}>' if family == 'vector' else f'hash<{element},string>'
    declaration = f'$original {type_name}=[]; '
    record = 'struct Item {public int $n;} ' if element == 'Item' else ''
    for body, error in [
        (declaration + f'$copy {type_name}=$original;', None),
        (declaration + '$copy=$original;', None if local else 'Cannot infer a local type from null'),
        (declaration + '$copy=true ? $original : $original;', None if local else 'Ternary arm requires a supported value result'),
        (declaration + '$copy=match(1){default=>$original};', None if local else 'Match arm requires a supported value result'),
        (f'class Holder {{public {type_name} $items;}}', None if field else 'Unsupported instance field type'),
    ]:
        yield record + body + ' return 0;', error
    if element == 'int':
        # Static properties reach conversion only after admission. Globals reject before conversion.
        yield (f'class Holder {{public static {type_name} $items=1;}} return 0;',
               'Unsupported explicit conversion' if field else 'String globals are deferred')
        yield f'global $items {type_name}=1; return 0;', 'String globals are deferred'


def prove(work):
    generator = work / 'tools/api_generator'
    shutil.copytree(GENERATOR.parent, generator)
    (work / 'runtime').symlink_to(ROOT / 'runtime', target_is_directory=True)
    generated = work / 'generated'
    boot = work / 'boot.php'
    bootstrap = (ROOT / 'compiler/boot.php').read_text().replace("__DIR__ . '/generated/", repr(str(generated) + '/') + " . '")
    boot.write_text(bootstrap.replace('__DIR__', repr(str(ROOT / 'compiler'))))
    layout = ROOT / 'build/clang/native-layout'
    for local_family in ('vector', 'hash'):
        programs = []
        for family in ('vector', 'hash'):
            path = generator / f'templates/collections/{family}.concrete.json'
            recipe = json.loads(path.read_text())
            local = family == local_family
            recipe['declarations'] = {'inferred_local': local, 'instance_field': not local}
            # Absence and explicit false both withdraw a grant.
            if family == 'hash':
                del recipe['declarations']['instance_field' if local else 'inferred_local']
            path.write_text(json.dumps(recipe))
            for element in ('int', 'Item'):
                programs.extend(cases(family, element, local, not local))
        subprocess.run(['php', generator / GENERATOR.name, 'emit-compiler', METADATA,
                        ROOT / 'build/clang/runtime-publication.json', layout / 'facts.json',
                        layout / 'context.json', generated], check=True)
        check = r'''
use scpp\compiler\{Source_Frontend, diagnostic, diagnostic_group, entry_target, flavor, source_file};
require $argv[1];
foreach (json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR) as [$source, $expected]) {
    $message = null;
    try { (new Source_Frontend())->prepare(new source_file('test', $source, flavor::php), new entry_target(31)); }
    catch (diagnostic|diagnostic_group $error) { $message = $error->getMessage(); }
    if ($expected === null ? $message !== null : !str_contains($message ?? '', $expected)) {
        throw new RuntimeException($source . "\nExpected " . var_export($expected, true) . ", got " . var_export($message, true));
    }
}
'''
        subprocess.run(['php', '-r', check, boot], input=json.dumps(programs), text=True, check=True)


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='collection-declarations-') as directory:
        prove(Path(directory))
