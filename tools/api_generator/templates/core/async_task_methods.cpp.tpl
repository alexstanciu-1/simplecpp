@inline@@task@ @task@::promise_type::get_return_object() noexcept {
    return @task@(handle::from_promise(*this));
}

@inline@void @task@::promise_type::unhandled_exception() noexcept {
    failure = std::current_exception();
}

@inline@void @task@::promise_type::completion::await_suspend(handle finished) const noexcept {
    if (auto parent = finished.promise().continuation) ::scpp::async::detail::schedule_continuation(parent);
}

@inline@@task@::@task@(@task@&& other) noexcept : frame_(other.frame_) {
    other.frame_ = {};
}

@inline@@task@::~@task@() {
    if (frame_) frame_.destroy();
}

@inline@bool @task@::await_ready() const noexcept {
    return !frame_ || frame_.done();
}

@inline@void @task@::await_suspend(std::coroutine_handle<> parent) {
    ::scpp::async::detail::schedule_child(frame_);
    frame_.promise().continuation = parent;
}

@inline@@result@ @task@::await_resume() const {
    if (!frame_ || !frame_.done()) ::scpp::async::detail::incomplete_result();
    auto& promise = frame_.promise();
    if (promise.failure) std::rethrow_exception(promise.failure);
@extract@
}

@inline@@result@ sync_wait(@task@ root) {
    ::scpp::async::detail::run_until_complete(root.frame_);
    return root.await_resume();
}
