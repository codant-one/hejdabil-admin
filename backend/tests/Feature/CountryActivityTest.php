<?php

namespace Tests\Feature;

use App\Http\Controllers\CountryController;
use App\Http\Requests\CountryRequest;
use App\Models\Country;
use App\Models\SupplierActivity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CountryActivityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        Storage::fake('public');
        Cache::shouldReceive('forget')->with('countries.all')->andReturn(true);
        $user = new class {
            public int $id = 1;

            public function getRoleNames()
            {
                return collect(['Admin']);
            }
        };
        Auth::shouldReceive('check')->andReturn(true);
        Auth::shouldReceive('user')->andReturn($user);

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            foreach (['name', 'iso', 'iso3', 'numcode', 'phonecode', 'phone_digits', 'flag'] as $field) {
                $table->string($field)->nullable();
            }
            $table->timestamps();
        });
        Schema::create('supplier_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('entity_id');
            foreach (['entity_type', 'action_type', 'title', 'description', 'icon', 'route', 'metadata'] as $field) {
                $table->text($field)->nullable();
            }
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    private function countryData(): array
    {
        return [
            'name' => 'Sweden',
            'iso' => 'SE',
            'iso3' => 'SWE',
            'numcode' => '752',
            'phonecode' => '46',
            'phone_digits' => '9',
        ];
    }

    private function request(bool $withFlag = false): CountryRequest
    {
        $request = new CountryRequest($this->countryData());
        if ($withFlag) {
            $request->files->set('flag', UploadedFile::fake()->createWithContent(
                'se.png',
                base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')
            ));
        }

        return $request;
    }

    public function test_replacing_a_flag_records_old_and_saved_paths_even_with_the_same_filename(): void
    {
        $country = Country::create([...$this->countryData(), 'flag' => 'flags/se.png']);

        $response = (new CountryController)->update($this->request(true), $country->id);
        $this->assertSame(200, $response->getStatusCode());
        $metadata = json_decode(SupplierActivity::first()->metadata, true);
        $savedFlag = $country->fresh()->flag;

        $this->assertSame('flags/se.png', $metadata['old_values']['flag']);
        $this->assertSame($savedFlag, $metadata['new_values']['flag']);
        $this->assertIsString($savedFlag);
        $this->assertNotSame('flags/se.png', $savedFlag);
        Storage::disk('public')->assertExists($savedFlag);
    }

    public function test_updating_without_an_upload_preserves_the_flag_in_both_snapshots(): void
    {
        $country = Country::create([...$this->countryData(), 'flag' => 'flags/se.png']);

        $response = (new CountryController)->update($this->request(), $country->id);
        $this->assertSame(200, $response->getStatusCode());
        $metadata = json_decode(SupplierActivity::first()->metadata, true);

        $this->assertSame('flags/se.png', $metadata['old_values']['flag']);
        $this->assertSame('flags/se.png', $metadata['new_values']['flag']);
    }

    public function test_creation_records_the_saved_flag_instead_of_the_uploaded_file(): void
    {
        $response = (new CountryController)->store($this->request(true));
        $this->assertSame(200, $response->getStatusCode());
        $metadata = json_decode(SupplierActivity::first()->metadata, true);

        $this->assertSame(Country::first()->flag, $metadata['new_values']['flag']);
        $this->assertSame('flags/se.png', $metadata['new_values']['flag']);
    }

    public function test_updating_a_missing_country_with_an_upload_returns_not_found(): void
    {
        $response = (new CountryController)->update($this->request(true), 999);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame(0, SupplierActivity::count());
    }
}
