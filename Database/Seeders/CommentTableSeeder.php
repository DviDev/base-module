<?php

namespace Modules\Base\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Modules\App\Entities\Comment\CommentEntityModel;
use Modules\App\Models\CommentModel;
use Modules\Post\Entities\PostCommentVote\PostCommentVoteEntityModel;
use Modules\Post\Models\PostCommentVoteModel;
use Modules\Post\Models\PostModel;
use Modules\Workspace\Models\WorkspaceModel;

class CommentTableSeeder extends Seeder
{

    public function __construct()
    {
    }

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(PostModel $post, User $user, WorkspaceModel $workspace)
    {
        Model::unguard();

        $p = CommentEntityModel::props();
        CommentModel::factory()
            ->for($user)
            ->sequence(
                [$p->parent_id => null],
                [$p->parent_id => CommentModel::query()->inRandomOrder()->first()->id ?? null])
            ->create(['entity_item_id' => $post->entity_item_id]);
//        $post->addComment();
        $this->commentVotes($post, $user, $workspace);
    }

    protected function commentVotes(PostModel $post, User $user, WorkspaceModel $workspace): void
    {
//        dd($post->entity_item_id);
//        dd($post->comments()->dd());
        $post->comments->each(function (CommentModel $comment) use ($user, $workspace) {
            $p = PostCommentVoteEntityModel::props();
            $fnUpVote = fn(Factory $factory) => $factory->create([$p->up_vote => 1]);
            $fnDownVote = fn(Factory $factory) => $factory->create([$p->down_vote => 1]);

            /**@var \Closure $choice */
            $choice = collect([$fnUpVote, $fnDownVote])->random();
            $factory = PostCommentVoteModel::factory()->for($comment, 'comment')->for($user, 'user');

            /**@var PostCommentVoteModel $vote */
            $choice($factory);
        });
    }
}
