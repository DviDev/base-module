<?php

use Modules\Base\Console\DispatchBaseEventsCommand;
use Modules\Base\Console\DispatchInitialIndependentDataEventCommand;
use Modules\Base\Console\FeatureFlushCommand;
use Modules\Base\Console\InstallModulesCommand;
use Modules\Base\Contracts\BaseLivewireFormContract;
use Modules\Base\Contracts\BaseModel;
use Modules\Base\Contracts\BaseModelImplementation;
use Modules\Base\Contracts\BaseModelInterface;
use Modules\Base\Contracts\BaseServiceProviderContract;
use Modules\Base\Contracts\BaseServiceProviderInterface;
use Modules\Base\Contracts\EntityInterface;
use Modules\Base\Contracts\EntityModelInterface;
use Modules\Base\Contracts\HasFactory;
use Modules\Base\Database\Migrations\BaseMigration;
use Modules\Base\Database\Seeders\BaseDatabaseSeeder;
use Modules\Base\Database\Seeders\BaseSeeder;
use Modules\Base\Database\Seeders\ConfigTableSeeder;
use Modules\Base\Database\Seeders\DispatchBaseEventSeeder;
use Modules\Base\Database\Seeders\SeederEventDTO;
use Modules\Base\Domain\BaseDomain;
use Modules\Base\Entities\Actions\Builder;
use Modules\Base\Entities\Actions\GateContract;
use Modules\Base\Entities\BaseEntity;
use Modules\Base\Entities\BaseEntityModel;
use Modules\Base\Entities\Config\ConfigEntityModel;
use Modules\Base\Entities\Config\ConfigProps;
use Modules\Base\Entities\Props;
use Modules\Base\Entities\Record\RecordEntityModel;
use Modules\Base\Entities\Record\RecordProps;
use Modules\Base\Entities\RecordRelation\RecordRelationEntityModel;
use Modules\Base\Entities\RecordRelation\RecordRelationProps;
use Modules\Base\Entities\RecordType\RecordTypeEntityModel;
use Modules\Base\Entities\RecordType\RecordTypeProps;
use Modules\Base\Events\BaseSeederInitialIndependentDataEvent;
use Modules\Base\Events\DatabaseSeederEvent;
use Modules\Base\Events\SeederFinishedEvent;
use Modules\Base\Events\UsingSpotlightEvent;
use Modules\Base\Factories\AttributeFactory;
use Modules\Base\Factories\Attributes\BlueprintBigIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintBooleanFactory;
use Modules\Base\Factories\Attributes\BlueprintCharFactory;
use Modules\Base\Factories\Attributes\BlueprintDateFactory;
use Modules\Base\Factories\Attributes\BlueprintDateTimeFactory;
use Modules\Base\Factories\Attributes\BlueprintDecimalFactory;
use Modules\Base\Factories\Attributes\BlueprintDoubleFactory;
use Modules\Base\Factories\Attributes\BlueprintFloatFactory;
use Modules\Base\Factories\Attributes\BlueprintIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintLongTextFactory;
use Modules\Base\Factories\Attributes\BlueprintMediumIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintMediumTextFactory;
use Modules\Base\Factories\Attributes\BlueprintSmallIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintStringFactory;
use Modules\Base\Factories\Attributes\BlueprintTextFactory;
use Modules\Base\Factories\Attributes\BlueprintTimeFactory;
use Modules\Base\Factories\Attributes\BlueprintTimestampFactory;
use Modules\Base\Factories\Attributes\BlueprintTinyIntegerFactory;
use Modules\Base\Factories\Attributes\BlueprintYearFactory;
use Modules\Base\Factories\BaseFactory;
use Modules\Base\Http\Controllers\BaseController;
use Modules\Base\Http\Middleware\LocalEnvironmentMiddleware;
use Modules\Base\Http\Middleware\UseSpotlightMiddleware;
use Modules\Base\Listeners\CreateMenuItemsBaseListener;
use Modules\Base\Listeners\SeederInitialIndependentDataBaseListener;
use Modules\Base\Livewire\BaseLivewireComponent;
use Modules\Base\Livewire\BaseLivewireForm;
use Modules\Base\Livewire\Config\ConfigForm;
use Modules\Base\Livewire\Config\ConfigList;
use Modules\Base\Livewire\Config\ConfigListItem;
use Modules\Base\Livewire\Notification\NotificationList;
use Modules\Base\Livewire\Notification\NotificationView;
use Modules\Base\Models\ConfigModel;
use Modules\Base\Models\RecordModel;
use Modules\Base\Models\RecordRelationModel;
use Modules\Base\Models\RecordTypeModel;
use Modules\Base\Notifications\NotifyException;
use Modules\Base\Providers\BaseAuthServiceProvider;
use Modules\Base\Providers\BaseNewServiceProvider;
use Modules\Base\Providers\EventServiceProvider;
use Modules\Base\Providers\RouteServiceProvider;
use Modules\Base\Repository\BaseRepository;
use Modules\Base\Rules\MinWords;
use Modules\Base\Services\BaseLoginHttpServiceInterface;
use Modules\Base\Services\BaseService;
use Modules\Base\Services\Date\DateFn;
use Modules\Base\Services\Errors\BaseTypeErrors;
use Modules\Base\Services\Errors\Error;
use Modules\Base\Services\Errors\ErrorMessages;
use Modules\Base\Services\Errors\ExceptionBaseResponse;
use Modules\Base\Services\Functions;
use Modules\Base\Services\HttpContract;
use Modules\Base\Services\Notification\Action;
use Modules\Base\Services\Response\BaseResponse;
use Modules\Base\Services\Response\ResponseType;
use Modules\Base\Services\Tests\BaseTest;
use Modules\Base\Spotlight\GotoCommand;
use Modules\Base\View\Components\Form\BaseFormBladeComponent;
use Modules\Base\View\Components\Page\Notification\NotificationListPage;
use Modules\Base\View\Components\Page\Notification\NotificationViewPage;
use Modules\Permission\Enums\Actions;

uses(Tests\TestCase::class);

function expectClassesExist(array $files)
{
    foreach ($files as $key => $file) {
        expect(file_exists((new ReflectionClass($file))->getFileName()))
            ->toBeTrue()
            ->and($file)
            ->toBeString();
    }
}

function expectFilesExist(array $files)
{
    foreach ($files as $key => $file) {
        expect(file_exists(module_path('base', $file)))
            ->toBeTrue()
            ->and($file)
            ->toBeString();
    }
}

it('possui todos os arquivos de Comandos', function () {
    $files = [
        DispatchBaseEventsCommand::class,
        DispatchInitialIndependentDataEventCommand::class,
        FeatureFlushCommand::class,
        InstallModulesCommand::class,
    ];
    expectClassesExist($files);
});
it('possui todos os arquivos de Contratos', function () {
    $files = [
        BaseLivewireFormContract::class,
        BaseModelImplementation::class,
        BaseModelInterface::class,
        EntityInterface::class,
        EntityModelInterface::class,
        HasFactory::class,
    ];
    expectClassesExist($files);
});
it('possui todos os arquivos de Domain', function () {
    expectClassesExist([BaseDomain::class]);
});
describe('base.entities', function () {
    it('All entity actions files exist', function () {
        expectClassesExist([
            Actions::class,
            Builder::class,
            GateContract::class,
        ]);
    });

    it('all entity config files exist', function () {
        expectClassesExist([
            ConfigEntityModel::class,
            ConfigProps::class,
        ]);
    });
    it('all entity record files exist', function () {
        expectClassesExist([
            RecordEntityModel::class,
            RecordProps::class,
        ]);
    });
    it('all entity record relation files exist', function () {
        expectClassesExist([
            RecordRelationEntityModel::class,
            RecordRelationProps::class,
        ]);
    });
    it('all entity record type files exist', function () {
        expectClassesExist([
            RecordTypeEntityModel::class,
            RecordTypeProps::class,
        ]);
    });
    it('all entity base files exist', function () {
        expectClassesExist([
            BaseEntity::class,
            BaseEntityModel::class,
            BaseModelImplementation::class,
            Props::class,
        ]);
    });
});

it('possui todos os arquivos de Eventos', function () {
    expectClassesExist([
        BaseSeederInitialIndependentDataEvent::class,
        DatabaseSeederEvent::class,
        SeederFinishedEvent::class,
        UsingSpotlightEvent::class,
    ]);
});
it('possui todos os arquivos de Fabricas', function () {
    expectClassesExist([
        BlueprintBigIntegerFactory::class,
        BlueprintBooleanFactory::class,
        BlueprintCharFactory::class,
        BlueprintDateFactory::class,
        BlueprintDateTimeFactory::class,
        BlueprintDecimalFactory::class,
        BlueprintDoubleFactory::class,
        BlueprintFloatFactory::class,
        BlueprintIntegerFactory::class,
        BlueprintLongTextFactory::class,
        BlueprintMediumIntegerFactory::class,
        BlueprintMediumTextFactory::class,
        BlueprintSmallIntegerFactory::class,
        BlueprintStringFactory::class,
        BlueprintTextFactory::class,
        BlueprintTimeFactory::class,
        BlueprintTimestampFactory::class,
        BlueprintTinyIntegerFactory::class,
        BlueprintYearFactory::class,
        AttributeFactory::class,
        BaseFactory::class,
    ]);
});
it('possui todos os arquivos de Controllers', function () {
    expectClassesExist([BaseController::class]);
});
it('possui todos os arquivos de Livewire', function () {
    expectClassesExist([
        ConfigForm::class,
        ConfigList::class,
        ConfigListItem::class,
        NotificationList::class,
        NotificationView::class,
        BaseLivewireComponent::class,
        BaseLivewireForm::class,
    ]);
});
it('possui todos os arquivos de Middleware', function () {
    expectClassesExist([
        LocalEnvironmentMiddleware::class,
        UseSpotlightMiddleware::class,
    ]);
});
it('possui todos os arquivos de Listeners', function () {
    expectClassesExist([
        CreateMenuItemsBaseListener::class,
        SeederInitialIndependentDataBaseListener::class,
    ]);
});
it('possui todos os arquivos de Models', function () {
    expectClassesExist([
        BaseModel::class,
        ConfigModel::class,
        RecordModel::class,
        RecordRelationModel::class,
        RecordTypeModel::class,
    ]);
});
it('possui todos os arquivos de Notifications', function () {
    expectClassesExist([
        NotifyException::class,
    ]);
});

it('possui todos os arquivos de Providers', function () {
    expectClassesExist([
        BaseAuthServiceProvider::class,
        BaseNewServiceProvider::class,
        BaseServiceProviderContract::class,
        BaseServiceProviderInterface::class,
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ]);
});
it('possui todos os arquivos de Repositorios', function () {
    expectClassesExist([
        BaseRepository::class,
    ]);
});
it('possui todos os arquivos de Rules', function () {
    expectClassesExist([MinWords::class]);
});
it('possui todos os arquivos de Services', function () {
    expectClassesExist([
        DateFn::class,
        BaseTypeErrors::class,
        Error::class,
        ErrorMessages::class,
        ExceptionBaseResponse::class,
        Action::class,
        BaseResponse::class,
        ResponseType::class,
        BaseTest::class,
        BaseLoginHttpServiceInterface::class,
        BaseService::class,
        Functions::class,
        HttpContract::class,
    ]);
});
it('possui todos os arquivos de Spotlight', function () {
    expectClassesExist([GotoCommand::class]);
});
it('possui todos os arquivos de View', function () {
    expectClassesExist([
        BaseFormBladeComponent::class,
        NotificationListPage::class,
        NotificationViewPage::class,
    ]);
});
it('possui arquivos helper', function () {
    $filename = module_path('base') . '/app/Helpers/helpers.php';
    expect(file_exists($filename))->toBeTrue();
});
it('possui todos os arquivo em migrations', function () {
    $files = [
        '2022_02_27_000199_create_record_types_table.php',
        '2022_02_27_000200_create_records_table.php',
        '2022_02_27_025208_create_reccord_relations_table.php',
        '2022_09_12_145024_create_configs_table.php',
    ];
    foreach ($files as $file) {
        expect(file_exists(module_path('base') . '/database/migrations/' . $file))->toBeTrue();
    }
    expect(class_exists(BaseMigration::class))->toBeTrue();
});
it('all seeders files exist', function () {
    expectClassesExist([
        BaseDatabaseSeeder::class,
        BaseSeeder::class,
        ConfigTableSeeder::class,
        DispatchBaseEventSeeder::class,
        SeederEventDTO::class,
    ]);
});
it('all lang files exist', function () {
    $files = [
        'cache.php',
        'default.php',
        'form.php',
        'page.php',
        'pt_BR.json',
    ];
    foreach ($files as $file) {
        expect(file_exists(module_path('base') . '/resources/lang/pt_BR/' . $file))->toBeTrue();
    }
    expect(file_exists(module_path('base') . '/resources/lang/pt_BR.json'))->toBeTrue();
});
it('all resources livewire files exist', function () {
    $files = [
        'resources/livewire/base-form.blade.php',
    ];
    foreach ($files as $file) {
        expect(file_exists(module_path('base') . '/' . $file))->toBeTrue();
    }
});

it('all form component views files exist', function () {
    $files = [
        'resources/views/components/form/baseformbladecomponent.blade.php',
    ];
    foreach ($files as $file) {
        $filename = module_path('base', $file);
        expect(file_exists($filename))->toBeTrue();
    }
});
it('all layout component views files exists', function () {
    $files = [
        'resources/views/components/layouts/master.blade.php',
    ];
    expectFilesExist($files);
});
it('all page component views files exists', function () {
    expectFilesExist([
        'resources/views/components/page/notification/notification-list-page.blade.php',
        'resources/views/components/page/notification/notification-view-page.blade.php',
    ]);
});
it('all livewire views files exists', function () {
    expectFilesExist([
        'resources/views/livewire/config/config-form.blade.php',
        'resources/views/livewire/config/config-list.blade.php',
        'resources/views/livewire/config/config-list-item.blade.php',
        'resources/views/livewire/notification/notification-list.blade.php',
        'resources/views/livewire/notification/notification-view.blade.php',
        'resources/views/livewire/base-form.blade.php',
    ]);
});
it('readme file exist', function () {
    $filename = module_path('base') . '/README.md';
    expect(file_exists($filename))->toBeTrue();
});
