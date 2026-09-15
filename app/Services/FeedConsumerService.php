<?php

namespace App\Services;

use App\Models\Section;
use App\Models\Field;
use App\Models\FieldOption;
use App\Models\Submission;
use App\Models\Answer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Carbon\Carbon;
use Exception;

class FeedConsumerService
{
    /**
     * Consume JSON feed from file path with memory optimization.
     */
    public function consumeFromFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File feed tidak ditemukan di: {$filePath}");
        }

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        unset($jsonContent); // Release raw string memory immediately

        return $this->processFeedData($data);
    }

    /**
     * Process data using LazyCollection to maintain minimal O(1) memory footprint.
     */
    public function processFeedData(array $data): array
    {
        $stats = [
            'sections' => 0,
            'fields'   => 0,
            'options'  => 0,
            'answers'  => 0,
        ];

        DB::transaction(function () use ($data, &$stats) {
            // 1. Buat record submission awal
            $submission = Submission::create([
                'title'        => 'Initial Import - Kejadian Risiko Operasional',
                'submitted_at' => Carbon::now(),
            ]);

            // 2. Gunakan LazyCollection untuk memproses Section secara streaming
            LazyCollection::make($data)->each(function ($sectionData, $sectionIndex) use ($submission, &$stats) {

                // Simpan Section
                Section::updateOrCreate(
                    ['id' => $sectionData['id']],
                    [
                        'name'      => $sectionData['name'],
                        'order'     => $sectionIndex,
                        'is_active' => true,
                    ]
                );
                $stats['sections']++;

                // 3. Gunakan LazyCollection untuk memproses Payloads / Fields
                if (!empty($sectionData['payloads'])) {
                    LazyCollection::make($sectionData['payloads'])->each(function ($fieldData, $fieldIndex) use ($sectionData, $submission, &$stats) {

                        $field = Field::updateOrCreate(
                            ['id' => $fieldData['id']],
                            [
                                'parent_id'   => $sectionData['id'],
                                'label'       => $fieldData['label'],
                                'type'        => $fieldData['type'],
                                'sub_type'    => $fieldData['sub_type'] ?? null,
                                'description' => $fieldData['description'] ?? null,
                                'orm_only'    => $fieldData['orm_only'] ?? 'no',
                                'order'       => $fieldIndex,
                            ]
                        );
                        $stats['fields']++;

                        // Simpan Options
                        if (!empty($fieldData['options']) && is_array($fieldData['options'])) {
                            foreach ($fieldData['options'] as $optionData) {
                                FieldOption::updateOrCreate(
                                    ['id' => $optionData['id']],
                                    [
                                        'field_id' => $field->id,
                                        'label'    => $optionData['label'],
                                        'value'    => $optionData['value'] ?? '',
                                    ]
                                );
                                $stats['options']++;
                            }
                        }

                        // Simpan Answer
                        if (isset($fieldData['answer'])) {
                            $answerVal = $fieldData['answer']['value'] ?? null;
                            $supportingFile = $fieldData['supporting_file'] ?? ($fieldData['supporting_files'] ?? null);

                            $extracted = $this->extractTypedValues(
                                $fieldData['type'],
                                $fieldData['sub_type'] ?? null,
                                $answerVal
                            );

                            $answerData = [
                                'submission_id' => $submission->id,
                                'field_id'      => $field->id,
                                'raw_answer'    => $fieldData['answer'],
                                'text_value'    => $extracted['text_value'],
                                'numeric_value' => $extracted['numeric_value'],
                                'date_value'    => $extracted['date_value'],
                            ];

                            if (\Illuminate\Support\Facades\Schema::hasColumn('answers', 'supporting_file')) {
                                $answerData['supporting_file'] = $supportingFile;
                            } elseif (\Illuminate\Support\Facades\Schema::hasColumn('answers', 'supporting_files')) {
                                $answerData['supporting_files'] = $supportingFile;
                            }

                            Answer::create($answerData);
                            $stats['answers']++;
                        }
                    });
                }
            });
        });

        return $stats;
    }

    /**
     * Ekstraksi nilai terstruktur untuk Data Analyst (Date, Amount/Decimal, Text).
     */
    private function extractTypedValues(string $type, ?string $subType, mixed $answerValue): array
    {
        $textValue = null;
        $numericValue = null;
        $dateValue = null;

        if (is_array($answerValue)) {
            $textValue = json_encode($answerValue);
        } elseif (is_string($answerValue) || is_numeric($answerValue)) {
            $textValue = (string) $answerValue;

            if ($subType === 'date' && !empty($answerValue) && $answerValue !== '-') {
                try {
                    $dateValue = Carbon::parse($answerValue)->toDateString();
                } catch (Exception $e) {
                    $dateValue = null;
                }
            }

            if ($subType === 'amount' && is_numeric($answerValue)) {
                $numericValue = (float) $answerValue;
            }
        }

        return [
            'text_value'    => $textValue,
            'numeric_value' => $numericValue,
            'date_value'    => $dateValue,
        ];
    }
}
