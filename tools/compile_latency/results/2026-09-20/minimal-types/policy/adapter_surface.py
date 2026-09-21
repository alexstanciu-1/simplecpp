"""Bounded extraction of the current generated parameter-normalization adapters.

Preserves template bodies and reference semantics. This is not general C++ parsing.
"""
import re
from stable_locals import TOKENS

INLINE = re.compile(r'^\ttemplate <([^\n]+)>\n\tstatic (.+?) (\w+)\(([^\n]*)\) \{\n[\s\S]*?^\t}\n', re.M)


def extract(body):
    matches = list(INLINE.finditer(body))
    if len(matches) != body.count('template <'):
        raise ValueError('Unparsed adapter template')
    inline = {m[3]: m for m in matches}
    if len(inline) != len(matches):
        raise ValueError('Overloaded adapter identity')
    return INLINE.sub('', body), inline


def lower(cls, match, symbols):
    if re.search(r'\b' + re.escape(cls) + r'::', match[0]):
        raise ValueError('Qualified owner reference outside bounded adapter form')
    text = match[0].replace('\tstatic ', '\t', 1)
    dependencies = set()
    def rewrite(token):
        key = cls + '::' + token[0]
        prefix = text[:token.start()].rstrip()
        qualified = prefix.endswith(('.', '->', '::'))
        if key in symbols and not qualified and re.match(r'\s*\(', text[token.end():]):
            dependencies.add(key)
            return symbols[key]
        return token[0]
    result = TOKENS.sub(rewrite, text)
    dependencies.discard(cls + '::' + match[3])
    return result, dependencies
