<?php

namespace Kazoo\Api\Data\Entity;

use Kazoo\Api\Data\AbstractEntity;

class Cdr extends AbstractEntity {

    protected static $_schema_name = null;
    protected static $_callflow_module = null;

    public function initDefaultValues() {
        
    }
    
    /**
     * 
     * @param type $prop
     * @return type
     */
    public function __get($prop) {
        $return = null;
        switch ($this->_state) {
            case self::STATE_NEW:
            case self::STATE_PARTIAL_HYDRATED:
                $pk = self::DOC_KEY;
                
                if (property_exists($this->_data, $prop)) {
                    $return = $this->_data->$prop;
                } else if (isset($this->$pk)) {
                    $result = $this->_client->get($this->_uri, array());
                    $this->updateFromResult($result->data);
                    $return = $this->_data->$prop;
                }
                break;
            case self::STATE_HYDRATED:
                if (property_exists($this->_data, $prop)) {
                    $return = $this->_data->$prop;
                }
                break;
        }
        
        return $return;
    }

}