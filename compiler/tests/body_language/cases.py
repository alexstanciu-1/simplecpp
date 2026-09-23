import json

def build():
    cases=[]
    def add(name,body,ret='int',values=2,statements=1,targets=('literal_int',),bytes_=(b'abc',),default=True,error=''):
        cases.append(dict(name=name,source=f'function f(): {ret} {{ {body} }} return 0;',values=values,statements=statements,calls=len(targets),targets=list(targets),bytes=[json.dumps({'hex':v.hex()},separators=(',',':')) for v in bytes_],default=default,error=error,fall=False))
    add('context_int','return "abc";')
    add('context_byte','return "abc";',ret='uint8',targets=('literal_byte',))
    add('context_without_default','return "abc";',default=False)
    add('empty_literal','return "";',bytes_=(b'',))
    add('binary_literal',r'return "A\0\xff\n";',bytes_=(b'A\x00\xff\n',))
    add('utf8_literal','return "é😀";',bytes_=('é😀'.encode(),))
    add('single_quote',r"return '\n';",bytes_=(b'\\n',))
    add('direct_span','return span_size("abc");',targets=('span_size',))
    add('span_no_default','return span_size("abc");',targets=('span_size',),default=False)
    add('default_expression','"abc"; return 0;',values=3,statements=2)
    add('echo_literal','echo "abc"; return 0;',values=3,statements=2,targets=('literal_int','print_int'))
    add('echo_integer','echo 42; return 0;',values=2,statements=2,targets=('print_int',),bytes_=())
    add('echo_context_byte','$x uint8 = "abc"; echo $x; return 0;',values=4,statements=3,targets=('literal_byte','print_byte'))
    add('echo_operands','echo "a", 7, "b"; return 0;',values=6,statements=4,targets=('literal_int','print_int','print_int','literal_int','print_int'),bytes_=(b'a',b'b'))
    add('nested_provider','echo span_size("abc"); return 0;',values=3,statements=2,targets=('span_size','print_int'))
    add('no_default','"abc"; return 0;',default=False,error='No byte-literal construction contract for this context')
    add('echo_no_default','echo "abc"; return 0;',default=False,error='No byte-literal construction contract for this context')
    add('no_context_binding','return "abc";',ret='int32',error='No byte-literal construction contract for this context')
    add('no_echo_binding','echo true; return 0;',error='No echo contract for this value type')
    add('unicode_escape',r'return "\u{41}";',error='Unicode escape syntax is not supported; use UTF-8 literal bytes')
    arguments = {'echo_literal':[1,2], 'echo_context_byte':[1,3], 'echo_operands':[1,2,3,4,5], 'nested_provider':[1,2]}
    for case in cases:
        case['arguments'] = arguments.get(case['name'], [1] * len(case['targets']))
    return cases
