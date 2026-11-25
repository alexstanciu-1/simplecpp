def build():
    bodies=[
        ('empty','return 0;',''),
        ('parameter','return $p;','$p int'),
        ('locals','$a int=1; $b int=2; return $b;','$p int'),
        ('nested','{ $a int=1; { $b int=2; } } $c int=3; return $c;',''),
        ('branch','$a int=1; if ($p) { $b int=2; } else { $c int=3; } return $a;','$p int'),
        ('returns','if ($p) { $a int=1; return $a; } else { $b int=2; return $b; }','$p int'),
        ('loop','$a int=1; while ($p) { $b int=2; $p=0; } return $a;','$p int'),
        ('nested_loops','$a int=1; while ($p) { $b int=2; while ($p) { $c int=3; $p=0; } $p=0; } return $a;','$p int'),
        ('branch_loop','while ($p) { if ($p) { $a int=1; } else { $b int=2; } $p=0; } return $p;','$p int'),
        ('unreachable','return 0; $a int=1; while (1) { $b int=2; }',''),
        ('shadow','{ $x int=1; } { $x int=2; } return 0;',''),
        ('many_locals',' '.join(f'$x{i} int={i};' for i in range(256))+' if ($p) { return $x255; } return $x0;','$p int'),
        ('deep_scopes','{'*128+'$x int=1;'+'}'*128+' return 0;',''),
        ('many_branches',' '.join(f'if ($p) {{ $x{i} int={i}; }}' for i in range(64))+' return $p;','$p int'),
    ]
    cases=[dict(name=n,source=f'function f({params}): int {{ {body} }} return 0;',blocks=[]) for n,body,params in bodies]
    # Isolated checked-graph inputs force predecessor intersection/requeue independently
    # of structured source CFG generation. Each declaration retains its own statement ID.
    source='function f($p int): int { $a int=1; $b int=2; return 0; } return 0;'
    cases.append(dict(name='diamond_intersection',source=source,blocks=[[0,1,1,2,2,3],[1,1,1,1,4,0],[2,0,1,1,4,0],[2,1,1,3,0,0]]))
    cases.append(dict(name='loop_intersection',source=source,blocks=[[0,1,1,1,2,0],[1,0,1,2,3,4],[1,1,1,1,2,0],[2,1,1,3,0,0]]))
    return cases
