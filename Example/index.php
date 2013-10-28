<?php

require_once('ZendeskWrapper.php');
require_once('config.php');

define('SC_TRIAL_USER', 0);
define('SC_CUSTOMER_USER', 1);
define('SC_UPGRADE_USER', 2);

$scenario = SC_TRIAL_USER;

$zd = new ZendeskWrapper($account, $apiKey, $user, $localHost);

$sample = array (
    'email' => 'john@test-company.com',
    'name' => 'John Doe',
    'password' => 'TestTest',
    'orgName' => 'Test Company',
);

switch ($scenario) {
    case 0:
        $result = $zd->CreateTrialUser($sample['email'],$sample['name'],$sample['password'],$sample['orgName']);
        break;
    case 1:
        $result = $zd->CreatePartnerUser($sample['email'],$sample['name'],$sample['password'],$sample['orgName']);
        break;
    case 2:
        $result = $zd->ChangeToPartnerGroup($sample['email']);
        break;
    default:
        $result = 'No valid scenario selected';
}

// DUMP SCENARIO
var_dump($result);



