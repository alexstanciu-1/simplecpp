function jtwo(&s: string, &n: string): int {
    return s.indexOf(n);
}

function jthree(&s: string, &n: string, i: int): int {
    return s.indexOf(n, i);
}
