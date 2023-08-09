<?php


namespace EcclesiaCRM\Tasks;

use EcclesiaCRM\FamilyQuery;
use EcclesiaCRM\dto\SystemURLs;

class UpdateFamilyCoordinatesTask
{
    private $count;

    public function __construct()
    {
        $query = FamilyQuery::create()->filterByLatitude("")->find();
        $this->count = $query->count();
    }

    public function isActive()
    {
        return $this->count > 0;
    }

    public function isAdmin()
    {
        return false;
    }

    public function getLink()
    {
        return SystemURLs::getRootPath() . '/v2/people/UpdateAllLatLon';
    }

    public function getTitle()
    {
        return gettext('Missing Coordinates') . " (" . $this->count . ")";
    }

    public function getDesc()
    {
        return gettext("Family Coordinates Data for Some Families");
    }
}
