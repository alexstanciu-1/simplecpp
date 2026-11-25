"""Lifecycle enumeration must preserve type/role order and exclude rebound owners."""
def build():
    return [dict(name=f'policy-{mask}-binding-{binding}',mask=mask,binding=binding,accept=True)
            for mask in range(32) for binding in range(5)]
