namespace scpp {
namespace detail { struct @name@_state; struct @name@_access; }
class @name@ final {
    detail::@name@_state* state_ = nullptr;
    friend struct detail::@name@_access;
public:
    @name@() noexcept = default;
    ~@name@();
    @name@(const @name@&) noexcept;
    @name@(@name@&&) noexcept;
    @name@& operator=(const @name@&) noexcept;
    @name@& operator=(@name@&&) noexcept;
};
}
