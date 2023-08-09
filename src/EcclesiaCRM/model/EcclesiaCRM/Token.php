<?php

namespace EcclesiaCRM;

use EcclesiaCRM\Base\Token as BaseToken;

/**
 * Skeleton subclass for representing a row from the 'tokens' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */

use EcclesiaCRM\Utils\MiscUtils;

class Token extends BaseToken
{

    const typeFamilyVerify = "verifyFamily";
    const typePassword     = "password";

    public function build($type, $referenceId, $path=NULL)
    {
        $this->setReferenceId($referenceId);
        $this->setToken(uniqid());
        switch ($type) {
            case "verifyFamily":
                $this->setValidUntilDate(strtotime("+2 week"));
                $this->setRemainingUses(20);
                break;
            case "password":
                $this->setValidUntilDate(strtotime("+1 day"));
                $this->setRemainingUses(1);
                break;
            case "filemanager":
                $this->setValidUntilDate(strtotime("+1 day"));
                $this->setRemainingUses(1);
                $this->setComment($path);
                break;
        }
        $this->setType($type);
    }

    public function isVerifyFamilyToken()
    {
        return self::typeFamilyVerify === $this->getType();
    }

    public function isPasswordResetToken()
    {
        return self::typePassword === $this->getType();
    }

    public function isValid()
    {
        $hasUses = true;
        if ($this->getRemainingUses() !== null) {
            $hasUses = $this->getRemainingUses() > 0;
        }

        $stillValidDate = true;
        if ($this->getValidUntilDate() !== null) {
            $today = new \DateTime();
            $stillValidDate = $this->getValidUntilDate() > $today;
        }
        return $stillValidDate && $hasUses;
    }

}
