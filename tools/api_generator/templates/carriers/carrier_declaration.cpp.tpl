class @n@ final {
    struct storage;
public:
@lh@    explicit @n@(@p@ value);
    bool_t has_value() const noexcept;
    @p@ value() const;
    @p@& checked_ref();
    const @p@& checked_ref() const;
    class borrow_guard {
        friend class @n@;
        storage* slot_;
        const @p@* payload_;
        borrow_guard(storage*, const @p@*) noexcept;
    public:
        borrow_guard(const borrow_guard&) = delete;
        borrow_guard& operator=(const borrow_guard&) = delete;
        ~borrow_guard();
        const @p@& operator*() const noexcept { return *payload_; }
    };
    class writable_borrow final : public borrow_guard {
        friend class @n@;
        writable_borrow(storage* slot, @p@* payload) noexcept : borrow_guard(slot, payload) {}
    public:
        @p@& operator*() const noexcept { return const_cast<@p@&>(borrow_guard::operator*()); }
    };
    writable_borrow pin();
    borrow_guard pin() const;
    @explicit@operator @p@() const;
