<?php

namespace App\Filament\Pages;

use App\Models\UserLoginLog;
use App\Models\UserSetting;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class UserSettings extends Page implements Tables\Contracts\HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.user-settings';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Sol menüde görünmesin
    }

    public ?array $profileData = [];
    public ?array $securityData = [];
    public ?array $notificationData = [];
    public ?array $preferenceData = [];

    public function mount(): void
    {
        $user = auth()->user();
        $settings = $user->getSettings();

        // Profil bilgileri
        $this->profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'title' => $user->title,
            'phone' => $user->phone,
            'bio' => $user->bio,
            'language' => $user->language,
            'avatar' => null,
        ];

        // Güvenlik bilgileri
        $this->securityData = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        // Bildirim tercihleri
        $this->notificationData = [
            'email_notifications' => $settings->email_notifications,
            'email_return_requests' => $settings->email_return_requests,
            'email_large_transactions' => $settings->email_large_transactions,
            'email_transaction_threshold' => $settings->email_transaction_threshold,
            'email_partner_activities' => $settings->email_partner_activities,
            'email_weekly_report' => $settings->email_weekly_report,
            'email_monthly_report' => $settings->email_monthly_report,
            'app_notifications' => $settings->app_notifications,
            'app_return_requests' => $settings->app_return_requests,
            'app_large_transactions' => $settings->app_large_transactions,
            'app_partner_activities' => $settings->app_partner_activities,
            'notification_language' => $settings->notification_language,
        ];

        // Çalışma tercihleri
        $this->preferenceData = [
            'default_currency' => $settings->default_currency,
            'date_format' => $settings->date_format,
            'time_format' => $settings->time_format,
            'records_per_page' => $settings->records_per_page,
            'timezone' => $settings->timezone,
            'default_store_id' => $settings->default_store_id,
        ];
    }

    protected function getForms(): array
    {
        return [
            'profileForm',
            'securityForm',
            'notificationForm',
            'preferenceForm',
        ];
    }

    public function profileForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Profil Bilgileri')
                    ->description('Temel profil bilgilerinizi güncelleyin')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Profil Fotoğrafı')
                            ->image()
                            ->avatar()
                            ->directory('avatars')
                            ->maxSize(2048)
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200'),

                        Forms\Components\TextInput::make('name')
                            ->label('Ad Soyad')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('E-posta')
                            ->disabled()
                            ->maxLength(255),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Ünvan/Pozisyon')
                                    ->placeholder('Örn: Satış Müdürü')
                                    ->maxLength(100),

                                Forms\Components\TextInput::make('phone')
                                    ->label('Telefon')
                                    ->tel()
                                    ->placeholder('05XX XXX XX XX')
                                    ->maxLength(20),
                            ]),

                        Forms\Components\Textarea::make('bio')
                            ->label('Hakkımda')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Maksimum 500 karakter'),

                        Forms\Components\Select::make('language')
                            ->label('Dil')
                            ->options(UserSetting::getLanguageOptions())
                            ->required(),
                    ]),
            ])
            ->statePath('profileData')
            ->model(auth()->user());
    }

    public function securityForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Şifre Değiştir')
                    ->description('Hesap güvenliğiniz için güçlü bir şifre kullanın')
                    ->schema([
                        Forms\Components\TextInput::make('current_password')
                            ->label('Mevcut Şifre')
                            ->password()
                            ->required()
                            ->currentPassword(),

                        Forms\Components\TextInput::make('password')
                            ->label('Yeni Şifre')
                            ->password()
                            ->required()
                            ->rule(Password::default())
                            ->different('current_password'),

                        Forms\Components\TextInput::make('password_confirmation')
                            ->label('Yeni Şifre (Tekrar)')
                            ->password()
                            ->required()
                            ->same('password'),
                    ]),
            ])
            ->statePath('securityData');
    }

    public function notificationForm(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Bildirimler')
                    ->tabs([
                        Tabs\Tab::make('E-posta Bildirimleri')
                            ->schema([
                                Forms\Components\Toggle::make('email_notifications')
                                    ->label('E-posta bildirimlerini etkinleştir')
                                    ->reactive(),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('email_return_requests')
                                            ->label('İade talepleri'),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('email_large_transactions')
                                                    ->label('Büyük tutarlı işlemler'),

                                                Forms\Components\TextInput::make('email_transaction_threshold')
                                                    ->label('Eşik tutarı')
                                                    ->numeric()
                                                    ->prefix('$')
                                                    ->visible(fn ($get) => $get('email_large_transactions')),
                                            ]),

                                        Forms\Components\Toggle::make('email_partner_activities')
                                            ->label('Partner hareketleri'),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\Toggle::make('email_weekly_report')
                                                    ->label('Haftalık özet rapor'),

                                                Forms\Components\Toggle::make('email_monthly_report')
                                                    ->label('Aylık özet rapor'),
                                            ]),
                                    ])
                                    ->visible(fn ($get) => $get('email_notifications')),
                            ]),

                        Tabs\Tab::make('Uygulama İçi Bildirimler')
                            ->schema([
                                Forms\Components\Toggle::make('app_notifications')
                                    ->label('Uygulama içi bildirimleri etkinleştir')
                                    ->reactive(),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Toggle::make('app_return_requests')
                                            ->label('İade talepleri'),

                                        Forms\Components\Toggle::make('app_large_transactions')
                                            ->label('Büyük tutarlı işlemler'),

                                        Forms\Components\Toggle::make('app_partner_activities')
                                            ->label('Partner hareketleri'),
                                    ])
                                    ->visible(fn ($get) => $get('app_notifications')),
                            ]),
                    ]),

                Forms\Components\Select::make('notification_language')
                    ->label('Bildirim Dili')
                    ->options(UserSetting::getLanguageOptions())
                    ->required(),
            ])
            ->statePath('notificationData');
    }

    public function preferenceForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Çalışma Tercihleri')
                    ->description('Uygulamayı nasıl kullanmak istediğinizi belirleyin')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('default_currency')
                                    ->label('Varsayılan Para Birimi')
                                    ->options(UserSetting::getCurrencyOptions())
                                    ->required(),

                                Forms\Components\Select::make('default_store_id')
                                    ->label('Varsayılan Mağaza')
                                    ->options(fn () => auth()->user()->company->stores()->pluck('name', 'id'))
                                    ->placeholder('Seçiniz'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('date_format')
                                    ->label('Tarih Formatı')
                                    ->options(UserSetting::getDateFormatOptions())
                                    ->required(),

                                Forms\Components\Select::make('time_format')
                                    ->label('Saat Formatı')
                                    ->options(UserSetting::getTimeFormatOptions())
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('timezone')
                                    ->label('Saat Dilimi')
                                    ->options(UserSetting::getTimezoneOptions())
                                    ->required(),

                                Forms\Components\Select::make('records_per_page')
                                    ->label('Sayfa Başına Kayıt')
                                    ->options(UserSetting::getRecordsPerPageOptions())
                                    ->required(),
                            ]),
                    ]),
            ])
            ->statePath('preferenceData');
    }

    public function saveProfile(): void
    {
        $data = $this->profileForm->getState();
        $user = auth()->user();

        // Avatar yükleme
        if ($data['avatar'] instanceof TemporaryUploadedFile) {
            // Eski avatarı sil
            if ($user->avatar) {
                \Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $data['avatar']->store('avatars', 'public');
        } else {
            unset($data['avatar']);
        }

        $user->update($data);

        Notification::make()
            ->title('Profil bilgileri güncellendi')
            ->success()
            ->send();
    }

    public function savePassword(): void
    {
        $data = $this->securityForm->getState();

        auth()->user()->update([
            'password' => Hash::make($data['password']),
        ]);

        $this->securityData = [
            'current_password' => '',
            'password' => '',
            'password_confirmation' => '',
        ];

        Notification::make()
            ->title('Şifreniz başarıyla güncellendi')
            ->success()
            ->send();
    }

    public function saveNotifications(): void
    {
        $data = $this->notificationForm->getState();
        $user = auth()->user();
        $settings = $user->getSettings();

        $settings->update($data);

        Notification::make()
            ->title('Bildirim tercihleri güncellendi')
            ->success()
            ->send();
    }

    public function savePreferences(): void
    {
        $data = $this->preferenceForm->getState();
        $user = auth()->user();
        $settings = $user->getSettings();

        $settings->update($data);

        Notification::make()
            ->title('Çalışma tercihleri güncellendi')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(UserLoginLog::where('user_id', auth()->id())->latest('logged_in_at'))
            ->columns([
                Tables\Columns\TextColumn::make('logged_in_at')
                    ->label('Giriş Zamanı')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP Adresi')
                    ->searchable(),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Cihaz')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'mobile' => 'Mobil',
                        'tablet' => 'Tablet',
                        'desktop' => 'Masaüstü',
                        default => $state
                    })
                    ->icon(fn ($state) => match($state) {
                        'mobile' => 'heroicon-o-device-phone-mobile',
                        'tablet' => 'heroicon-o-device-tablet',
                        default => 'heroicon-o-computer-desktop',
                    }),

                Tables\Columns\TextColumn::make('browser')
                    ->label('Tarayıcı'),

                Tables\Columns\TextColumn::make('platform')
                    ->label('İşletim Sistemi'),

                Tables\Columns\TextColumn::make('formatted_duration')
                    ->label('Oturum Süresi')
                    ->getStateUsing(fn ($record) => $record->formatted_duration),

                Tables\Columns\IconColumn::make('is_successful')
                    ->label('Durum')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('logged_in_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}