def build():
    modes=['new','full','incremental','removed','reordered','equal_task','project','root','identity','identity_map','reason','state','operation','layout','missing','duplicate','foreign_result','stale_selection','unexpected','duplicate_identity','wrong_id','stale_previous','invalid_abi','nonzero_stack']
    rejected={'missing','duplicate','foreign_result','stale_selection','unexpected','duplicate_identity','wrong_id','stale_previous','invalid_abi','nonzero_stack'}
    return [dict(mode=m,accept=m not in rejected) for m in modes]
