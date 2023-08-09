<?php

namespace EcclesiaCRM\Synchronize;

use Propel\Runtime\Propel;
use EcclesiaCRM\Synchronize\DashboardItemInterface;

class ClassificationDashboardItem implements DashboardItemInterface {

  public static function getDashboardItemName() {
    return "ClassificationBreakdown";
  }

  public static function getDashboardItemValue() {
        $data = [];
        $sSQL = 'select lst_OptionName as Classification, count(*) as count
                from person_per INNER JOIN list_lst ON  per_cls_ID = lst_OptionID
                LEFT JOIN family_fam ON family_fam.fam_ID = person_per.per_fam_ID
                WHERE lst_ID =1 and family_fam.fam_DateDeactivated is null and person_per.per_DateDeactivated is null
                group by per_cls_ID, lst_OptionName order by count desc;';

        $connection = Propel::getConnection();

        $statement = $connection->prepare($sSQL);
        $statement->execute();

        while ($row = $statement->fetch( \PDO::FETCH_ASSOC )) {
            $data[$row['Classification']] = $row['count'];
        }

        return $data;
  }

  public static function shouldInclude($PageName) {
    return $PageName=="/v2/people/dashboard"; // this ID would be found on all pages.
  }

}
