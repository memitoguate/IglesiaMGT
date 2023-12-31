<?php


namespace EcclesiaCRM\Tasks;


class CheckUploadSizeTask
{
    private $sizeString;
    private $sizeBytes;

    public function __construct()
    {
        $this->sizeString = ini_get('upload_max_filesize');
        $this->sizeBytes = Self::return_bytes($this->sizeString);
    }

    public function isActive()
    {
        return $this->sizeBytes < Self::return_bytes('5M');
    }

    public function isAdmin()
    {
        return true;
    }

    public function getLink()
    {
        return 'https://mediatemple.net/community/products/dv/204404784/how-do-i-increase-the-php-upload-limits';
    }

    public function getTitle()
    {
        return gettext('PHP Max File size too small') . " (" . $this->sizeString . ")";
    }

    public function getDesc()
    {
        return gettext("Increase the php upload limits to allow for mobile photo upload, and backup restore.");
    }

    static function return_bytes($val)
    {
        $val = substr($val, 0, -1);
        $last = strtolower($val[strlen($val) - 1]);
        switch ($last) {
            case 'g':
                $val *= 1073741824;
            case 'm':
                $val *= 1048576;
            case 'k':
                $val *= 1024;
        }
        return $val;
    }
}
