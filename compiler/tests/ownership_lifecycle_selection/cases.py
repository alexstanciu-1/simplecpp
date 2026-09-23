def build():
    return [dict(mode=mode,missing=missing,expected='Missing checked source lifecycle body' if missing else 'accepted') for mode in ['constructor','destructor','copy','assign','all'] for missing in [False,True]]
