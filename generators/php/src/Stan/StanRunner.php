<?php
declare(strict_types=1);

namespace Scpp\S2S\Stan;

final class StanRunner
{
	public function __construct(
		private readonly StanWorkspaceSession $workspaceSession = new StanWorkspaceSession(),
	)
	{
	}

	/** @return array{project_root:string,php_profile:string,source_unit_count:int,analyzed_count:int,reused_count:int,warning_count:int,duplicate_count:int,resolution_warning_count:int,override_warning_count:int,return_chain_warning_count:int,expression_chain_warning_count:int,local_type_warning_count:int,property_type_warning_count:int,property_read_warning_count:int,initialization_warning_count:int,call_site_warning_count:int,return_type_warning_count:int,symbol_count:int,state_path:string,runtime_shallow_sources:list<array{profile:string,path:string,generated:int,skipped:list<string>}>,warning_samples:list<string>} */
	public function run(string $projectRoot, string $configPath): array
	{
		return $this->workspaceSession->run($projectRoot, $configPath);
	}
}
