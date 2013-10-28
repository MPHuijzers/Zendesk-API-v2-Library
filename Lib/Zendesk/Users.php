<?php
/*
Zendesk Users endpoint
more information: http://developer.zendesk.com/documentation/rest_api/users.html
*/

class Zendesk_Users extends Zendesk_Entity
{
    const ENDPOINT      = 'users';

    private $userRoles       = array ('end-user', 'agent', 'admin');
    private $availableKeys   = array ('name', 'time_zone', 'locale_id','organization_id', 'role', 'verified', 'email', 'phone', 'photo');

    public function all()
    {
        $endpoint = self::ENDPOINT;
        return $this->client->Request($endpoint);
    }

    public function get($id)
    {
        if(!is_int($id))
        {
            throw new InvalidArgumentException('Id must be an integer');
        }
        $endpoint = self::ENDPOINT . '/' . $id;
        return $this->client->Request($endpoint);
    }

    public function create($role, $email, $name, $settings = array())
    {
        //check if valid role
        if(!in_array($role, $this->userRoles))
        {
            throw new InvalidArgumentException('Role parameter is invalid. Must be one of "'.implode('", "', $this->userRoles).'".');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
            throw new InvalidArgumentException('Email parameter is invalid.');
        }

        $data = array (
            'role' => $role,
            'email' => $email,
            'name' => (string) $name,
        );
        $data = array_merge($settings, $data); // array order ensures that role, email and name are not overwritten
        $data = parent::CleanSettings($data, $this->availableKeys);

        $endpoint = self::ENDPOINT;
        return $this->client->Request($endpoint, "POST", array("user" => $data));
    }

    public function update($id, $settings = array())
    {
        $data = parent::CleanSettings($settings, $this->availableKeys);
        $endpoint = self::ENDPOINT . '/' . $id;

        return $this->client->Request($endpoint, "PUT", array("user" => $data));
    }

    public function setpassword($id, $password)
    {
        if(!is_int($id))
        {
            throw new InvalidArgumentException('Id must be an integer');
        }
        if(!is_string($password) || (strlen($password) < 2))
        {
            throw new InvalidArgumentException('Password is not a valid string');
        }
        $endpoint = self::ENDPOINT . '/' . $id . '/password';
        return $this->client->Request($endpoint, "POST", array("password" => $password));
    }
}
