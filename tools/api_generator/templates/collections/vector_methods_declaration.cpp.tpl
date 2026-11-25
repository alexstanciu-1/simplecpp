    @count@ size() const;
    bool_t empty() const noexcept;
    void clear() noexcept;
    bool_t remove(@count@ index);
    bool_t has(@count@ index) const noexcept;
    void append(@e@ value);
    @e@ at(@count@ index) const;
    void set(@count@ index, @e@ value);
    // Compiler traversal API: caller keeps this wrapper alive; no element references escape.
    @e@& iteration_reference(@count@ index);
    @count@ @limit_method@() const;
    bool_t @next_method@(@count@& position, @count@ limit, @e@& value) const;
    bool_t @next_method@(@count@& position, @count@ limit, @count@& key, @e@& value) const;
