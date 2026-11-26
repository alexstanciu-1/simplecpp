namespace scpp::detail {
struct @name@_access {
    static @name@ adopt(@name@_state* state) noexcept { @name@ value; value.state_ = state; return value; }
    static @name@_state* get(const @name@& value) noexcept { return value.state_; }
};
}
