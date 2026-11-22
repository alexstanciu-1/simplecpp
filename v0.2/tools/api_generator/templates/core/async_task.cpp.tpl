#pragma once

@includes@
#include <coroutine>
#include <exception>

namespace scpp::async {
class @task@ final {
public:
    struct promise_type;
    using handle = std::coroutine_handle<promise_type>;

    struct promise_type final {
