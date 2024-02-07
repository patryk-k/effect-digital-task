<?php

namespace Tests\Feature;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreateDocumentTest extends TestCase
{
    private $dateFormat = 'Y-m-d\TH:i:s.u\Z';

    /**
     * Test that a document can be created via the endpoint
     */
    public function test_document_can_be_created(): void
    {
        $response = $this->postDocument([
            'document' => base64_encode($this->getExample('example'))
        ]);

        /** @var Document */
        $model = Document::find(json_decode($response->baseResponse->content())->data->id);

        $this->assertTrue(is_a($model, Document::class));
        $this->assertTrue($model->exists);
        $this->assertNotEmpty($model->request_time);
        $this->assertEquals($model->text, 'Dummy PDF file');

        $response->assertStatus(201);
        $response->assertJsonPath('data.text', 'Dummy PDF file');
        $response->assertJsonPath('data.request_time', $model->request_time->format($this->dateFormat));
    }

    /**
     * Test that an empty document cannot be created via the endpoint
     */
    public function test_document_cannot_be_created_empty(): void
    {
        $documentsCountBefore = Document::count();

        $response = $this->postDocument([
            'document' => base64_encode($this->getExample('blank'))
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The document provided is not a valid pdf file.');
        $response->assertJsonPath('errors.document.0', 'The document provided is not a valid pdf file.');

        $this->assertDatabaseCount('documents', $documentsCountBefore);
    }

    /**
     * Test that a corrupted document cannot be used to create via the endpoint
     */
    public function test_document_cannot_be_created_from_corrupted(): void
    {
        $documentsCountBefore = Document::count();

        $response = $this->postDocument([
            'document' => base64_encode($this->getExample('corrupted'))
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The document provided is not a valid pdf file.');
        $response->assertJsonPath('errors.document.0', 'The document provided is not a valid pdf file.');

        $this->assertDatabaseCount('documents', $documentsCountBefore);
    }

    /**
     * Test that a non pdf cannot be used to create via the endpoint
     */
    public function test_document_cannot_be_created_from_image(): void
    {
        $documentsCountBefore = Document::count();

        $response = $this->postDocument([
            'document' => base64_encode($this->getExample('example', 'png'))
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The document provided is not a valid pdf file.');
        $response->assertJsonPath('errors.document.0', 'The document provided is not a valid pdf file.');

        $this->assertDatabaseCount('documents', $documentsCountBefore);
    }

    /**
     * Test that an error is returned if no document provided
     */
    public function test_document_cannot_be_created_without_document_in_body(): void
    {
        $documentsCountBefore = Document::count();

        $response = $this->postDocument();

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'The document field is required.');
        $response->assertJsonPath('errors.document.0', 'The document field is required.');

        $this->assertDatabaseCount('documents', $documentsCountBefore);
    }

    private function getExample(string $name, string $extension = 'pdf')
    {
        return Storage::disk('test_examples')->get($name . '.' . $extension);
    }

    private function postDocument(array $data = [])
    {
        return $this->postJson(route('documents.store'), $data);
    }
}
