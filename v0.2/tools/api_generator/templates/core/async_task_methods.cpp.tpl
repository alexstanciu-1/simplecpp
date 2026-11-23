@task@ @task@::promise_type::get_return_object() noexcept {
    return @task@(handle::from_promise(*this));
}

void @task@::promise_type::unhandled_exception() noexcept {
    failure = std::current_exception();
}

void @task@::promise_type::completion::await_suspend(handle finished) const noexcept {
    if (auto parent = finished.promise().continuation) detail::schedule_continuation(parent);
}

@task@::@task@(@task@&& other) noexcept : frame_(std::exchange(other.frame_, {})) {}

@task@::~@task@() {
    if (frame_) frame_.destroy();
}

bool @task@::await_ready() const noexcept {
    return !frame_ || frame_.done();
}

void @task@::await_suspend(std::coroutine_handle<> parent) {
    detail::schedule_child(frame_);
    frame_.promise().continuation = parent;
}

@result@ @task@::await_resume() const {
    if (!frame_ || !frame_.done()) throw std::logic_error("async task has no completed result");
    auto& promise = frame_.promise();
    if (promise.failure) std::rethrow_exception(promise.failure);
@extract@
}

@result@ wait_for(@task@ (*factory)()) {
    return sync_wait(factory());
}

@result@ sync_wait(@task@ root) {
    if (!root.frame_) throw std::logic_error("async wait requires a valid task");
    detail::run_until_complete(root.frame_);
    return root.await_resume();
}
