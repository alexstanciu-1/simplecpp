    @count@ size() const;
    bool_t empty() const noexcept;
    bool_t has(const @k@& key) const;
    // Owned lookup result; absent entries never construct an element.
    struct entry_snapshot {
        using value_type = @v@;
        const bool found;
        union { unsigned char empty; value_type value; };
        entry_snapshot() noexcept : found(false), empty{} {}
        explicit entry_snapshot(const value_type& v) : found(true), value(v) {}
        entry_snapshot(const entry_snapshot&) = delete;
        entry_snapshot& operator=(const entry_snapshot&) = delete;
        ~entry_snapshot() { if (found) value.~value_type(); }
    };
    entry_snapshot @coalescing_lookup@(const @k@& key) const;
    @v@ at(const @k@& key) const;
    @v@& @checked_slot@(const @k@& key) &;
    void set(@k@ key, @v@ value);
    bool_t remove(const @k@& key);
    void clear() noexcept;
