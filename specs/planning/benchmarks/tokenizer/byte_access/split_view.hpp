// Isolated scanner prototype. The immutable owner is a separate, enclosing local.
#pragma once
#include <scpp/string.hpp>
#include <stdexcept>

namespace tokenizer_bench {
class split_view final {
    const char* const bytes_;
    const scpp::int64 size_;

    [[noreturn, gnu::noinline]] static void invalid_index() {
        throw std::out_of_range("sequence_index_out_of_range");
    }

public:
    explicit split_view(const scpp::string& owner)
        : bytes_(owner.data()), size_(owner.size()) {}
    split_view(const scpp::string&&) = delete;
    split_view(const split_view&) = delete;
    split_view& operator=(const split_view&) = delete;

    scpp::int64 size() const noexcept { return size_; }
    scpp::uint8 byte_at(scpp::int64 index) const {
        if (index < 0 || index >= size_) invalid_index();
        return static_cast<scpp::uint8>(bytes_[index]);
    }
};
}
