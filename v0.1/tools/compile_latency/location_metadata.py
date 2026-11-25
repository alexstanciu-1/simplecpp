#!/usr/bin/env python3
"""Experimental separation of source-location data from compiled method bodies.

Call-depth diagnostics retain exact source lines, but an inserted source line no
longer changes every later method's C++ implementation. No runtime changes needed.
"""
import argparse
import json
import re
from pathlib import Path
from experiment import prepare, write_changed
from stable_locals import normalize_locals, validate_source_names


def prepare_locations(app, sources, stable_locals=True):
    if stable_locals:
        validate_source_names(app, sources)
    build = app / '.prism/build'
    generated = app / '.prism/generated'
    ids_file = build / 'latency-location-ids.json'
    retained = json.loads(ids_file.read_text()) if ids_file.exists() else {}
    metadata_objects = []
    sidecars = []
    descriptors = {}
    for source in sources:
        ids = retained.setdefault(source, {})
        symbol = 'lines_' + source.encode().hex()
        base = generated / '__latency_locations' / Path(source).with_suffix('')
        header = base.with_suffix('.hpp')
        write_changed(header, f'#pragma once\nnamespace scpp_latency_metadata {{ extern const int {symbol}[]; }}\n')
        descriptors[source] = {'ids': ids, 'values': {}, 'symbol': symbol, 'header': header, 'impl': base.with_suffix('.cpp')}

    def transform(source, contents):
        descriptor = descriptors[source]
        ids, values, symbol = descriptor['ids'], descriptor['values'], descriptor['symbol']
        def replace(match):
            key = match['name']
            if key not in ids:
                ids[key] = max(ids.values(), default=-1) + 1
            index, line = ids[key], int(match['line'])
            if index in values and values[index] != line:
                raise ValueError('Ambiguous diagnostic location identity')
            values[index] = line
            return match['prefix'] + f'::scpp_latency_metadata::{symbol}[{index}]' + ');'
        contents = re.sub(r'(?P<prefix>SCPP_CALL_DEPTH_GUARD\("(?P<name>[^"\n]+)", "[^"\n]+", )(?P<line>\d+)\);', replace, contents)
        if re.search(r'SCPP_CALL_DEPTH_GUARD\([^\n]+, \d+\);', contents):
            raise ValueError('Unconverted diagnostic location')
        return '#include "' + str(descriptor['header']) + '"\n' + contents

    ninja = prepare(app, sources, 180, transform=transform, publish_ninja=False, body_transform=normalize_locals if stable_locals else None)
    ninja += '''\nrule compile_latency_locations
  command = $cxx $cxxflags -MMD -MF $out.d -MT $out -c $in -o $out
  depfile = $out.d
  deps = gcc
  description = LOCATIONS $out
'''
    for source, descriptor in descriptors.items():
        ids, values, symbol = descriptor['ids'], descriptor['values'], descriptor['symbol']
        header, impl = descriptor['header'], descriptor['impl']
        if not values:
            raise ValueError('No diagnostic location witnesses in ' + source)
        initializer = ', '.join(str(values.get(i, 0)) for i in range(max(ids.values()) + 1))
        write_changed(impl, '#include "' + str(header) + '"\nnamespace scpp_latency_metadata {\nextern const int ' + symbol + '[] = {' + initializer + '};\n}\n')
        obj = '__latency_locations/' + str(Path(source).with_suffix('.o'))
        metadata_objects.append(obj)
        ninja += f'\nbuild {obj}: compile_latency_locations {impl}\n'
        sidecars.append({'source': source, 'object': obj, 'cpp': str(impl), 'header': str(header), 'entries': len(values)})
    ninja = ninja.replace('build main: link ', 'build main: link ' + ' '.join(metadata_objects) + ' ')
    write_changed(build / 'latency-locations.ninja', ninja)
    write_changed(ids_file, json.dumps(retained, indent=2) + '\n')
    write_changed(build / 'latency-location-manifest.json', json.dumps(sidecars, indent=2) + '\n')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--app', type=Path, required=True)
    args = parser.parse_args()
    app = args.app.resolve()
    corpus = json.loads((app.parent / 'corpus.json').read_text())
    prepare_locations(app, [r['source'] for r in corpus[:12]])
