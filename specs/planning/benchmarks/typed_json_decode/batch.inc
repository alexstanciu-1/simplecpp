// Inserted inside a private copy of pull_reader by build.py.
template<class Emit> void read_integer_run(Emit emit) {
    begin_array();
    auto& frame = frames_.back();
    while (true) {
        const auto checkpoint = pos_;
        skip_whitespace();
        if (peek() == ']') {
            ++pos_;
            path_ = frame.path;
            frames_.pop_back();
            return;
        }
        if (!frame.first) {
            if (peek() != ',') break_to_scalar(checkpoint);
            ++pos_;
            skip_whitespace();
        }
        const char* first = input_.data() + pos_;
        const char* end = input_.data() + input_.size();
        std::int64_t value;
        const auto parsed = std::from_chars(first, end, value);
        const char* digits = first + (first != end && *first == '-');
        // Defer malformed tokens to the ordinary reader for its exact error and path.
        if (parsed.ec != std::errc{} || (digits < parsed.ptr && *digits == '0' && parsed.ptr - digits > 1)
            || (parsed.ptr < end && (*parsed.ptr == '.' || *parsed.ptr == 'e' || *parsed.ptr == 'E'))) {
            break_to_scalar(checkpoint);
        }
        pos_ = static_cast<std::size_t>(parsed.ptr - input_.data());
        frame.first = false;
        ++frame.index;
        emit(value);
    }
}
[[noreturn]] void break_to_scalar(std::size_t checkpoint) {
    pos_ = checkpoint;
    if (next_element()) (void)read_integer();
    throw std::logic_error("integer fast path and scalar reader disagree");
}
