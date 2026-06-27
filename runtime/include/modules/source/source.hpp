#pragma once

#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/string_t.hpp"
#include "scpp/support/hash_t.hpp"

#include <cstddef>
#include <cstdint>
#include <limits>
#include <string>
#include <string_view>

namespace scpp::source {

class byte_span;

class source_buffer final {
private:
	std::string bytes_;
	std::uint64_t generation_ = 1;

	void check_bounds(const std::size_t offset, const std::size_t length, const char *context) const {
		if (offset > bytes_.size() || length > bytes_.size() - offset) {
			throw runtime_error(std::string(context), "source buffer byte range is out of bounds");
		}
	}

public:
	source_buffer() = default;

	explicit source_buffer(std::string bytes)
		: bytes_(std::move(bytes)) {
	}

	[[nodiscard]] std::size_t byte_size() const noexcept {
		return bytes_.size();
	}

	[[nodiscard]] bool empty() const noexcept {
		return bytes_.empty();
	}

	[[nodiscard]] std::uint64_t generation() const noexcept {
		return generation_;
	}

	[[nodiscard]] std::string_view view() const noexcept {
		return std::string_view(bytes_);
	}

	[[nodiscard]] unsigned char byte_at(const std::size_t offset) const {
		check_bounds(offset, 1U, "source_buffer_byte_at");
		return static_cast<unsigned char>(bytes_[offset]);
	}

	[[nodiscard]] string_t slice(const std::size_t offset, const std::size_t length) const {
		check_bounds(offset, length, "source_buffer_slice");
		return string_t(bytes_.substr(offset, length));
	}

	[[nodiscard]] byte_span span(std::size_t offset, std::size_t length) const;

	[[nodiscard]] string_t release() {
		std::string out = std::move(bytes_);
		bytes_.clear();
		++generation_;
		return string_t(std::move(out));
	}
};

class byte_span final {
private:
	const source_buffer *owner_ = nullptr;
	std::size_t offset_ = 0;
	std::size_t length_ = 0;
	std::uint64_t generation_ = 0;

	[[nodiscard]] const source_buffer &owner(const char *context) const {
		if (owner_ == nullptr || owner_->generation() != generation_) {
			throw runtime_error(std::string(context), "byte span source buffer is no longer valid");
		}
		return *owner_;
	}

public:
	byte_span() = default;

	byte_span(const source_buffer &owner, const std::size_t offset, const std::size_t length)
		: owner_(&owner), offset_(offset), length_(length), generation_(owner.generation()) {
	}

	[[nodiscard]] std::size_t byte_size() const {
		static_cast<void>(owner("byte_span_len"));
		return length_;
	}

	[[nodiscard]] unsigned char byte_at(const std::size_t offset) const {
		const auto &buffer = owner("byte_span_at");
		if (offset >= length_) {
			throw runtime_error("byte_span_at", "byte span offset is out of bounds");
		}
		return buffer.byte_at(offset_ + offset);
	}

	[[nodiscard]] std::string_view view() const {
		const auto &buffer = owner("byte_span_view");
		return buffer.view().substr(offset_, length_);
	}

	[[nodiscard]] string_t to_string() const {
		return string_t(std::string(view()));
	}
};

inline byte_span source_buffer::span(const std::size_t offset, const std::size_t length) const {
	check_bounds(offset, length, "source_buffer_span");
	return byte_span(*this, offset, length);
}

namespace detail {

[[nodiscard]] inline std::uint32_t checked_u32_size(const std::size_t value, const char *context) {
	if (value > static_cast<std::size_t>(std::numeric_limits<std::uint32_t>::max())) {
		throw runtime_error(std::string(context), "byte length does not fit uint32");
	}
	return static_cast<std::uint32_t>(value);
}

[[nodiscard]] inline std::uint32_t checked_u32_arg(const int_t<> &value, const char *context) {
	const auto native = value.native_value();
	if (native < 0 || native > static_cast<std::int64_t>(std::numeric_limits<std::uint32_t>::max())) {
		throw runtime_error(std::string(context), "byte offset or length does not fit uint32");
	}
	return static_cast<std::uint32_t>(native);
}

[[nodiscard]] inline string_t hash_to_hex(const std::uint64_t value) {
	char buffer[17];
	static constexpr char digits[] = "0123456789abcdef";
	for (int i = 15; i >= 0; --i) {
		buffer[i] = digits[(value >> ((15 - i) * 4)) & 0x0F];
	}
	buffer[16] = '\0';
	return string_t(std::string(buffer, 16));
}

} // namespace detail

[[nodiscard]] inline source_buffer source_buffer_take(string_t &text) {
	source_buffer buffer(text.native_value());
	text._unset_();
	return buffer;
}

[[nodiscard]] inline string_t source_buffer_release(source_buffer &buffer) {
	return buffer.release();
}

[[nodiscard]] inline int_t<std::uint32_t> source_buffer_byte_len(const source_buffer &buffer) {
	return int_t<std::uint32_t>(detail::checked_u32_size(buffer.byte_size(), "source_buffer_byte_len"));
}

[[nodiscard]] inline int_t<std::uint8_t> source_buffer_byte_at(const source_buffer &buffer, const int_t<> &offset) {
	return int_t<std::uint8_t>(buffer.byte_at(detail::checked_u32_arg(offset, "source_buffer_byte_at")));
}

[[nodiscard]] inline byte_span source_buffer_span(
	const source_buffer &buffer,
	const int_t<> &offset,
	const int_t<> &length
) {
	return buffer.span(detail::checked_u32_arg(offset, "source_buffer_span"), detail::checked_u32_arg(length, "source_buffer_span"));
}

[[nodiscard]] inline string_t source_buffer_slice(
	const source_buffer &buffer,
	const int_t<> &offset,
	const int_t<> &length
) {
	return buffer.slice(detail::checked_u32_arg(offset, "source_buffer_slice"), detail::checked_u32_arg(length, "source_buffer_slice"));
}

[[nodiscard]] inline int_t<std::uint32_t> byte_span_len(const byte_span &span) {
	return int_t<std::uint32_t>(detail::checked_u32_size(span.byte_size(), "byte_span_len"));
}

[[nodiscard]] inline int_t<std::uint8_t> byte_span_at(const byte_span &span, const int_t<> &offset) {
	return int_t<std::uint8_t>(span.byte_at(detail::checked_u32_arg(offset, "byte_span_at")));
}

[[nodiscard]] inline string_t byte_span_to_string(const byte_span &span) {
	return span.to_string();
}

[[nodiscard]] inline string_t hash_bytes(const byte_span &span) {
	return detail::hash_to_hex(scpp::hash_detail::key_ops<string_t>::hash(span.to_string()));
}

} // namespace scpp::source
