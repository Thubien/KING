<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Events\Auth\Registered;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Auth\Events\Registered as LaravelRegistered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class Register extends BaseRegister
{
    protected static string $view = 'filament.register';

    public ?array $data = [];
    public bool $terms = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('company_name')
                    ->label('Company Name')
                    ->required()
                    ->maxLength(255)
                    ->autofocus(),
                    
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ])
            ->statePath('data');
    }

    public function register(): ?RegistrationResponse
    {
        if (!$this->terms) {
            Notification::make()
                ->title('Please accept the terms and conditions')
                ->danger()
                ->send();
            return null;
        }

        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        DB::beginTransaction();
        try {
            // Create company first
            $company = Company::create([
                'name' => $data['company_name'],
                'status' => 'active',
                'is_trial' => true,
                'trial_ends_at' => now()->addDays(14),
                'subscription_plan' => 'trial',
                'plan_limits' => [
                    'max_stores' => 3,
                    'max_users' => 5,
                    'max_transactions_per_month' => 1000,
                ],
            ]);

            // Create user
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'company_id' => $company->id,
                'user_type' => 'admin',
                'email_verified_at' => now(), // Auto verify for sandbox
            ]);

            // Assign owner role
            $user->assignRole('owner');

            DB::commit();

            event(new Registered($user));
            event(new LaravelRegistered($user));

            $this->sendEmailVerificationNotification($user);

            auth()->login($user);

            session()->regenerate();

            return app(RegistrationResponse::class);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Notification::make()
                ->title('Registration failed')
                ->body('Please try again or contact support.')
                ->danger()
                ->send();
                
            return null;
        }
    }

    protected function sendEmailVerificationNotification(\Illuminate\Database\Eloquent\Model $user): void
    {
        // Skip email verification for sandbox
        // In production, uncomment this:
        // if (! $user->hasVerifiedEmail()) {
        //     $user->sendEmailVerificationNotification();
        // }
    }
}