<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;


class OrderFileUpladTest extends TestCase
{

    use RefreshDatabase;
    /**
     * A basic feature test example.
     */
    public function test_upload_validation(): void
    {
        $response = $this->post('/api/upload-document', [
            'file' => "file"
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_file(): void
    {

        Storage::fake('local');
        $csvFile = UploadedFile::fake()->create('order.csv');
        $response = $this->post('/api/upload-document', [
            'file' => $csvFile
        ]);

        /// dd($response);
        $response->assertStatus(200);
    }

    public function test_return_order_data()
    {
        $customer = Customer::factory()->create();
        $product = Product::factory()->create();
        $order = Order::factory()->create();
        $orderList = OrderItem::factory()->create();

        $data = (new \App\Models\OrderItem())->getData();
        \Log::info('orderList' . json_encode($orderList, JSON_PRETTY_PRINT));
        \Log::info('data' . json_encode($data, JSON_PRETTY_PRINT));
        $this->assertNotEmpty($data);
    }
}
