def build():
    cases=[]
    def add(name,body,params='',ret='int32',extra='',statements=1,values=2,calls=1,receiver=0,error=''):
        cases.append(dict(name=name,source=f'function f({params}): {ret} {{ {body} }} '+extra+' return 0;',error=error,statements=statements,values=values,calls=calls,fall=False,receiver=receiver,receiver_local=3 if name=='receiver_plus_arguments' else 1))
    add('mutable_receiver','$p Point; return $p->get();',statements=2,values=3,receiver=2)
    add('const_receiver','$p Point; return $p->read();',statements=2,values=3,receiver=1)
    add('const_parameter_receiver','return $p->read();',params='const Point &$p',receiver=1)
    add('const_to_mutable','return $p->get();',params='const Point &$p',receiver=2,error='A const reference cannot be passed as a mutable reference')
    add('receiver_plus_arguments','$p Point; return $p->pair($x,$y);',params='$x int32,$y int32',statements=2,values=5,receiver=2)
    add('repeated_method','$p Point; $p->read(); return $p->read();',statements=3,values=5,calls=2,receiver=1)
    identity='template<typename T> function identity($x T): T { return $x; }'
    add('template_identity','return identity<int32>($x);',params='$x int32',extra=identity)
    add('nested_template','return identity<int32>(identity<int32>($x));',params='$x int32',extra=identity,values=3,calls=2)
    add('template_value','return literal<7>();',ret='int',extra='template<int N> function literal(): int { return N; }',values=1)
    add('bound_template_call','return outer<int32>($x);',params='$x int32',extra=identity+' template<typename T> function outer($x T): T { return identity<T>($x); }')
    add('generic_widen','return identity<int32>($x);',params='$x int32',ret='int',extra=identity,values=3)
    add('method_missing_arg','$p Point; return $p->pair($x);',params='$x int32',statements=2,values=4,receiver=2,error='Call argument count does not match the resolved signature')
    add('method_extra_arg','$p Point; return $p->get($x);',params='$x int32',statements=2,values=3,receiver=2,error='Call argument count does not match the resolved signature')
    add('this_member_call','$p Point; return $p->forward();',statements=2,values=3,receiver=1)
    return cases
