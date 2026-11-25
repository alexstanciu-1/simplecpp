function ptwo(string &$s, string &$n): result_or_false<int> {
    return strpos($s, $n);
}

function pthree(string &$s, string &$n, int $i): result_or_false<int> {
    return strpos($s, $n, $i);
}
