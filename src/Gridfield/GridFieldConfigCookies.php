<?php

namespace XD\CookieConsent\Gridfield;

use Symbiote\GridFieldExtensions\GridFieldAddNewInlineButton;
use SilverStripe\Forms\GridField\GridFieldConfig;
use SilverStripe\Forms\GridField\GridFieldButtonRow;
use SilverStripe\Forms\GridField\GridFieldDeleteAction;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\GridField\GridFieldSortableHeader;
use SilverStripe\Forms\GridField\GridFieldToolbarHeader;

/**
 * Class GridFieldConfigCookies
 *
 */
class GridFieldConfigCookies extends GridFieldConfig
{
    public function __construct()
    {
        parent::__construct();
        $this->addComponent(GridFieldToolbarHeader::create());
        $this->addComponent(GridFieldButtonRow::create('before'));
        $this->addComponent($sort = GridFieldSortableHeader::create());
        $this->addComponent($filter = GridFieldFilterHeader::create());
        $this->addComponent(GridFieldEditableColumns::create());
        $this->addComponent(GridFieldDeleteAction::create());
        $this->addComponent(GridFieldAddNewInlineButton::create('toolbar-header-right'));

        $this->extend('updateConfig');
    }
}
