<?php

namespace App\UI\Admin\Translate\DataGrid;

use Nette\Database\Table\ActiveRow;

interface InlineEditFactory
{
    public function create(ActiveRow $language):InlineEdit;
}