    constexpr @rep@ native_value() const noexcept { return value_; }
private:
    friend struct detail::integer_access;
    @rep@ value_ = 0;
};
