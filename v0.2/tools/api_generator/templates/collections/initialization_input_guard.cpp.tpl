    class @input@ final {
        @e@* data_;
    public:
        explicit @input@(@e@* values) noexcept : data_(values) {}
        @input@(const @input@&) = delete;
        @input@& operator=(const @input@&) = delete;
        ~@input@() noexcept;
        @e@* data() const noexcept { return data_; }
    };
