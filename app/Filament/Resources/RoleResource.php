<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?int $navigationSort = 99;
    protected static ?string $navigationLabel = 'Roles & Permissions';


    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin') || auth()->user()->can('view_roles');
    }

    public static function form(Form $form): Form
    {
        // Group permissions by resource
        $permissions = Permission::all()->groupBy(function ($perm) {
            $parts = explode('_', $perm->name, 2);
            return $parts[1] ?? $perm->name;
        });

        $permissionSections = [];
        $labels = [
            'activity_log' => 'Activity Log',
            'badges' => 'Badges',
            'categories' => 'Categories',
            'comments' => 'Comments',
            'donations' => 'Donations',
            'files' => 'Files',
            'lua_scripts' => 'Lua Scripts',
            'menus' => 'Menus',
            'pages' => 'Pages',
            'partner_links' => 'Partner Links',
            'polls' => 'Polls',
            'posts' => 'Posts',
            'reports' => 'Reports',
            'tags' => 'Tags',
            'tracker_games' => 'Tracker Games',
            'tracker_servers' => 'Tracker Servers',
            'tracker_maps' => 'Tracker Maps',
            'tutorial_categories' => 'Tutorial Categories',
            'tutorials' => 'Tutorials',
            'users' => 'Users',
            'wiki_articles' => 'Wiki Articles',
            'wiki_categories' => 'Wiki Categories',
            'roles' => 'Roles & Permissions',
            'fast_dl_clan' => 'FastDL Clans',
            'fast_dl_directory' => 'FastDL Directories',
            'fast_dl_file' => 'FastDL Files',
            'fast_dl_game' => 'FastDL Games',
            'translation_manager' => 'Translation Manager',
            'fastdl_monitor' => 'FastDL Monitor',
        ];

        foreach ($permissions as $group => $perms) {
            $options = [];
            foreach ($perms as $perm) {
                $action = explode('_', $perm->name)[0];
                $icon = match($action) {
                    'view' => '👁️',
                    'create' => '➕',
                    'update' => '✏️',
                    'delete' => '🗑️',
                    default => '•',
                };
                $options[$perm->name] = "{$icon} " . ucfirst($action);
            }
            $label = $labels[$group] ?? ucfirst(str_replace('_', ' ', $group));
            $permissionSections[] = Forms\Components\CheckboxList::make("permissions_{$group}")
                ->label($label)
                ->options($options)
                ->columns(4)
                ->gridDirection('row')
                ->afterStateHydrated(function ($component, $state, $record) use ($perms) {
                    if ($record) {
                        $rolePerms = $record->permissions->pluck('name')->toArray();
                        $selected = $perms->pluck('name')->intersect($rolePerms)->values()->toArray();
                        $component->state($selected);
                    }
                });
        }

        return $form->schema([
            Forms\Components\Section::make('Role')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                ]),
            Forms\Components\Section::make('Permissions')
                ->description('Define what this role can see and do in the admin panel.')
                ->schema($permissionSections),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->sortable()->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'admin' => 'danger',
                        'moderator' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('permissions_count')
                    ->counts('permissions')
                    ->label('Permissions')
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Users')
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('d.m.Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
