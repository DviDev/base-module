<?php

namespace Modules\Base\Database\Factories;

use Modules\Base\Factories\BaseFactory;
use Modules\App\Models\MessageModel;

/**
 * @method MessageModel create(array $attributes = [])
 * @method MessageModel make(array $attributes = [])
 */
class MessageFactory extends BaseFactory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MessageModel::class;

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
