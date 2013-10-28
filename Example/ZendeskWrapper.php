<?php

require_once('../lib/Zendesk.php');

class ZendeskWrapper {

    const GROUP_NAME_TRIAL 	= 'test_partners';
    const GROUP_NAME_SERVICE = 'service_partners_new';

    private $zd;
    
    public function __construct($account, $apiKey, $user, $localHost = false)
    {

		if ($account && $apiKey && $user)
        {
			$this->zd = new Zendesk($account, $apiKey, $user, $localHost);
		}
		else
        {
			throw new Exception('Invalid Zendesk credentials');
		}
    }

    public function CreateTrialUser($email, $name, $password, $organizationName){return self::CreateUser($email, $name, $password, $organizationName, self::GROUP_NAME_TRIAL);}
    public function CreatePartnerUser($email, $name, $password, $organizationName) {return self::CreateUser($email, $name, $password, $organizationName, self::GROUP_NAME_SERVICE);}
    public function ChangeToPartnerGroup($email) {return self::UpdateOrganizationGroup($email, self::GROUP_NAME_SERVICE);}
    
    private function CreateUser($email, $name, $password, $organizationName, $groupName) {
        $user = self::GetUserByEmail($email);
        if (!$user) {
            $user = self::AddUser($email, $name, $password);
        }
        if (!$user) return false; // all failed

        if (array_key_exists('organization_id', $user) && is_int($user['organization_id']))
        {
            return $user; // User company exists, so we're done
        }

        $organization = self::GetOrganizationByName($organizationName);

        if(!$organization)
        {
            $organization = self::AddOrganization($organizationName, $groupName);
        }

        // add Organization to User by IDs
        return self::AddOrganizationToUser($user['id'], $organization['id']);
    }

    private function UpdateOrganizationGroup ($email, $groupName)
    {
        $user = self::GetUserByEmail($email);
        if (!$user || !is_int($user['organization_id'])) return false; // No organization id found.

        $settings = array (
            'tags' => array(''.$groupName),
            'organization_fields' => array('organisation_group'=> ''.$groupName),
        );

        return self::UpdateOrganization($user['organization_id'], $settings);
    }

    private function UpdateOrganization($id, $settings)
    {
        $zd = $this->zd;

        $result = $zd->organizations->update($id, $settings);

        if(!array_key_exists('organization',$result)) return false; // user not created

        return $result['organization']; //return array with user details
    }

    private function AddOrganizationToUser ($userId, $organizationId)
    {
        $zd = $this->zd;
        $settings = array (
            'organization_id' => $organizationId,
        );

        $result = $zd->users->update($userId, $settings);

        if(!array_key_exists('user',$result)) return false; // user not created

        return $result['user']; //return array with user details
    }

    private function  AddUser ($email, $name, $password)
    {
        $zd = $this->zd;
        $settings = array (
            'verified' => true,
        );
        $result = $zd->users->create('end-user', $email, $name, $settings);

        if(!array_key_exists('user',$result)) return false; // user not created

        $zd->users->setpassword($result['user']['id'], $password);

        return $result['user']; //return array with user details
    }

    private function  AddOrganization ($name, $groupName)
    {
        $zd = $this->zd;
        $settings = array (
            'tags' => array(''.$groupName),
            'organization_fields' => array('organisation_group'=> ''.$groupName),
        );
        $result = $zd->organizations->create($name, $settings);

        if(!array_key_exists('organization',$result)) return false; // organization not created

        return $result['organization']; //return array with organization details
    }

    private function GetOrganizationByName ($name)
    {
        $zd = $this->zd;
        $search = array (
            'name' => (string) $name,
        );
        $result = $zd->search->organizations($search);

        if(array_key_exists('count',$result) && $result['count'] === 1)
        {
            return $result['results'][0]; //return array with organisation details
        }
        return false;
    }

    private function GetUserByEmail($email)
    {
        $zd = $this->zd;
        $search = array (
            'email' => (string) $email,
        );
        $result = $zd->search->users($search);

        if(array_key_exists('count',$result) && $result['count'] === 1)
        {
            return $result['results'][0]; //return array with organisation details
        }
        return false;
    }
}