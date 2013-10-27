#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

// Reuse the caching hack from the MediaEmbed tests
eval('namespace s9e\TextFormatter\Tests;class Test{}');
include __DIR__ . '/../tests/bootstrap.php';
include __DIR__ . '/../tests/Plugins/MediaEmbed/ParserTest.php';

function patchDir($dirpath)
{
	$dirpath = realpath($dirpath);
	array_map('patchDir',  glob($dirpath . '/*', GLOB_ONLYDIR));
	array_map('patchFile', glob($dirpath . '/*.md'));
}

function patchFile($filepath)
{
	$file = file_get_contents($filepath);

	// Execute the lone PHP in 02_Expert.md
	if (strpos($filepath, '02_Expert.md'))
	{
		$text = preg_replace_callback(
			'#```php([^`]+)\\n```\\s+(?!```html|<pre>)#s',
			function ($m)
			{
				eval($m[1]);

				return $m[0];
			},
			$file
		);
	}

	// Execute PHP and replace output
	$text = preg_replace_callback(
		'#(```php([^`]+)\\n```\\s+(?:```\\w*|<pre>)).*?(\\n(?:```|</pre>)(?:\\n|$))#s',
		function ($m)
		{
			$php = preg_replace(
				'/\\$configurator =.*/',
				"\$0\n\$configurator->registeredVars['cacheDir'] = " . var_export(__DIR__ . '/../tests/.cache', true) . ";\n",
				$m[2]
			);

			ob_start();
			eval($php);

			return $m[1] . "\n" . rtrim(ob_get_clean(), "\n") . $m[3];
		},
		$file
	);

	if ($text === $file)
	{
		echo "Skipping $filepath\n";
	}
	else
	{
		echo "\x1b[1mPatching $filepath\x1b[0m\n";
		file_put_contents($filepath, $text);
	}
}

patchDir(__DIR__ . '/../src/s9e/TextFormatter/Plugins/');
patchDir(__DIR__ . '/../docs/Cookbook/');

die("Done.\n");