#include "scpp_compiler/token_buffer.hpp"
#include "scalar_access.hpp"
#include "string_state.hpp"
#include "modules/filesystem/filesystem.hpp"
#include "scpp/memory.hpp"
#include <stdexcept>
#include <utility>

namespace scpp::compiler_support
{
	struct token_buffer::state {
		shared_p<const string_t> content;
	};

	token_buffer::token_buffer() noexcept = default;

	token_buffer::token_buffer(const token_buffer &other)
		: state_(other.state_ == nullptr ? nullptr : new state(*other.state_))
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
	 * Own exactly the checked bytes; a failed read cannot create a published buffer.
	 */
	token_buffer token_buffer::read(const string &path, int64 mtime, int64 bytes)
	{
		auto result = fs::read_snapshot(runtime_access::get(path), scalar_access::get(mtime), scalar_access::get(bytes));
		if (!result.has_value().native_value()) {
			throw std::runtime_error("Cannot read source " + runtime_access::get(path).native_value() + ": " +
				result.require_error("source read").get_message().native_value());
		}
		token_buffer buffer;
		buffer.state_ = new state{shared<const string_t>(std::move(result.value()))};
		return buffer;
	}

	token_buffer token_buffer::from_text(const string &text)
	{
		token_buffer result;
		result.state_ = new state{shared<const string_t>(runtime_access::get(text))};
		return result;
	}

	int32 token_buffer::peek(uint64 offset) const
	{
		const auto index = scalar_access::get(offset).native_value();
		if (!this->state_ || index >= this->state_->content->byte_size()) {
			return -1;
		}
		return static_cast<unsigned char>(this->state_->content->native_value()[index]);
	}

	/**
	 * Compare borrowed spelling directly; no substring or temporary string is created.
	 */
	boolean token_buffer::matches(uint64 offset, uint64 length, const char *literal) const
	{
		if (!this->state_) {
			return false;
		}
		const auto start = scalar_access::get(offset).native_value();
		const auto size = scalar_access::get(length).native_value();
		const auto &bytes = this->state_->content->native_value();
		return start <= bytes.size() && size <= bytes.size() - start &&
			std::char_traits<char>::length(literal) == size && bytes.compare(start, size, literal) == 0;
	}

	boolean token_buffer::present() const noexcept
	{
		return this->state_ != nullptr;
	}

	uint64 token_buffer::byte_size() const noexcept
	{
		return this->state_ == nullptr ? 0 : this->state_->content->byte_size();
	}

	uint32 token_buffer::byte_at(uint64 offset) const
	{
		if (!this->present() || offset >= this->byte_size()) {
			throw std::out_of_range("Source buffer byte index");
		}
		return static_cast<unsigned char>(this->state_->content->native_value()[scalar_access::get(offset).native_value()]);
	}

	boolean token_buffer::equals(const string &text) const
	{
		return this->state_ != nullptr && this->state_->content->native_value() == runtime_access::get(text).native_value();
	}

	boolean token_buffer::same_storage(const token_buffer &other) const noexcept
	{
		return this->state_ != nullptr && other.state_ != nullptr && this->state_->content.get() == other.state_->content.get();
	}

	void token_buffer::swap(token_buffer &other) noexcept
	{
		std::swap(this->state_, other.state_);
	}
}
