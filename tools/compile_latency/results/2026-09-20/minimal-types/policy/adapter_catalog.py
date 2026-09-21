#!/usr/bin/env python3
"""Read-only audit of generated parameter-adapter ownership, not an emitter."""
import argparse
import json
import re
from pathlib import Path
from callable_surface import CLASS
from stable_locals import TOKENS

from adapter_surface import INLINE
DECL = re.compile(r'^\tstatic (.+?) (\w+)\(([^\n]*)\);$', re.M)


def audit(generated):
    accepted, rejected = [], []
    for path in sorted(generated.rglob('*.hpp')):
        rel = path.relative_to(generated)
        if any(part.startswith('__') for part in rel.parts):
            continue
        for cls in CLASS.finditer(path.read_text()):
            if 'template <' not in cls[2]:
                continue
            inline = {m[3]: m for m in INLINE.finditer(cls[2])}
            declarations = {m[2]: m[0] for m in DECL.finditer(cls[2]) if not m[2].startswith('__scpp_')}
            residual = INLINE.sub('', cls[2])
            residual = re.sub(r'^\tstatic .+;\n', '', residual, flags=re.M)
            residual = re.sub(r'^\tstatic const void\* __scpp_static_token\(\).*\n', '', residual, flags=re.M)
            if residual.strip() or len(inline) != cls[2].count('template <'):
                rejected.append({'owner': cls[1], 'header': str(rel), 'reason': 'Unparsed member or template shape'})
                continue
            members = set(inline) | set(declarations)
            wrappers, normalizers = {}, {}
            for name, match in inline.items():
                # The token stream skips quoted diagnostics and comments.
                tokens = list(TOKENS.finditer(match[0]))
                calls = sorted({token[0] for token in tokens
                                if token[0] in members and token[0] != name
                                and re.match(r'\s*\(', match[0][token.end():])})
                entry = {'template_parameters': match[1], 'return_type': match[2],
                         'parameters': match[4], 'same_owner_calls': calls}
                (normalizers if name.startswith('_norm_') else wrappers)[name] = entry
            for name, wrapper in wrappers.items():
                if name + '__exec' not in wrapper['same_owner_calls']:
                    raise ValueError('Wrapper does not forward to matching implementation: ' + cls[1] + '::' + name)
                if any(call not in normalizers and call != name + '__exec' for call in wrapper['same_owner_calls']):
                    raise ValueError('Unexpected adapter closure: ' + cls[1] + '::' + name)
            accepted.append({'owner': cls[1], 'header': str(rel), 'normalizers': normalizers,
                             'wrappers': wrappers, 'concrete_declaration_count': len(declarations)})
    return {'scope': 'Structural generated-adapter audit only; no transformed native correctness or timing proof',
            'accepted': accepted, 'rejected': rejected}


if __name__ == '__main__':
    p = argparse.ArgumentParser()
    p.add_argument('--generated', type=Path, required=True)
    p.add_argument('--out', type=Path, required=True)
    a = p.parse_args()
    result = audit(a.generated)
    a.out.parent.mkdir(parents=True, exist_ok=True)
    a.out.write_text(json.dumps(result, indent=2) + '\n')
    print(json.dumps({'owners': len(result['accepted']), 'rejected': len(result['rejected']),
                      'wrappers': sum(len(r['wrappers']) for r in result['accepted']),
                      'normalizers': sum(len(r['normalizers']) for r in result['accepted'])}))
