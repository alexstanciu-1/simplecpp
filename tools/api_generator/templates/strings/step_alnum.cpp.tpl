namespace str {
template<bool increment>
static string step_alnum(const string& value) {
    const auto input = detail::string_access::read(value);
    constexpr auto argument = increment ? "str_increment(): Argument #1 ($string) " : "str_decrement(): Argument #1 ($string) ";
    if (input.empty()) throw ValueError(std::string(argument) + "must not be empty");
    for (char ch : input) {
        if ((ch < '0' || ch > '9') && (ch < 'A' || ch > 'Z') && (ch < 'a' || ch > 'z'))
            throw ValueError(std::string(argument) + "must be composed only of alphanumeric ASCII characters");
    }
    if constexpr (!increment) {
        if (input.front() == '0' || input == "a" || input == "A")
            throw ValueError(std::string(argument) + "\"" + std::string(input) + "\" is out of decrement range");
    }

    std::string bytes(input);
    for (std::size_t i = bytes.size(); i > 0; --i) {
        char& ch = bytes[i - 1];
        const char first = ch <= '9' ? '0' : (ch <= 'Z' ? 'A' : 'a');
        const char last = first == '0' ? '9' : (first == 'A' ? 'Z' : 'z');
        if constexpr (increment) {
            if (ch != last) { ++ch; break; }
            ch = first;
            if (i == 1) bytes.insert(bytes.begin(), first == '0' ? '1' : first);
        } else {
            if (i == 1 && bytes.size() > 1 && (ch == '1' || ch == first)) {
                bytes.erase(0, 1);
                break;
            }
            if (ch != first) { --ch; break; }
            ch = last;
        }
    }
    return detail::string_access::own(std::move(bytes));
}
}
