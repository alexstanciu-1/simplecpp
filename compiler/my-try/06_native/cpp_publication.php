<?php
namespace scpp\compiler;

/** Decode the published runtime contract shared by disk build consumers. */
final class Native_CPP_Publication
{
	/** Consume the authenticated publication, never reopen module catalogs during a build. */
	public static function runtime_inputs(\scpp\Json_View $view): runtime_cpp_build_spec
	{
		if (($view->kind() !== 'object') || ($view->size() !== 3)) {
			throw new \RuntimeException('Invalid runtime build publication');
		}
		$result = new runtime_cpp_build_spec();
		foreach (['sources', 'libraries', 'definitions'] as $name)
		{
			$items = $view->member($name);
			if ($items->kind() !== 'array') {
				throw new \RuntimeException('Runtime build entries require arrays');
			}
			$values /** vector<string> */ = [];
			for ($index = 0; $index < $items->size(); $index++) {
				$values[] = $items->at($index)->text();
			}
			if ($name === 'sources') {
				$result->sources = $values;
			}
			elseif ($name === 'libraries') {
				$result->libraries = $values;
			}
			else {
				$result->definitions = $values;
			}
		}
		Runtime_Build_Contract::validate($result);
		return $result;
	}
}
