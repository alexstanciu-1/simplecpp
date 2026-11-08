    static constexpr unsigned inline_capacity = @inline_capacity@;
    bool is_inline() const noexcept { return rep_.small.tag <= inline_capacity; }
    @count@ size() const;
    bool_t empty() const noexcept;
    const char* data() const noexcept;
    void append(const @n@&);
    void clear() noexcept;
private:
    struct storage;
    // Common initial tag is readable in either standard-layout union member.
    struct short_form { unsigned char tag; char bytes[inline_capacity + 1]; };
    struct long_form { unsigned char tag; storage* ptr; };
    union representation { short_form small{}; long_form large; } rep_;
    friend struct detail::string_access;
};
static_assert(sizeof(@n@) == 24);
