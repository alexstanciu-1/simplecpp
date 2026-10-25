<?php
namespace scpp\compiler;

/** Drive analysis/materialization rounds without making either stage invoke the other. */
final class Preparation_Run
{
	/** Successful rounds publish only after every demanded implementation has settled. */
	public static function prepare(Storage $sources /** Storage<collected_file> */, scope $language): Storage /** Storage<prepared_file> */
	{
		for (;;)
		{
			$worker = new Preparation_Worker($language);
			try {
				return $worker->prepare($sources);
			}
			catch (\RuntimeException $error) {
				if (!$worker->is_materialization_yield($error)) {
					throw $error;
				}
			}
			$requests /** Storage<callable_instance_request> */ = $worker->materialization_requests();
			if ($requests->is_empty()) {
				throw new \LogicException('Preparation yielded without a materialization request');
			}
			foreach ($requests as $request)
			{
				if (Callable_Inventory::find($request->origin, $request->called_type) !== null) {
					throw new \LogicException('Callable materialization made no progress');
				}
				Callable_Instances::obtain($request->origin, $request->called_type);
			}
		}
	}
}
