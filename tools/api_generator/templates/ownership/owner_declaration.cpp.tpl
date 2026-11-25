class @n@ final {
public:
    @n@() noexcept;
    ~@n@() noexcept;
    @n@(@n@&& other) noexcept;
    @n@& operator=(@n@&& other) noexcept;
    @n@(const @n@& other)@copy_spec@;
    @n@& operator=(const @n@& other)@copy_spec@;
    void reset() noexcept;
    void swap(@n@& other) noexcept;
