<?php

// use Filament\Upgrade\Rector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\Rector\String_\RenameStringRector;

return static function (RectorConfig $rectorConfig): void {
    // $rectorConfig->rules([
    //     Rector\FixGetSetClosureTypesRector::class,
    //     Rector\MoveImportedClassesRector::class,
    //     Rector\SecondaryToGrayColorRector::class,
    //     Rector\SimpleMethodChangesRector::class,
    //     Rector\SimplePropertyChangesRector::class,
    // ]);

    $rectorConfig->ruleWithConfiguration(
        RenameClassRector::class,
        // @todo Alphabetical
        [
            'Filament\\Forms\\Commands\\MakeLayoutComponentCommand' => 'Filament\\Schema\\Commands\\MakeLayoutComponentCommand',
            'Filament\\Pages\\Actions\\Action' => 'Filament\\Actions\\Action',
            'Filament\\Forms\\Components\\BelongsToManyCheckboxList' => 'Filament\\Forms\\Components\\CheckboxList',
            'Filament\\Forms\\Components\\BelongsToManyMultiSelect' => 'Filament\\Forms\\Components\\MultiSelect',
            'Filament\\Forms\\Components\\BelongsToSelect' => 'Filament\\Forms\\Components\\Select',
            'Filament\\Forms\\Components\\Card' => 'Filament\\Schema\\Components\\Section',
            'Filament\\Forms\\Components\\HasManyRepeater' => 'Filament\\Forms\\Components\\RelationshipRepeater',
            'Filament\\Forms\\Components\\MorphManyRepeater' => 'Filament\\Forms\\Components\\RelationshipRepeater',
            'Filament\\Actions\\Exceptions\\Hold' => 'Filament\\Support\\Exceptions\\Halt',
            'Filament\\Actions\\Modal\\Actions' => 'Filament\\Actions\\StaticAction',
            'Filament\\Forms\\Components\\Concerns\\HasExtraAlpineAttributes' => 'Filament\\Support\\Concerns\\HasExtraAlpineAttributes',
            'Filament\\Forms\\Components\\Concerns\\HasExtraAttributes' => 'Filament\\Support\\Concerns\\HasExtraAttributes',
            'Filament\\Infolists\\Components\\Card' => 'Filament\\Schema\\Components\\Section',
            'Filament\\Http\\Livewire\\Auth\\Login' => 'Filament\\Pages\\Auth\\Login',
            'Filament\\Navigation\\UserMenuItem' => 'Filament\\Navigation\\MenuItem',
            'Filament\\Pages\\Actions\\Modal\\Actions\\Action' => 'Filament\\Actions\\StaticAction',
            'Filament\\Pages\\Actions\\Modal\\Actions\\ButtonAction' => 'Filament\\Actions\\StaticAction',
            'Filament\\Pages\\Actions\\ActionGroup' => 'Filament\\Actions\\ActionGroup',
            'Filament\\Pages\\Actions\\ButtonAction' => 'Filament\\Actions\\Action',
            'Filament\\Pages\\Actions\\CreateAction' => 'Filament\\Actions\\CreateAction',
            'Filament\\Pages\\Actions\\DeleteAction' => 'Filament\\Actions\\DeleteAction',
            'Filament\\Pages\\Actions\\EditAction' => 'Filament\\Actions\\EditAction',
            'Filament\\Pages\\Actions\\ForceDeleteAction' => 'Filament\\Actions\\ForceDeleteAction',
            'Filament\\Pages\\Actions\\ReplicateAction' => 'Filament\\Actions\\ReplicateAction',
            'Filament\\Pages\\Actions\\RestoreAction' => 'Filament\\Actions\\RestoreAction',
            'Filament\\Pages\\Actions\\SelectAction' => 'Filament\\Actions\\SelectAction',
            'Filament\\Pages\\Actions\\ViewAction' => 'Filament\\Actions\\ViewAction',
            'Filament\\Resources\\Pages\\ListRecords\\Tab' => 'Filament\\Resources\\Components\\Tab',
            'Filament\\Tables\\Actions\\Modal\\Actions\\Action' => 'Filament\\Actions\\StaticAction',
            'Filament\\Tables\\Actions\\Modal\\Actions\\ButtonAction' => 'Filament\\Actions\\StaticAction',
            'Filament\\Tables\\Actions\\LinkAction' => 'Filament\\Actions\\Action',
            'Filament\\Tables\\Columns\\Concerns\\HasExtraAttributes' => 'Filament\\Support\\Concerns\\HasExtraAttributes',
            'Filament\\Widgets\\StatsOverviewWidget\\Card' => 'Filament\\Widgets\\StatsOverviewWidget\\Stat',
            'Filament\\Forms\\Concerns\\BelongsToLivewire' => 'Filament\\Schema\\ComponentContainer\\Concerns\\BelongsToLivewire',
            'Filament\\Forms\\Concerns\\BelongsToModel' => 'Filament\\Schema\\ComponentContainer\\Concerns\\BelongsToModel',
            'Filament\\Forms\\Concerns\\BelongsToParentComponent' => 'Filament\\Schema\\ComponentContainer\\Concerns\\BelongsToParentComponent',
            'Filament\\Forms\\Concerns\\CanBeDisabled' => 'Filament\\Schema\\ComponentContainer\\Concerns\\CanBeDisabled',
            'Filament\\Forms\\Concerns\\CanBeHidden' => 'Filament\\Schema\\ComponentContainer\\Concerns\\CanBeHidden',
            'Filament\\Forms\\Concerns\\CanBeValidated' => 'Filament\\Schema\\ComponentContainer\\Concerns\\CanBeValidated',
            'Filament\\Forms\\Concerns\\Cloneable' => 'Filament\\Schema\\ComponentContainer\\Concerns\\Cloneable',
            'Filament\\Forms\\Concerns\\HasComponents' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasComponents',
            'Filament\\Forms\\Concerns\\HasFieldWrapper' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasFieldWrapper',
            'Filament\\Forms\\Concerns\\HasInlineLabels' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasInlineLabels',
            'Filament\\Forms\\Concerns\\HasOperation' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasOperation',
            'Filament\\Forms\\Concerns\\HasState' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasState',
            'Filament\\Forms\\Concerns\\HasColumns' => 'Filament\\Schema\\Components\\Concerns\\HasColumns',
            'Filament\\Infolists\\Concerns\\HasColumns' => 'Filament\\Schema\\Components\\Concerns\\HasColumns',
            'Filament\\Infolists\\Infolist' => 'Filament\\Schema\\ComponentContainer',
            'Filament\\Forms\\Concerns\\HasStateBindingModifiers' => 'Filament\\Schema\\Components\\Concerns\\HasStateBindingModifiers',
            'Filament\\Forms\\Form' => 'Filament\\Schema\\ComponentContainer',
            'Filament\\Forms\\Get' => 'Filament\\Schema\\Components\\Utilities\\Get',
            'Filament\\Forms\\Set' => 'Filament\\Schema\\Components\\Utilities\\Set',
            'Filament\\Forms\\Components\\Component' => 'Filament\\Schema\\Components\\Component',
            'Filament\\Forms\\Components\\Concerns\\BelongsToContainer' => 'Filament\\Schema\\Components\\Concerns\\BelongsToContainer',
            'Filament\\Forms\\Components\\Concerns\\BelongsToModel' => 'Filament\\Schema\\Components\\Concerns\\BelongsToModel',
            'Filament\\Forms\\Components\\Concerns\\CanBeConcealed' => 'Filament\\Schema\\Components\\Concerns\\CanBeConcealed',
            'Filament\\Forms\\Components\\Concerns\\CanBeDisabled' => 'Filament\\Schema\\Components\\Concerns\\CanBeDisabled',
            'Filament\\Forms\\Components\\Concerns\\CanBeHidden' => 'Filament\\Schema\\Components\\Concerns\\CanBeHidden',
            'Filament\\Forms\\Components\\Concerns\\CanBeRepeated' => 'Filament\\Schema\\Components\\Concerns\\CanBeRepeated',
            'Filament\\Forms\\Components\\Concerns\\CanSpanColumns' => 'Filament\\Schema\\Components\\Concerns\\CanSpanColumns',
            'Filament\\Forms\\Components\\Concerns\\Cloneable' => 'Filament\\Schema\\Components\\Concerns\\Cloneable',
            'Filament\\Forms\\Components\\Concerns\\HasActions' => 'Filament\\Schema\\Components\\Concerns\\HasActions',
            'Filament\\Forms\\Components\\Concerns\\HasChildComponents' => 'Filament\\Schema\\Components\\Concerns\\HasChildComponents',
            'Filament\\Forms\\Components\\Concerns\\HasFieldWrapper' => 'Filament\\Schema\\Components\\Concerns\\HasFieldWrapper',
            'Filament\\Forms\\Components\\Concerns\\HasId' => 'Filament\\Schema\\Components\\Concerns\\HasId',
            'Filament\\Forms\\Components\\Concerns\\HasInlineLabel' => 'Filament\\Schema\\Components\\Concerns\\HasInlineLabel',
            'Filament\\Forms\\Components\\Concerns\\HasKey' => 'Filament\\Schema\\Components\\Concerns\\HasKey',
            'Filament\\Forms\\Components\\Concerns\\HasLabel' => 'Filament\\Schema\\Components\\Concerns\\HasLabel',
            'Filament\\Forms\\Components\\Concerns\\HasMaxWidth' => 'Filament\\Schema\\Components\\Concerns\\HasMaxWidth',
            'Filament\\Forms\\Components\\Concerns\\HasMeta' => 'Filament\\Schema\\Components\\Concerns\\HasMeta',
            'Filament\\Forms\\Components\\Concerns\\HasState' => 'Filament\\Schema\\Components\\Concerns\\HasState',
            'Filament\\Forms\\Components\\Actions\\Concerns\\BelongsToComponent' => 'Filament\\Actions\\Concerns\\BelongsToSchemaComponent',
            'Filament\\Forms\\Components\\Actions' => 'Filament\\Schema\\Components\\Actions',
            'Filament\\Forms\\Components\\Actions\\Action' => 'Filament\\Actions\\Action',
            'Filament\\Forms\\Components\\Actions\\ActionContainer' => 'Filament\\Schema\\Components\\Actions\\ActionContainer',
            'Filament\\Forms\\Components\\Tabs' => 'Filament\\Schema\\Components\\Tabs',
            'Filament\\Forms\\Components\\Tabs\\Tab' => 'Filament\\Schema\\Components\\Tabs\\Tab',
            'Filament\\Forms\\Components\\Contracts\\CanConcealComponents' => 'Filament\\Schema\\Components\\Contracts\\CanConcealComponents',
            'Filament\\Forms\\Components\\Wizard' => 'Filament\\Schema\\Components\\Wizard',
            'Filament\\Forms\\Components\\Wizard\\Step' => 'Filament\\Schema\\Components\\Wizard\\Step',
            'Filament\\Forms\\Components\\Fieldset' => 'Filament\\Schema\\Components\\Fieldset',
            'Filament\\Forms\\Components\\Concerns\\EntanglesStateWithSingularRelationship' => 'Filament\\Schema\\Components\\Concerns\\EntanglesStateWithSingularRelationship',
            'Filament\\Forms\\Components\\Contracts\\CanEntangleWithSingularRelationships' => 'Filament\\Schema\\Components\\Contracts\\CanEntangleWithSingularRelationships',
            'Filament\\Forms\\Components\\Grid' => 'Filament\\Schema\\Components\\Grid',
            'Filament\\Forms\\Components\\Group' => 'Filament\\Schema\\Components\\Group',
            'Filament\\Forms\\Components\\Livewire' => 'Filament\\Schema\\Components\\Livewire',
            'Filament\\Forms\\Components\\Section' => 'Filament\\Schema\\Components\\Section',
            'Filament\\Forms\\Components\\Split' => 'Filament\\Schema\\Components\\Split',
            'Filament\\Forms\\Components\\View' => 'Filament\\Schema\\Components\\View',
            'Filament\\Forms\\Components\\Concerns\\CanBeCollapsed' => 'Filament\\Schema\\Components\\Concerns\\CanBeCollapsed',
            'Filament\\Forms\\Components\\Concerns\\CanBeCompacted' => 'Filament\\Schema\\Components\\Concerns\\CanBeCompacted',
            'Filament\\Forms\\Components\\Concerns\\HasFooterActions' => 'Filament\\Schema\\Components\\Concerns\\HasFooterActions',
            'Filament\\Forms\\Components\\Concerns\\HasHeaderActions' => 'Filament\\Schema\\Components\\Concerns\\HasHeaderActions',
            'Filament\\Forms\\Components\\Contracts\\HasFooterActions' => 'Filament\\Schema\\Components\\Contracts\\HasFooterActions',
            'Filament\\Forms\\Components\\Contracts\\HasHeaderActions' => 'Filament\\Schema\\Components\\Contracts\\HasHeaderActions',
            'Filament\\Infolists\\ComponentContainer' => 'Filament\\Schema\\ComponentContainer',
            'Filament\\Infolists\\Concerns\\BelongsToLivewire' => 'Filament\\Schema\\ComponentContainer\\Concerns\\BelongsToLivewire',
            'Filament\\Infolists\\Concerns\\BelongsToParentComponent' => 'Filament\\Schema\\ComponentContainer\\Concerns\\BelongsToParentComponent',
            'Filament\\Infolists\\Concerns\\CanBeHidden' => 'Filament\\Schema\\ComponentContainer\\Concerns\\CanBeHidden',
            'Filament\\Infolists\\Concerns\\Cloneable' => 'Filament\\Schema\\ComponentContainer\\Concerns\\Cloneable',
            'Filament\\Infolists\\Concerns\\HasComponents' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasComponents',
            'Filament\\Infolists\\Concerns\\HasInlineLabels' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasInlineLabels',
            'Filament\\Infolists\\Concerns\\HasState' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasState',
            'Filament\\Infolists\\Concerns\\HasEntryWrapper' => 'Filament\\Schema\\ComponentContainer\\Concerns\\HasEntryWrapper',
            'Filament\\Infolists\\Components\\Actions\\Action' => 'Filament\\Actions\\Action',
            'Filament\\Infolists\\Components\\Component' => 'Filament\\Schema\\Components\\Component',
            'Filament\\Infolists\\Components\\Actions\\Concerns\\BelongsToInfolist' => 'Filament\\Actions\\Concerns\\BelongsToSchemaComponent',
            'Filament\\Infolists\\Components\\Concerns\\BelongsToContainer' => 'Filament\\Schema\\Components\\Concerns\\BelongsToContainer',
            'Filament\\Infolists\\Components\\Concerns\\CanBeHidden' => 'Filament\\Schema\\Components\\Concerns\\CanBeHidden',
            'Filament\\Infolists\\Components\\Concerns\\CanSpanColumns' => 'Filament\\Schema\\Components\\Concerns\\CanSpanColumns',
            'Filament\\Infolists\\Components\\Concerns\\Cloneable' => 'Filament\\Schema\\Components\\Concerns\\Cloneable',
            'Filament\\Infolists\\Components\\Concerns\\HasActions' => 'Filament\\Schema\\Components\\Concerns\\HasActions',
            'Filament\\Infolists\\Components\\Concerns\\HasChildComponents' => 'Filament\\Schema\\Components\\Concerns\\HasChildComponents',
            'Filament\\Infolists\\Components\\Concerns\\HasId' => 'Filament\\Schema\\Components\\Concerns\\HasId',
            'Filament\\Infolists\\Components\\Concerns\\HasInlineLabel' => 'Filament\\Schema\\Components\\Concerns\\HasInlineLabel',
            'Filament\\Infolists\\Components\\Concerns\\HasKey' => 'Filament\\Schema\\Components\\Concerns\\HasKey',
            'Filament\\Infolists\\Components\\Concerns\\HasLabel' => 'Filament\\Schema\\Components\\Concerns\\HasLabel',
            'Filament\\Infolists\\Components\\Concerns\\HasMaxWidth' => 'Filament\\Schema\\Components\\Concerns\\HasMaxWidth',
            'Filament\\Infolists\\Components\\Concerns\\HasEntryWrapper' => 'Filament\\Schema\\Components\\Concerns\\HasEntryWrapper',
            'Filament\\Infolists\\Components\\Concerns\\HasMeta' => 'Filament\\Schema\\Components\\Concerns\\HasMeta',
            'Filament\\Infolists\\Components\\Concerns\\HasState' => 'Filament\\Schema\\Components\\Concerns\\HasState',
            'Filament\\Infolists\\Components\\Concerns\\CanGetStateFromRelationships' => 'Filament\\Schema\\Components\\Concerns\\CanGetStateFromRelationships',
            'Filament\\Infolists\\Components\\Contracts\\HasAffixActions' => 'Filament\\Schema\\Components\\Contracts\\HasAffixActions',
            'Filament\\Infolists\\Components\\Contracts\\HasFooterActions' => 'Filament\\Schema\\Components\\Contracts\\HasFooterActions',
            'Filament\\Infolists\\Components\\Contracts\\HasHeaderActions' => 'Filament\\Schema\\Components\\Contracts\\HasHeaderActions',
            'Filament\\Infolists\\Components\\Contracts\\HasHintActions' => 'Filament\\Schema\\Components\\Contracts\\HasHintActions',
            'Filament\\Forms\\Components\\Contracts\\HasAffixActions' => 'Filament\\Schema\\Components\\Contracts\\HasAffixActions',
            'Filament\\Forms\\Components\\Contracts\\HasHintActions' => 'Filament\\Schema\\Components\\Contracts\\HasHintActions',
            'Filament\\Forms\\Components\\Contracts\\HasExtraItemActions' => 'Filament\\Schema\\Components\\Contracts\\HasExtraItemActions',
            'Filament\\Infolists\\Commands\\MakeLayoutComponentCommand' => 'Filament\\Schema\\Commands\\MakeLayoutComponentCommand',
            'Filament\\Infolists\\Components\\Actions' => 'Filament\\Schema\\Components\\Actions',
            'Filament\\Infolists\\Components\\Actions\\ActionContainer' => 'Filament\\Schema\\Components\\Actions\\ActionContainer',
            'Filament\\Infolists\\Components\\Tabs' => 'Filament\\Schema\\Components\\Tabs',
            'Filament\\Infolists\\Components\\Tabs\\Tab' => 'Filament\\Schema\\Components\\Tabs\\Tab',
            'Filament\\Infolists\\Components\\Fieldset' => 'Filament\\Schema\\Components\\Fieldset',
            'Filament\\Infolists\\Components\\Concerns\\EntanglesStateWithSingularRelationship' => 'Filament\\Schema\\Components\\Concerns\\EntanglesStateWithSingularRelationship',
            'Filament\\Infolists\\Components\\Grid' => 'Filament\\Schema\\Components\\Grid',
            'Filament\\Infolists\\Components\\Group' => 'Filament\\Schema\\Components\\Group',
            'Filament\\Infolists\\Components\\Livewire' => 'Filament\\Schema\\Components\\Livewire',
            'Filament\\Infolists\\Components\\Section' => 'Filament\\Schema\\Components\\Section',
            'Filament\\Infolists\\Components\\Split' => 'Filament\\Schema\\Components\\Split',
            'Filament\\Infolists\\Components\\View' => 'Filament\\Schema\\Components\\View',
            'Filament\\Infolists\\Components\\Concerns\\CanBeCollapsed' => 'Filament\\Schema\\Components\\Concerns\\CanBeCollapsed',
            'Filament\\Infolists\\Components\\Concerns\\CanBeCompacted' => 'Filament\\Schema\\Components\\Concerns\\CanBeCompacted',
            'Filament\\Infolists\\Components\\Concerns\\HasFooterActions' => 'Filament\\Schema\\Components\\Concerns\\HasFooterActions',
            'Filament\\Infolists\\Components\\Concerns\\HasHeaderActions' => 'Filament\\Schema\\Components\\Concerns\\HasHeaderActions',
            'Filament\\Tables\\Actions\\Action' => 'Filament\\Actions\\Action',
        ],
    );

    $rectorConfig->ruleWithConfiguration(
        RenameStringRector::class,
        [
            'filament-forms::component-container' => 'filament-schema::component-container',
            'filament-infolists::component-container' => 'filament-schema::component-container',
            'filament-forms::components.actions' => 'filament-schema::components.actions',
            'filament-forms::components.actions.action-container' => 'filament-schema::components.actions.action-container',
            'filament-forms::components.tabs' => 'filament-schema::components.tabs',
            'filament-forms::components.tabs.tab' => 'filament-schema::components.tabs.tab',
            'filament-forms::components.wizard' => 'filament-schema::components.wizard',
            'filament-forms::components.wizard.step' => 'filament-schema::components.wizard.step',
            'filament-forms::components.fieldset' => 'filament-schema::components.fieldset',
            'filament-forms::components.grid' => 'filament-schema::components.grid',
            'filament-forms::components.group' => 'filament-schema::components.grid',
            'filament-forms::components.livewire' => 'filament-schema::components.livewire',
            'filament-forms::components.section' => 'filament-schema::components.section',
            'filament-forms::components.split' => 'filament-schema::components.split',
            'filament-infolists::components.actions' => 'filament-schema::components.actions',
            'filament-infolists::components.actions.action-container' => 'filament-schema::components.actions.action-container',
            'filament-infolists::components.tabs' => 'filament-schema::components.tabs',
            'filament-infolists::components.tabs.tab' => 'filament-schema::components.tabs.tab',
            'filament-infolists::components.fieldset' => 'filament-schema::components.fieldset',
            'filament-infolists::components.grid' => 'filament-schema::components.grid',
            'filament-infolists::components.group' => 'filament-schema::components.grid',
            'filament-infolists::components.livewire' => 'filament-schema::components.livewire',
            'filament-infolists::components.section' => 'filament-schema::components.section',
            'filament-infolists::components.split' => 'filament-schema::components.split',
        ],
    );
};
