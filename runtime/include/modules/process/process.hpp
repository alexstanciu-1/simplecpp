#pragma once

#include "scpp/result.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/vector_t.hpp"
#include <memory>

namespace scpp::process {

class output final {
public:
	string_t stdout_text;
	string_t stderr_text;
	int_t<> exit_code{-1};
	int_t<> signal{0};
	bool_t timed_out{false};
	bool_t stopped{false};
};

// Aliases share one lifecycle; only its designated application owner may operate it.
class handle final {
public:
	struct state;
	~handle();
	handle(const handle &) = delete;
	handle &operator=(const handle &) = delete;
private:
	handle();
	std::unique_ptr<state> state_;
	friend result<shared_p<handle>> start(const string_t &, const vector_t<string_t> &, const string_t &, const int_t<> &, const string_t &);
	friend result<bool_t> poll(const shared_p<handle> &);
	friend result<shared_p<output>> collect(const shared_p<handle> &);
	friend result<bool_t> stop(const shared_p<handle> &);
	friend result<bool_t> close(const shared_p<handle> &);
};

[[nodiscard]] result<shared_p<handle>> start(const string_t &executable, const vector_t<string_t> &args,
	const string_t &input, const int_t<> &timeout_ms, const string_t &cwd = string_t(""));
[[nodiscard]] result<bool_t> poll(const shared_p<handle> &);
[[nodiscard]] result<shared_p<output>> collect(const shared_p<handle> &);
[[nodiscard]] result<bool_t> stop(const shared_p<handle> &);
[[nodiscard]] result<bool_t> close(const shared_p<handle> &);
}

namespace scpp {
using process_handle = process::handle;
using process_output = process::output;
}
