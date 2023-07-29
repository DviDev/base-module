<?php

namespace Modules\Base\Database\Factories;

use Modules\Base\Factories\BaseFactory;
use Modules\App\Models\CommentModel;

/**
 * @method CommentModel create(array $attributes = [])
 * @method CommentModel make(array $attributes = [])
 */
class CommentFactory extends BaseFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CommentModel::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition(): array
    {
        return $this->getValues();
    }
}
