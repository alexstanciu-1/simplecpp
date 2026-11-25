#pragma once

#include <stdexcept>
#include <string>

// Native exception identities shared by core operators and language adapters.
// This implementation header is not part of the template-free public root.
namespace scpp {

class ValueError : public std::runtime_error {
public:
	explicit ValueError(const std::string &message) : std::runtime_error(message) {}
};

class TypeError : public std::runtime_error {
public:
	explicit TypeError(const std::string &message) : std::runtime_error(message) {}
};

} // namespace scpp
