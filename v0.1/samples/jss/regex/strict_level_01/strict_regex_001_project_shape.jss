let err: error;

let caps: vector<string> = [];
if (!take(caps, regex_match("/(ab+)-(cd+)/i", "xxAbb-cDDyy"))) {
    print("MATCH_ERROR\n");
} else {
    print(caps[0], "\n");
    print(caps[1], "\n");
    print(caps[2], "\n");
}

let parts: vector<string> = [];
if (!take(parts, regex_split("/,/", "a,b,c"))) {
    print("SPLIT_ERROR\n");
} else {
    print(implode("|", parts), "\n");
}

let replaced: string = "";
if (!take(replaced, regex_replace("/ab+/i", "X", "ab xx ABB yy"))) {
    print("REPLACE_ERROR\n");
} else {
    print(replaced, "\n");
}
