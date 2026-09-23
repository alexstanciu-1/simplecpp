import random

def build():
    cases = []
    def add(rows):
        seen = set()
        todo = [1]
        invalid = False
        while todo:
            node = todo.pop()
            if node in seen:
                continue
            if not 1 <= node <= len(rows):
                invalid = True
                break
            seen.add(node)
            row = rows[node - 1]
            if row[3] in (1, 2): todo.append(row[4])
            if row[3] == 2: todo.append(row[5])
        expected = [] if invalid else sorted(seen, key=lambda n: (rows[n-1][0], n))
        cases.append(dict(rows=rows, expected=expected, error=invalid))
    add([])
    add([[0,0,1,4,0,0]])
    add([[8,1,1,1,1,0]])
    add([[4,1,1,2,2,3],[2,1,1,1,4,0],[2,1,1,1,4,0],[0,1,1,3,0,0],[1,0,1,4,0,0]])
    add([[0,0,1,1,0,0]])
    add([[0,0,1,2,1,9]])
    add([[0,0,1,3,999,999],[1,0,1,1,999,0]])
    rng=random.Random(8317)
    for n in [2,3,7,16,31,64,129,257]:
        for trial in range(5):
            rows=[]
            for i in range(n):
                kind=rng.randint(1,4)
                rows.append([rng.randrange(12),rng.randrange(4),1,kind,rng.randint(1,n),rng.randint(1,n)])
            add(rows)
    # A deep chain exercises iterative traversal and sorting without recursion.
    add([[3000-i,1,1,1 if i<2999 else 3,i+2 if i<2999 else 0,0] for i in range(3000)])
    return cases
