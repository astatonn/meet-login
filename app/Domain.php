<?php

namespace App;

use App\Classes\Api\Zimbra;
use Illuminate\Database\Eloquent\Model;

class Domain extends Model
{
    //CONST PENSO_MAIL = ['zimbra-zimlet-jitsi','com_zimbra_new_pensomeet'];
    //CONST PENSO_MAIL = ['zimbra-pensomeet-cal-modern','zimbra-zimlet-anyframe','com_zimbra_pensomeet_cal','com_zimbra_new_pensomeet'];

    CONST PENSO_MAIL = ['zimbra-pensomeet-cal-modern','com_zimbra_pensomeet_cal','com_zimbra_new_pensomeet'];

    CONST ZIMLET_DISABLED = '-';
    private $domain;
    private $email;
    private $ldapConfig;
    public function checkDomain($email)
    {
        $pieces = explode("@",$email);
        $this->domain = $pieces[1];
        $this->email = $email;
        // $this->domain = \DB::table('domains')->where('domain_name',$pieces[1])
        // ->where('hosts.has_meet','yes')
        // ->where('hosts.active','yes')
        // ->select('hosts.host_name','domains.host_id','hosts.ldap_host','domains.domain_name')
        // ->join('hosts','domains.host_id','=','hosts.host_name')
        // ->first();
        if(!$this->domain){
            return false;
        }
        return $this->domain;
    } 

    public function getLdapConfig()
    {
        $this->ldapConfig = $this->domain->ldap_host;
        return $this->ldapConfig;
    }
    public function checkLdapSpecialConfig()
    {
        return in_array($this->domain->host_id,explode(",",env('SPECIAL_LDAP_HOSTS')));        
    }

    //quem for desse host só pode logar no whitelabel
    public function checkWhiteLabelHost()
    {
        return in_array($this->domain->host_id,explode(",",env('WHITELABEL_HOSTS')));
    }
    public function checkJitsi()
    {
        $zimbra = new Zimbra($this->domain->host_id);
        $zimbra->setForceRaw(true);
        $info = $zimbra->getAccountByName($this->email);
        $xml = new \SimpleXMLElement($info);
        $xml->registerXPathNamespace('soap', 'http://www.w3.org/2003/05/soap-envelope');
        $xml->registerXPathNamespace('zimbra', 'urn:zimbraAdmin');
        $body = $xml->xpath('//soap:Body/zimbra:GetAccountResponse');
        foreach($body[0] as $account){
            $account->registerXPathNamespace('zimbra', 'urn:zimbraAdmin');
            $zimlets = $account->xpath("zimbra:a[@n=\"zimbraZimletAvailableZimlets\"]");
            $zimletsArr = [];
            foreach($zimlets as $zimlet){
                //if(substr((string)$zimlet[0],1,strlen((string)$zimlet[0])) == self::PENSO_MAIL){
                    if(substr((string)$zimlet[0],0,1) == self::ZIMLET_DISABLED){
                        continue;
                    } 
                   $zimletName = substr((string)$zimlet[0],1,strlen((string)$zimlet[0]));
                   if(in_array($zimletName,self::PENSO_MAIL)){
                        return true;
                   }
                    //array_push($zimletsArr,substr((string)$zimlet[0],1,strlen((string)$zimlet[0])));
                    /*if(!in_array(substr((string)$zimlet[0],1,strlen((string)$zimlet[0])),self::PENSO_MAIL)){
                    continue;
                }
                    if(substr((string)$zimlet[0],0,1) == self::ZIMLET_DISABLED){
                        continue;
                    } 
                    return true;
                    */
            }
        }
        return false;
        //if(count(array_diff(self::PENSO_MAIL,$zimletsArr))){
        //    return false;
        //}   
        //return true;
    }
    
    public function getLdap()
    {
        return $this->ldapConfig;
    }

    public static function checkWhiteLabelUrl()
    {
        return request()->server()['SERVER_NAME'] == env('WHITELABEL_URL');
    }

}
