"""Distinct contracts must not collapse merely because their native layout matches."""
def build():
    return [dict(kind=kind,left=a,right=b,accept=True,equal=a==b)
            for kind,count in [('reference',14),('callable',27)]
            for a in range(count) for b in range(count)]
