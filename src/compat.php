<?php

// Global namespace stubs for functions normally provided by composer packages.

// symfony/deprecation-contracts, called by Twig when a template uses deprecated syntax
if (! function_exists('trigger_deprecation'))
{
	function trigger_deprecation(string $package, string $version, string $message, mixed ...$args) : void
	{}
}
