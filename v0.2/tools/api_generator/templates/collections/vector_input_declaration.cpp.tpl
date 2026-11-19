    // Compiler-private ownership of a fully constructed heap input array.
    // Local arrays use native scope cleanup; this cap is constrained by the byte budget.
    static_assert(sizeof(@e@) * @inline_limit@ <= @stack_bytes@, "initializer inline limit exceeds byte budget");
    class @input@ final {
        @e@* data_;
    public:
        explicit @input@(@e@* values) noexcept : data_(values) {}
        @input@(const @input@&) = delete;
        @input@& operator=(const @input@&) = delete;
        ~@input@() noexcept;
        @e@* data() const noexcept { return data_; }
    };
    static @n@ @read_only@(const @e@* values, @count@ count);
