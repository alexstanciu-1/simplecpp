// The caller tries each distinct schema field once in declaration order.
// On a miss the generic row loop continues at the same byte and uses the accumulated mask.
bool match_row_key(std::string_view quoted) {
    auto& scope = frames_.back();
    const auto checkpoint = pos_;
    skip_whitespace();
    if (!scope.first) {
        if (peek() != ',') { pos_ = checkpoint; return false; }
        ++pos_;
        skip_whitespace();
    }
    if (!input_.substr(pos_).starts_with(quoted)) { pos_ = checkpoint; return false; }
    pos_ += quoted.size();
    skip_whitespace();
    if (peek() != ':') { pos_ = checkpoint; return false; }
    ++pos_;
    scope.first = false;
    path_ = scope.path;
    path_.push_back('[');
    path_.append(quoted);
    path_.push_back(']');
    pending_ = true;
    return true;
}
