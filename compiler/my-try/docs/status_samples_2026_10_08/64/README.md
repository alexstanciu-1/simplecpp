# 64 — Shared parameter contravariance and result covariance
Doc Status: supporting

Representative subset; generation only, not native execution or full row coverage.

Source and generated text below are a representative example, not proof of the whole feature row. No emitted C++ was compiled or executed.

## PHP++ sample

[Source file](main.phs) · [Intended strict project configuration](prism.json)

```php
class Node { public int $value = 1; }
class Leaf extends Node {}
abstract class Provider { abstract public function node(): Node; }
class Leaf_Provider extends Provider { public function node(): Leaf { return new Leaf(); } }
$provider Provider = new Leaf_Provider();
return $provider->node()->value;
```

## S2S generated v1

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

[Complete emitted text](v1.cpp.txt). File separators identify the emitted units.

```cpp
// === main.hpp ===
#pragma once

#include <scpp/lang/php.hpp>
#include <cstddef>
#include <type_traits>
#include <utility>

namespace scpp {

class Leaf;
class Leaf_Provider;
class Node;
class Provider;

class Node : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	int_t<> value = static_cast<int_t<> >(1);
};

class Leaf : public Node, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
};

class Provider : public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	shared_p<Node> node() { return __scpp_return_node(std::type_identity<shared_p<Node>>{}); }
	virtual shared_p<Node> __scpp_return_node(std::type_identity<shared_p<Node>>) = 0;
};

class Leaf_Provider : public Provider, public virtual ::scpp::shared_self {
public:
	static const void* __scpp_static_token() { static int __scpp_token = 0; return &__scpp_token; }
	static bool_t __scpp_static_accepts(const void* __scpp_token);
	shared_p<Leaf> node() { return __scpp_return_node(std::type_identity<shared_p<Leaf>>{}); }
	virtual shared_p<Leaf> __scpp_return_node(std::type_identity<shared_p<Leaf>>);
	shared_p<Node> __scpp_return_node(std::type_identity<shared_p<Node>>) override;
};

int __scpp_main();

}

// === main.cpp ===
#include "main.hpp"

namespace scpp {
	using namespace ::scpp;

bool_t Node::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Node::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	else if (__scpp_token == Leaf::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t Leaf::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Leaf::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

bool_t Provider::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Provider::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	else if (__scpp_token == Leaf_Provider::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

shared_p<Node> Leaf_Provider::__scpp_return_node(std::type_identity<shared_p<Node>>) { return __scpp_return_node(std::type_identity<shared_p<Leaf>>{}); }
bool_t Leaf_Provider::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == Leaf_Provider::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

shared_p<Leaf> Leaf_Provider::__scpp_return_node(std::type_identity<shared_p<Leaf>>) {
	SCPP_CALL_DEPTH_GUARD("Leaf_Provider::node", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/64/main.phs", 4);
	return create<Leaf>();
}

int __scpp_main() {
	int_t<> argc = php::cli_argc();
	mixed_t argv = php::cli_argv();
	SCPP_CALL_DEPTH_GUARD("__scpp_main", "/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/my-try/docs/status_samples_2026_10_08/64/main.phs", 0);
	shared_p<Provider> provider = create<Leaf_Provider>();
	return provider->node()->value;
	return 0;
}
}

int main(int __scpp_argc, char** __scpp_argv) {
	try {
		::scpp::php::set_cli_args(__scpp_argc, __scpp_argv);
		return scpp::__scpp_main();
	} catch (const std::exception &exception) {
		::scpp::print_runtime_exception(exception);
		return 1;
	}
}
```

## S2S generated v2

Emitted; exit code `0`. [Command and raw logs](../logs/commands.json).

[Complete emitted text](v2.cpp.txt). File separators identify the emitted units.

```cpp
// Readable bundle. File separator comments added by the report renderer.

// === types/n4e6f6465.hpp ===
#pragma once
#include "scpp/int_t.hpp"
struct record_Node
{
public:
	scpp::int_t<> field_value = static_cast<scpp::int_t<>>(1LL);
public:
};


// === types/n4c656166.hpp ===
#pragma once
#include "types/n4e6f6465.hpp"
struct record_Leaf : public record_Node
{
public:
};


// === types/n50726f7669646572.hpp ===
#pragma once
#include "scpp/shared_p.hpp"
struct record_Node;
struct record_Provider
{
public:
public:
	virtual ::scpp::shared_p<record_Node> dispatch_Provider__method_node() = 0;
::scpp::shared_p<record_Node> method_node();
public:
	virtual ~record_Provider() noexcept = default;
	record_Provider() = default;
	record_Provider(const record_Provider&) = default;
	record_Provider(record_Provider&&) = default;
	record_Provider& operator=(const record_Provider&) = default;
	record_Provider& operator=(record_Provider&&) = default;
};


// === types/n4c6561665f50726f7669646572.hpp ===
#pragma once
#include "scpp/shared_p.hpp"
#include "types/n50726f7669646572.hpp"
struct record_Leaf;
struct record_Node;
struct record_LeafU_Provider : public record_Provider
{
public:
public:
	::scpp::shared_p<record_Leaf> implementation_LeafU_Provider__method_node();
	virtual ::scpp::shared_p<record_Leaf> dispatch_LeafU_Provider__method_node();
::scpp::shared_p<record_Leaf> method_node();
public:
	::scpp::shared_p<record_Node> dispatch_Provider__method_node() override;
public:
	virtual ~record_LeafU_Provider() noexcept = default;
	record_LeafU_Provider() = default;
	record_LeafU_Provider(const record_LeafU_Provider&) = default;
	record_LeafU_Provider(record_LeafU_Provider&&) = default;
	record_LeafU_Provider& operator=(const record_LeafU_Provider&) = default;
	record_LeafU_Provider& operator=(record_LeafU_Provider&&) = default;
};


// === units/n6d61696e/n6d61696e2e706873.cpp ===
#include "types/n4c6561665f50726f7669646572.hpp"
#include "types/n4c656166.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/memory.hpp"
#include "types/n4e6f6465.hpp"
#include "types/n50726f7669646572.hpp"
::scpp::shared_p<record_Node> record_Provider::method_node() { return dispatch_Provider__method_node(); }
::scpp::shared_p<record_Leaf> record_LeafU_Provider::dispatch_LeafU_Provider__method_node() { return record_LeafU_Provider::implementation_LeafU_Provider__method_node(); }
::scpp::shared_p<record_Leaf> record_LeafU_Provider::method_node() { return dispatch_LeafU_Provider__method_node(); }
::scpp::shared_p<record_Node> record_LeafU_Provider::dispatch_Provider__method_node() { return ::scpp::shared_p<record_Node>{dispatch_LeafU_Provider__method_node()}; }
::scpp::shared_p<record_Leaf> record_LeafU_Provider::implementation_LeafU_Provider__method_node()
{
	return ::scpp::shared<record_Leaf>();
}


// === entry.cpp ===
#include "types/n4c6561665f50726f7669646572.hpp"
#include "scpp/memory.hpp"
#include "types/n50726f7669646572.hpp"
#include "scpp/shared_p.hpp"
#include "types/n4e6f6465.hpp"

int main()
{
	::scpp::shared_p<record_Provider> local_provider = ::scpp::shared_p<record_Provider>{::scpp::shared<record_LeafU_Provider>()};
	return static_cast<int>(((([&]() -> ::scpp::shared_p<record_Node> {
auto receiver_0 = local_provider;
(void)receiver_0.operator->();
	return receiver_0->method_node();
}()))->field_value).native_value());
	return 0;
}
```

