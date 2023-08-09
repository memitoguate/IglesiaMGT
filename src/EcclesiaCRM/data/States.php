<?php

namespace EcclesiaCRM\data;

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\dto\LocaleInfo;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\Bootstrapper;

class States
{
    private $countryCode;
    private $states = [];
    
    public function __construct()
    {       
        $this->countryCode = Bootstrapper::GetCurrentLocale()->getCountryCode();
        
        $stateFileName = SystemURLs::getDocumentRoot() . '/locale/states/'. $this->countryCode .'.json';
        
        if( is_file($stateFileName)) {
            $satesFile = file_get_contents($stateFileName);
            $this->states = json_decode($satesFile, true);
        }
    }
    
    public function getNames()
    {
        if (!empty($states)) {
          return array_values($this->states);
        } else {
          return [];
        }
    }
    public function getAll()
    {
        return $this->states;
    }
}