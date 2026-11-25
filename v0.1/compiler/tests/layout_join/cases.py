def build():
    modes=['new','reversed','partial','reuse','removed','empty','opaque','provider','provider_target','provider_layout','provider_size','provider_alignment','provider_count','provider_offset',
           'duplicate_task','unknown_task','task_definition','task_config','task_input','task_fields','task_type_count','task_field_identity','task_field_spelling','task_policy',
           'missing_result','duplicate_result','unexpected_result','equal_result_task','result_definition','result_config','result_lineage','result_dependency','result_field_identity','result_spelling',
           'missing_previous','stale_previous','stale_lineage']
    accepted={'new','reversed','partial','reuse','removed','empty','opaque','provider'}
    return [dict(mode=m,accept=m in accepted) for m in modes]
