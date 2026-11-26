"""Compile both regex result shapes and check module removal/recovery through the native bundle."""
import json
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

from app_namespace_native import artifacts, invoke
from extends_native import source_edit

root = Path(__file__).resolve().parents[2]
php, cxx, mold, build = sys.argv[1:]

driver = r'''
require $argv[1];
$p = new scpp\compiler\Incremental_Project($argv[2], $argv[3], new scpp\compiler\entry_target(31), $argv[4]);
while (fgets(STDIN) !== false) {
    try { $r = $p->run(); } catch (Throwable $e) { $r = ['error'=>$e->getMessage()]; }
    echo json_encode($r, JSON_THROW_ON_ERROR), "\n";
}
'''


def fixture(flavor):
    statements = []
    def local(name, type_name, expr):
        declarations = {'php': f'${name} {type_name}', 'cpp': f'{type_name} {name}', 'js': f'let {name}: {type_name}'}
        statements.append(declarations[flavor] + ' = ' + expr + ';')
        return ('$' if flavor == 'php' else '') + name

    for name, pattern, subject, expected in [
        ('hit', '/(a)(b)/i', 'xABy', 1), ('miss', '/c/', 'ab', 0),
        ('nul', '/a\\0b/', 'xa\\0by', 1), ('pastnul', '/b$/', 'a\\0b', 1),
        ('emptyMatch', '//', '', 1), ('paired', '{a{2}}', 'aa', 1),
        ('escaped', '/a\\\\/b/', 'a/b', 1), ('invalid', '/[/', 'a', None),
        ('modifier', '/a/q', 'a', None), ('badUtf', '/./u', None, None),
    ]:
        text = f'"{subject}"' if subject is not None else 'string_byte_from_int(255)'
        args = f'("{pattern}", {text})'
        for function, scalar, result in [('regex_test', 'bool', 'result<bool>')] + (
                [('preg_match', 'int', 'result_or_false<int>')] if flavor == 'php' else []):
            key = name + function
            owner = local(key, result, function + args)
            if expected is None:
                statements.append(f'if (isset({owner})) {{ return 2; }}')
            else:
                statements.append(f'if (!isset({owner})) {{ return 3; }}')
                value = local(key + 'Value', scalar, owner)
                want = str(expected) if scalar == 'int' else ('true' if expected else 'false')
                statements.append(f'if ({value} !== {want}) {{ return 4; }}')
    for prefix in (['regex', 'preg'] if flavor == 'php' else ['regex']):
        local(prefix + 'Jit', 'bool', prefix + '_jit_available()')
        for suffix, args, expected in [('Plain', '"a.b/"', r'a\\.b/'), ('Delimited', '"a.b/", "/"', r'a\\.b\\/')]:
            value = local(prefix + suffix, 'string', f'{prefix}_quote({args})')
            statements.append(f'if ({value} !== "{expected}") {{ return 8; }}')
    if flavor == 'php':
        statements.append(r'''
$literal string = preg_replace("/(a)/", "$1!", "aa", 1);
if ($literal !== "a!a") { return 44; }
$mark string = "!";
$format function<string(vector<string>)> = fn($row) => $row[0] . $mark;
$rewritten string = regex_replace_callback("/a/", $format, "aa", 1);
if ($rewritten !== "a!a") { return 36; }
$rules hash<function<string(vector<string>)>,string> = ["/a/" => $format, "/!/" => fn($row) => "b"];
$rewritten = regex_replace_callback_array($rules, "aa", 1);
if ($rewritten !== "aba") { return 37; }
$render function<string(mixed)> = fn($row) => (string)$row[1];
$rewritten = preg_replace_callback("/(a)b/", $render, "ab");
if ($rewritten !== "a") { return 38; }
$phpRules hash<function<string(mixed)>,string> = ["/(a)b/" => $render];
$rewritten = preg_replace_callback_array($phpRules, "abab");
if ($rewritten !== "aa") { return 39; }
$rewritten = regex_replace_callback("/(?:|a)/", fn($row) => "$1", "a");
if ($rewritten !== "$1$1$1") { return 40; }
$throwing function<string(vector<string>)> = function(vector<string> $row):string { throw new Exception("callback"); };
$caught bool = false;
try { regex_replace_callback("/a/", $throwing, "a"); } catch (Exception $failure) {
    $caught = $failure->get_message() === "callback";
}
if (!$caught) { return 41; }
$named hash<string,string> = regex_match_named("/(?<tag>a)/", "a");
if ($named["0"] !== "a" || $named["tag"] !== "a") { return 31; }
$rows vector<vector<string>> = regex_match_all("/(a)?b/", "bab");
$second vector<string> = $rows[1];
if (count($rows) !== 2 || $second[1] !== "a") { return 32; }
$rows = regex_match_all_pattern_order("/(a)?b/", "bab");
$second = $rows[1];
if ($second[0] !== "" || $second[1] !== "a") { return 33; }
$namedRows vector<hash<string,string>> = regex_match_all_named("/(?<tag>a)/", "aa", -1);
$named = $namedRows[0];
if (count($namedRows) !== 1 || $named["tag"] !== "a") { return 34; }
$nestedFail result_or_false<vector<vector<string>>> = regex_match_all("/[/", "");
if (isset($nestedFail)) { return 35; }
$boxes mixed = ["p"=>"ax", "q"=>"xz", "r"=>"!"];
$kept result_or_false<mixed> = preg_grep("/x/", $boxes);
$values mixed = $kept;
if (count($values) !== 2 || (string)$values["q"] !== "xz") { return 21; }
$values["q"] = "updated";
$original mixed = $kept;
if ((string)$original["q"] !== "xz") { return 30; }
$numeric mixed = ["year"=>2026];
$values = preg_grep("/26$/", $numeric);
if ((string)$values["year"] !== "2026") { return 43; }
$values = preg_filter("/x/", "x", $boxes, 1);
if (count($values) !== 2 || (string)$values["p"] !== "ax") { return 22; }
$parts = preg_split("/,/", "", -1, PREG_SPLIT_NO_EMPTY);
if (!isset($parts)) { return 23; }
$fields vector<string> = $parts;
if (count($fields) !== 0) { return 24; }
$parts = preg_split("/[/", "");
if ($parts !== false) { return 25; }
if (cli_argc() === 3) { preg_filter("/[/", "", "", 0); return 55; }
if (cli_argc() === 4) { preg_filter("/./u", "", string_byte_from_int(255)); return 56; }
if (cli_argc() > 1) { preg_grep("/./", $parts); return 45; }
$rowsByIndex = preg_grep("/^keep/", preg_split("/,/", "drop,keepA,drop,keepB"));
$selected hash<string,int> = $rowsByIndex;
if (count($selected) !== 2 || $selected->has(0) || $selected[1] !== "keepA" || $selected[3] !== "keepB") { return 46; }
$rowsByIndex = preg_grep("/z/", preg_split("/,/", "abc"));
if (!isset($rowsByIndex)) { return 47; }
$selected = $rowsByIndex;
if (count($selected) !== 0) { return 48; }
$rowsByIndex = preg_grep("/a/", $fields);
if (!isset($rowsByIndex)) { return 49; }
$selected = $rowsByIndex;
if (count($selected) !== 0) { return 50; }
$rowsByIndex = preg_grep("/[/", $fields);
if ($rowsByIndex !== false) { return 51; }
$badUtfRows vector<string> = ["ok", string_byte_from_int(255)];
$rowsByIndex = preg_grep("/./u", $badUtfRows);
if ($rowsByIndex !== false) { return 54; }
$filtered ?string = preg_filter("/x/", "", "x");
if (!isset($filtered) || $filtered !== "") { return 57; }
$filtered = preg_filter("/x/", "x", "x", 1);
if ($filtered !== "x") { return 58; }
$filtered = preg_filter("/x/", "", "other");
if ($filtered !== null) { return 59; }
$filtered = preg_filter("/x/", "!", "x", 0);
if ($filtered !== null) { return 60; }
$rowsByIndex = preg_filter("/x/", "", preg_split("/,/", "drop,x,skip,xx"));
$selected = $rowsByIndex;
if (count($selected) !== 2 || $selected[1] !== "" || $selected[3] !== "") { return 61; }
$rowsByIndex = preg_filter("/x/", "!", $badUtfRows, 1);
$selected = $rowsByIndex;
if (count($selected) !== 0) { return 62; }
$rowsByIndex = preg_filter("/./u", "", $badUtfRows);
if ($rowsByIndex !== false) { return 63; }
$selected = preg_filter("/x/", "!", preg_grep("/x/", preg_split("/,/", "skip,xx")), 1);
if (count($selected) !== 1 || $selected[1] !== "!x") { return 64; }
$rowsByIndex = preg_filter("/[/", "", $selected);
if ($rowsByIndex !== false) { return 65; }
$words hash<string,string> = ["z" => "xx", "skip" => "a", "b" => "x"];
$byName hash<string,string> = preg_filter("/x/", "", $words, 1);
if (count($byName) !== 2 || $byName["z"] !== "x" || $byName["b"] !== "") { return 66; }
$order string = "";
foreach ($byName as $key => $word) { $order .= $key; }
if ($order !== "zb") { return 67; }
$byName = preg_filter("/missing/", "", $words);
if (count($byName) !== 0) { return 68; }
$failedNames result_or_false<hash<string,string>> = preg_filter("/[/", "", $byName);
if ($failedNames !== false) { return 69; }
$boxedFilter mixed = preg_filter("/x/", "", ["skip", "x"]);
if (count($boxedFilter) !== 1 || (string)$boxedFilter[1] !== "") { return 70; }
$boxedScalar mixed = "x";
$boxedFilter = preg_filter("/x/", "", $boxedScalar);
if (count($boxedFilter) !== 0) { return 71; }
$badBox result_or_false<mixed> = preg_filter("/[/", "", $boxes);
if ($badBox !== false) { return 72; }
$boxedAgain mixed = preg_grep("/x/", $kept);
if ((string)$boxedAgain["p"] !== "ax") { return 52; }
$literalBox mixed = preg_grep("/keep/", ["drop", "keep"]);
if ((string)$literalBox[1] !== "keep") { return 53; }
$n int = preg_match_all("/(?<letter>a)/", "aa", $values);
$column mixed = $values["letter"];
if ($n !== 2 || (string)$column[1] !== "a") { return 26; }
$n = preg_match_all("/(?<letter>a)/", "aa", $values, PREG_SET_ORDER, -1);
$column = $values[0];
if ($n !== 1 || (string)$column["letter"] !== "a") { return 27; }
$n = preg_match_all("/(a)?b/", "babb", $values, PREG_PATTERN_ORDER);
$column = $values[1];
if (count($column) !== 3 || (string)$column[0] !== "" || (string)$column[1] !== "a" || (string)$column[2] !== "") { return 42; }
$n = preg_match("/(?<letter>a)/", "a", $values);
if ($n !== 1 || (string)$values[0] !== "a" || (string)$values["letter"] !== "a") { return 28; }
$status result_or_false<int> = preg_match("/[/", "", $values);
if (isset($status) || count($values) !== 0) { return 29; }
$numbered vector<string> = ["stale"];
$n = preg_match("/^$/", "", $numbered);
if ($n !== 1 || count($numbered) !== 1 || $numbered[0] !== "") { return 73; }
$n = preg_match("/(?<first>a)?(b)(c)?/", "xb", $numbered, 0, -1);
if ($n !== 1 || count($numbered) !== 3 || $numbered[0] !== "b" || $numbered[1] !== "" || $numbered[2] !== "b") { return 74; }
$n = preg_match("/x/", "", $numbered, 0);
if ($n !== 0 || count($numbered) !== 0) { return 75; }
$numbered = ["stale"];
$status = preg_match("/[/", "", $numbered);
if ($status !== false || count($numbered) !== 0) { return 76; }
$matrix vector<vector<string>> = [];
$n = preg_match_all("/(a)?(b)(c)?/", "bab", $matrix);
if ($n !== 2 || count($matrix) !== 4) { return 77; }
$columnText vector<string> = $matrix[1];
if (count($columnText) !== 2 || $columnText[0] !== "" || $columnText[1] !== "a") { return 78; }
$columnText = $matrix[3];
if (count($columnText) !== 2 || $columnText[0] !== "" || $columnText[1] !== "") { return 79; }
$n = preg_match_all("/(a)?(b)(c)?/", "", $matrix, PREG_PATTERN_ORDER);
if ($n !== 0 || count($matrix) !== 4 || count($matrix[0]) !== 0 || count($matrix[3]) !== 0) { return 80; }
$orientation int = PREG_SET_ORDER;
$n = preg_match_all("/(a)?(b)(c)?/", "xb", $matrix, $orientation, -1);
if ($n !== 1 || count($matrix) !== 1) { return 81; }
$columnText = $matrix[0];
if (count($columnText) !== 3 || $columnText[1] !== "" || $columnText[2] !== "b") { return 82; }
$n = preg_match_all("/x/", "", $matrix, $orientation);
if ($n !== 0 || count($matrix) !== 0) { return 83; }
$n = preg_match_all("/^$/", "", $matrix);
if ($n !== 1 || count($matrix) !== 1 || $matrix[0][0] !== "") { return 84; }
$status = preg_match_all("/./u", string_byte_from_int(255), $matrix);
if ($status !== false || count($matrix) !== 0) { return 85; }
$namedPattern string = "/(?<z>a)?(?<b>b)(?<tail>c)?/";
$n = preg_match($namedPattern, "b", $named);
$order = "";
foreach ($named as $key => $value) { $order .= $key; }
if ($n !== 1 || $order !== "0z1b2" || $named["0"] !== "b" || $named["z"] !== "" || $named["2"] !== "b") { return 86; }
$n = preg_match("/^$/", "", $named, 0);
if ($n !== 1 || count($named) !== 1 || $named["0"] !== "") { return 87; }
$n = preg_match($namedPattern, "", $named);
if ($n !== 0 || count($named) !== 0) { return 88; }
$dupes string = "/(?J)(?<x>a)|(?<x>b)/";
$n = preg_match_all($dupes, "ab", $namedRows, $orientation);
if ($n !== 2) { return 89; }
$named = $namedRows[0];
if ($named["x"] !== "a" || $named->has("2")) { return 90; }
$named = $namedRows[1];
if ($named["x"] !== "b" || $named["1"] !== "" || $named["2"] !== "b") { return 91; }
$n = preg_match("/(?J)(?<x>a)(?<x>b)?(c)/", "ac", $named, 0, 0);
if ($n !== 1 || $named["x"] !== "a" || $named["2"] !== "") { return 92; }
$columns hash<vector<string>,string> = [];
$n = preg_match_all($dupes, "ab", $columns);
$order = "";
foreach ($columns as $key => $texts) { $order .= $key; }
if ($n !== 2 || $order !== "0x12" || $columns["x"][0] !== "" || $columns["x"][1] !== "b" || $columns["1"][0] !== "a") { return 93; }
$n = preg_match_all($namedPattern, "", $columns, PREG_PATTERN_ORDER, 0);
$order = "";
foreach ($columns as $key => $texts) { $order .= $key; }
if ($n !== 0 || $order !== "0z1b2tail3" || count($columns["tail"]) !== 0) { return 94; }
$n = preg_match_all($dupes, "", $namedRows, PREG_SET_ORDER, 0);
if ($n !== 0 || count($namedRows) !== 0) { return 95; }
$status = preg_match("/[/", "a", $named);
if ($status !== false || count($named) !== 0) { return 96; }
$status = preg_match_all("/./u", string_byte_from_int(255), $columns);
if ($status !== false || count($columns) !== 0) { return 97; }
$n = preg_match_all($dupes, "b", $namedRows, PREG_SET_ORDER);
$status = preg_match_all("/[/", "b", $namedRows, PREG_SET_ORDER);
if ($status !== false || count($namedRows) !== 0) { return 98; }
$n = preg_match_all_set_order("/(a)(b)?/", "aba", $matrix);
if ($n !== 2 || count($matrix) !== 2 || $matrix[0][2] !== "b" || count($matrix[1]) !== 2) { return 99; }
$n = preg_match_all_set_order("/(.)/u", "éax", $matrix, 2);
if ($n !== 2 || $matrix[0][0] !== "a") { return 100; }
$n = preg_match_all_pattern_order("/(a)(b)?/", "aba", $matrix);
if ($n !== 2 || count($matrix) !== 3 || $matrix[2][0] !== "b" || $matrix[2][1] !== "") { return 101; }
$n = preg_match_all_pattern_order("/(.)/u", "éax", $matrix, -1);
if ($n !== 1 || $matrix[1][0] !== "x") { return 102; }
$n = preg_match_all_set_order($dupes, "ab", $namedRows);
if ($n !== 2 || $namedRows[0]["x"] !== "a" || $namedRows[1]["x"] !== "b") { return 103; }
$n = preg_match_all_set_order($dupes, "ab", $namedRows, -1);
if ($n !== 1 || $namedRows[0]["x"] !== "b") { return 104; }
$n = preg_match_all_pattern_order($dupes, "ab", $columns);
if ($n !== 2 || $columns["x"][0] !== "" || $columns["x"][1] !== "b") { return 105; }
$n = preg_match_all_pattern_order($dupes, "ab", $columns, 2);
if ($n !== 0 || count($columns) !== 4 || count($columns["x"]) !== 0) { return 106; }
$status = preg_match_all_pattern_order("/[/", "a", $columns);
if ($status !== false || count($columns) !== 0) { return 107; }
$row result_or_false<vector<string>> = regex_match("/(a)?(b)(z)?/", "xxb", -1);
$v vector<string> = $row;
if (count($v) !== 3 || $v[0] !== "b" || $v[1] !== "" || $v[2] !== "b") { return 11; }
$v = regex_match("/x/", "x", 2);
if (count($v) !== 0) { return 12; }
$row = regex_match("/[/", "x");
if (isset($row)) { return 13; }
$input vector<string> = ["aa", "!", "ba"];
$v = regex_grep("/a/", $input);
if (count($v) !== 2 || $v[1] !== "ba") { return 14; }
$v = regex_filter("/a/", "*", $input, 1);
if (count($v) !== 2 || $v[0] !== "*a" || $v[1] !== "b*") { return 15; }
$v = regex_split("/(,)/", ",a,b,", -1, 3);
if (count($v) !== 5 || $v[0] !== "," || $v[3] !== "b") { return 16; }
$t string = regex_replace("/(?:|a)/", "-", "a");
if ($t !== "---") { return 17; }
$t = regex_replace("/()/u", "-", "é");
if ($t !== "-é-") { return 18; }
$t = regex_replace("/(a)(b)/", "\\2\\1", "abab", 1);
if ($t !== "baab") { return 19; }
$bad result_or_false<string> = regex_replace("/[/", "", "", 0);
if (isset($bad)) { return 20; }
''')
        # At byte 2, group 1 is unset, group 2 matches empty and group 4 is trailing/unset.
        statements.append('$capturePattern string = "/(?<pre>x)?(?<empty>)(é)(?<tail>z)?/u";')
        for suffix, cell, flag, offsets, nulls in [
            ('Offset', 'regex_offset_capture', 'PREG_OFFSET_CAPTURE', True, False),
            ('Null', '?string', 'PREG_UNMATCHED_AS_NULL', False, True),
            ('Both', 'regex_nullable_offset_capture', '(PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL)', True, True),
        ]:
            number = local('numbers' + suffix, f'vector<{cell}>', '[]')
            named = local('named' + suffix, f'hash<{cell},string>', '[]')
            rows = local('sets' + suffix, f'vector<hash<{cell},string>>', '[]')
            columns = local('cols' + suffix, f'hash<vector<{cell}>,string>', '[]')
            matrix = local('matrix' + suffix, f'vector<vector<{cell}>>', '[]')
            for target in (number, named):
                statements.append(f'$n = preg_match($capturePattern, "αé", {target}, {flag}, 2);')
                statements.append(f'if ($n !== 1 || count({number}) !== {5 if nulls else 4}) {{ return 108; }}')
            statements.append(f'if (isset({named}["missing"]) || !isset({named}["empty"])) {{ return 118; }}')
            if not offsets:
                statements.append(f'if (isset({named}["pre"]) || !{named}->has("pre")) {{ return 119; }}')
            statements.append(f'if ({named}->has("tail") !== {"true" if nulls else "false"}) {{ return 120; }}')
            field = '->text' if offsets else ''
            statements.append(f'if ({number}[3]{field} !== "é") {{ return 121; }}')
            for order, target in [('PREG_SET_ORDER', rows), ('PREG_PATTERN_ORDER', columns)]:
                statements.append(f'$n = preg_match_all($capturePattern, "αé", {target}, {order} | {flag}, 2);')
                statements.append('if ($n !== 1) { return 109; }')
            for args, pre, empty in [
                (number, '[1]', '[2]'), (named, '["pre"]', '["empty"]'),
                (rows, '[0]["pre"]', '[0]["empty"]'), (columns, '["pre"][0]', '["empty"][0]'),
            ]:
                text = '->text' if offsets else ''
                absent = 'null' if nulls else '""'
                statements.append(f'if ({args}{pre}{text} !== {absent} || {args}{empty}{text} !== "") {{ return 110; }}')
                if offsets:
                    statements.append(f'if ({args}{pre}->byte_offset !== -1 || {args}{empty}->byte_offset !== 2) {{ return 111; }}')
            statements.append(f'$n = preg_match_all_set_order($capturePattern, "αé", {matrix}, -2);')
            statements.append(f'if ($n !== 1 || count({matrix}[0]) !== {5 if nulls else 4}) {{ return 112; }}')
            statements.append(f'$n = preg_match_all_pattern_order($capturePattern, "αé", {matrix}, 2);')
            statements.append(f'if ($n !== 1 || count({matrix}) !== 5 || count({matrix}[4]) !== 1) {{ return 113; }}')
            statements.append(f'$n = preg_match_all_pattern_order($capturePattern, "", {columns});')
            statements.append(f'if ($n !== 0 || count({columns}["tail"]) !== 0) {{ return 114; }}')
            statements.append(f'$status = preg_match_all_set_order("/[/", "x", {rows});')
            statements.append(f'if ($status !== false || count({rows}) !== 0) {{ return 115; }}')
        statements.append(r'''
$n = preg_match($capturePattern, "αé", $values, PREG_UNMATCHED_AS_NULL, 2);
if ($n !== 1 || $values["pre"] !== null || (string)$values["empty"] !== "") { return 122; }
$n = preg_match($capturePattern, "αé", $values, PREG_OFFSET_CAPTURE, 2);
$onlyPair mixed = $values["empty"];
if ($n !== 1 || (string)$onlyPair[0] !== "" || (int)$onlyPair[1] !== 2) { return 123; }
$flags int = PREG_OFFSET_CAPTURE | PREG_UNMATCHED_AS_NULL;
$n = preg_match_all($dupes, "ab", $values, PREG_PATTERN_ORDER | $flags);
$duplicate mixed = $values["x"];
$absentPair mixed = $duplicate[0];
$presentPair mixed = $duplicate[1];
if ($n !== 2 || $absentPair[0] !== null || (int)$absentPair[1] !== -1 ||
    (string)$presentPair[0] !== "b" || (int)$presentPair[1] !== 1) { return 116; }
$n = preg_match_all($dupes, "ab", $values, PREG_SET_ORDER | $flags);
$firstRow mixed = $values[0];
$firstPair mixed = $firstRow["x"];
if ((string)$firstPair[0] !== "a" || (int)$firstPair[1] !== 0) { return 117; }
''')
    end = local('endByte', 'int', '99')
    for i, (pattern, subject, offset, start, after) in enumerate([
        ('/x/', 'x', 0, 0, 1), ('/x/', 'é!x', 2, 3, 4),
        (r'/\\Gx/', 'é!x', 2, -1, 99), (r'/\\Gx/', 'é!x', 3, 3, 4),
        ('/(x)/', r'a\0x', 2, 2, 3), ('//', '', 0, 0, 0),
        ('//', 'é', 2, 2, 2), ('/x/', 'xx', 2, -1, 99),
        ('/é/u', 'éé', 2, 2, 4), ('/./u', 'é', 1, None, 99),
        ('/[/', 'x', 0, None, 99), ('//', 'x', -1, None, 99),
        ('//', 'x', 9223372036854775807, None, 99),
    ]):
        statements.append(f'{end} = 99;')
        owner = local(f'span{i}', 'result<int>', f'regex_find_span("{pattern}", "{subject}", {offset}, {end})')
        if start is None:
            statements.append(f'if (isset({owner})) {{ return 5; }}')
        else:
            value = local(f'start{i}', 'int', owner)
            statements.append(f'if ({value} !== {start}) {{ return 6; }}')
        statements.append(f'if ({end} !== {after}) {{ return 7; }}')
    return '\n'.join(statements) + '\nreturn 0;'


def prove(work, runtime, flavor):
    app = work / flavor
    app.mkdir()
    output = work / ('out-' + flavor)
    source = app / ('main.' + flavor)
    manifest = app / 'module.json'
    enabled = json.dumps({'general_api': True, 'dependencies': {'runtime': {'modules': ['regex']}}})
    def compiler(out):
        return subprocess.Popen([php, '-r', driver, root/'compiler/boot.php', app, out, runtime],
                                stdin=subprocess.PIPE, stdout=subprocess.PIPE, text=True)
    source_edit(manifest, '{"general_api":true}')
    interpolation = {'php': '$n int = 8; echo "é{$n}";', 'cpp': 'int n = 8; echo f"é{n}";',
                     'js': 'let n: int = 8; echo `é${n}`;'}[flavor]
    source_edit(source, interpolation + ' return 0;')
    with compiler(output) as resident:
        def ok():
            result = invoke(resident)
            assert 'error' not in result, result
        def links():
            return json.loads((output/'manifest.json').read_text())['link_inputs']
        ok()
        assert [Path(path).name for path in links()] == ['libscpp_runtime.a'], links()
        if flavor == 'php':
            source_edit(source, '$f int = PREG_SET_ORDER; return 0;')
            assert 'PREG_SET_ORDER' in invoke(resident).get('error', '')
            source_edit(source, 'preg_match("/a/", "a"); return 0;')
            assert 'requires module runtime-regex' in invoke(resident).get('error', '')
        source_edit(source, fixture(flavor))
        assert 'requires module runtime-regex' in invoke(resident).get('error', '')
        if flavor != 'cpp':
            source_edit(manifest, '{"dependencies":{"runtime":{"modules":["regex"]}}}')
            source_edit(source, 'regex_test("/a/", "a"); return 0;')
            assert 'regex_test' in invoke(resident).get('error', '')
            if flavor == 'php':
                source_edit(source, 'preg_quote("."); preg_match("/a/", "a"); '
                                   'preg_grep("/x/", preg_split("/,/", "x,y")); return 0;')
                ok()
                lowered = b'\n'.join(artifacts(output).values())
                assert b'::scpp::regex::split(' in lowered and b'::scpp::regex::preg_grep_vector(' in lowered
                assert b'::scpp::regex::preg_grep(' not in lowered
            source_edit(source, fixture(flavor))
        source_edit(manifest, enabled)
        ok()
        if flavor == 'php':
            lowered = b'\n'.join(artifacts(output).values())
            for target in ('preg_filter_string', 'preg_filter_vector', 'preg_filter_int_keys',
                           'preg_filter_string_keys', 'preg_filter', 'preg_match_numbered', 'preg_match_all_numbered',
                           'preg_match_named', 'preg_match_all_named_rows', 'preg_match_all_named_columns',
                           'preg_match_all_set_order_named', 'preg_match_all_pattern_order_named',
                           'preg_match_all_set_order_numbered', 'preg_match_all_pattern_order_numbered'):
                assert f'::scpp::regex::{target}('.encode() in lowered, target
            for bad_source, diagnostic in [
                ('$m hash<vector<string>,string> = []; preg_match_all_set_order("/a/", "a", $m);', 'Unsupported argument type'),
                ('$m vector<hash<string,string>> = []; preg_match_all_pattern_order("/a/", "a", $m);', 'Unsupported argument type'),
                ('$m = preg_split("/,/", "a"); preg_match("/a/", "a", $m);', 'Unsupported argument type'),
                ('$m vector<string> = []; preg_match_all("/a/", "a", $m);', 'Unsupported argument type'),
                ('function inspect(const vector<string> &$m): void { preg_match("/./", "x", $m); }',
                 'Cannot write through read-only reference'),
                ('function fresh(): vector<string> { return []; } preg_match("/a/", "a", fresh());', 'requires a writable'),
            ]:
                source_edit(source, bad_source + ' return 0;')
                error = invoke(resident).get('error', '')
                assert diagnostic in error, error
            source_edit(source, '$items vector<int> = [1]; preg_filter("/./", "", $items); return 0;')
            assert 'Unsupported argument type for preg_filter' in invoke(resident).get('error', '')
            source_edit(source, '$items vector<int> = [1]; preg_grep("/./", $items); return 0;')
            assert 'Unsupported argument type for preg_grep' in invoke(resident).get('error', '')
            source_edit(source, '$boxed mixed = preg_split("/,/", "a"); return 0;')
            assert 'Unsupported boxing into mixed' in invoke(resident).get('error', '')
        source_edit(source, 'regex_find_span("/a/", "a", 0, 1); return 0;')
        bad = invoke(resident)
        assert 'reference' in bad.get('error', '').lower(), bad
        with compiler(work/('invalid-' + flavor)) as fresh:
            assert invoke(fresh).get('error') == bad['error']
            fresh.stdin.close()
        source_edit(source, fixture(flavor))
        ok()
        byte_local = {'php': '$b byte = 0;', 'js': 'let b: byte = 0;', 'cpp': 'byte b = 0;'}[flavor]
        var = '$b' if flavor == 'php' else 'b'
        source_edit(source, byte_local + f'regex_find_span("/a/", "a", 0, {var}); return 0;')
        bad = invoke(resident)
        assert 'Reference argument requires exact storage type' in bad.get('error', ''), bad
        source_edit(source, fixture(flavor))
        ok()
        if flavor != 'php':
            source_edit(source, 'preg_match("/a/", "a"); return 0;')
            error = invoke(resident).get('error', '')
            assert 'preg_match' in error, error
            source_edit(source, fixture(flavor))
            ok()
        source_edit(manifest, '{"general_api":true}')
        assert 'requires module runtime-regex' in invoke(resident).get('error', '')
        source_edit(manifest, enabled)
        ok()
        with compiler(work/('fresh-' + flavor)) as fresh:
            result = invoke(fresh)
            assert 'error' not in result, result
            fresh.stdin.close()
        assert artifacts(output) == artifacts(work/('fresh-' + flavor))
        resident.stdin.close()
    assert [Path(path).name for path in links()] == ['libscpp_regex.a', 'libpcre2-8.so', 'libscpp_runtime.a'], links()
    native = work / ('native-' + flavor)
    request = dict(action='build', cwd=str(work), bundle=str(output), native=str(native),
                   config=dict(cxx=cxx, flags=['-std=c++23', '-stdlib=libc++', '-O2', '-g0', '-ftime-trace',
                                               '-I', str(root/'runtime/generated/public')],
                               link_flags=['--ld-path=' + mold], jobs=8, cache=str(work/'qualification')))
    def build():
        host = subprocess.run([php, root/'compiler/tests/native_build_host.php'], input=json.dumps(request)+'\n',
                              text=True, capture_output=True, check=True)
        report = json.loads(host.stdout)['result']
        assert report['exit_code'] == 0, report
        subprocess.run([report['executable']], check=True)
        return report

    first_build = build()
    if flavor == 'php':
        for name in ('regex_named_order', 'regex_capture_allocations'):
            executable = work / name
            subprocess.run([cxx, '-std=c++23', '-stdlib=libc++', '-O2', '--ld-path=' + mold,
                            '-I', root/'runtime/generated/public', root/'compiler/tests'/f'{name}.cpp',
                            *links(), '-o', executable], check=True)
            subprocess.run([executable], check=True)
        failed = subprocess.run([first_build['executable'], 'failed-split'], capture_output=True, text=True)
        assert failed.returncode == 1 and failed.stderr.strip() == 'carrier_no_value', failed
        for args, message in [(['invalid', 'pattern'], 'preg_filter: invalid pattern'),
                              (['invalid', 'utf', 'subject'], 'preg_filter: matching failed')]:
            failed = subprocess.run([first_build['executable'], *args], capture_output=True, text=True)
            assert failed.returncode == 1 and message in failed.stderr, failed
    # Recompile the complete fixture with the toolchain already qualified.
    for obj in native.glob('unit-*.o'):
        obj.unlink()
    report = build()
    total = {}
    for path in native.glob('unit-*.json'):
        for event in json.loads(path.read_text())['traceEvents']:
            if event['name'] in ('Total Frontend', 'Total Backend'):
                total[event['name']] = total.get(event['name'], 0) + event['dur']/1000
    link = next(row.split('\t') for row in reversed((native/'.ninja_log').read_text().splitlines()) if '\tprogram\t' in row)
    print(flavor, report['qualification'], 'native ms', round(report['native_ms'], 1),
          total, 'link ms', int(link[1])-int(link[0]), flush=True)


with tempfile.TemporaryDirectory(prefix='scpp-regex-') as directory:
    work = Path(directory)
    runtime = work/'runtime'
    runtime.mkdir()
    for name in ('module.json', 'regex.module.json'):
        shutil.copy(root/'runtime'/name, runtime/name)
    (work/'build').mkdir()
    (work/'build/clang').symlink_to(build, target_is_directory=True)
    for flavor in ('php', 'cpp', 'js'):
        prove(work, runtime, flavor)
