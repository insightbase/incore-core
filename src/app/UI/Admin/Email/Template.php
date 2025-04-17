<?php

namespace App\UI\Admin\Email;

use App\Model\Entity\EmailLogEntity;
use App\UI\Admin\BaseTemplate;
use Nette\Database\Table\ActiveRow;

class Template extends BaseTemplate
{
    /**
     * @var EmailLogEntity
     */
    public ActiveRow $emailLog;
}