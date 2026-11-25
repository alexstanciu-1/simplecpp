#pragma once
#include <stdexcept>
#include <utility>
#include <variant>

namespace scpp::detail {
// Private adaptation of nullable/result_core state and checked extraction. The
// generated concrete wrapper controls which states and constructors are exposed.
template<class Payload, class Error = std::monostate>
class carrier_storage {
    std::variant<std::monostate, Payload, Error> content_;
    bool true_state_ = false;
public:
    carrier_storage() = default;
    explicit carrier_storage(Payload value)
        : content_(std::in_place_index<1>, std::move(value)) {}
    static carrier_storage failure(Error error) {
        if (!static_cast<bool>(error.has_error()))
            throw std::invalid_argument("result_requires_real_error");
        return carrier_storage(std::in_place_index<2>, std::move(error));
    }
    static carrier_storage sentinel_true() {
        carrier_storage out; out.true_state_ = true; return out;
    }
    bool has_value() const noexcept { return content_.index() == 1; }
    bool has_error() const noexcept { return content_.index() == 2; }
    bool is_false() const noexcept { return content_.index() == 0 && !true_state_; }
    bool is_true() const noexcept { return content_.index() == 0 && true_state_; }
    Payload value() const {
        if (!has_value()) throw std::runtime_error("carrier_no_value");
        return std::get<1>(content_);
    }
    Error error() const {
        if (!has_error()) throw std::runtime_error("result_no_error");
        return std::get<2>(content_);
    }
private:
    carrier_storage(std::in_place_index_t<2>, Error error)
        : content_(std::in_place_index<2>, std::move(error)) {}
};
}
