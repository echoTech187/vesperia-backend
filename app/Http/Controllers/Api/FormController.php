<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Section;
use App\Models\Field;
use App\Models\Submission;
use App\Models\Answer;
use App\Services\FeedConsumerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class FormController extends Controller
{
    /**
     * GET /api/form-schema
     * Mengambil skema struktur form lengkap beserta options dan jawaban default/terakhir.
     */
    public function getFormSchema(): JsonResponse
    {
        $latestSubmission = Submission::with(['answers'])->latest()->first();
        $answerMap = $latestSubmission ? $latestSubmission->answers->keyBy('field_id') : collect();

        $sections = Section::with(['fields.options'])
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(function ($section) use ($answerMap) {
                return [
                    'id' => $section->id,
                    'name' => $section->name,
                    'order' => $section->order,
                    'payloads' => $section->fields->map(function ($field) use ($answerMap) {
                        $existingAnswer = $answerMap->get($field->id);
                        return [
                            'id' => $field->id,
                            'parent_id' => $field->parent_id,
                            'label' => $field->label,
                            'type' => $field->type,
                            'sub_type' => $field->sub_type,
                            'description' => $field->description,
                            'orm_only' => $field->orm_only,
                            'options' => $field->options->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'label' => $option->label,
                                    'value' => $option->value ?? '',
                                ];
                            }),
                            'answer' => $existingAnswer ? $existingAnswer->raw_answer : [
                                'name' => '',
                                'value' => $field->type === 'checkbox' ? [] : '',
                            ],
                            'supporting_file' => $existingAnswer ? ($existingAnswer->supporting_file ?? $existingAnswer->supporting_files) : [
                                'name' => '',
                                'value' => '',
                            ],
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Form schema berhasil diambil',
            'data' => $sections,
        ], 200);
    }

    /**
     * POST /api/upload-feed
     * Upload file JSON dari frontend, simpan fisik ke storage/app/private/private_feeds/, dan masukkan ke DB.
     */
    public function uploadFeed(Request $request, FeedConsumerService $consumer): JsonResponse
    {
        $request->validate([
            'feed_file' => 'required|file',
        ]);

        try {
            $file = $request->file('feed_file');

            // 1. Pastikan folder fisik storage/app/private/private_feeds/ dibuat
            $destinationFolder = storage_path('app/private/private_feeds');
            if (!file_exists($destinationFolder)) {
                mkdir($destinationFolder, 0755, true);
            }

            // 2. Simpan file secara eksplisit dengan nama unik
            $fileName = 'feed_' . time() . '_' . $file->getClientOriginalName();
            $file->move($destinationFolder, $fileName);
            $savedFilePath = $destinationFolder . DIRECTORY_SEPARATOR . $fileName;

            // 3. Baca isi file JSON dari file yang sudah tersimpan
            $jsonContent = file_get_contents($savedFilePath);
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
            unset($jsonContent);

            // 4. Proses dan simpan ke database PostgreSQL
            $stats = $consumer->processFeedData($data);

            return response()->json([
                'success' => true,
                'message' => 'File JSON berhasil diunggah, disimpan di storage, dan skema database diperbarui!',
                'data'    => $stats,
                'file'    => 'storage/app/private/private_feeds/' . $fileName,
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file JSON: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/submissions
     * Menerima submit form dari user dan menyimpannya ke database.
     */
    public function submitForm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'answers' => 'required|array',
            'answers.*.field_id' => 'required|exists:fields,id',
            'answers.*.value' => 'nullable',
            'answers.*.supporting_file' => 'nullable|array',
        ]);

        try {
            $submission = DB::transaction(function () use ($validated) {
                $submission = Submission::create([
                    'title' => $validated['title'] ?? 'Form Submission - ' . Carbon::now()->toDateTimeString(),
                    'submitted_at' => Carbon::now(),
                ]);

                foreach ($validated['answers'] as $item) {
                    $field = Field::find($item['field_id']);
                    if (!$field) {
                        continue;
                    }

                    $value = $item['value'] ?? null;
                    $supportingFile = $item['supporting_file'] ?? null;

                    $textValue = null;
                    $numericValue = null;
                    $dateValue = null;

                    if (is_array($value)) {
                        $textValue = json_encode($value);
                    } elseif (is_string($value) || is_numeric($value)) {
                        $textValue = (string) $value;

                        if ($field->sub_type === 'date' && !empty($value) && $value !== '-') {
                            try {
                                $dateValue = Carbon::parse($value)->toDateString();
                            } catch (Exception $e) {
                                $dateValue = null;
                            }
                        }

                        if ($field->sub_type === 'amount' && is_numeric($value)) {
                            $numericValue = (float) $value;
                        }
                    }

                    $answerData = [
                        'submission_id' => $submission->id,
                        'field_id' => $field->id,
                        'raw_answer' => [
                            'name' => '',
                            'value' => $value ?? '',
                        ],
                        'text_value' => $textValue,
                        'numeric_value' => $numericValue,
                        'date_value' => $dateValue,
                    ];

                    if (\Illuminate\Support\Facades\Schema::hasColumn('answers', 'supporting_file')) {
                        $answerData['supporting_file'] = $supportingFile;
                    } elseif (\Illuminate\Support\Facades\Schema::hasColumn('answers', 'supporting_files')) {
                        $answerData['supporting_files'] = $supportingFile;
                    }

                    Answer::create($answerData);
                }

                return $submission;
            });

            return response()->json([
                'success' => true,
                'message' => 'Form berhasil disimpan',
                'data' => [
                    'submission_id' => $submission->id,
                    'submitted_at' => $submission->submitted_at,
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan submission: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/submissions
     * Mengambil daftar semua submission yang pernah dibuat.
     */
    public function getSubmissions(): JsonResponse
    {
        $submissions = Submission::with(['answers.field'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Submissions berhasil diambil',
            'data' => $submissions,
        ], 200);
    }

    /**
     * GET /api/submissions/{id}
     * Mengambil detail submission tertentu beserta jawabannya.
     */
    public function getSubmissionDetail($id): JsonResponse
    {
        $submission = Submission::with(['answers.field.section'])->find($id);

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Submission tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Submission detail berhasil diambil',
            'data' => $submission,
        ], 200);
    }
}
