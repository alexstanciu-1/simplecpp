class error_t final {
public:
    error_t() = default;
    explicit error_t(string message, int64 line = int64{}, string file = string{});
    static const error_t no_error;
    bool_t has_error() const noexcept;
    explicit operator bool() const noexcept;
    string get_message() const;
    int64 get_line() const noexcept;
    string get_file() const;
private:
    bool present_ = false;
    string message_;
    int64 line_;
    string file_;
};
