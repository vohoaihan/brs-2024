<?php

namespace Tests\Unit;

use App\Http\Controllers\AuthController;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Models\User;
// use GuzzleHttp\Psr7\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class APITest extends TestCase
{

    public function test_import()
    {
        $faker = \Faker\Factory::create();

        $user = User::factory()->create([
            'name' => $faker->name,
            'email' => $faker->email,
            'password' => Hash::make('test2024')
        ]);

        $response = $this->post('api/auth/login', [
            'email' => $user->email,
            'password' => 'test2024'
        ]);
        $response->assertStatus(200);

        $token = $response->json('access_token');

        $file = UploadedFile::fake()->createWithContent('test.csv', 'date,content,amount,type' . PHP_EOL . '25/07/2024 14:18:00,Test 2024,-3344,Withdraw');
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ])->post('api/import', ['file' => $file]);
        // $response = $this->withHeaders([
        //     'Authorization' => 'Bearer ' . $token,
        //     'Accept' => 'application/json'
        // ])->post('api/import', []);
        $response->assertStatus(200);
    }
}
