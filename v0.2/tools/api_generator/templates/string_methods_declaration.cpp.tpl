    @count@ size() const;
    bool_t empty() const noexcept;
    const char* data() const noexcept;
    void append(const @n@&);
    void clear() noexcept;
private:
    struct storage;
    storage* storage_ = nullptr;
    friend struct detail::string_access;
};
