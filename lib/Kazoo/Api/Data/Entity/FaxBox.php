<?php

namespace Kazoo\Api\Data\Entity;

use Kazoo\Api\Data\AbstractEntity;

class FaxBox extends AbstractEntity {

    protected static $_schema_name = "faxbox.json";
    protected static $_callflow_module = "faxbox";

    public function initDefaultValues() {
        
    }

    public function getCallflowDefaultData() {
        $this->_default_callflow_data->id = $this->id;
        return $this->_default_callflow_data;
    }

}
