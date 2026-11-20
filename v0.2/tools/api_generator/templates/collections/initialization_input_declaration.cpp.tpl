    // Compiler-private ownership of a fully constructed heap input array.
    // Local arrays use native scope cleanup; this cap is constrained by the byte budget.
    static_assert(sizeof(@e@) * @inline_limit@ <= @stack_bytes@, "initializer inline limit exceeds byte budget");
@guard@    static @n@ @read_only@(const @e@* values, @count@ count);
