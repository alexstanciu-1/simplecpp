#pragma once

#include "modules/source/source.hpp"
#include "scpp/runtime_error.hpp"
#include "scpp/string_t.hpp"

#include <cstddef>
#include <cstdint>
#include <limits>
#include <string>
#include <string_view>
#include <utility>

namespace scpp::binary {

// Low-level little-endian binary codec for compiler cache/artifact payloads.
//
// This deliberately stays schema-agnostic. Higher layers own record versions,
// field tags, and compatibility; this module only writes and reads typed bytes.
class writer final {
private:
	std::string bytes_;

public:
	writer() = default;

	void reserve(const std::size_t byte_capacity) {
		bytes_.reserve(byte_capacity);
	}

	[[nodiscard]] std::size_t size() const noexcept {
		return bytes_.size();
	}

	[[nodiscard]] const std::string &native_bytes() const noexcept {
		return bytes_;
	}

	[[nodiscard]] string_t to_string() const {
		return string_t(bytes_);
	}

	[[nodiscard]] std::string take_bytes() {
		return std::move(bytes_);
	}

	void write_uint8(const std::uint8_t value) {
		bytes_.push_back(static_cast<char>(value));
	}

	void write_uint16(const std::uint16_t value) {
		bytes_.push_back(static_cast<char>(value & 0xFFU));
		bytes_.push_back(static_cast<char>((value >> 8U) & 0xFFU));
	}

	void write_uint32(const std::uint32_t value) {
		for (std::uint32_t shift = 0; shift < 32U; shift += 8U) {
			bytes_.push_back(static_cast<char>((value >> shift) & 0xFFU));
		}
	}

	void write_uint64(const std::uint64_t value) {
		for (std::uint32_t shift = 0; shift < 64U; shift += 8U) {
			bytes_.push_back(static_cast<char>((value >> shift) & 0xFFULL));
		}
	}

	void write_bytes(const std::string_view bytes) {
		bytes_.append(bytes.data(), bytes.size());
	}

	void write_byte_span(const source::byte_span &span) {
		write_bytes(span.view());
	}

	void write_string(const string_t &value) {
		const auto &native = value.native_value();
		if (native.size() > static_cast<std::size_t>(std::numeric_limits<std::uint32_t>::max())) {
			throw runtime_error("binary_write_string", "string length does not fit uint32");
		}
		write_uint32(static_cast<std::uint32_t>(native.size()));
		write_bytes(native);
	}
};

class reader final {
private:
	std::string_view bytes_;
	std::size_t offset_ = 0;

	void require_available(const std::size_t byte_count, const char *context) const {
		if (byte_count > remaining()) {
			throw runtime_error(std::string(context), "truncated binary input");
		}
	}

	[[nodiscard]] std::uint8_t read_raw_byte(const char *context) {
		require_available(1U, context);
		return static_cast<std::uint8_t>(static_cast<unsigned char>(bytes_[offset_++]));
	}

public:
	reader() = default;

	explicit reader(const std::string_view bytes)
		: bytes_(bytes) {
	}

	explicit reader(const string_t &bytes)
		: bytes_(bytes.native_value()) {
	}

	[[nodiscard]] std::size_t offset() const noexcept {
		return offset_;
	}

	[[nodiscard]] std::size_t size() const noexcept {
		return bytes_.size();
	}

	[[nodiscard]] std::size_t remaining() const noexcept {
		return bytes_.size() - offset_;
	}

	[[nodiscard]] bool done() const noexcept {
		return offset_ == bytes_.size();
	}

	[[nodiscard]] std::uint8_t read_uint8() {
		return read_raw_byte("binary_read_uint8");
	}

	[[nodiscard]] std::uint16_t read_uint16() {
		std::uint16_t value = 0;
		for (std::uint32_t shift = 0; shift < 16U; shift += 8U) {
			value |= static_cast<std::uint16_t>(read_raw_byte("binary_read_uint16")) << shift;
		}
		return value;
	}

	[[nodiscard]] std::uint32_t read_uint32() {
		std::uint32_t value = 0;
		for (std::uint32_t shift = 0; shift < 32U; shift += 8U) {
			value |= static_cast<std::uint32_t>(read_raw_byte("binary_read_uint32")) << shift;
		}
		return value;
	}

	[[nodiscard]] std::uint64_t read_uint64() {
		std::uint64_t value = 0;
		for (std::uint32_t shift = 0; shift < 64U; shift += 8U) {
			value |= static_cast<std::uint64_t>(read_raw_byte("binary_read_uint64")) << shift;
		}
		return value;
	}

	[[nodiscard]] std::string_view read_bytes(const std::size_t byte_count) {
		require_available(byte_count, "binary_read_bytes");
		const auto out = bytes_.substr(offset_, byte_count);
		offset_ += byte_count;
		return out;
	}

	[[nodiscard]] string_t read_string() {
		const auto byte_count = read_uint32();
		const auto view = read_bytes(byte_count);
		return string_t(std::string(view));
	}
};

} // namespace scpp::binary
