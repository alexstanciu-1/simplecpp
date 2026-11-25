// Benchmark only. A scan consumes its private input after retaining the result source.
#pragma once
#include <scpp/string.hpp>
#include <stdexcept>
#include <utility>

namespace tokenizer_bench {
class checked_byte_view final {
    const scpp::string owner_;
    const char* const bytes_;
    const scpp::int64 size_;

    [[noreturn, gnu::noinline]] static void invalid_index() {
        throw std::out_of_range("sequence_index_out_of_range");
    }

public:
    explicit checked_byte_view(scpp::string&& source)
        : owner_(std::move(source)), bytes_(owner_.data()), size_(owner_.size()) {}
    checked_byte_view(const checked_byte_view&) = delete;
    checked_byte_view& operator=(const checked_byte_view&) = delete;

    scpp::int64 size() const noexcept { return size_; }
    scpp::uint8 byte_at(scpp::int64 index) const {
        if (index < 0 || index >= size_) invalid_index();
        return static_cast<scpp::uint8>(bytes_[index]);
    }
};
}
