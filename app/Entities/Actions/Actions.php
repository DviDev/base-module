<?php

namespace Modules\Base\Entities\Actions;

enum Actions
{
    case list_access;
    case view;
    case create;
    case update;
    case delete;
    case save;
    case manage;
}
