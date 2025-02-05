<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProcessStoreOrderData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    private $filePath;
    public function __construct($file)
    {
        $this->filePath = $file;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {


        info("run");
        if (!Storage::exists($this->filePath)) {
            \Log::error('File not found: ' . $this->filePath);
            return;
        }

        $fullPath = Storage::path($this->filePath);
        $data = array_filter(file($fullPath), 'trim');
        $chunk = array_chunk($data, 50);

        DB::transaction(function () use ($chunk) {

            $customerData = [];
            $productData = [];
            $orderData = [];
            $orderItemData = [];
            $email = [];
            $customerMap = [];
            $productMap = [];
            $orderMap = [];

            $firstChunk = true;
            $secondChunk = true;

            foreach ($chunk as $chunk_data) {
                if ($firstChunk) {
                    $chunk_data = array_slice($chunk_data, 1);
                    $firstChunk = false;
                }

                if (empty($chunk_data)) {
                    continue;
                }

                $data = array_map('str_getcsv', $chunk_data);

                foreach ($data as $item) {
                    $customerEmail = $item[1] ?? null;
                    $productName = $item[4] ?? null;
                    $orderReference = $item[2] ?? null;
                    $orderDate = $item[3] ?? null;

                    if (!isset($customerMap[$customerEmail])) {
                        $customerData[] = [
                            'name' => $item[0] ?? null,
                            'email' => $customerEmail,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                        $customerMap[$customerEmail] = null;
                    }

                    if (!isset($productMap[$productName])) {
                        $productMap[$productName] = [
                            'name' => $productName,
                            'price' => $item[5] ?? null,
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                    $orderData[] = [
                        'customer_email' => $customerEmail,
                        'order_reference' => $orderReference,
                        'order_date' => $orderDate,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            $productData = array_values($productMap);

            DB::table('customers')->insertOrIgnore($customerData);
            DB::table('products')->insertOrIgnore($productData);

            $customerData = DB::table('customers')->whereIn('email', array_keys($customerMap))->pluck('id', 'email');

            foreach ($customerMap as $email => $id) {
                $customerMap[$email] = $customerData[$email];
            }

            foreach ($orderData as &$order) {
                $order['customer_id'] = $customerMap[$order['customer_email']];
                unset($order['customer_email']);
            }
            DB::table('orders')->insertOrIgnore($orderData);

            $orderData = DB::table('orders')->get(['id', 'customer_id', 'order_date'])->toArray();

            foreach ($orderData as $order) {
                $orderMap[] = $order->id;
            }

            $productData = DB::table('products')->whereIn('name', array_keys($productData))->pluck('id', 'name');

            foreach ($chunk as $chunk_data) {
                if ($secondChunk) {
                    $chunk_data = array_slice($chunk_data, 1);
                    $secondChunk = false;
                }

                if (empty($chunk_data)) {
                    continue;
                }

                $data = array_map('str_getcsv', $chunk_data);

                foreach ($data as $index => $item) {
                    $orderId = $orderMap[$index];
                    $productId = $productData[$item[4] ?? null] ?? null;
                    $quantity = $item[6] ?? null;

                    $orderItemData[] = [
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'quantity' => $quantity,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                }
            }

            DB::table('order_items')->insert($orderItemData);
            info("end run transaction");
        });


        info("end run ");
    }
}
