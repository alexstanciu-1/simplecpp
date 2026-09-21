"""Bounded alpha-renaming proof for three current generator-owned local families.

A future emitter should allocate these names in the resolved callable's context.
This experiment verifies absence of source-name collisions and never edits quoted
strings/comments. It is not a general C++ identifier rewriter.
"""
import re

GENERATED = re.compile(r'__(?:append_value|scpp_foreach_(?:entry|source))_\d+(?:_\d+)?\Z')
TOKENS = re.compile(r'//[^\n]*|/\*[\s\S]*?\*/|"(?:\\[\s\S]|[^"\\])*"|\'(?:\\[\s\S]|[^\'\\])*\'|[A-Za-z_]\w*')


def validate_source_names(app, sources):
    for source in sources:
        text = (app / source).read_text()
        # Conservative rejection even if such text appears only in a source comment.
        if re.search(r'__(?:append_value|scpp_foreach_(?:entry|source))_\d|__latency_local_', text):
            raise ValueError('Source may collide with reserved experimental locals: ' + source)


def normalize_locals(source, identity, body):
    names = {}
    if '__latency_local_' in body:
        raise ValueError('Generated local prefix is already occupied')
    def rewrite(match):
        token = match[0]
        if token in {'R', 'u8R', 'uR', 'UR', 'LR'} and body[match.end():match.end()+1] == '"':
            raise ValueError('Raw C++ strings are outside this generated-code probe')
        if GENERATED.fullmatch(token):
            if token not in names:
                names[token] = '__latency_local_' + str(len(names))
            return names[token]
        return token
    return TOKENS.sub(rewrite, body)
