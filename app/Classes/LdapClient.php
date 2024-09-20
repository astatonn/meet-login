<?php

namespace App\Classes;

class LdapClient
{
    private $host = "";
    private $connection;
    private $user;
    private $pass;
    private $searchBase;
    private $filter;
    private $searchFilter;
    private $auth;
    private $searchResponse;
    private $filteredResponse;
    public function __construct(string $host)
    {
        $this->host = $host;
        $this->connect($this->connection);
        $this->setLdapOpt();
    }
    
    private function setLdapOpt()
    {
        ldap_set_option ($this->connection, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    }

    public function connect()
    {
        $this->connection = ldap_connect($this->host);
        if($this->connection) {
            return true;
        }

        print sprintf("Erro ao conectar no servidor LDAP: %s \n",$this->host);
        return false;
    }



    public function authenticateUser(bool $showError = false)
    {
        if($showError){
            $this->auth = ldap_bind($this->connection,$this->user,$this->pass);
        }else{
            $this->auth = @ldap_bind($this->connection,$this->user,$this->pass);
        }
        if(!$this->auth){
            return false;
        }
        return true;

    }

    public function search()
    {
        if($this->searchFilter){
            $this->searchResponse = ldap_search($this->connection, $this->searchBase, $this->filter,$this->searchFilter);
        }else{
            $this->searchResponse = ldap_search($this->connection, $this->searchBase, $this->filter);
        }
            $this->filteredResponse = ldap_get_entries($this->connection, $this->searchResponse);
            
    }
    /*
    *
    * Getters e Setters
    */
    public function setSearchFilter(array $filter)
    {
        $this->searchFilter = $filter;
    }

    public function getSearchFilter()
    {
        return $this->searchFilter;
    }

    public function getResponse()
    {
        return $this->filteredResponse;
    }
    
    public function getAuth()
    {
        return $this->auth;
    }

    public function getConnection()
    {
        return $this->connection;
    }

    public function setConnection(string $connection)
    {
        $this->connection = $connection;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(string $user)
    {
        $this->user = $user;
    }

    public function setPass(string $pass)
    {
        $this->pass = $pass;
    }

    public function getSearchBase()
    {
        return $this->searchBase;
    }

    public function setSearchBase($search)
    {
        $this->searchBase = $search;
    }

    public function getFilter()
    {
        return $this->filter;
    }

    public function setFilter($filter)
    {
        $this->filter = $filter;
    }


}