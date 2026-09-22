"""Independent all-pairs operation variations and complete valid policy combinations."""
import itertools

def build():
    cases=[dict(kind='operation',left=a,right=b,equal=a==b,accept=True) for a in range(21) for b in range(21)]
    baseline=[0,0,0,0,0]
    for policy in itertools.product(range(3),range(2),range(3),range(3),range(4)):
        if policy[4]==2 and policy[0]==0: continue
        policy=list(policy)
        cases.append(dict(kind='lifetime',a=policy,b=policy,change=0,reverse=True,equal=True,accept=True))
        cases.append(dict(kind='lifetime',a=policy,b=baseline,change=0,reverse=False,equal=policy==baseline,accept=True))
    # Same permission, but a changed implementation of each role must prevent reuse.
    for role in range(1,6):
        cases.append(dict(kind='lifetime',a=[2,1,2,2,3],b=[2,1,2,2,3],change=role,reverse=False,equal=False,accept=True))
    return cases
