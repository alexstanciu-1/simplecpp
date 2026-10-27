#include "scalar_access.hpp"
#include "scpp_compiler/textbuilder.hpp"
#include "modules/strings/strings.hpp"
#include "scpp/util/global_string_pool.hpp"
#include "string_state.hpp"

namespace scpp::compiler_support
{

	struct textbuilder::state {
		str::text_builder buffer;
	};

	textbuilder::textbuilder() : state_(new state)
	{
	}

	textbuilder::~textbuilder() noexcept
	{
		delete this->state_;
	}

	void textbuilder::append(const char *text)
	{
		this->state_->buffer.append(string_t(text));
	}

	void textbuilder::append(const string &text)
	{
		this->state_->buffer.append(runtime_access::get(text));
	}

	void textbuilder::append_number(uint64 value)
	{
		this->state_->buffer.append(string_t(std::to_string(scalar_access::get(value).native_value())));
	}

	void textbuilder::append_number(int64 value)
	{
		this->state_->buffer.append(string_t(std::to_string(scalar_access::get(value).native_value())));
	}

	string textbuilder::finish() const
	{
		return runtime_access::wrap(this->state_->buffer.to_string());
	}

	boolean textbuilder::equals(const char *text) const
	{
		return this->state_->buffer.to_string().native_value() == string_t(text).native_value();
	}

	boolean textbuilder::roundtrip_through_runtime() const
	{
		const auto text = this->state_->buffer.to_string();
		auto &pool = global_string_pool::instance();
		return pool.resolve(pool.intern(text)).native_value() == text.native_value();
	}

}
