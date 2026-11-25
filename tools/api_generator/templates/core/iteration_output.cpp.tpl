    // Compiler-owned copied value; empty construction never invokes the element constructor.
    union { value_type value; unsigned char empty[sizeof(value_type)]; };
    bool active = false;
    iteration_output() noexcept : empty{} {}
    iteration_output(const iteration_output&) = delete;
    iteration_output& operator=(const iteration_output&) = delete;
    iteration_output(iteration_output&& other) noexcept : empty{} {
        if (other.active) { ::new (&value) value_type(static_cast<value_type&&>(other.value)); active = true; }
    }
    ~iteration_output() { if (active) value.~value_type(); }
    void publish(value_type selected) noexcept {
        if (active) value = static_cast<value_type&&>(selected);
        else { ::new (&value) value_type(static_cast<value_type&&>(selected)); active = true; }
    }
};
