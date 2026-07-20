#pragma once

#include "scpp/int_t.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/string_t.hpp"
#include "scpp/stable_hash.hpp"
#include "scpp/support/hash_t.hpp"

#include <algorithm>
#include <cstddef>
#include <cstdint>
#include <limits>
#include <string>
#include <string_view>
#include <vector>

namespace scpp::source {

class byte_span;

struct source_location final {
	std::uint32_t offset = 0;
	std::uint32_t line = 1;
	std::uint32_t column = 1;
};

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

	} // namespace detail

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

class source_line_index final {
private:
	std::uint32_t source_byte_len_ = 0;
	std::vector<std::uint32_t> line_start_offsets_;

	void check_offset(const std::uint32_t offset, const char *context) const {
		if (offset > source_byte_len_) {
			throw runtime_error(std::string(context), "source offset is out of bounds");
		}
	}

public:
	source_line_index() {
		line_start_offsets_.push_back(0);
	}

	explicit source_line_index(const source_buffer &buffer)
		: source_byte_len_(detail::checked_u32_size(buffer.byte_size(), "source_line_index_build")) {
		line_start_offsets_.push_back(0);
		const auto bytes = buffer.view();
		for (std::size_t offset = 0; offset < bytes.size(); ++offset) {
			const auto ch = bytes[offset];
			if (ch == '\r') {
				if (offset + 1U < bytes.size() && bytes[offset + 1U] == '\n') {
					++offset;
				}
				line_start_offsets_.push_back(detail::checked_u32_size(offset + 1U, "source_line_index_build"));
				continue;
			}
			if (ch == '\n') {
				line_start_offsets_.push_back(detail::checked_u32_size(offset + 1U, "source_line_index_build"));
			}
		}
	}

	[[nodiscard]] std::uint32_t source_byte_len() const noexcept {
		return source_byte_len_;
	}

	[[nodiscard]] std::uint32_t line_count() const noexcept {
		return static_cast<std::uint32_t>(line_start_offsets_.size());
	}

	[[nodiscard]] source_location offset_to_location(const std::uint32_t offset) const {
		check_offset(offset, "source_line_index_offset_to_location");
		const auto it = std::upper_bound(line_start_offsets_.begin(), line_start_offsets_.end(), offset);
		const auto line_index = static_cast<std::size_t>(it == line_start_offsets_.begin() ? 0 : (it - line_start_offsets_.begin() - 1));
		const auto line_start = line_start_offsets_[line_index];
		return source_location{
			.offset = offset,
			.line = static_cast<std::uint32_t>(line_index + 1U),
			.column = static_cast<std::uint32_t>(offset - line_start + 1U),
		};
	}

	[[nodiscard]] std::uint32_t line_column_to_offset(const std::uint32_t line, const std::uint32_t column) const {
		if (line == 0 || line > line_count()) {
			throw runtime_error("source_line_index_line_column_to_offset", "source line is out of bounds");
		}
		if (column == 0) {
			throw runtime_error("source_line_index_line_column_to_offset", "source column is out of bounds");
		}
		const auto line_start = line_start_offsets_[static_cast<std::size_t>(line - 1U)];
		const auto offset = line_start + column - 1U;
		check_offset(offset, "source_line_index_line_column_to_offset");
		return offset;
	}
};

inline byte_span source_buffer::span(const std::size_t offset, const std::size_t length) const {
	check_bounds(offset, length, "source_buffer_span");
	return byte_span(*this, offset, length);
}

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
	return scpp::stable_hash::to_hex(scpp::stable_hash::string_u64(span.to_string()));
}

[[nodiscard]] inline int_t<std::uint64_t> stable_hash_bytes_u64(const byte_span &span) {
	return scpp::stable_hash::string_uint64(span.to_string());
}

[[nodiscard]] inline source_line_index source_line_index_build(const source_buffer &buffer) {
	return source_line_index(buffer);
}

[[nodiscard]] inline int_t<std::uint32_t> source_line_index_line_count(const source_line_index &index) {
	return int_t<std::uint32_t>(index.line_count());
}

[[nodiscard]] inline source_location source_line_index_offset_to_location(const source_line_index &index, const int_t<> &offset) {
	return index.offset_to_location(detail::checked_u32_arg(offset, "source_line_index_offset_to_location"));
}

[[nodiscard]] inline int_t<std::uint32_t> source_line_index_line_column_to_offset(
	const source_line_index &index,
	const int_t<> &line,
	const int_t<> &column
) {
	return int_t<std::uint32_t>(index.line_column_to_offset(
		detail::checked_u32_arg(line, "source_line_index_line_column_to_offset"),
		detail::checked_u32_arg(column, "source_line_index_line_column_to_offset")
	));
}

[[nodiscard]] inline int_t<std::uint32_t> source_location_offset(const source_location &location) {
	return int_t<std::uint32_t>(location.offset);
}

[[nodiscard]] inline int_t<std::uint32_t> source_location_line(const source_location &location) {
	return int_t<std::uint32_t>(location.line);
}

[[nodiscard]] inline int_t<std::uint32_t> source_location_column(const source_location &location) {
	return int_t<std::uint32_t>(location.column);
}

} // namespace scpp::source
