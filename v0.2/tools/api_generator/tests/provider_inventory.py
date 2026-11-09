"""Expected configure-time headers from the fully prepared runtime publication."""


def prepared_provider_headers(plan):
    graph = plan['submodules']
    headers = {graph['nodes'][provider]['header'] for provider in graph['published']}
    for function in plan.get('string_functions', {}).values():
        headers.update(adapter['header'] for adapter in function.get('adapters', {}).values())
    return sorted(headers)
