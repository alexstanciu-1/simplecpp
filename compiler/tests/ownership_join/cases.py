def build():
    cases=[]
    for mode in ['normal','changed','move_source','reverse_batch','body']:cases.append(dict(mode=mode,expected='accepted'))
    for mode,expected in [('const_source','Invalid ownership field transition'),('unaccessed','Invalid ownership field transition'),('wrong_field','Incomplete ownership summary fields'),('missing_parameter','Incomplete ownership summary parameters'),('extra_result','Incomplete owned result resource summary'),('missing_result','Incomplete ownership batch'),('duplicate_task','Duplicate ownership task')]:cases.append(dict(mode=mode,expected=expected))
    for mode in ['duplicate_result','stale','unexpected']:cases.append(dict(mode=mode,expected='Unexpected, duplicate or stale ownership result'))
    return cases
