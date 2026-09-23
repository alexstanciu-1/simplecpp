def build():
    cases=[]
    def add(name,body,params='',ret='int',extra='',values=(),locals_=(),cleanups=(),statements=1,fall=False,counts=None):
        cases.append(dict(name=name,source=f'function f({params}): {ret} {{ {body} }} '+extra+' return 0;',values=list(values),locals=list(locals_),cleanups=list(cleanups),statements=statements,fall=fall,exact=counts is None,counts=list(counts or (len(values),len(locals_),len(cleanups)))))
    def v(i,s,end,c=0):return [i,s,end,c]
    def l(i,start,end,kind='return_exit',block=1):return [i,start,end,kind,block]
    def c(kind,i,boundary,block=1):return [kind,i,boundary,block]
    add('literal','return 7;',values=[v(1,1,'return_copy')])
    add('empty','',ret='void',statements=0,fall=True)
    add('bare_return','return;',ret='void')
    add('parameter','return $p;',params='$p int',values=[v(1,1,'return_copy')],locals_=[l(1,0,1)])
    add('local_write','$x int=1; $x=2; return $x;',values=[v(1,1,'local_copy'),v(2,2,'assignment_source'),v(3,3,'return_copy')],locals_=[l(1,1,3)],statements=3)
    add('addition','return 1+2;',values=[v(1,1,'operation_input',3),v(2,1,'operation_input',3),v(3,1,'return_copy')])
    add('widen','return $x;',params='$x int32',values=[v(1,1,'conversion_input',2),v(2,1,'return_copy')],locals_=[l(1,0,1)])
    add('call','return id(7);',extra='function id($x int): int { return $x; }',values=[v(1,1,'argument_copy',1),v(2,1,'return_copy')])
    add('nested_call','return id(id(7));',extra='function id($x int): int { return $x; }',values=[v(1,1,'argument_copy',1),v(2,1,'argument_copy',2),v(3,1,'return_copy')])
    add('managed_default','$x Managed; return 0;',values=[v(1,1,'local_construct'),v(2,2,'return_copy')],locals_=[l(1,1,2)],cleanups=[c('local',1,2)],statements=2)
    add('managed_copy','$x Managed; $y Managed=$x; return 0;',values=[v(1,1,'local_construct'),v(2,2,'copy_source'),v(3,3,'return_copy')],locals_=[l(2,2,3),l(1,1,3)],cleanups=[c('local',2,3),c('local',1,3)],statements=3)
    add('managed_assign','$x Managed; $y Managed; $y=$x; return 0;',values=[v(1,1,'local_construct'),v(2,2,'local_construct'),v(3,3,'assignment_source'),v(4,4,'return_copy')],locals_=[l(2,2,4),l(1,1,4)],cleanups=[c('local',2,4),c('local',1,4)],statements=4)
    add('managed_move_return','$x Managed; return $x;',ret='Managed',values=[v(1,1,'local_construct'),v(2,2,'return_construct')],locals_=[l(1,1,2)],cleanups=[c('local',1,2)],statements=2)
    add('borrowed_copy_return','return $x;',ret='Managed',params='const Managed &$x',values=[v(1,1,'return_construct')],locals_=[l(1,0,1)])
    add('return_new','return new Managed();',ret='Managed',values=[v(1,1,'return_construct')])
    add('return_provider','return make_managed();',ret='Managed',values=[v(1,1,'return_construct')])
    add('discard_new','new Managed(); return 0;',values=[v(1,1,'discard'),v(2,2,'return_copy')],cleanups=[c('temporary',1,1)],statements=2)
    add('provider_local','$x Managed=make_managed(); return 0;',values=[v(1,1,'local_construct'),v(2,2,'return_copy')],locals_=[l(1,1,2)],cleanups=[c('local',1,2)],statements=2)
    add('borrow_temporary','read(make_managed()); return 0;',extra='function read(const Managed &$x): int { return 0; }',values=[v(1,1,'argument_borrow',2),v(2,1,'discard'),v(3,2,'return_copy')],cleanups=[c('temporary',1,1)],statements=2)
    add('two_temporaries','read(make_managed(),make_managed()); return 0;',extra='function read(const Managed &$x,const Managed &$y): int { return 0; }',values=[v(1,1,'argument_borrow',3),v(2,1,'argument_borrow',3),v(3,1,'discard'),v(4,2,'return_copy')],cleanups=[c('temporary',2,1),c('temporary',1,1)],statements=2)
    add('scope_exit','{ $x Managed; } return 0;',values=[v(1,1,'local_construct'),v(2,2,'return_copy')],locals_=[l(1,1,1,'scope_exit')],cleanups=[c('local',1,1)],statements=2)
    add('unreachable','return 0; $x Managed;',values=[v(1,1,'return_copy')],statements=1)
    add('branch','if ($p) { $x Managed; } else { $y Managed; } return 0;',params='$p int',statements=4,counts=(4,3,2))
    add('loop','while ($p) { $x Managed; $p=0; } return 0;',params='$p int',statements=4,counts=(4,2,1))
    # Retained lifetime_analysis.php: discard/void/return/unreachable partition.
    add('retained_reachability','value(); quiet(); stable(); return value(); quiet();',extra='function value(): int { return 7; } function quiet(): void {} function stable(): int { return 7; }',values=[v(1,1,'discard'),v(2,3,'discard'),v(3,4,'return_copy')],statements=4)
    add('void_call','quiet(); return;',ret='void',extra='function quiet(): void {}',statements=2)
    add('indexed_assignment','$b Buffer; $b->data[index()] = value($x); return $b->data[0];',params='$x int32',ret='int32',extra='function index(): int { return 0; } function value($x int32): int32 { return $x; }',values=[v(1,1,'local_construct'),v(2,2,'target_index'),v(3,2,'argument_copy',2),v(4,2,'assignment_source'),v(5,3,'index_input',6),v(6,3,'return_copy')],locals_=[l(2,1,3),l(1,0,3)],statements=3)
    add('deep_calls','return '+'id('*128+'1'+')'*128+';',extra='function id($x int): int { return $x; }',counts=(129,0,0))
    add('many_managed_locals',' '.join(f'$x{i} Managed;' for i in range(64))+' return 0;',statements=65,counts=(65,64,64))
    import json
    for case in cases:
        for field in ['values','locals','cleanups']:
            case[field+'_json'] = json.dumps(case[field],separators=(',',':'))
    return cases
