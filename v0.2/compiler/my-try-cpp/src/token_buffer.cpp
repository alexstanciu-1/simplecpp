#include "scpp_compiler/token_buffer.hpp"
#include <memory>
#include <stdexcept>
#include <string>
#include <utility>

namespace scpp::compiler_support
{
	struct token_buffer::state {
		std::shared_ptr<const std::string> content;
	};

	token_buffer::token_buffer() noexcept = default;

	token_buffer::token_buffer(const token_buffer &other)
		: state_(other.state_ ? new state(*other.state_) : nullptr)
	{
	}

	token_buffer::token_buffer(token_buffer &&other) noexcept
	{
		this->swap(other);
	}

	token_buffer &token_buffer::operator=(const token_buffer &other)
	{
		token_buffer candidate(other);
		this->swap(candidate);
		return *this;
	}

	token_buffer &token_buffer::operator=(token_buffer &&other) noexcept
	{
		if (this != &other) {
			token_buffer candidate(std::move(other));
			this->swap(candidate);
		}
		return *this;
	}

	token_buffer::~token_buffer() noexcept
	{
		delete this->state_;
	}

	/**
	 * Validate host length before reading borrowed memory, then publish a complete
	 * immutable snapshot. The caller guarantees the supplied byte range is readable.
	 */
	token_buffer token_buffer::from_bytes(const char *bytes, uint64 length)
	{
		if (!bytes) {
			throw std::invalid_argument("Null source bytes");
		}
		const auto count = length.native_value();
		if (count > std::string{}.max_size()) {
			throw std::length_error("Source snapshot exceeds host string capacity");
		}
		auto content = std::make_shared<const std::string>(bytes, static_cast<std::size_t>(count));
		token_buffer result;
		result.state_ = new state{std::move(content)};
		return result;
	}

	bool_t token_buffer::present() const noexcept
	{
		return bool_t{this->state_ != nullptr};
	}

	uint64 token_buffer::byte_size() const noexcept
	{
		return uint64{this->state_ ? this->state_->content->size() : std::size_t{0}};
	}

	int32 token_buffer::peek(uint64 offset) const
	{
		const auto index = offset.native_value();
		if (!this->state_ || index >= this->state_->content->size()) {
			return int32{-1};
		}
		return int32{static_cast<unsigned int>(static_cast<unsigned char>((*this->state_->content)[index]))};
	}

	uint32 token_buffer::byte_at(uint64 offset) const
	{
		const auto byte = this->peek(offset);
		if (byte.native_value() < 0) {
			throw std::out_of_range("Source buffer byte index");
		}
		return static_cast<uint32>(byte);
	}

	/**
	 * Check bounds by subtraction, avoiding overflow for hostile lookahead spans.
	 * Compare in place without a substring allocation.
	 */
	bool_t token_buffer::matches(uint64 offset, uint64 length, const char *literal) const
	{
		if (!literal) {
			throw std::invalid_argument("Null token spelling");
		}
		if (!this->state_) {
			return bool_t{false};
		}
		const auto start = offset.native_value();
		const auto count = length.native_value();
		const auto &bytes = *this->state_->content;
		return bool_t{start <= bytes.size() && count <= bytes.size() - start &&
			std::char_traits<char>::length(literal) == count && bytes.compare(start, count, literal) == 0};
	}

	bool_t token_buffer::same_storage(const token_buffer &other) const noexcept
	{
		return bool_t{this->state_ && other.state_ && this->state_->content == other.state_->content};
	}

	void token_buffer::swap(token_buffer &other) noexcept
	{
		std::swap(this->state_, other.state_);
	}
}
