<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Section;
use App\Models\Field;
use App\Models\FieldOption;
use App\Models\Submission;
use App\Models\Answer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FeedConsumerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test struktur database relasional dan penyimpanan jawaban insiden risiko.
     */
    public function test_can_store_and_retrieve_risk_assessment_data(): void
    {
        // 1. Buat Section
        $section = Section::create([
            'id' => '1609227754-u4t8-cck1-w18p7azbr',
            'name' => 'Detail Kejadian Risiko Operasional',
            'order' => 0,
            'is_active' => true,
        ]);

        // 2. Buat Field
        $field = Field::create([
            'id' => '1617779234-f0oy-phln-ppl0u1qx5',
            'parent_id' => $section->id,
            'label' => 'Bulan Pelaporan',
            'type' => 'radio_button',
            'sub_type' => null,
            'description' => 'Pilih bulan pelaporan',
            'orm_only' => 'no',
            'order' => 0,
        ]);

        // 3. Buat Options
        FieldOption::create([
            'id' => '1617779275-lt0k-zexz-uol8cts7s',
            'field_id' => $field->id,
            'label' => 'Januari',
            'value' => '',
        ]);

        // 4. Buat Submission & Answer
        $submission = Submission::create([
            'title' => 'Initial Import - Kejadian Risiko Operasional',
            'submitted_at' => now(),
        ]);

        Answer::create([
            'submission_id' => $submission->id,
            'field_id' => $field->id,
            'raw_answer' => [
                'name' => '',
                'value' => [
                    [
                        'id' => '1617779275-lt0k-zexz-uol8cts7s',
                        'value' => '',
                        'parent_id' => '1617779234-f0oy-phln-ppl0u1qx5',
                        'label' => 'Januari',
                    ]
                ]
            ],
            'text_value' => 'Januari',
            'numeric_value' => 6000.00,
            'date_value' => '2019-01-11',
        ]);

        // Assertions
        $this->assertDatabaseHas('sections', ['id' => '1609227754-u4t8-cck1-w18p7azbr']);
        $this->assertDatabaseHas('fields', ['id' => '1617779234-f0oy-phln-ppl0u1qx5']);
        $this->assertDatabaseHas('field_options', ['id' => '1617779275-lt0k-zexz-uol8cts7s']);
        $this->assertDatabaseHas('submissions', ['title' => 'Initial Import - Kejadian Risiko Operasional']);
        $this->assertDatabaseHas('answers', [
            'numeric_value' => 6000.00,
            'date_value' => '2019-01-11',
        ]);
    }
}
