<?php

/**
 * PhpOpenerp
 * simple library to connect to openerp by using xmlrpc and php
 *
 * @author Bastian Ike <thebod@thebod.de>
 * @copyright Copyright (c) 2012, Bastian Ike
 * @license http://creativecommons.org/licenses/by/3.0/ CC-BY 3.0
 * @todo implement json-rpc... someday...
 * @todo implement getLastResponse/Request-wrapper
 */
final class PhpOpenerp {
    /**
     * openerp common client
     * @var Zend_XmlRpc_Client
     * @access private
     */
    private $_commonClient;

    /**
     * openerp object client
     * @var Zend_XmlRpc_Client
     * @access private
     */
    private $_objectClient;

    /**
     * configuration array
     * @var array
     * @access private
     */
    private $_config;

    /**
     * configuration file, can be overriden by instance()
     * @var string
     * @access private
     */
    private $_configFile = 'config.php';

    /**
     * singleton instance
     * @var PhpOpenerp
     * @access private
     */
    private static $_instance = false;

    /**
     * log file
     * @todo configurable
     * @var string
     * @access private
     */
    private static $_logfile = '/tmp/phpopenerp.log';

    /**
     * singleton instance getter
     * yes, i love singletons ;)
     * @access public
     * @param string|null $configFile
     * @return PhpOpenerp
     */
    public function getInstance($configFile = null) {
        if(!self::$_instance) {
            self::$_instance = new PhpOpenerp();
        }

        if($configFile) {
            self::$_instance->_configFile = $configFile;
        }

        self::$_instance->loadConfig()->init();

        return self::$_instance;
    }

    /**
     * static logger, logs $string to $_logfile
     * @todo implement better log functions
     * @access public
     * @param string $string
     */
    public static function log($string) {
        @file_put_contents(self::$_logfile, $string);
    }

    /**
     * loads configuration
     * @access public
     * @return PhpOpenerp
     */
    public function loadConfig() {
        include($this->_configFile);

        $this->_config = $phpOpenerpConfig;

        return $this;
    }

    /**
     * init PhpOpenerp
     * set up common and object client and tries to login
     * @return PhpOpenerp
     */
    public function init() {
        $this->_commonClient = new Zend_XmlRpc_Client($this->_config['url'] . '/xmlrpc/common');
        $this->_objectClient = new Zend_XmlRpc_Client($this->_config['url'] . '/xmlrpc/object');

        // openerp doesn't supports system.methodSignature -.-
        $this->_commonClient->setSkipSystemLookup(true);
        $this->_objectClient->setSkipSystemLookup(true);

        try {
            $this->_config['userid'] = $this->_commonClient->call('login', array(
                $this->_config['database'],
                $this->_config['username'],
                $this->_config['password'],
            ));
        } catch (Zend_XmlRpc_Client_HttpException $e) {
            self::log('HTTP error: ' . $e->getMessage());
        } catch (Zend_XmlRpc_Client_FaultException $e) {
            self::log('XmlRpc Error! ' . $e->getMessage());
        }

        if(!$this->_config['userid']) {
            self::log('Login failed for user ' . $this->_config['username']);
        }

        return $this;
    }

    /**
     * hooks search()-calls to add a missing empty array to prevent errors
     * @access private
     * @param array $arguments
     * @return array
     */
    private function _func_search($arguments) {
        if(!isset($arguments[1])) {
            $arguments[1] = array();
        }

        return $arguments;
    }

    /**
     * called by calls on PhpOpenerp instance.
     * sets up arguments and pass them to openerp
     * @param string $func
     * @param array $arguments
     * @return mixed
     */
    public function __call($func, $arguments) {
        // handle hooks, e.g. for search-calls
        $hookfunc = '_func_' . $func;
        if(method_exists($this, $hookfunc)) {
            $arguments = $this->$hookfunc($arguments);
        }

        // first argument must be always the object
        $object = $arguments[0];
        unset($arguments[0]);

        // arguments needed by openerp, like database and user
        $args = array(
            $this->_config['database'],
            $this->_config['userid'],
            $this->_config['password'],
            $object,
            $func,
        );

        // merge argument arrays
        $args = array_merge($args, $arguments);

        try {
            // call execute and pass the arguments
            $result = $this->_objectClient->call('execute', $args);
        } catch (Zend_XmlRpc_Client_HttpException $e) {
            self::log('HTTP error: ' . $e->getMessage());
            return false;
        } catch (Zend_XmlRpc_Client_FaultException $e) {
            self::log(
                    "XmlRpc Error!\n".
                    $e->getMessage()."\n".
                    $this->_objectClient->getLastRequest()."\n".
                    $this->_objectClient->getLastResponse()."\n".
                    "------------------\n\n"
            );
            return false;
        }

        return $result;
    }
}

