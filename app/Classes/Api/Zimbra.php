<?php

/**
 * @author wgomes
 * Classe de Integração com a API Zimbra
 *
 *  @author acolognesi since 11/2020
 * Implementando novos métodos/ajustes
 */

namespace App\Classes\Api;

use App\Mail\UserMail;
use Illuminate\Support\Facades\Mail;
use SoapClient;
use SoapHeader;
use SoapVar;
use Illuminate\Support\Facades\Auth;
use App\Models\DomainAccounts;
use Exception;
use Illuminate\Support\Facades\Storage;
use App\Classes\Helper\Helper;

class Zimbra
{
    private $errors = [
        'account.ACCOUNT_EXISTS' => 'Já existe uma conta com esse nome.',
        'account.INVALID_PASSWORD' => 'A senha está fora dos padrões do sistema.',
        'account.DISTRIBUTION_LIST_EXISTS' => 'Já existe uma lista de distribuição com esse nome.',
        'account.TOO_MANY_ACCOUNTS' => 'Já foi atingido o limite de contas para este domínio',
        'account.NO_SUCH_DISTRIBUTION_LIST' => 'Lista de distribuição não existe/e ou já foi deletada.',
        'account.NO_SUCH_COS' => 'Não foi possivel encontrar o COS.',
        'account.AUTH_FAILED' => 'Falha na autenticação, verifique a senha e/ou o usuário.'

    ];

    public const DOMAINBYNAME = 'name';
    public const DOMAINBYID = 'id';
    public const DEFAULT_CONNECTION = 'default';

    private $errorMessage = null;
    public $authToken;
    private $user;
    private $pass;
    private $forceRaw = false;
    private $hasPooling = false;
    private $url;
    private $wsdl;
    private $useSsl = true;
    public $rawResponse;
    public $domainId;
    public $adminUrl = "https://trt3.pensomail.com.br:7071";
    private $soap;
    public $message;
    public $hasError;
    public $createAdminAccount = true;
    private $rawData;
    private $connection;
    private $adminAccount = null;

    public function getApiUser()
    {
        return $this->user;
    }

    public function __construct($connection = null, $connectionData = null)
    {
        $this->setConnection($connection);
        if ($connectionData) {
            $this->setCustomConnection($connectionData);
            $this->connect();
            return;
        }
        $this->readConfig($connectionData);
        $this->connect();
    }

    private function connect()
    {
        $option = ['trace' => 1,'encoding' => ' UTF-8'];
        //caso a conexão não use SSL devemos desabilitar do soap
        if (!$this->useSsl) {
            $context = stream_context_create(
                ['ssl' => ["verify_peer" => false,
                "verify_peer_name" => false]
                ]
            );
            $option['stream_context'] = $context;
        }
        try {
            $this->soap = new SoapClient($this->wsdl, $option);
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return;
        }
    }

    public function setConnection($connection)
    {
        $this->connection = $connection;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function getRawData()
    {
        return $this->rawData;
    }

    public function hasPooling()
    {
        return $this->hasPooling;
    }

    public function setForceRaw(bool $value)
    {
        $this->forceRaw = $value;
    }

    public function getForceRaw()
    {
        return $this->forceRaw;
    }
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    public function testConnection($con)
    {
        foreach ($con as $key => $value) {
            $this->$key = $value;
        }
        $this->useSsl = false;
        $context = stream_context_create(
            ['ssl' => ["verify_peer" => false,
            "verify_peer_name" => false]
            ]
        );
        $option['stream_context'] = $context;
        try {
            $this->soap = new SoapClient($this->wsdl, $option);
            if (!$this->checkHealth()) {
                $this->errorMessage = $this->getErrorMessage();
                return false;
            }
            return true;
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            return false;
        }
    }

    private function readConfigJson()
    {
        $context = strtolower(env('CONTEXT'));
        $connectionName = $this->connection;
        ;
        if ($connectionName == null) {
            $connectionName = config(
                sprintf(
                    'zimbra.%s.%s',
                    $context,
                    self::DEFAULT_CONNECTION
                )
            );
        }

        $config = config(sprintf("zimbra.%s.%s", $context, $connectionName));
        // $config = config(sprintf("zimbra.%s",$context))[$connectionName];
        if (!$config) {
            return false;
        }
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
        return true;
    }

    private function setCustomConnection($con)
    {
        if (is_object($con)) {
            $this->pass = self::decryptPassword($con->password);
            $this->user = $con->user;
            $this->url = $con->url_soap;
            $this->wsdl = $con->url_wsdl;
            $this->useSsl = $con->use_ssl == 'yes' ? true : false;
            return true;
        }

        if (is_array($con)) {
            $this->pass = self::decryptPassword($con['password']);
            $this->user = $con['user'];
            $this->url = $con['url_soap'];
            $this->wsdl = $con['url_wsdl'];
            $this->useSsl = $con['use_ssl'] == 'yes' ? true : false;
            return true;
        }
        $this->errorMessage = sprintf(
            'Não foi possivel conectar ao servidor Zimbra [%s], dados insuficientes.',
            $this->connection
        );
        return false;
    }
    private function readConfig($connectionData = null)
    {
        $connectionName = $this->connection;
        $con = \DB::table('hosts')->where('host_name', $connectionName)->first();
        if (!$con) {
            $this->errorMessage = sprintf(
                'Não foi possivel conectar ao servidor Zimbra [%s], dados insuficientes.',
                $this->connection
            );
            return false;
        }
        $this->pass = self::decryptPassword($con->password);
        $this->user = $con->user;
        $this->url = $con->url_soap;
        $this->wsdl = $con->url_wsdl;
        $this->useSsl = $con->use_ssl == 'yes' ? true : false;
        return true;
    }

    public function getAccountsCos()
    {
        $context = strtolower(env('CONTEXT'));
        $fileName = $context . "_config.json";
        $config = json_decode(Storage::disk('local')->get('zimbra/' . $fileName), true);
        if (!isset($config['account']['COS'])) {
            return false ;
        }
        return $config['account']['COS'];
    }

    public static function getDefaultHost()
    {
        return env('ZIMBRA_DEFAULT_HOST');
    }



    public function getDefaultAccountCos()
    {
        $context = strtolower(env('CONTEXT'));
        $fileName = $context . "_config.json";
        $config = json_decode(Storage::disk('local')->get('zimbra/' . $fileName), true);
        if (!isset($config['account']['default_cos'])) {
            return false ;
        }
        return $config['account']['default_cos'];
    }

    public function getAdminUrl()
    {
        return $this->adminUrl;
    }

    public function checkIfDomainExists(string $domain)
    {
        if (!$this->getDomainRequest($domain)) {
            return false;
        }
        return true;
    }

    public function flushCache($type = 'skin')
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
            <soapenv:Body>
                <urn1:FlushCacheRequest>
                    <urn1:cache type="%s">
                    </urn1:cache>
                </urn1:FlushCacheRequest>
            </soapenv:Body>
        </soapenv:Envelope>',
            $type
        );

        $function = "FlushCacheRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);

        if ($this->checkForError($result)) {
            return false;
        }

        return $result["soapBody"]["FlushCacheResponse"];
    }


    public function adminAccountsAsArray()
    {
        $adminsAcc = $this->GetAllAdminAccountsRequest()['account'];
        $resArr = [];
        foreach ($adminsAcc as $value) {
            array_push(
                $resArr,
                [
                    'id' => $value['@attributes']['id'],
                    'name' => $value['@attributes']['name']
                ]
            );
        }
        return $resArr;
    }

    public function setAdminAccount($id)
    {
        $this->createAdminAccount = false;
        $acc = $this->getAccountInfoRequest($id);
        if (!$acc) {
            return false;
        }
        $this->adminAccount = $acc['name'];
    }

    public function getAdminAccount()
    {
        return $this->adminAccount;
    }

    public function createFromScratch($domain, $domainModel)
    {
        $acc = $domainModel->getAdminAccount();
        if ($this->createAdminAccount == false) {
            $acc = $this->getAdminAccount();
            $newAccount = true;
        }

        $domainInfo = [
            'zimbraSkinLogoURL' => 'https://zimbramail.penso.com.br/logo/penso440.png',
            'zimbraPrefTimeZoneId' => 'America/Sao_Paulo',
            'zimbraSkinLogoAppBanner' => 'https://zimbramail.penso.com.br/logo/penso200x35.png',
            'zimbraSkinLogoLoginBanner' => 'https://zimbramail.penso.com.br/logo/penso440.png',
            'zimbraSkinLogoURL' => 'https://zimbramail.penso.com.br/',
            'zimbraPublicServiceProtocol' => 'https',
            'ZimbraDomainDefaultCOSId' => 'ff2be481-0859-44c7-b33c-f9f8054531c1',
            'zimbraPublicServiceHostname' => 'zimbramail.penso.com.br',
            'amavisSpamLover' => 'TRUE' ,
            'amavisBypassSpamChecks' => 'TRUE'
        ];
        $password = $domainModel->getAdminPassword();
        $accountInfo = [
            'givenName' => 'Administrador',
            'displayName' => 'Administrador',
        ];
        $newDomain = $this->createDomain($domain);
        $domainId = $newDomain['domain']['@attributes']['id'];
        $domainModified = $this->modifyDomainInfo($domainId, $domainInfo);
        if ($this->createAdminAccount) {
            $newAccount = $this->createAccount("admin", $domain, $accountInfo, $password);
        }

        if ($newAccount && $newDomain) {
            $domainAccount = new DomainAccounts();
            $domainAccount->created_by = Auth::user()->id;
            $domainAccount->domain = $domain;
            $domainAccount->account = $acc;
            $domainAccount->status = 1;
            $domainAccount->save();
        }

        return true;
    }

    public function renameAccountRequest($id, $newName)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:RenameAccountRequest>
                    <id>%s</id>
                    <newName>%s</newName>
                    </urn1:RenameAccountRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $id,
            $newName
        );
        $function = "RenameAccountRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result['soapBody']['RenameAccountResponse'];
    }

    public function renameDistributionListRequest($id, $newName)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:RenameDistributionListRequest>
                        <id>%s</id>
                        <newName>%s</newName>
                    </urn1:RenameDistributionListRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $id,
            $newName
        );
        $function = "RenameDistributionListRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result['soapBody']['RenameDistributionListResponse'];
    }


    public function setAccountPassword($id, $password)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:SetPasswordRequest>
                        <id>%s</id>
                        <newPassword>%s</newPassword>
                    </urn1:SetPasswordRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $id,
            $password
        );
        $function = "SetPasswordRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result['soapBody']['SetPasswordResponse'];
    }

    public function deleteDomain($id)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:DeleteDomainRequest>
                        <id>%s</id>
                    </urn1:DeleteDomainRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $id
        );
        $function = "DeleteDomainRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result['soapBody']['DeleteDomainResponse'];
    }

    public function getDomainRequest($domain, $by = self::DOMAINBYNAME)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetDomainRequest>
                        <domain by="' . $by . '">%s</domain>
                    </urn1:GetDomainRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $domain
        );
        $function = "GetDomainRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result['soapBody']['GetDomainResponse'];
    }

    public function getAllCosRequest()
    {
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
        <soapenv:Body>
           <urn1:GetAllCosRequest>
           </urn1:GetAllCosRequest>
        </soapenv:Body>
     </soapenv:Envelope>';
        $function = "GetAllCosRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllCosResponse"];
    }

    public function getDomainId($domain)
    {
        if ($this->checkIfDomainExists($domain)) {
            $info = $this->getDomainInfoRequest($domain);
            $id = $info['domain']['@attributes']['id'];
            $this->domainId = $id;
            return $id;
        }

        return false;
    }

    public function getDomainInfoRequest($domain)
    {

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetDomainInfoRequest>
                        <domain by="name">%s</domain>
                    </urn1:GetDomainInfoRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $domain
        );
        $function = "GetDomainInfoRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetDomainInfoResponse"];
    }

    private function convertSoapResult($xml)
    {
        $this->rawResponse = $xml;

        $xml = preg_replace("/(<\/?)(\w+):([^>]*>)/", "$1$2$3", $xml);
        $xml = simplexml_load_string($xml);
        $json = json_encode($xml);
        $responseArray = json_decode($json, true);
        $xml = null;
        return $responseArray;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    public function modifyDomainInfo($domainId, array $field)
    {
        $values = '';
        foreach ($field as $key => $value) {
            $values .= sprintf("<urn1:a n=\"%s\">%s</urn1:a> \n", $key, $value);
        }

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:ModifyDomainRequest>
                        <id>%s</id>
                        %s
                        </urn1:ModifyDomainRequest>
                    </soapenv:Body>
            </soapenv:Envelope>',
            $domainId,
            $values
        );
        $function = "ModifyDomainRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["ModifyDomainResponse"];
    }

    public function createAccount($name, $domain, array $infos = null, $password = null)
    {
        $info = '';
        if ($infos) {
            foreach ($infos as $key => $value) {
                $info .= sprintf(
                    '<urn1:a n="%s">%s</urn1:a>',
                    $this->replaceXml($key),
                    $this->replaceXml($value)
                );
            }
        }
        $pass = '';
        if ($password) {
            $pass = sprintf("<password><![CDATA[%s]]></password>", $password);
        }

        $name = sprintf("%s@%s", $name, $domain);
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:CreateAccountRequest>
                        <name>%s</name>
                        %s
                        %s
                    </urn1:CreateAccountRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $name,
            $pass,
            $info
        );
        $function = "CreateAccountRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["CreateAccountResponse"];
    }

    public function createMultipleAccount()
    {

        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
        <soapenv:Body>
            <BulkImportAccountsRequest [op="{operation}"]>
            <createDomains>{createDomains} (String)</createDomains>
            <SMTPHost>{SMTPHost} (String)</SMTPHost>
            <SMTPPort>{SMTPPort} (String)</SMTPPort>
            <sourceType>{sourceType} (String)</sourceType>
            <aid>{attachmentID} (String)</aid>
            <password>{password} (String)</password>
            <genPasswordLength>{genPasswordLength} (Integer)</genPasswordLength>
            <generatePassword>{generatePassword} (String)</generatePassword>
            <maxResults>{maxResults} (Integer)</maxResults>
            <mustChangePassword>{mustChangePassword} (String)</mustChangePassword>
            (<a name="{attr-name}">{value}</a> ## Attr)*
            </BulkImportAccountsRequest>
            </soapenv:Body>
        </soapenv:Envelope>';
        $function = "CreateAccountRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["CreateAccountResponse"];
    }


    public function preAuthRequest($account, $password, $by = 'name')
    {
        $this->authRequest();
        $date = new \DateTime();
        $timestamp =  $date->getTimestamp();
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAccount"
            >
                <soapenv:Body>
                    <urn1:AuthRequest>
                        <account>%s</account>
                        <password>%s</password>

                        </urn1:AuthRequest>
                    </soapenv:Body>
            </soapenv:Envelope>
      ',
            $account,
            $password
        );
        $function = "AuthRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["AuthResponse"];
    }

    public function createDomainAlias($mainDomain, $aliasDomain, $domainModel)
    {
        $main = $this->getDomainInfoRequest($mainDomain);
        if (!$main) {
            return false;
        }
        $mainId = $main['domain']['@attributes']['id'];
        $forward = sprintf("@%s", $main['domain']['@attributes']['name']);
        $description = sprintf("Nome alternativo do dominío %s", $mainDomain);
        $info = [
            'zimbraDomainType' => 'alias',
            'zimbraDomainAliasTargetId' => $mainId,
            'zimbraMailCatchAllForwardingAddress' => $forward,
            'description' => $description,
            'zimbraSkinLogoURL' => 'https://zimbramail.penso.com.br/logo/penso440.png',
            'zimbraPrefTimeZoneId' => 'America/Sao_Paulo',
            'zimbraSkinLogoAppBanner' => 'https://zimbramail.penso.com.br/logo/penso200x35.png',
            'zimbraSkinLogoLoginBanner' => 'https://zimbramail.penso.com.br/logo/penso440.png',
            'zimbraSkinLogoURL' => 'https://zimbramail.penso.com.br/',
            'zimbraPublicServiceProtocol' => 'https',
            'ZimbraDomainDefaultCOSId' => 'ff2be481-0859-44c7-b33c-f9f8054531c1',
            'amavisSpamLover' => 'TRUE' ,
            'amavisBypassSpamChecks' => 'TRUE'
        ];
        if (!$this->createDomain($aliasDomain, $info)) {
            return false;
        }

        //cria conta para o robo setar o admin do alias(admin@maindomain - aliasdomain)
        $acc = sprintf("admin@%s", $mainDomain);
        $domainAccount = new DomainAccounts();
        $domainAccount->created_by = Auth::user()->id;
        $domainAccount->domain = $aliasDomain;
        $domainAccount->account = $acc;
        $domainAccount->status = 1;
        $domainAccount->save();

        //envia o email
        $email = new UserMail();
        $email->__set('subject', sprintf('Novo alias para o dominio %s criado.', $mainDomain));
        $email->__set('title', sprintf('Olá %s, foi criado um novo aliás de domínio.', Auth::user()->name));
        $email->__set('alias', $aliasDomain);
        $email->__set('domain', $mainDomain);
        $email->__set('account', $acc);
        Mail::to(Auth::user()->email)->send($email);
        return true;
    }

    public function createDomain($domain, array $info = null)
    {
        $values = '';
        if ($info) {
            foreach ($info as $key => $value) {
                $values .= sprintf("<urn1:a n=\"%s\">%s</urn1:a>\n", $key, $value);
            }
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:CreateDomainRequest>
                        <name>%s</name>
                        %s
                    </urn1:CreateDomainRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $domain,
            $values
        );
        $function = "CreateDomainRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);

        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["CreateDomainResponse"];
    }


    public function createAccountAliasRequest($zimbraId, $alias)
    {

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:AddAccountAliasRequest>
                        <id>%s</id>
                        <alias>%s</alias>
                    </urn1:AddAccountAliasRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $zimbraId,
            $alias
        );
        $function = "AddAccountAliasRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false
            ;
        }
        return $result["soapBody"]["AddAccountAliasResponse"];
    }


    public function createDlsAliasRequest($zimbraId, $alias)
    {

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:AddDistributionListAliasRequest>
                        <id>%s</id>
                        <alias>%s</alias>
                    </urn1:AddDistributionListAliasRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $zimbraId,
            $alias
        );
        $function = "AddDistributionListAliasRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false
            ;
        }
        return $result["soapBody"]["AddDistributionListAliasResponse"];
    }

     // Cria uma lista de distribuição para o dominio setado
    public function createDistributionListRequest($name, array $info = null)
    {
        $values = '';
        if ($info) {
            foreach ($info as $key => $value) {
                $values .= sprintf("<urn1:a n=\"%s\">%s</urn1:a>\n", $key, $value);
            }
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:CreateDistributionListRequest>
                        <name>%s</name>
                        %s
                    </urn1:CreateDistributionListRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $name,
            $values
        );
        $function = "CreateDistributionListRequest";
        $response = $this->request($function, $xml);


        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }

        return $result["soapBody"]["CreateDistributionListResponse"];
    }

    // Adiciona uma conta para a lista setada.
    public function addDistributionListMemberRequest($idlist, $members = [])
    {
        $newMembers = '';
        foreach ($members as $member) {
            $newMembers .= "<dlm>{$member}</dlm>";
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:AddDistributionListMemberRequest id="%s">
                        %s
                    </urn1:AddDistributionListMemberRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $idlist,
            $newMembers
        );
        $function = "AddDistributionListMemberRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["AddDistributionListMemberResponse"];
    }
    // tras todas as lista de distribuição daquele dominio setado
    public function getAllDomainDistributionListsRequest($domain, $by = 'id')
    {
                $xml = sprintf(
                    '<soapenv:Envelope 
                        xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                        xmlns:urn="urn:zimbra" 
                        xmlns:urn1="urn:zimbraAdmin"
                    >
                        <soapenv:Body>
                            <urn1:GetAllDistributionListsRequest>
                                <domain by="%s">%s</domain>
                            </urn1:GetAllDistributionListsRequest>
                        </soapenv:Body>
                    </soapenv:Envelope>',
                    $by,
                    $domain
                );

        $function = "GetAllDistributionListsRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllDistributionListsResponse"];
    }


    // Deleta a lista de distribuição de acordo com o id setado (deve ser passado o id da lista, não o do dominio).gt
    public function deleteDistributionList($id)
    {

            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin"
                >
                    <soapenv:Body>
                        <urn1:DeleteDistributionListRequest id="%s">
                        </urn1:DeleteDistributionListRequest>
                    </soapenv:Body>
                </soapenv:Envelope>',
                $id
            );

        $function = "DeleteDistributionListRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["DeleteDistributionListResponse"];
    }


    public function deleteAccountRequest($zimbraId)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:DeleteAccountRequest>
                        <id>%s</id>
                    </urn1:DeleteAccountRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $zimbraId
        );

        $function = "DeleteAccountRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["DeleteAccountResponse"];
    }



    public function deleteMailAlias($zimbraId, $alias)
    {

            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin"
                >
                    <soapenv:Body>
                        <urn1:RemoveAccountAliasRequest>
                            <id>%s</id>
                            <alias>%s</alias>
                        </urn1:RemoveAccountAliasRequest>
                    </soapenv:Body>
                </soapenv:Envelope>',
                $zimbraId,
                $alias
            );

        $function = "RemoveAccountAliasRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["RemoveAccountAliasResponse"];
    }


    public function deleteDlsAlias($zimbraId, $alias)
    {

            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin"
                >
                    <soapenv:Body>
                        <urn1:RemoveDistributionListAliasRequest>
                            <id>%s</id>
                            <alias>%s</alias>
                        </urn1:RemoveDistributionListAliasRequest>
                    </soapenv:Body>
                </soapenv:Envelope>',
                $zimbraId,
                $alias
            );

        $function = "RemoveDistributionListAliasRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["RemoveDistributionListAliasResponse"];
    }

    public function deleteDistributionListMember($zimbraId, $members = [])
    {
        $toRemove = '';
        foreach ($members as $member) {
            $toRemove .= "<dlm>{$member}</dlm>";
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:RemoveDistributionListMemberRequest>
                        <id>%s</id>
                        %s
                    </urn1:RemoveDistributionListMemberRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $zimbraId,
            $toRemove
        );
        $function = "RemoveDistributionListMemberRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["RemoveDistributionListMemberResponse"];
    }



    public function modifyDistributionList($zimbraId, $info = [])
    {
        $values = '';
        if ($info) {
            foreach ($info as $key => $value) {
                $values .= sprintf("<urn1:a n=\"%s\">%s</urn1:a>\n", $key, $value);
            }
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:ModifyDistributionListRequest>
                        <id>%s</id>
                        %s
                    </urn1:ModifyDistributionListRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $zimbraId,
            $values
        );
        $function = "ModifyDistributionListRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["ModifyDistributionListResponse"];
    }




    private function checkForError($result)
    {
        if (!is_array($result)) {
            $this->errorMessage = 'Ocorreu um erro desonhecido.';
            return false;
        }
        if (!isset($result['soapBody'])) {
            $this->errorMessage = '[Zimbra] Erro desconhecido.';
            return true;
        }
        if (isset($result['soapBody']['soapFault'])) {
            if (isset($result['soapBody']['soapFault']['detail']['Error']['Code'])) {
                if (isset($this->errors[$result['soapBody']['soapFault']['detail']['Error']['Code']])) {
                    $this->errorMessage = sprintf(
                        'Erro: %s',
                        $this->errors[$result['soapBody']['soapFault']['detail']['Error']['Code']]
                    );
                    return true;
                }
                $this->errorMessage = sprintf(
                    'Erro [%s]',
                    $result['soapBody']['soapFault']['detail']['Error']['Code']
                );
            }
            return true;
        }
    }

    private function getSoapHeader()
    {
        $soapHeader = array(
            new SoapHeader(
                'urn:zimbra',
                'context',
                new SoapVar('<ns2:context><authToken>' . $this->authToken . '</authToken></ns2:context>', XSD_ANYXML)
            )
        );

        return $soapHeader;
    }


    /**
     * Faz a autenticação, e carrega o token de autenticação
     */
    private function authRequest()
    {
        $function = "AuthRequest";
        $arguments = array("AuthRequest" => array("account" => $this->user,"password" => $this->pass));

        $r = $this->request($function, $arguments);
        $this->authToken = $r->authToken;
    }

    /**
     * @param  string $function
     * @param  array  $arguments
     * @return mixed
     * Responsável por todas as requisições na API.
     * Recebe o nome da função a ser executada, e os parâmetros
     * @return $r
     */
    private function request($function, $arguments)
    {
        if ($this->soap == null) {
            $this->errorMessage = sprintf(
                'Não foi possivel se conectar ao servidor Zimbra [%s] [SOAP]',
                $this->connection
            );
            return false;
        }
        $soapHeader = null;
        //Se não autenticou, faz a autenticação
        if (!$this->authToken && $function != "AuthRequest") {
            //Novo auth, pois a forma antiga não funcionava na versão 9.0 do zimbra
            $this->newAuth();
            //  $this->AuthRequest();
        }
        //Se não for uma chamada de autenticação, seta o Header com o token de autenticação

        if ($function != "AuthRequest") {
            $soapHeader = array(
                new SoapHeader(
                    'urn:zimbra',
                    'context',
                    new SoapVar(
                        '<ns2:context><authToken>' . $this->authToken . '</authToken></ns2:context>',
                        XSD_ANYXML
                    )
                )
            );
            $this->soap->__setSoapHeaders($soapHeader);
        }

        if (is_array($arguments)) {
            $r = $this->soap->__call($function, $arguments);
        } else {
            $r = $this->soap->__doRequest($arguments, $this->url, $function, null);
        }

        $this->rawResponse = $r;
        return $r;
    }

    /**
     * Lista todos os métodos disponíveis na API, conforme o WSDL
     *
     * @return array
     */
    public function listMethods()
    {
        $r = $this->soap->__getFunctions();
        return $r;
    }

    public function listTypes()
    {
        $r = $this->soap->__getTypes();
        return $r;
    }


    public function newAuth()
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:AuthRequest>
                        <urn1:account>%s</urn1:account>
                        <urn1:password><![CDATA[%s]]></urn1:password>
                    </urn1:AuthRequest>           
                </soapenv:Body>
            </soapenv:Envelope>',
            $this->user,
            $this->pass
        );
        $function = "AuthRequest";
        $response = $this->request($function, $xml);
        if (!$response) {
            return false;
        }
        $result = $this->convertSoapResult($response);

        if (!isset($result['soapBody']['AuthResponse'])) {
            $this->checkForError($result);
            $this->errorMessage = sprintf(
                'Ocorreu um erro ao obter o token de autenticação [%s] ,%s',
                $this->connection,
                $this->errorMessage
            );
            throw new \Exception($this->errorMessage);
        }
        $this->authToken = $result['soapBody']['AuthResponse']['authToken'];
    }
    /**
     * Busca informações do Backup
     *
     * @return $r
     */
    public function backupQueryRequest($queryParameters)
    {
        $function = "BackupQueryRequest";
        $arguments = array("BackupQueryRequest" => array("query" => $queryParameters));
        $r = $this->request($function, $arguments);
        return $r;
    }

    /**
     * Busca informações do Backup
     *
     * @return $r
     */
    public function backupAccountQueryRequest($queryParameters)
    {
        $function = "BackupAccountQueryRequest";
        $arguments = array("BackupAccountQueryRequest" => array("query" => $queryParameters));
        $r = $this->request($function, $arguments);
        return $r;
    }


    public function getAllAccountsRequest($domain = null)
    {
        $accountsBy = '';
        if ($domain) {
            $accountsBy = sprintf('<urn1:domain by="name">%s</urn1:domain>', $domain);
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                <urn1:GetAllAccountsRequest>

                %s
                    </urn1:GetAllAccountsRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $accountsBy
        );
        $function = "GetAllAccountsRequest";
        $response = $this->request($function, $xml);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllAccountsResponse"];
    }


    public function getAllDomainsRequest()
    {
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
            <soapenv:Body>
                <urn1:GetAllDomainsRequest>
                </urn1:GetAllDomainsRequest>
            </soapenv:Body>
        </soapenv:Envelope>';
        $function = "GetAllDomainsRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        return $result["soapBody"]["GetAllDomainsResponse"];
    }

    public function getAllResourcesRequest($domain = null)
    {

        $accountsBy = '';
        if ($domain) {
            $accountsBy = sprintf('<urn1:domain by="name">%s</urn1:domain>', $domain);
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetAllCalendarResourcesRequest>
                    %s
                    </urn1:GetAllCalendarResourcesRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $accountsBy
        );
        $function = "GetAllCalendarResourcesRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllCalendarResourcesResponse"];
    }

    public function getCalendarResourceRequest($resource, $by = 'name')
    {
            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin"
                >
                    <soapenv:Body>
                        <urn1:GetCalendarResourceRequest applyCos="0">
                            <urn1:calresource by="%s">%s</urn1:calresource>
                        </urn1:GetCalendarResourceRequest>
                    </soapenv:Body>
                </soapenv:Envelope>',
                $by,
                $resource
            );
        $function = "GetCalendarResourceRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetCalendarResourceResponse"];
    }

     // Cria uma lista de distribuição para o dominio setado
    public function createCalendarResourceRequest($name, array $info = null)
    {
        $values = '';
        if ($info) {
            foreach ($info as $key => $value) {
                $values .= sprintf("<urn1:a n=\"%s\">%s</urn1:a>\n", $key, $value);
            }
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:CreateCalendarResourceRequest>
                        <name>%s</name>
                        %s
                    </urn1:CreateCalendarResourceRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $name,
            $values
        );
        $function = "CreateCalendarResourceRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["CreateCalendarResourceResponse"];
    }

    public function modifyCalendarResource($zimbraId, $info = [])
    {
        $values = '';
        if ($info) {
            foreach ($info as $key => $value) {
                $values .= sprintf("<urn1:a n=\"%s\">%s</urn1:a>\n", $key, $value);
            }
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:ModifyCalendarResourceRequest>
                        <id>%s</id>
                        %s
                    </urn1:ModifyCalendarResourceRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $zimbraId,
            $values
        );
        $function = "ModifyCalendarResourceRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["ModifyCalendarResourceResponse"];
    }

    public function deleteCalendarResource($zimbraId)
    {

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:DeleteCalendarResourceRequest>
                        <id>%s</id>
                    </urn1:DeleteCalendarResourceRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $zimbraId
        );
        $function = "DeleteCalendarResourceRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["DeleteCalendarResourceResponse"];
    }

    public function getAccountInfoRequest($email)
    {
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
                   <soapenv:Body>
                      <urn1:GetAccountInfoRequest>
                         <urn1:account by="name">' . $email . '</urn1:account>
                      </urn1:GetAccountInfoRequest>
                   </soapenv:Body>
                </soapenv:Envelope>';

        $function = "GetAccountInfoRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAccountInfoResponse"];
    }

    public function getAccountRequest($id, $attribute = null)
    {

        $attrs = '';
        if ($attribute) {
            $attrs = 'attrs="' . $attribute . '"';
        }
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
                   <soapenv:Body>
                      <urn1:GetAccountRequest ' . $attrs . '>
                         <urn1:account by="id">' . $id . '</urn1:account>
                      </urn1:GetAccountRequest>
                   </soapenv:Body>
                </soapenv:Envelope>';

        $function = "GetAccountRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        return $result["soapBody"]["GetAccountResponse"];
    }

    public function getAccountByName($name)
    {

        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
                   <soapenv:Body>
                   <urn1:GetAccountRequest>
                         <urn1:account by="name">' . $name . '</urn1:account>
                      </urn1:GetAccountRequest>
                   </soapenv:Body>
                </soapenv:Envelope>';

        $function = "GetAccountRequest";
        $response = $this->request($function, $xml);
        if($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAccountResponse"];
    }

    public function getAllMailboxesRequest()
    {

        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
                   <soapenv:Body>
                      <urn1:GetAllMailboxesRequest>
                      </urn1:GetAllMailboxesRequest>
                   </soapenv:Body>
                </soapenv:Envelope>';

        $function = "GetAllMailboxesRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        return $result["soapBody"]["GetAllMailboxesResponse"];
    }


    public function getQuotaUsageRequest($domain = null)
    {
        $byDomain = null;
        if ($domain) {
            $byDomain = sprintf('<urn1:domain>%s</urn1:domain>', $domain);
        }

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetQuotaUsageRequest  allServers="1" refresh="1">
                        %s
                    </urn1:GetQuotaUsageRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $byDomain
        );
        $function = "GetQuotaUsageRequest";
        $response = $this->request($function, $xml);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetQuotaUsageResponse"];
    }


    public function getAllAdminAccountsRequest()
    {
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
            <soapenv:Body>
                <urn1:GetAllAdminAccountsRequest allServers="1" refresh="1">
                </urn1:GetAllAdminAccountsRequest>
            </soapenv:Body>
        </soapenv:Envelope>';

        $function = "GetAllAdminAccountsRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        return $result["soapBody"]["GetAllAdminAccountsResponse"];
    }


    public function getAllDistributionListsRequest()
    {
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
            <soapenv:Body>
                <urn1:GetAllDistributionListsRequest>
                </urn1:GetAllDistributionListsRequest>
            </soapenv:Body>
        </soapenv:Envelope>';

        $function = "GetAllDistributionListsRequest";
        $response = $this->request($function, $xml);

        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllDistributionListsResponse"];
    }

    public function setDistributionListOwnersRequest($dl, array $ownersArr = [], $action = 'addOwners', $by = 'name')
    {
        if (count($ownersArr)) {
            $owners = '';
            foreach ($ownersArr as $owner) {
                $owners .= sprintf(
                    '<owner by="name" type="%s">%s</owner>',
                    'usr',
                    trim($owner)
                );
            }
        }

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" xmlns:urn1="urn:zimbraAccount">
                <soapenv:Body>
                    <urn1:DistributionListActionRequest>
                        <urn1:dl by="%s">%s</urn1:dl>
                        <urn1:action op="%s">
                            %s
                        </urn1:action>
                    </urn1:DistributionListActionRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $by,
            $dl,
            $action,
            $owners
        );
        $function = "DistributionListActionRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["DistributionListActionResponse"];
    }

    public function getDistributionListRequest($dl, $by = 'name', $limit = 0)
    {
            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin">
                    <soapenv:Body>
                        <urn1:GetDistributionListRequest 
                            limit="%s" >
                            <urn1:dl by="%s">%s</urn1:dl>
                        </urn1:GetDistributionListRequest>
                    </soapenv:Body>
                </soapenv:Envelope>',
                $limit,
                $by,
                $dl
            );
        $function = "GetDistributionListRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
    }

    public function setPasswordRequest($id, $newPassword)
    {

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:SetPasswordRequest>
                        <id>%s</id>
                        <newPassword>%s</newPassword>
                    </urn1:SetPasswordRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $id,
            $newPassword
        );
        $function = "SetPasswordRequest";
        $response = $this->request($function, $xml);

        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["SetPasswordResponse"];
    }
    protected function replaceXml($arg)
    {
        return str_replace(['"',"'","<",">","&"], ["&quot;","&apos;","&lt;","&gt;","&amp;"], $arg);
    }
    public function modifyAccountRequest($id, array $info)
    {
        $values = false;
        foreach ($info as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $position) {
                    $values .= sprintf(
                        "<urn1:a n=\"%s\">%s</urn1:a>\n",
                        $this->replaceXml($key),
                        $this->replaceXml($position)
                    );
                }
                continue;
            }
            $values .= sprintf(
                "<urn1:a n=\"%s\">%s</urn1:a>\n",
                $this->replaceXml($key),
                $this->replaceXml($value)
            );
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:ModifyAccountRequest>
                        <id>%s</id>
                        %s
                    </urn1:ModifyAccountRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $id,
            $values
        );
        $function = "ModifyAccountRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["ModifyAccountResponse"];
    }


    public function getCosRequest($cos, $by = 'id')
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetCosRequest>
                        <cos by="%s">%s</cos>
                    </urn1:GetCosRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $by,
            $cos
        );
        $function = "GetCosRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetCosResponse"];
    }

    public function copyCosRequest($newCos, $cos, $by = 'id')
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:CopyCosRequest>
                        <name>%s</name>
                        <cos by="%s">%s</cos>
                    </urn1:CopyCosRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $newCos,
            $by,
            $cos
        );
        $function = "CopyCosRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["CopyCosResponse"];
    }


    public function countAccount($domain, $by = 'name')
    {

        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:CountAccountRequest>
                        <domain by="%s">%s</domain>
                    </urn1:CountAccountRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $by,
            $domain
        );
        $function = "CountAccountRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["CountAccountResponse"];
    }

    public function getAllZimlets()
    {
            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin"
                >
                    <soapenv:Body>
                        <urn1:GetAllZimletsRequest >
                        </urn1:GetAllZimletsRequest>
                    </soapenv:Body>
                </soapenv:Envelope>'
            );
        $function = "GetAllZimletsRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllZimletsResponse"];
    }

    public function getZimlet($zimlet)
    {
            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin"
                >
                    <soapenv:Body>
                        <urn1:GetZimletRequest>
                            <urn1:zimlet name="%s" />
                        </urn1:GetZimletRequest>
                    </soapenv:Body>
                </soapenv:Envelope>',
                $zimlet
            );
        $function = "GetZimletRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetZimletResponse"];
    }


    public function checkHealth()
    {

        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
            <soapenv:Body>
            <urn1:CheckHealthRequest>
                <urn1:domain>%s</urn1:domain>
                <urn1:types>%s</urn1:types>
                %s
            </urn1:CheckHealthRequest>
            </soapenv:Body>
        </soapenv:Envelope>';
        $function = "CheckHealthRequest";
        $response = $this->request($function, $xml);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["CheckHealthResponse"];
    }

    public function searchDirectoryRequest($domain, $type = 'accounts', $attrs = [], $max = false)
    {
        $options = '';
        $maxResult = '';
        if ($max) {
            $maxResult = sprintf("<urn1:maxResults>%s</urn1:maxResults>\n", $max);
        }
        foreach ($attrs as $key => $value) {
            $options .= sprintf("<urn1:%s>%s</urn1:%s>\n", $key, $this->replaceXml($value), $key);
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:SearchDirectoryRequest>
                        %s
                        <urn1:domain>%s</urn1:domain>
                        <urn1:types>%s</urn1:types>
                        %s
                    </urn1:SearchDirectoryRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $maxResult,
            $domain,
            $type,
            $options
        );
        $function = "SearchDirectoryRequest";
        $response = $this->request($function, $xml);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["SearchDirectoryResponse"];
    }


    public function getMailboxRequest($id)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetMailboxRequest>
                        <urn1:mbox id="%s"/>
                    </urn1:GetMailboxRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $id
        );
        $function = "GetMailboxRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        return $result;
    }

    public function batchModifyAccountRequest($accounts)
    {
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
            <soapenv:Body>
                <urn1:ModifyAccountRequest id="b8a7c324-d2fd-4b3d-bf4f-2ba83254a322">
                    <urn1:a n="displayName">teste lote 3</urn1:a>
                </urn1:ModifyAccountRequest>
                <urn1:ModifyAccountRequest id="6dc5e7b7-7b6d-41d1-87a2-29700bca01ef">
                    <urn1:a n="displayName">teste lote 4</urn1:a>
                </urn1:ModifyAccountRequest>
            </soapenv:Body>
        </soapenv:Envelope>';
        $function = "ModifyAccountRequest";
        $response = $this->request($function, $xml);
    }

    public function versionCheck($action = 'check')
    {
            $xml = sprintf(
                '<soapenv:Envelope 
                    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                    xmlns:urn="urn:zimbra" 
                    xmlns:urn1="urn:zimbraAdmin"
                >
                    <soapenv:Body>
                        <urn1:GetVersionInfoRequest >
                        </urn1:GetVersionInfoRequest>
                    </soapenv:Body>
                </soapenv:Envelope>'
            );
        $function = "GetVersionInfoRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetVersionInfoResponse"];
    }

    public function getAllServersRequest()
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetAllServersRequest >
                    </urn1:GetAllServersRequest>
                </soapenv:Body>
            </soapenv:Envelope>'
        );
        $function = "GetAllServersRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllServersResponse"];
    }

    public function getServerRequest($server, $by = 'name')
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetServerRequest>
                        <urn1:server by="%s">%s</urn1:server>
                    </urn1:GetServerRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $by,
            $server
        );
        $function = "GetServerRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetServerResponse"];
    }


    public function getServiceStatusRequest()
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetServiceStatusRequest >
                    </urn1:GetServiceStatusRequest>
                </soapenv:Body>
            </soapenv:Envelope>'
        );
        $function = "GetServiceStatusRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetServiceStatusResponse"];
    }

    public function getAllConfigRequest()
    {
        $xml = '<soapenv:Envelope 
            xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:urn="urn:zimbra" 
            xmlns:urn1="urn:zimbraAdmin"
        >
                    <soapenv:Body>
                        <urn1:GetAllConfigRequest >
                        </urn1:GetAllConfigRequest>
                    </soapenv:Body>
                </soapenv:Envelope>';
        $function = "GetAllConfigRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAllConfigResponse"];
    }


    public function modifyConfigRequest($attr, $value)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:ModifyConfigRequest>
                        <urn1:a n="%s">%s</urn1:a>
                    </urn1:ModifyConfigRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $attr,
            $value
        );
        $function = "ModifyConfigRequest";
        $response = $this->request($function, $xml);
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["ModifyConfigResponse"];
    }

    public function getAccountMembershipRequest($account, $by = 'name')
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetAccountMembershipRequest>
                        <urn1:account by="%s">%s</urn1:account>
                    </urn1:GetAccountMembershipRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $by,
            $account
        );

        $function = "GetAccountMembershipRequest";
        $response = $this->request($function, $xml);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetAccountMembershipResponse"];
    }

    public function getDistributionListMembershipRequest($dl, $by = 'name')
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAdmin"
            >
                <soapenv:Body>
                    <urn1:GetDistributionListMembershipRequest>
                        <urn1:dl by="%s">%s</urn1:dl>
                    </urn1:GetDistributionListMembershipRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $by,
            $dl
        );

        $function = "GetDistributionListMembershipRequest";
        $response = $this->request($function, $xml);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetDistributionListMembershipResponse"];
    }

    public function getDistributionListMembersRequest(string $dl)
    {
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" 
                xmlns:urn1="urn:zimbraAccount">
                <soapenv:Body>
                    <urn1:GetDistributionListMembersRequest>
                        <urn1:dl>%s</urn1:dl>
                    </urn1:GetDistributionListMembersRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $dl
        );
        $function = "GetDistributionListMembersRequest";
        $response = $this->request($function, $xml);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["GetDistributionListMembersResponse"];
    }

    public function subscribeDistributionListRequest($dl, bool $op, $by = 'name')
    {
        $opString = 'unsubscribe';
        if ($op) {
            $opString = 'subscribe';
        }
        $xml = sprintf(
            '<soapenv:Envelope 
                xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
                xmlns:urn="urn:zimbra" xm
                lns:urn1="urn:zimbraAccount"
            >
                <soapenv:Body>
                    <urn1:SubscribeDistributionListRequest>
                        <urn1:op>%s</urn1:op>
                        <urn1:dl by="%s">%s</urn1:dl>
                    </urn1:SubscribeDistributionListRequest>
                </soapenv:Body>
            </soapenv:Envelope>',
            $opString,
            $by,
            $dl
        );

        $function = "SubscribeDistributionListRequest";
        $response = $this->request($function, $xml);
        dd($response);
        if ($this->forceRaw) {
            return $response;
        }
        $result = $this->convertSoapResult($response);
        if ($this->checkForError($result)) {
            return false;
        }
        return $result["soapBody"]["SubscribeDistributionListResponse"];
    }
    public static function decryptPassword($pass)
    {
        $decryptedPass = Helper::decryptString(
            $pass,
            base64_decode(env('ENCRYPT_KEY')),
            base64_decode(env('ENCRYPT_BYTES')),
            'AES-256-CBC'
        );
        return $decryptedPass;
    }
}
