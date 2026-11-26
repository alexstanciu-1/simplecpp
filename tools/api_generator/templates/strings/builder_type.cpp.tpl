class @type@ final {
    struct storage;
    storage* data_ = nullptr;
    storage& writable();
public:
    @type@() noexcept = default;
    ~@type@();
    @type@(const @type@&);
    @type@(@type@&&) noexcept;
    @type@& operator=(const @type@&);
    @type@& operator=(@type@&&) noexcept;
@methods@};
