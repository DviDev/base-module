<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Base\Entities\RecordRelation\RecordRelationEntityModel;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('base_record_relations', function (Blueprint $table) {
            $p = RecordRelationEntityModel::props(force: true);
            $table->id();
            $table->foreignId($p->record1)->references('id')->on('base_records')
                ->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId($p->record2)->references('id')->on('base_records')
                ->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('base_record_relations');
    }
};
