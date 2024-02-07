<?php

namespace Tests\Unit;

use App\Models\Document;
use Tests\TestCase;

class DocumentFactoryTest extends TestCase
{
    /**
     * Test that DocumentFactory can create a Document
     */
    public function test_document_factory_single(): void
    {
        $model = Document::factory()->create();

        $this->assertModelIsValid($model);
    }

    /**
     * Test that DocumentFactory can create three Documents
     */
    public function test_document_factory_multiple(): void
    {
        $models = Document::factory()->count(3)->create();

        foreach ($models as $_model) {
            $this->assertModelIsValid($_model);
        }
    }

    private function assertModelIsValid($model): void
    {
        $this->assertTrue(is_a($model, Document::class));
        $this->assertTrue($model->exists);
        $this->assertNotEmpty($model->request_time);
        $this->assertNotEmpty($model->text);
    }
}
