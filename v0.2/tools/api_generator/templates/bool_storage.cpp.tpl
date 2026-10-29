    explicit constexpr operator bool() const noexcept { return value_; }
    constexpr bool native_value() const noexcept { return value_; }
private:
    bool value_ = false;
