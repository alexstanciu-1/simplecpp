// Public handles share one single-consumer cursor. Structural validation belongs to pull_reader.
#include "structural_reader.hpp"
#include <memory>

namespace scpp {
struct json_pull_reader::state {
    std::size_t owners = 1;
    bool started = false;
    std::unique_ptr<detail::json::pull_reader> cursor;
};
json_pull_reader::json_pull_reader() : data_(new state{}) {}
json_pull_reader::~json_pull_reader() { if (data_ && --data_->owners == 0) delete data_; }
json_pull_reader::json_pull_reader(const json_pull_reader& other) noexcept : data_(other.data_) {
    if (data_) ++data_->owners;
}
json_pull_reader::json_pull_reader(json_pull_reader&& other) noexcept : data_(std::exchange(other.data_, nullptr)) {}
json_pull_reader& json_pull_reader::operator=(const json_pull_reader& other) noexcept {
    json_pull_reader copy(other);
    std::swap(data_, copy.data_);
    return *this;
}
json_pull_reader& json_pull_reader::operator=(json_pull_reader&& other) noexcept {
    json_pull_reader moved(std::move(other));
    std::swap(data_, moved.data_);
    return *this;
}
result_bool_t json_pull_reader::start(const string& text, const string& file, int64 limit) const {
    if (!data_ || data_->started) return result_bool_t{error_t{string{"JSON reader cannot be restarted"}}};
    data_->started = true;
    try {
        data_->cursor = std::make_unique<detail::json::pull_reader>(
            std::string(text.data(), text.size()), std::string(file.data(), file.size()), limit);
        return result_bool_t{true};
    } catch (const detail::json::read_error& failure) {
        return result_bool_t{error_t{string{failure.what()}}};
    }
}
namespace detail::json {
template<class Result, class Operation>
static Result read_checked(pull_reader* cursor, Operation op) {
    if (!cursor) return Result{error_t{string{"JSON reader is not initialized"}}};
    try {
        return Result{op(*cursor)};
    } catch (const read_error& failure) {
        return Result{error_t{string{failure.what()}}};
    }
}
}
}
