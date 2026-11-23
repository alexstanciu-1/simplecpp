        std::exception_ptr failure;
        std::coroutine_handle<> continuation;

        @task@ get_return_object() noexcept;
        std::suspend_always initial_suspend() const noexcept { return {}; }
        struct completion {
            bool await_ready() const noexcept { return false; }
            void await_suspend(handle finished) const noexcept;
            void await_resume() const noexcept {}
        };
        completion final_suspend() const noexcept { return {}; }
        void unhandled_exception() noexcept;
    };

    @task@(@task@&& other) noexcept;
    ~@task@();
    bool await_ready() const noexcept;
    void await_suspend(std::coroutine_handle<> parent);
    @result@ await_resume() const;

private:
    explicit @task@(handle frame) noexcept : frame_(frame) {}
    handle frame_;
    friend @result@ sync_wait(@task@ root);
};

@result@ sync_wait(@task@ root);
@close@
