const error_t error_t::no_error{};
error_t::error_t(string message, int64 line, string file) : present_(true), message_(std::move(message)), line_(line), file_(std::move(file)) {}
bool_t error_t::has_error() const noexcept { return bool_t{present_}; }
error_t::operator bool() const noexcept { return present_; }
string error_t::get_message() const { return message_; }
int64 error_t::get_line() const noexcept { return line_; }
string error_t::get_file() const { return file_; }
