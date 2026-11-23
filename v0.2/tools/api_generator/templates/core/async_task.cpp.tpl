#pragma once

@includes@
#include <coroutine>
#include <exception>

@open@
class @task@ final {
public:
    struct promise_type;
    using handle = std::coroutine_handle<promise_type>;

    struct promise_type final {
