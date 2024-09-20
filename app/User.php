<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use LdapRecord\Connection; 
use App\Classes\LdapClient;

class User extends Authenticatable
{
    use Notifiable;

    const MAIN_DOMAIN = 'penso.com.br';
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];
    public $name;
    public $email;

    /**
     * The attributes that should be hidden for arrays. 
     *
     * @var array
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function __construct($values)
    {
        $this->name = $values['name'];
        $this->email = $values['email'];
        return parent::__construct([]);
    }

    public static function getRules()
    {
        return [
            'rules' => [
                'email' => 'required|email',
                'password' => 'required|min:4'
            ],
            'messages' => [
                'email.required' => 'O campo e-mail é obrigatório',
                'email.email'   => 'O campo e-mail não é válido',
                'password.required' => 'O campo senha é obrigatório',
                'password.min' => 'O campo senha precisa de no mínimo 4 caractéres',
            ]
        ];
    }

    public static function getGuestRules()
    {
        return [
            'rules' => [
            'guest.email' => 'required|email',
            'guest.name'  => 'required|min:3'
        ],'messages' => [
            'guest.email.required' => 'O campo e-mail é obrigatório',
            'guest.email.email' => 'O campo e-mail não é válido',
            'guest.name.required' => 'O campo nome é obrigatório',
            'guest.email.min' => 'O tamanho mínimo para o campo nome é de 3 carácteres',
        ]
        ];
    }

    public static function login(array $request)
    {
        return self::loginAdmin($request);
        /*
        if(explode("@",$request['email'])[1] == self::MAIN_DOMAIN){
            return self::loginAdmin($request);
        }
        $domain = new Domain();
          
       if(!$domain->checkDomain($request['email'])){
            return null;
        }
       if(!$domain->getLdapConfig()){
            return null;
       }
       if($domain->checkWhiteLabelHost()){
            return self::loginWhiteLabel($request,$domain->getLdap(),$domain);
       }
       if($domain->checkLdapSpecialConfig()){
            return self::loginSpecialLdap($request,$domain->getLdap());
    }
       $user = self::loginLdap($request['email'],$request['password'],$domain->getLdap());
       if($user){
            if(!$domain->checkJitsi()){
                return false;
            }
            
            $userName = self::handleLdapUserResponse($user);
            if(is_bool($userName)){
                $userName = $request['email'];
            }
            $request['username'] = $userName;
            session()->forget(['guest']);
            return \Auth::login($request);
        }
        return null;
    */

    }

    private static function loginWhiteLabel($request,$host,$domain)
    {
        //caso não seja a URL do whitelabel não loga
        if(!Domain::checkWhiteLabelUrl()){
            return false;
        }
        //se não faz o login normal
        
        $user = self::loginLdap($request['email'],$request['password'],$domain->getLdap());
        if($user){
             if(!$domain->checkJitsi()){
                 return false;
             }
             
             $userName = self::handleLdapUserResponse($user);
             if(is_bool($userName)){
                 $userName = $request['email'];
             }
             $request['username'] = $userName;
             session()->forget(['guest']);
             return \Auth::login($request);
         }
         return false;
    }
    private static function authenticateSpecialLdap($user,$password,$ldap)
    {
        //tenta com o email todo
        $ldap->setUser($user);
        $ldap->setPass($password);
        if($ldap->authenticateUser()){
            return true;
        }
        //tenta com só com o nome
        $user = explode("@",$user)[0];
        $ldap->setUser($user);
        $ldap->setPass($password);
        if($ldap->authenticateUser()){
            return true;
        }
        return false;
    }
    private static function loginSpecialLdap($request,$config)
    {
        $password = $request['password'];
        $host = $config;
        if(is_array($config)){
            $host = $config['host'];
        }

        $ldap = new LdapClient($host);
        if(!self::authenticateSpecialLdap($request['email'],$password,$ldap)){
            return false;
        }
        $request['username'] = $request['email'];
        session()->forget(['guest']);
        return \Auth::login($request);
    } 

    public static function loginAdmin($request)
    {
        $user = self::loginLdap($request['email'],$request['password'],['host' => env('LDAP_HOST')]);
        if(!$user){
            return false;
        }
            $userName = self::handleLdapUserResponse($user);
            $request['username'] = $userName;
            session()->forget(['guest']);
            return \Auth::login($request);
    }
 
    private static function loginLdap($email,$password,$config)
    {
        $host = $config;
        if(is_array($config)){
            $host = $config['host'];
        }
        $user = self::handleUserName($email);
        $ldap = new LdapClient($host);
        $ldap->setUser($user);
        $ldap->setPass($password);
        $ldap->setFilter(sprintf('(&(objectClass=zimbraAccount))', ''));
        $ldap->setSearchBase($user);
        if(!$ldap->authenticateUser()){
            return false;
        }
        $ldap->search();
        return $ldap->getResponse();
        //return $ldap->authenticateUser(false);
    }

    protected static function handleLdapUserResponse($user)
    {
        if(isset($user[0]['displayname'])){
            return $user[0]['displayname'][0];
        }
        if(isset($user[0]['mail'])){
            return $user[0]['mail'][0];
        }
        return true;

    }

    private static function handleUserName($username)
    {
        $user = explode("@",$username);
        $dc = str_replace(".",",dc=",$user[1]);
        $userDn = sprintf("uid=%s,ou=people,dc=%s",$user[0],$dc);
        return $userDn;
    }

    public static function guestLogin($request)
    {
    //    session()->put('guest.email',$request['guest']['email']);
   //     session()->put('guest.name',$request['guest']['name']);
        session()->put('guest.auth',true);
        return true;
    }

    public function setUser(array $request)
    {
        if(!isset($request['name']) || !isset($request['email']))
        {
            return null;
        }
        $this->name = $request['name'];
        $this->email = $request['email'];
    }
}
