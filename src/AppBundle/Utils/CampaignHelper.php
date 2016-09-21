<?php

// src/AppBundle/Utils/ValidationHelper.php

namespace AppBundle\Utils;

use AppBundle\Entity\Campaignsetting;
use DateTime;
class CampaignHelper
{
    private $campaignsettings = [];

    public function __construct(array $objects)
    {

      foreach ($objects as $object) {
          $this->setCampaignSetting($object->getDisplayName(), $object->getValue());
      }

    }

    public function setCampaignSetting($key, $value)
    {
        if (strpos($key, 'date')) {
            $this->campaignsettings[$key] = DateTime::createFromFormat('m/d/Y', $value);
        } elseif (strpos($key, 'amount')) {
            $this->campaignsettings[$key] = floatval($value);
        }else{
            $this->campaignsettings[$key] = $value;
        }
    }

    public function getCampaignSetting($key)
    {
        return $this->campaignsettings[$key];
    }

    public function getCampaignSettings()
    {
        return $this->campaignsettings;
    }
}
