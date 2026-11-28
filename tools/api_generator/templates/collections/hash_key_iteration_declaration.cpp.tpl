    bool_t @next_key_method@(@count@& position, @count@ limit, @k@& key) const;
    @v@ @current_value_method@(@count@ position) const;
    @v@& iteration_reference(@count@ position);
    const @v@& scpp_escape_read_slot(@count@ position) const &;
    const @v@& scpp_escape_read_slot(@count@) const && = delete;
