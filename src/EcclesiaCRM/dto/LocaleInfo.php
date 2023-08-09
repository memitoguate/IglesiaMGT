<?php

namespace EcclesiaCRM\dto;

class LocaleInfo
{
    public $locale;
    public $language;
    public $country;
    public $dataTables;

    public function __construct($locale)
    {
        $this->locale = $locale;
        $localesFile = file_get_contents(SystemURLs::getDocumentRoot() . "/locale/locales.json");
        $locales = json_decode($localesFile, true);
        foreach ($locales as $key => $value) {
            if ($value["locale"] == $locale) {
                $this->language = $value["languageCode"];
                $this->country = $value["countryCode"];
                $this->dataTables = $value["dataTables"];
            }
        }
    }

    public function getLocale()
    {
        return $this->locale;
    }

    public function getShortLocale()
    {
        return substr($this->getLocale(), 0, 2);
    }

    public function getLanguageCode()
    {
        return $this->language;
    }

    public function getCountryCode()
    {
        return strtolower($this->country);
    }

    public function getDataTables()
    {
        return $this->dataTables;
    }

    public function getThousandSeparator()
    {
        $sep = ',';
        if ($this->language == 'it_IT') {
            $sep = '.';
        }

        return $sep;
    }

    public function getLocaleArray()
    {
        $utfList = ['.utf8', '.UTF8', '.utf-8', '.UTF-8'];
        $localArray = [];
        array_push($localArray, $this->getLanguageCode());
        foreach ($utfList as $item) {
            array_push($localArray, $this->getLanguageCode() . $item);
        }

        return $localArray;
    }

    public function getCurrency ()
    {
        return SystemConfig::getValue("sCurrency");
    }
    public function getLocaleInfo()
    {
        setlocale(LC_ALL, $this->getLocale());
        $localeInfo = localeconv();

        $localeInfo['decimal_point'] = '.';
        $localeInfo['mon_thousands_sep'] = ',';
        $localeInfo['currency_symbol'] = $this->getCurrency();// we set the correct currency

        // patch some missing data for Italian.  This shouldn't be necessary!
        if ($this->locale == 'it_IT') {
            $localeInfo['thousands_sep'] = '.';
            $localeInfo['frac_digits'] = '2';
            $localeInfo['decimal_point'] = ',';
        } elseif ($localeInfo['currency_symbol'] == "€") {
            $localeInfo['decimal_point'] = ',';
            $localeInfo['mon_thousands_sep'] = ' ';
        }

        return $localeInfo;
    }
}
