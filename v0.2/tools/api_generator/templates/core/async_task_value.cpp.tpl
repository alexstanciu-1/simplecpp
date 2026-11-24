        using result_type = @result@;
        union { result_type value; };
        bool has_value = false;

        static_assert(__is_nothrow_constructible(result_type, result_type&&) && noexcept(value.~result_type()),
            "Async result delivery requires nonthrowing move construction and destruction");
        promise_type() noexcept {}
        ~promise_type() { if (has_value) value.~result_type(); }
        void return_value(result_type result) noexcept {
            ::new (&value) result_type(static_cast<result_type&&>(result));
            has_value = true;
        }
