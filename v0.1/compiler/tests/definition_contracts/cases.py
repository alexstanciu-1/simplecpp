"""Independent semantic expectations; empty resource wrappers and map order are non-semantic."""
def build():
    cases=[]
    for kind,count in [('definition',30),('family',18),('record',17)]:
        for a in range(count):
            for b in range(count):
                aa=0 if kind=='definition' and a==10 else a
                bb=0 if kind=='definition' and b==10 else b
                if kind=='family':
                    aa=0 if a==17 else a
                    bb=0 if b==17 else b
                cases.append(dict(kind=kind,left=a,right=b,equal=aa==bb))
    return cases
