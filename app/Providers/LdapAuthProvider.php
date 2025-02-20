<?php namespace App\Providers;

use App\User;
use App\Auth\CustomUserProvider;
use Illuminate\Support\ServiceProvider;

class LdapAuthProvider extends ServiceProvider {

	/**
	 * Bootstrap the application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->app['auth']->extend('ldap',function()
		{
			return new CustomUserProvider(new User);
		});
	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}