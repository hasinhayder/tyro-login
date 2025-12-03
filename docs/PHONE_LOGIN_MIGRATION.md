# Phone Login Migration

If you're adding phone login to an existing application, use this migration to add the phone column to your users table.

## Migration

Create a new migration:

```bash
php artisan make:migration add_phone_to_users_table
```

Add the following content:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->unique()->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
```

Run the migration:

```bash
php artisan migrate
```

## Update User Model

Add `phone` to your User model's fillable attributes:

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    protected $fillable = [
        'name',
        'email',
        'phone', // Add this line
        'password',
    ];
    
    // ... rest of your model
}
```

## Update Existing Users

If you need to populate phone numbers for existing users, you can use tinker or create a seeder:

### Using Tinker

```bash
php artisan tinker
```

```php
// Update a specific user
$user = App\Models\User::find(1);
$user->phone = '01700000000';
$user->save();

// Bulk update (example)
App\Models\User::where('id', '<', 10)->each(function($user) {
    $user->phone = '0170000' . str_pad($user->id, 4, '0', STR_PAD_LEFT);
    $user->save();
});
```

### Using a Seeder

Create a seeder:

```bash
php artisan make:seeder UpdateUserPhonesSeeder
```

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UpdateUserPhonesSeeder extends Seeder
{
    public function run(): void
    {
        // Example: Update users with sample phone numbers
        $users = User::whereNull('phone')->get();
        
        foreach ($users as $index => $user) {
            $user->update([
                'phone' => '0170000' . str_pad($index + 1, 4, '0', STR_PAD_LEFT)
            ]);
        }
    }
}
```

Run the seeder:

```bash
php artisan db:seed --class=UpdateUserPhonesSeeder
```

## Validation Rules

When using phone login, consider adding validation rules for phone numbers in your forms:

```php
'phone' => ['required', 'string', 'max:20', 'unique:users,phone']
```

For international phone number validation, consider using the `Laravel Phone` package or custom regex patterns based on your region.

## Configuration

Don't forget to set the login field in your `.env` file:

```env
TYRO_LOGIN_FIELD=phone
```

## Authentication Provider

You'll need to configure a custom authentication provider to handle phone number lookups. See the main README for detailed instructions on setting up the authentication provider.
