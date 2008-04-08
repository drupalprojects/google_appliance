<?php

class GoogleFeeder {
  
  private $_metaData = array();
  
  public function addMetaData($label, $value) {
    
  }
  
  public function addLanguages($languages = null) {
    
  }
  
  public function addDate($date) {
    
  }
  
  public function addKeywords($keys) {
    
  }
  
  public function getMetaData($key = null) {
    if (!$key) {
      return $this->_metaData;
    } else {
      if (isset($this->_metaData[$key])) {
        return $this->_metaData[$key];
      } else {
        throw new Exception("Meta Data Code not found",E_WARNING);
      }
    }
  }
  
}

?>