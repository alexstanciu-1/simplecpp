"""Shared boundary-case oracle for the isolated native experiments."""
import itertools

def verification_data():
    values = [-9223372036854775808, -1000000001, -1000000000, -999999999,
              -10001, -10000, -5001, -5000, -3, -1, 0, 1, 2, 3, 4999, 5000,
              5001, 9999, 10000, 10001, 999999, 1000000, 1000001,
              999999999, 1000000000, 1000000001, 9223372036854775807]
    cases = [(op,a,b,0) for op in range(3) for a,b in itertools.product(values, repeat=2)]
    cases += [(3,a,b,c) for a,b,c in itertools.product(values, repeat=3)]
    def oracle(op,a,b,c):
        accepts = lambda n: -1000000000 <= n <= 1000000000
        if op == 0:
            if not accepts(a) or not accepts(b): return (0,0,1)
            return (a+b,1,0) if accepts(a+b) else (0,0,2)
        if op == 1:
            if not accepts(a) or not 0 <= b <= 1000000: return (0,0,3)
            return (a*b,1,0) if accepts(a*b) else (0,0,2)
        reason = 4 if op == 2 else 5
        if op == 2: c = 10000
        if not accepts(a) or not 0 <= b <= c or not 0 < c <= 1000000: return (0,0,reason)
        q,r = divmod(abs(a)*b,c)
        amount = q + (2*r >= c)
        return (-amount if a < 0 else amount,1,0)
    inputs = ''.join(' '.join(map(str,c))+'\n' for c in cases)
    expected = ''.join(' '.join(map(str,oracle(*c)))+'\n' for c in cases)
    return cases, inputs, expected
