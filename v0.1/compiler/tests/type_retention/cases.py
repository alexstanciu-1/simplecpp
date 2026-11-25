"""Compare complete rows; then exercise reuse eligibility and late rejection separately."""
def build():
    cases=[dict(kind='compare',left=a,right=b,equal=a==b,accept=True) for a in range(11) for b in range(11)]
    for mode in [0,1,2,3,4,6,8,9]:
        for binding in range(6):
            cases.append(dict(kind='retain',mode=mode,binding=binding,accept=binding!=0 or mode==0))
    cases += [dict(kind='source',mode=m,binding=2,accept=m==0) for m in range(3)]
    return cases
