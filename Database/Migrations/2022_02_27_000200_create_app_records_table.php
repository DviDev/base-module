<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Base\Entities\Record\RecordEntityModel;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('app_records', function (Blueprint $table) {
            $p = RecordEntityModel::props(force: true);
            $table->id();
            $table->string($p->name);
            $table->foreignId($p->type_id)->references('id')->on('app_record_types')
                ->cascadeOnUpdate()->restrictOnDelete();

            $table->timestamp($p->created_at)->useCurrent();
            $table->timestamp($p->updated_at)->useCurrent()->useCurrentOnUpdate();
            $table->timestamp($p->deleted_at)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('app_records');
    }
};
