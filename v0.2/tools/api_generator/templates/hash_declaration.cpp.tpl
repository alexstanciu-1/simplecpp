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
