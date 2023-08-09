<?php

namespace EcclesiaCRM\Tasks;

use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\PersonQuery;
use Propel\Runtime\ActiveQuery\Criteria;

class PersonClassificationDataCheck implements iTask
{
    private $count;

    public function __construct()
    {
        $personQuery = PersonQuery::create()
            ->filterByClsId(0)
            ->filterById(1, Criteria::NOT_EQUAL)
            ->find();
        $this->count = $personQuery->count();
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
        return SystemURLs::getRootPath() . '/v2/people/list/person/-1/-1/0';//SelectList.php?mode=person&Classification=0&PersonColumn3=Classification
    }

    public function getTitle()
    {
        return gettext('Missing Classification Data') . " (" . $this->count . ")";
    }

    public function getDesc()
    {
        return gettext("Missing Classification Data for Some People");
    }

}
