<?php

namespace EcclesiaCRM\Tasks;

use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\PersonQuery;
use Propel\Runtime\ActiveQuery\Criteria;

class PersonRoleDataCheck implements iTask
{

    private $count;

    public function __construct()
    {
        $personQuery = PersonQuery::create()->filterByFmrId(0)
            ->filterById(1, Criteria::NOT_EQUAL)
            ->filterByFamId("", Criteria::NOT_EQUAL)
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
        return SystemURLs::getRootPath() . '/v2/people/list/person/-1/0/-1';//SelectList.php?mode=person&FamilyRole=0&PersonColumn3=Family+Role
    }

    public function getTitle()
    {
        return gettext('Missing Role Data') . " (" . $this->count . ")";
    }

    public function getDesc()
    {
        return gettext("Missing Role Data for Some People");
    }

}
