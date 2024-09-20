<?php

namespace App\Auth;
use App\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class LdapAuth implements Guard {
    
    private $request;
    protected $user;

    public function __construct(Request $request)
    {
        
        $this->request = $request;
    }

    public function check(): bool
    {
        return (bool)$this->user();
        
    }

    /**
     * Check whether user is not logged in.
     *
     * @return bool
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Return user id or null.
     *
     * @return null|int
     */
    public function id(): ?int
    {
        $user = $this->user();
        return $user->id ?? null;
    }

    /**
     * Manually set user as logged in.
     * 
     * @param  null|\App\User|\Illuminate\Contracts\Auth\Authenticatable $user
     * @return $this
     */
    public function setUser(?Authenticatable $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param  array $credentials
     * @return bool
     */
    public function validate(array $credentials = []): bool
    {
        throw new \BadMethodCallException('Unexpected method call');
    }

    /**
     * Return user or throw AuthenticationException.
     *
     * @throws AuthenticationException
     * @return \App\User
     */
    public function authenticate(): User
    {
        $user = $this->user();
        if ($user instanceof User) {
            return $user;
        }
        throw new AuthenticationException();
    }

    /**
     * Return cached user or newly authenticate user.
     *
     * @return null|\App\User|\Illuminate\Contracts\Auth\Authenticatable
     */
    public function user(): ?User
    {
        return $this->user ?: $this->signLdap();
    }

    /**
     * Sign in using requested PIN.
     *
     * @return null|User
     */
    protected function signLdap(): ?User
    {
        
        if(session()->get('ldap.auth')){

            $this->user = new User([
                'name' => session()->get('ldap.name'),
                'email' => session()->get('ldap.email')
            ]);
            return $this->user();
        }
        return null;


    }

    public function login(array $data): bool
    {
        session()->put('ldap.auth',time());
        session()->put('ldap.email',$data['email']);
        session()->put('ldap.name',$data['username']);
        return true;
    }

    /**
     * Logout user.
     */
    public function logout(): void
    {
        $this->signLdap();
        if ($this->user) {
            session()->forget('ldap.auth');
        }
    }

}