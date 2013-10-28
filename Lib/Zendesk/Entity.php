<?php

abstract class Zendesk_Entity {
    public function __construct(Zendesk $client) {
        $this->client = $client;
    }

    protected function CleanSettings ($settings, $availableKeys)
    {
        $cleanSettings = array();
        foreach ($settings as $key => $value)
        {
            if (in_array($key, $availableKeys))
            {
                $cleanSettings[$key] = $value;
            }
        }
        return $cleanSettings;
    }
}