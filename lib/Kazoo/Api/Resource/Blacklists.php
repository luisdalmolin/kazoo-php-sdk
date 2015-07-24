<?php

namespace Kazoo\Api\Resource;
use Kazoo\Api\AbstractResource;

/**
 * 
 */
class Blacklists extends AbstractResource {
    
    protected static $_entity_class = "Kazoo\\Api\\Data\\Entity\\Blacklist";
    protected static $_entity_collection_class = "Kazoo\\Api\\Data\\Collection\\BlacklistCollection";
    
}