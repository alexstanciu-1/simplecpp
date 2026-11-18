class @n@ final {
public:
@lh@@methods@    @nullable@ find(const @k@& key) const;
    // Compiler traversal API: physical positions, not live entry count.
    @count@ @limit_method@() const;
    bool_t @next_method@(@count@& position, @count@ limit, @v@& value) const;
    bool_t @next_method@(@count@& position, @count@ limit, @k@& key, @v@& value) const;
