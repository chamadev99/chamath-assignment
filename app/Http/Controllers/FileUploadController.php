<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Http\Requests\FileDownloadRequest;
use App\Jobs\ProcessStoreOrderData;
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
 *      description="This is the API documentation for my technical assignment application.",
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
     *     description="Generate order data pdf file and store in to the app/pdf folder.",
     *     @OA\Response(
     *         response=200,
     *         description="Report Generated",
     *           @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Report Generated"),
     *             @OA\Property(property="link", type="string", example="pdf/order_1707154602.pdf")
     *         )
     *     ),
     *  @OA\Response(
     *         response=500,
     *         description="Report Generated failed",
     *           @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Report not generated,please retry"),
     *             @OA\Property(property="link", type="string", example="null")
     *         )
     *     ),
     *
     * )
     */
    public function index()
    {
        try {
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
        } catch (\Throwable $th) {
            throw $th;
        }
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
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     description="The CSV file to upload",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="File Uploaded,Start Data storing",
     *            @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="File Uploaded, Start Data storing")
     *         )
     *     ),
     *    @OA\Response(
     *         response=422,
     *         description="Validation Error (Invalid File Format)",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="The file must be a CSV."),
     *             @OA\Property(property="errors", type="array",
     *                 @OA\Items(
     *                     type="string",
     *                     example="The file field is required."
     *                 )
     *             )
     *         )
     *     ),
     *
     *     @OA\Response(
     *         response=500,
     *         description="File Upload Failed",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="File Not Upload")
     *         )
     *     )
     * )
     */

    public function store(FileUploadRequest $request): JsonResponse
    {

        try {
            $request->validated();
            $file = $request->file('file');

            $saveFile = $file->storeAs('uploads', 'order_list.csv');

            if (!$saveFile || !Storage::exists($saveFile)) {
                return response()->json(['success' => false, 'message' => "File Not Upload"]);
            }

            ProcessStoreOrderData::dispatch($saveFile);

            return response()->json(['success' => true, 'message' => "File Uploaded,Start Data storing"]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * @OA\Post(
     *     path="/api/download-document",
     *     summary="Download order data pdf file",
     *     tags={"download-document"},
     *     description="download order data pdf file from the app/pdf folder.",
     *
     *       @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"file"},
     *                 @OA\Property(
     *                     property="file",
     *                     description="The file name",
     *                     type="string",
     *                     format="binary"
     *                 )
     *             )
     *         )
     *     ),
     *      @OA\Response(
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
    public function show(FileDownloadRequest $request)
    {
        try {
            $filePath = storage_path('app/pdf/' . $request->file);

            if (!File::exists($filePath)) {
                return response()->json(['success' => false, 'message' => "File not existing"], 404);
            }
            return response()->download($filePath);
        } catch (\Throwable $th) {
            throw $th;
        }
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
