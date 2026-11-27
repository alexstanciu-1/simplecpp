namespace scpp {
bool exception::same_identity(const exception& other) const noexcept {
    return view == other.view || (view && other.view && @identity@);
}
}
