namespace str {
string @name@(@args@, int64& count) {
    int64 matches = 0;
    auto result = @name@_impl(subject, search, replacement, mode, [&matches] { ++matches; });
    count = matches;
    return result;
}
}
