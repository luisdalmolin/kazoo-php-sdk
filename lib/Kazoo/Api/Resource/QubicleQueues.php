<?php

namespace Kazoo\Api\Resource;
use Kazoo\Api\AbstractResource;

/**
 * 
 */
class QubicleQueues extends AbstractResource {
    
    protected static $_entity_class = "Kazoo\\Api\\Data\\Entity\\QubicleQueue";
    protected static $_entity_collection_class = "Kazoo\\Api\\Data\\Collection\\QubicleQueueCollection";
    
}