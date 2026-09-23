def build():
    def edges(bits):return {(a,b) for a in range(2) for b in range(2) if bits & (1 << (a*2+b))}
    def encoded(pairs):return sum(1 << (a*2+b) for a,b in pairs)
    cases=[]
    for state in range(16):
        relation=edges(state)
        for transition in range(16):
            other=edges(transition)
            composed={(a,c) for a,b in relation for b2,c in other if b==b2}
            cases.append(dict(kind='compose',state=state,operand=transition,expected=encoded(composed)))
        for required in range(4):
            allowed={b for b in range(2) if required & (1 << b)}
            accepted={a for a in range(2) if (outputs:={b for a2,b in relation if a==a2}) and outputs <= allowed}
            cases.append(dict(kind='compatible',state=state,operand=required,expected=sum(1 << a for a in accepted)))
        deterministic={a for a in range(2) if len({b for a2,b in relation if a==a2})==1}
        cases.append(dict(kind='deterministic',state=state,operand=0,expected=sum(1 << a for a in deterministic)))
    return cases
