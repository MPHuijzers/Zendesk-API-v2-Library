<?php

class Zendesk_Search extends Zendesk_Entity
{
    const ENDPOINT = 'search';

    private $availableTypes = array ('ticket','comment','user','organization','group','entry','topic');

    public function users($queryArray, $orderBy=null, $sort=null)
    {
        return self::exec('user', $queryArray, $orderBy=null, $sort=null);
    }

    public function organizations($queryArray, $orderBy=null, $sort=null)
    {
        return self::exec('organization', $queryArray, $orderBy=null, $sort=null);
    }

    public function exec($type, $queryArray, $orderBy=null, $sort=null)
    {
        if(!in_array($type, $this->availableTypes)) {
            throw new InvalidArgumentException('Unknown search type');
        }
        if(!is_array($queryArray)) {
            throw new InvalidArgumentException('queryArray must be an array');
        }
        $endpoint = self::ENDPOINT;
        $searchQuery = "type:$type ";

        foreach ($queryArray as $key => $value)
        {
            $searchQuery .= "$key:$value ";
        }

        //optional ordering and sorting
        if($orderBy && $sort && ($sort == 'asc' || $sort == 'desc'))
        {
            $searchQuery .= "order_by:$orderBy sort:$sort";
        }

        return $this->client->Request($endpoint, "GET", array('query' => $searchQuery));
    }
}

