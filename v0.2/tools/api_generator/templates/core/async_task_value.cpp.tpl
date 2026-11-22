        using result_type = @result@;
        result_type value{};
        static_assert(__is_nothrow_constructible(@result@) && __is_nothrow_constructible(@result@, @result@&&)
            && __is_nothrow_assignable(@result@&, @result@&&) && noexcept(value.~result_type()),
            "Async result storage requires nonthrowing default construction, move and destruction");
        void return_value(@result@ result) noexcept { value = static_cast<@result@&&>(result); }
