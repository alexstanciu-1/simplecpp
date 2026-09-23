def build():
    good=['scalar','entry','const_borrow','mutable_borrow','managed_const','method','const_method','constructor','copy','template','provider','family']
    bad=['void_parameter','scalar_borrow','record_value','managed_mutable','bad_lifecycle_return','bad_copy_type','uninstantiated_method','uninstantiated_template','record_nonparticipant','stale_instance','unresolved_provider','missing_family','missing_storage']
    return ([dict(mode=x,role=0,error=False) for x in good]+[dict(mode=x,role=0,error=True) for x in bad]+
            [dict(mode='storage',role=r,error=False) for r in range(6)]+
            [dict(mode='storage_record_push',role=1,error=False),dict(mode='storage_forged',role=0,error=False)])
