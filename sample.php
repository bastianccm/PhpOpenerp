<?php

/**
 * PhpOpenerp
 * simple library to connect to openerp by using xmlrpc and php
 *
 * example file
 *
 * @author Bastian Ike <thebod@thebod.de>
 * @copyright Copyright (c) 2012, Bastian Ike
 * @license http://creativecommons.org/licenses/by/3.0/ CC-BY 3.0
 */

// load zend autoloader for testing purposes
require_once 'Zend/Loader.php';
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->setFallbackAutoloader(true);

// include PhpOpenerp class
require_once 'lib/PhpOpenerp.php';

// load PhpOpenerp by getting an instance
$openerp = PhpOpenerp::getInstance();

// retrieve a list of all res.partner ids
$partner_ids = $openerp->search('res.partner');
// alternative, select everyone which name starts is like 'sh'
//$partner_ids = $openerp->search('res.partner', array(array('name', 'ilike', 'sh')));
//
// read the name and language of every partner
$partners = $openerp->read('res.partner', $partner_ids, array('name', 'lang'));

// output of every partner
foreach($partners as $partner) {
    echo $partner['id'] . ": '" . $partner['name'] . "' speaks " . $partner['lang'] . "\n";
}

