    @count@ size() const;
    bool_t empty() const noexcept;
    bool_t has(const @k@& key) const;
    @v@ at(const @k@& key) const;
    @v@& checked_slot(const @k@& key) &;
    void set(@k@ key, @v@ value);
    bool_t remove(const @k@& key);
    void clear() noexcept;
