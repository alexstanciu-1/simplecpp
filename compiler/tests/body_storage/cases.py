def build():
    import re
    cases=[]
    def add(name,body,params='Storage<int32> &$s',ret='int32',extra='',statements=1,values=2,calls=0,error=''):
        owner_types = re.findall(r'(Storage<[^>]+>) &\$(\w+)', params)
        local_owners = [(typ,name_) for typ,name_ in owner_types if f'const {typ} &${name_}' not in params]
        for typ,name_ in local_owners:
            params = params.replace(f'{typ} &${name_}', '')
            body = f'${name_} {typ}; ' + body
            statements += 1; values += 1
        params = ','.join(part for part in params.split(',') if part)
        cases.append(dict(name=name,source=f'function f({params}): {ret} {{ {body} }} '+extra+' return 0;',error=error,statements=statements,values=values,calls=calls,fall=False))
    add('read','return $s[0];')
    add('const_read','return $s[0];',params='const Storage<int32> &$s')
    add('write','$s[0] = $x; return $s[1];',params='Storage<int32> &$s,$x int32',statements=2,values=4)
    add('nested_index','return $s[$s[0]];',values=3)
    add('index_call','return $s[index()];',extra='function index(): int { return 0; }',calls=1)
    add('index_before_rhs','$s[index()] = value(); return $s[0];',params='Storage<int> &$s',ret='int',extra='function index(): int { return 0; } function value(): int { return 0; }',statements=2,values=4,calls=2)
    add('record_field','return $s[0]->value;',params='Storage<Point> &$s')
    add('record_field_write','$s[0]->value = $x; return $s[1]->value;',params='Storage<Point> &$s,$x int32',statements=2,values=4)
    add('record_borrow','return read($s[0]);',params='Storage<Point> &$s',extra='function read(const Point &$p): int32 { return $p->value; }',values=3,calls=1)
    add('record_mutable_borrow','return change($s[0]);',params='Storage<Point> &$s',extra='function change(Point &$p): int32 { return $p->value; }',values=3,calls=1)
    add('count','return storage_3<int32>($s);',ret='int',values=2,calls=1)
    add('const_count','return storage_3<int32>($s);',params='const Storage<int32> &$s',ret='int',values=2,calls=1)
    add('push','storage_1<int32>($s,$x); return $s[0];',params='Storage<int32> &$s,$x int32',statements=2,values=4,calls=1)
    add('record_push','storage_1<Point>($s,$p); return $s[0]->value;',params='Storage<Point> &$s,const Point &$p',statements=2,values=4,calls=1)
    add('allocate_release','$s Storage<int32>; storage_0<int32>($s,4); storage_4<int32>($s); return 0;',params='',ret='int',statements=4,values=5,calls=2)
    add('transfer','storage_5<int32>($a,$b); return 0;',params='Storage<int32> &$a,Storage<int32> &$b',ret='int',statements=2,values=3,calls=1)
    add('pop','storage_2<int32>($s); return 0;',ret='int',statements=2,values=2,calls=1)
    add('const_write','$s[0] = $x; return $x;',params='const Storage<int32> &$s,$x int32',error='Cannot write through a const reference parameter')
    add('const_field_write','$s[0]->value = $x; return $x;',params='const Storage<Point> &$s,$x int32',error='Cannot write through a const reference parameter')
    add('const_mutable_borrow','return change($s[0]);',params='const Storage<Point> &$s',extra='function change(Point &$p): int32 { return $p->value; }',error='A const reference cannot be passed as a mutable reference')
    add('const_push','storage_1<int32>($s,$x); return $x;',params='const Storage<int32> &$s,$x int32',error='A const reference cannot be passed as a mutable reference')
    add('void_index','return $s[index()];',extra='function index(): void {}',error='Array index must be an integer')
    add('owner_copy','$copy Storage<int32> = $s; return 0;',ret='int',error='Unsupported inline object copy; copy construction is unavailable')
    return cases
