def build():
    cases=[dict(mode='access',kind=i,access=j) for i in range(5) for j in range(5)]
    cases += [dict(mode='identity',kind=i,access=j) for i in range(5) for j in range(5)]
    cases += [dict(mode='clone',kind=i,access=0) for i in range(5)]
    cases += [dict(mode='invalid',kind=i,access=0) for i in range(3)]
    cases += [dict(mode='change',kind=i,access=0) for i in range(6)]
    cases += [dict(mode='receiver',kind=i,access=0) for i in range(3)]
    return cases
