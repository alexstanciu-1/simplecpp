class @n@ final {
public:
@lh@    @count@ size() const;
    bool_t empty() const noexcept;
    bool_t has(const @k@& key) const;
    @v@ at(const @k@& key) const;
    @nullable@ find(const @k@& key) const;
    void set(@k@ key, @v@ value);
    bool_t remove(const @k@& key);
    void clear() noexcept;
    // Compiler traversal API: physical positions, not live entry count.
    @count@ @limit_method@() const;
    bool_t @next_method@(@count@& position, @count@ limit, @v@& value) const;
    bool_t @next_method@(@count@& position, @count@ limit, @k@& key, @v@& value) const;
