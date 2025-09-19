<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Base\Entities\Config\ConfigEntityModel;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('base_configs', function (Blueprint $table) {
            $config = ConfigEntityModel::props(null, true);
            $table->id();

            $table->string($config->name)->unique();
            $table->longText($config->description)->nullable();
            $table->string($config->value);
            $table->foreignId($config->user_id)->references('id')->on('users')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->boolean($config->default)->nullable();

            $table->timestamp($config->created_at)->nullable();
            $table->timestamp($config->updated_at)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('base_configs');
    }
};
