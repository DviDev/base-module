<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Modules\Base\Entities\Config\ConfigEntityModel;
use Modules\Base\Entities\Record\RecordEntityModel;
use Modules\Base\Entities\RecordRelation\RecordRelationEntityModel;
use Modules\Base\Entities\RecordType\RecordTypeEntityModel;

uses(Tests\TestCase::class);

describe('base.migrations', function () {
    describe('base.table.base_record_types', function () {
        it('check table record types ', function () {
            $p = RecordTypeEntityModel::props();
            $this->assertTrue(Schema::hasTable($p->table()));
            foreach ($p->getAttributes() as $attribute) {
                $this->assertTrue(Schema::hasColumn($p->table(), $attribute));
            }
        });
    });

    it('create.base_records.table', function () {
        $p = RecordEntityModel::props();
        $this->assertTrue(Schema::hasTable($p->table()));

        $this->assertTrue(Schema::hasColumn($p->table(), $p->id));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->type_id));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->created_at));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->updated_at));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->deleted_at));
    });

    it('create.base_record_relations.table', function () {
        $p = RecordRelationEntityModel::props();
        $this->assertTrue(Schema::hasTable($p->table()));

        $this->assertTrue(Schema::hasColumn($p->table(), $p->id));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->record1));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->record2));
    });

    it('create.base_configs.table', function () {
        $p = ConfigEntityModel::props();
        $this->assertTrue(Schema::hasTable($p->table()));

        $this->assertTrue(Schema::hasColumn($p->table(), $p->id));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->name));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->description));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->value));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->user_id));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->default));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->created_at));
        $this->assertTrue(Schema::hasColumn($p->table(), $p->updated_at));
    });
});
