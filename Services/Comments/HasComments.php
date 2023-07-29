<?php

namespace Modules\Base\Services\Comments;

use Modules\App\Models\EntityItemModel;
use Modules\App\Models\EntityRelationModel;
use Modules\App\Models\CommentModel;

trait HasComments
{
    public function addComment(CommentModel $comment): CommentModel
    {
        if (!$this->entity_item_id) {
            $this->entity_item_id = EntityItemModel::create()->id;
            $this->save();
        }

        $commentItem = EntityItemModel::create();
        EntityRelationModel::create(['item1' => $this->entity_item_id, 'item2' => $commentItem->id]);

        $comment->entity_item_id = $commentItem->id;
        $comment->save();
        return $comment;
    }


}
