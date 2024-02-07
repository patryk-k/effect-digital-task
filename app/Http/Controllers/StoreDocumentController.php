<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDocumentRequest;
use App\Models\Document;
use App\Rules\ValidBase64Pdf;
use Aws\Credentials\CredentialProvider;
use Aws\Textract\TextractClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class StoreDocumentController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function __invoke(StoreDocumentRequest $request): JsonResponse
    {
        /*
            The file needs to be processed from S3. The two Textract client function available to extract text are:
                - detectDocumentText
                - startDocumentTextDetection
            The former doesn't work with PDFs while the latter can only process files on S3
        */

        $requestTime = now();

        $s3Disk = Storage::disk('s3');
        $fileName = Str::random() . '.pdf';

        $s3Disk->put($fileName, base64_decode($request->document));

        $credentials = CredentialProvider::sso(config('services.aws.sso_profile'));

        $textractClient = new TextractClient([
            'credentials' => $credentials
        ]);

        $startDocumentTextDetectionResult = $textractClient->startDocumentTextDetection([
            'DocumentLocation' => [
                'S3Object' => [
                    'Bucket' => config('filesystems.disks.s3.bucket'),
                    'Name' => $fileName,
                ],
            ],
            'NotificationChannel' => [
                'RoleArn' => config('services.aws.role_arn'),
                'SNSTopicArn' => config('services.aws.sns_topic_arn'),
            ],
            'OutputConfig' => [
                'S3Bucket' => config('filesystems.disks.s3.bucket')
            ],
        ]);

        $jobId = $startDocumentTextDetectionResult->get('JobId');

        $finished = false;
        $success = false;
        $text = '';

        while(!$finished) {
            $getDocumentTextDetectionResult = $textractClient->getDocumentTextDetection([
                'JobId' => $jobId,
            ]);

            $status = $getDocumentTextDetectionResult->get('JobStatus');

            if($status != 'IN_PROGRESS') {
                $finished = true;

                if($status == 'SUCCEEDED') {
                    $success = true;

                    foreach($getDocumentTextDetectionResult->get('Blocks') as $_block) {
                        if(isset($_block['Text']) && ($_block['BlockType'] ?? null) == 'LINE') {
                            if(strlen($text) > 0) {
                                $text .= ' ';
                            }

                            $text .= $_block['Text'];
                        }
                    }
                }
            }

            if(!$finished) {
                sleep(1);
            }
        }

        $s3Disk->delete($fileName);
        $s3Disk->deleteDirectory('textract_output/' . $jobId);

        if(!$success) {
            if($getDocumentTextDetectionResult->get('StatusMessage') == 'INVALID_IMAGE_TYPE') {
                throw ValidationException::withMessages([
                    'document' => ValidBase64Pdf::FAILURE_TEXT
                ]);
            }

            return new JsonResponse(['message' => 'Something went wrong.'], 500);
        }

        $document = Document::query()
            ->create([
                'text' => $text,
                'request_time' => $requestTime
            ]);

        return new JsonResponse($document);
    }
}
