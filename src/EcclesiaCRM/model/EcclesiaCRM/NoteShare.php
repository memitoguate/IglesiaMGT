<?php

namespace EcclesiaCRM;

use EcclesiaCRM\NoteQuery;
use EcclesiaCRM\Base\NoteShare as BaseNoteShare;
use EcclesiaCRM\dto\SystemURLs;

/**
 * Skeleton subclass for representing a row from the 'note_nte_share' table.
 *
 *
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 */
class NoteShare extends BaseNoteShare
{
    public function getEditLink()
    {
        $url = '<a href="#" data-id="' . $this->getNote()->getId() . '" data-perid="';

        if ($this->getSharePerId() != '') {
            $url .= $this->getSharePerId().'" data-famid="0" class="editDocument">';
        } else {
            $url .= '0" data-famid="' . $this->getShareFamId() . '" class="editDocument">';
        }

        return $url;
    }
    
    public function setNoteId($v)
    {
      $note = NoteQuery::create()->findOneById($v);
      
      $note->setDateLastEdited(new \DateTime()); 
      $note->save();
      
      parent::setNoteId($v);
    }

}
