"""Private generated-header controls for the tokenizer append experiment."""
import re
from shutil import copyfile


def scanner_control(src, label, folder, text, once):
    declaration = re.search(r'(scpp_vector_\w+) (var_items_\d+) = ', text)
    vector, items = declaration.groups()
    slot = label == 'slot'
    if slot or label.startswith('aggregate'):
        token = re.search(
            r'\[\[maybe_unused\]\] scpp_records::Token (\w+) = \{\};\s+'
            r'\(\1\)\.offset = ([^;]+);\s+\(\1\)\.length = ([^;]+);\s+'
            r'\(\1\)\.kind = ([^;]+);\s+\(' + items + r'\)\.append\(\1\);', text)
        _, offset, length, kind = token.groups()
        args = f'{offset}, {length}, {kind}'
        call = f'append_fields({args})' if slot else f'append({{{args}}})'
        text = once(text, token[0], f'({items}).{call};')
    if slot or label.endswith('fast'):
        # Both the driver and scanner must see identical collection definitions.
        signature = f'inline void {vector}::append(scpp_records::Token value) {{'
        for header in src.parent.glob('*.hpp'):
            target = folder/header.name
            content = header.read_text()
            if signature not in content:
                copyfile(header, target)
                continue
            content = once(content, 'void append(scpp_records::Token value);',
                           'void append(scpp_records::Token value);\nprivate:\n'
                           '    void append_slow(scpp_records::Token value);\npublic:')
            fast_path = """
    if (storage_ && storage_->count < storage_->capacity) {
        ::new (storage_->data + storage_->count * sizeof(value)) scpp_records::Token(static_cast<scpp_records::Token&&>(value));
        ++storage_->count;
        return;
    }
    append_slow(static_cast<scpp_records::Token&&>(value));
}
"""
            slow = '[[gnu::noinline]] ' + signature.replace('::append(', '::append_slow(')
            extra = ''
            if slot:
                fields = 'scpp::int64 offset, scpp::int64 length, scpp::int64 kind'
                content = once(content, 'void append(scpp_records::Token value);',
                               f'void append(scpp_records::Token value);\n    void append_fields({fields});')
                extra = f'inline void {vector}::append_fields({fields})' + """
{
    using token = scpp_records::Token;
    if (storage_ && storage_->count < storage_->capacity) {
        ::new (storage_->data + storage_->count * sizeof(token)) token{offset, length, kind};
        ++storage_->count;
        return;
    }
    append_slow({offset, length, kind});
}
"""
            target.write_text(once(content, signature, extra + signature + fast_path + slow))
        assert any('void append_slow' in p.read_text() for p in folder.glob('*.hpp'))
    return text
