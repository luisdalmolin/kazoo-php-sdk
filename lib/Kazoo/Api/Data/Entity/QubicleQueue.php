<?php

namespace Kazoo\Api\Data\Entity;

use Kazoo\Api\Data\AbstractEntity;

class QubicleQueue extends AbstractEntity
{
    protected static $_schema_name = "qubicle_queues.json";
    protected static $_callflow_module = "qubicle";

    public function initDefaultValues() {
        
    }
}
