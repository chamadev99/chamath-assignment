<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Jobs\ProcessStoreOrderData;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

/**
 * @OA\Info(
 *      title="API Documentation",
 *      version="1.0.0",
 *      description="This is the API documentation for my assignment application.",
 *      @OA\Contact(
 *          email="chamathpk1991@gmail.com"
 *      ),
 *      @OA\License(
 *          name="Apache 2.0",
 *          url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *      )
 * )
 */


class FileUploadController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/generate-document",
     *     summary="generate order pdf file",
     *     tags={"generate-document"},
     *     description="generate order data pdf file and store in the app/pdf folder.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $orders = OrderItem::with(['order.customer', 'product'])->get();
        $pdf = Pdf::loadView('pdf.order_summary', compact('orders'))
            ->setPaper('a4', 'landscape');

        $fileName = "order_" . time() . '.pdf';
        $filePath = 'pdf/' . $fileName;

        $storage = Storage::put($filePath, $pdf->output());

        if (!$storage) {
            return response()->json(['success' => false, 'message' => 'Report not generated,please retry', 'link' => null], 500);
        }
        return response()->json(['success' => true, 'message' => 'Report Generated', 'link' => $filePath], 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * @OA\Post(
     *     path="/api/upload-document",
     *     summary="upload order csv data file",
     *     tags={"upload-document"},
     *     description="Upload order data and store in the database.",
     *     @OA\Response(
     *         response=200,
     *         description="File Uploaded,Start Data storing",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     )
     * )
     */

    public function store(FileUploadRequest $request): JsonResponse
    { {

            $request->validated();
            $file = $request->file('file');

            $saveFile = $file->storeAs('uploads', 'order_list.csv');

            if (!$saveFile || !Storage::exists($saveFile)) {
                return response()->json(['success' => false, 'message' => "File Not Upload"]);
            }

            ProcessStoreOrderData::dispatch($saveFile);

            return response()->json(['success' => true, 'message' => "File Uploaded,Start Data storing"]);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/download-document",
     *     summary="download order pdf file",
     *     tags={"download-document"},
     *     description="download order data pdf file from the app/pdf folder.",
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="email", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function show(string $file)
    {
        $filePath = storage_path('app/pdf/' . $file);

        if (!File::exists($filePath)) {
            return response()->json(['success' => false, 'message' => "File not existing"], 404);
        }
        return response()->download($filePath);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
