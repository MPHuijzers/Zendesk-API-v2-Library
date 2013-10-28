<?php

class Zendesk_Organizations extends Zendesk_Entity
{
    const ENDPOINT = 'organizations';

    private $availableKeys   = array ('external_id', 'name', 'domain_names','details', 'notes', 'group_id', 'shared_tickets', 'shared_comments', 'tags', 'organization_fields');

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

    public function create($name, $settings = array())
    {
        $data = array (
            'name' => (string) $name,
        );

        $data = parent::CleanSettings(array_merge($settings, $data), $this->availableKeys);
        $endpoint = self::ENDPOINT;

        return $this->client->Request($endpoint, "POST", array("organization" => $data));
    }

    public function update($id, $settings = array())
    {
        $data = parent::CleanSettings($settings, $this->availableKeys);
        $endpoint = self::ENDPOINT . '/' . $id;

        return $this->client->Request($endpoint, "PUT", array("organization" => $data));
    }
}


