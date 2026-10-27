#include "scalar_access.hpp"
#include "scpp_compiler/json_value.hpp"
#include "string_state.hpp"
#include "modules/json/document.hpp"
#include "scpp/vector_t.hpp"
#include <limits>
#include <stdexcept>

namespace scpp::compiler_support
{
	struct json_value::state {
		bindings::native_json_value node;
	};

	struct vector_json::state {
		bindings::native_vector_json nodes;
	};

	json_value::json_value() : state_(new state)
	{
	}

	json_value::json_value(const json_value &other) : state_(new state{other.state_->node})
	{
	}

	json_value &json_value::operator=(const json_value &other)
	{
		this->state_->node = other.state_->node;
		return *this;
	}

	json_value::~json_value() noexcept
	{
		delete this->state_;
	}

	/**
	 * Parse an owning document and retain its root through the concrete node wrapper.
	 */
	json_value json_value::parse(const string &text)
	{
		shared_p<json::parse_error> error;
		auto parsed = json::document_parse(runtime_access::get(text), error);
		if (!parsed.has_value().native_value()) {
			throw std::runtime_error(parsed.require_error("parse").get_message().native_value());
		}
		json_value result;
		result.state_->node = json::document_root(parsed.value()).value();
		return result;
	}

	/**
	 * Translate the runtime node-kind vocabulary into the compiler-facing enumeration.
	 */
	json_kind json_value::kind() const
	{
		const auto value = json::node_kind(this->state_->node).value().native_value();
		if (value == "null") {
			return json_kind::null_value;
		}
		if (value == "boolean") {
			return json_kind::boolean;
		}
		if (value == "number") {
			return json_kind::number;
		}
		if (value == "string") {
			return json_kind::text;
		}
		if (value == "array") {
			return json_kind::array;
		}
		if (value == "object") {
			return json_kind::object;
		}
		throw std::logic_error("unknown JSON kind");
	}

	int64 json_value::size() const
	{
		return json::node_size(this->state_->node).value().native_value();
	}

	boolean json_value::has(const string &name) const
	{
		return json::node_has(this->state_->node, runtime_access::get(name)).value().native_value();
	}

	boolean json_value::has(const char *name) const
	{
		return json::node_has(this->state_->node, string_t(name)).value().native_value();
	}

	json_value json_value::member(const string &name) const
	{
		json_value result;
		result.state_->node = json::node_member(this->state_->node, runtime_access::get(name)).value();
		return result;
	}

	json_value json_value::member(const char *name) const
	{
		json_value result;
		result.state_->node = json::node_member(this->state_->node, string_t(name)).value();
		return result;
	}

	json_value json_value::at(int64 index) const
	{
		json_value result;
		result.state_->node = json::node_at(this->state_->node, scalar_access::get(index)).value();
		return result;
	}

	string json_value::key(int64 index) const
	{
		return runtime_access::wrap(json::node_key(this->state_->node, scalar_access::get(index)).value());
	}

	string json_value::text() const
	{
		return runtime_access::wrap(json::node_string(this->state_->node).value());
	}

	int64 json_value::integer() const
	{
		return json::node_int(this->state_->node).value().native_value();
	}

	boolean json_value::boolean() const
	{
		return json::node_boolean(this->state_->node).value().native_value();
	}

	vector_json::vector_json() : state_(new state)
	{
	}

	vector_json::~vector_json() noexcept
	{
		delete this->state_;
	}

	void vector_json::append(const json_value &value)
	{
		this->state_->nodes.append(value.state_->node);
	}

	json_value vector_json::read(int64 index) const
	{
		json_value result;
		result.state_->node = this->state_->nodes.at(scalar_access::get(index));
		return result;
	}

	int64 vector_json::size() const
	{
		const auto size = this->state_->nodes.size();
		if (size > static_cast<decltype(size)>(std::numeric_limits<std::int64_t>::max())) {
			throw std::length_error("Collection size exceeds signed index range");
		}
		return static_cast<std::int64_t>(size);
	}

	void vector_json::swap(vector_json &other) noexcept
	{
		std::swap(this->state_, other.state_);
	}
}
