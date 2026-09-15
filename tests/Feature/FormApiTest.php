<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Section;
use App\Models\Field;
use App\Models\FieldOption;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FormApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan struktur form uji
        $section = Section::create([
            'id' => 'sec-test-01',
            'name' => 'Detail Kejadian Risiko Operasional',
            'order' => 0,
            'is_active' => true,
        ]);

        $field = Field::create([
            'id' => 'field-test-01',
            'parent_id' => $section->id,
            'label' => 'Bulan Pelaporan',
            'type' => 'radio_button',
            'sub_type' => null,
            'description' => 'Pilih bulan pelaporan',
            'orm_only' => 'no',
            'order' => 0,
        ]);

        FieldOption::create([
            'id' => 'opt-test-01',
            'field_id' => $field->id,
            'label' => 'Januari',
            'value' => '',
        ]);
    }

    /**
     * Test GET /api/form-schema berhasil mengambil struktur form.
     */
    public function test_can_get_form_schema(): void
    {
        $response = $this->getJson('/api/form-schema');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'order',
                             'payloads' => [
                                 '*' => [
                                     'id',
                                     'label',
                                     'type',
                                     'options',
                                     'answer',
                                 ]
                             ]
                         ]
                     ]
                 ]);
    }

    /**
     * Test POST /api/submissions berhasil menyimpan form baru.
     */
    public function test_can_submit_form_successfully(): void
    {
        $field = Field::first();

        $payload = [
            'title' => 'Automated Test Submission',
            'answers' => [
                [
                    'field_id' => $field->id,
                    'value' => 'Januari',
                    'supporting_file' => null,
                ]
            ]
        ];

        $response = $this->postJson('/api/submissions', $payload);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Form berhasil disimpan',
                 ]);

        $this->assertDatabaseHas('submissions', [
            'title' => 'Automated Test Submission',
        ]);
    }

    /**
     * Test validasi error ketika payload tidak valid.
     */
    public function test_submit_form_validation_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/api/submissions', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['answers']);
    }
}
